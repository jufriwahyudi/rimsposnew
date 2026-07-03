<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Models\Divisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor'])->latest();

        // Filter: Date Range (dari DateRangePicker, format: "DD/MM/YYYY - DD/MM/YYYY")
        if ($request->filled('date_range')) {
            $parts = explode(' - ', $request->date_range);
            if (count($parts) === 2) {
                $dateFrom = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[0]))->startOfDay();
                $dateTo   = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[1]))->endOfDay();
                $query->whereBetween('request_date', [$dateFrom, $dateTo]);
            }
        }

        // Filter: No PO
        if ($request->filled('po_number')) {
            $query->where('po_number', 'like', '%' . $request->po_number . '%');
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pos = $query->paginate(15)->appends($request->query());

        return view('purchase_orders.index', compact('pos'));
    }
    public function create()
    {
        $products = Product::with([
            'variants' => function ($query) {
                $query->where('is_active', 'Y');
                $query->where('track_stock', true);
                $query->with('variantAttributes.attribute');
            }
        ])
        ->whereHas('variants', function ($query) {
            $query->where('is_active', 'Y');
            $query->where('track_stock', true);
        })
        ->get();
        // lihat sql yang terbentuk
        // dd($products->toSql(), $products->getBindings());
        $attributeValues = AttributeValue::pluck('nama', 'id');
        return view('purchase_orders.create', [
            'vendors' => Vendor::get(),
            'products' => $products,
            'attributeValues' => $attributeValues,
        ]);
    }
    /* =========================
     * CREATE PO (Gudang)
     * ========================= */
    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'notes' => 'nullable|string',
            'request_date' => 'required|date',
            'expected_date' => 'required|date',
            'tax_total' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {

            $po = PurchaseOrder::create([
                'store_id' => session('store_id'),
                'po_number'    => $this->generatePoNumber(),
                'vendor_id'  => $request->vendor_id,
                'notes'      => $request->notes,
                'request_date' => $request->request_date,
                'expected_date' => $request->expected_date,
                'status'       => 'DRAFT',
                'requested_by' => auth()->id(),
            ]);

            $total = 0;

            foreach ($request->items as $item) {
                $subtotal = $item['qty'] * $item['price'];

                PurchaseOrderItem::create([
                    'purchase_order_id'     => $po->id,
                    'product_variant_id'    => $item['variant_id'] ?? null,
                    'qty_order'             => $item['qty'],
                    'price'                 => $item['price'],
                    'subtotal'              => $subtotal,
                ]);

                $total += $subtotal;
            }

            $po->update([
                'subtotal' => $total,
                'tax_total' => $request->tax_total ?? 0,
                'discount_total' => $request->discount_total ?? 0,
                'grand_total' => ($total - ($request->discount_total ?? 0)) + ($request->tax_total ?? 0),
                'status' => 'APPROVED',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // 🔴 NANTI: buat jurnal hutang (AP)
            // AccountingService::createPurchaseAP($po);
        });

        return redirect()->route('po.index')->with('success', 'PO berhasil dibuat');
    }

    /* =========================
     * SUBMIT PO (Gudang)
     * ========================= */
    public function submit($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'DRAFT') {
            abort(400, 'PO tidak bisa disubmit');
        }

        $po->update(['status' => 'SUBMITTED']);

        return back()->with('success', 'PO berhasil disubmit ke finance');
    }

    /* =========================
     * APPROVE / REJECT (Finance)
     * ========================= */
    public function approve(Request $request, $id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        if ($po->status !== 'SUBMITTED') {
            abort(400, 'PO tidak bisa diproses');
        }

        DB::transaction(function () use ($po, $request) {

            if ($request->action === 'reject') {
                $po->update([
                    'status' => 'REJECTED',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                return;
            }

            // APPROVED
            $po->update([
                'status' => 'APPROVED',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // 🔴 NANTI: buat jurnal hutang (AP)
            // AccountingService::createPurchaseAP($po);
        });

        return back()->with('success', 'PO berhasil diproses');
    }

    /* =========================
     * DELETE DRAFT / APPROVED PO
     * ========================= */
    public function destroy(PurchaseOrder $po)
    {
        // APPROVED = belum ada goods receipt, aman untuk dihapus
        // PARTIAL_RECEIVED / RECEIVED tidak boleh dihapus
        if (!in_array($po->status, ['DRAFT', 'APPROVED'])) {
            return back()->with('error', 'Hanya PO berstatus DRAFT atau APPROVED yang dapat dihapus.');
        }

        DB::transaction(function () use ($po) {
            $po->items()->delete();
            $po->delete();
        });

        return back()->with('success', 'PO berhasil dihapus.');
    }

    /* =========================
     * HELPER
     * ========================= */
    private function generatePoNumber()
    {
        return 'PO-' . date('Ymd') . '-' . Str::upper(Str::random(4));
    }
}
