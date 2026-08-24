<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ServiceOrderController extends Controller
{
    public function index(Request $request)
    {
        $storeId = session('store_id');
        $statusCounts = ServiceOrder::where('store_id', $storeId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('service_orders.index', compact('statusCounts'));
    }

    public function datatables(Request $request)
    {
        $storeId = session('store_id');
        $query = ServiceOrder::with(['customer', 'assignedStaff', 'items'])
            ->where('store_id', $storeId)
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date   . ' 23:59:59',
            ]);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', fn($o) => $o->created_at->format('d/m/Y H:i'))
            ->addColumn('customer_info', function ($o) {
                $name = e($o->customer_name ?: ($o->customer?->name ?? '-'));
                $phone = e($o->customer_phone ?: ($o->customer?->phone ?? ''));
                return "<strong>{$name}</strong><br><small class='text-muted'>{$phone}</small>";
            })
            ->addColumn('target_info', function ($o) {
                $name = e($o->target_name ?? '-');
                $id = e($o->target_identifier ?? '');
                return "<strong>{$name}</strong>" . ($id ? "<br><span class='badge bg-light text-dark border'>{$id}</span>" : '');
            })
            ->addColumn('staff', fn($o) => $o->assignedStaff?->name ?? '<span class="text-muted">-</span>')
            ->editColumn('status', function ($o) {
                return match ($o->status) {
                    'received'      => '<span class="badge bg-secondary">Diterima</span>',
                    'diagnosing'    => '<span class="badge bg-info text-dark">Pemeriksaan</span>',
                    'waiting_parts' => '<span class="badge bg-warning text-dark">Menunggu Part</span>',
                    'in_progress'   => '<span class="badge bg-primary">Sedang Dikerjakan</span>',
                    'completed'     => '<span class="badge bg-success">Selesai (Siap Diambil)</span>',
                    'delivered'     => '<span class="badge bg-dark">Sudah Diambil / Lunas</span>',
                    'cancelled'     => '<span class="badge bg-danger">Batal</span>',
                    default         => '<span class="badge bg-secondary">' . ucfirst($o->status) . '</span>',
                };
            })
            ->addColumn('total_cost', fn($o) => 'Rp ' . number_format($o->total_cost, 0, ',', '.'))
            ->addColumn('action', function ($o) {
                $showUrl = route('service-orders.show', $o->id);
                $printUrl = route('service-orders.print-ticket', $o->id);
                return "
                    <div class='btn-group btn-group-sm'>
                        <a href='{$showUrl}' class='btn btn-primary' title='Detail & Pengerjaan'><i class='bi bi-eye'></i> Kelola</a>
                        <a href='{$printUrl}' target='_blank' class='btn btn-secondary' title='Cetak Tanda Terima'><i class='bi bi-printer'></i></a>
                    </div>
                ";
            })
            ->rawColumns(['customer_info', 'target_info', 'staff', 'status', 'action'])
            ->make(true);
    }

    public function create()
    {
        $storeId = session('store_id');
        $customers = Customer::where('store_id', $storeId)->orderBy('name')->get();
        $staffs = User::whereHas('stores', fn($q) => $q->where('stores.id', $storeId))->orderBy('name')->get();

        return view('service_orders.create', compact('customers', 'staffs'));
    }

    public function store(Request $request)
    {
        $storeId = session('store_id');
        $request->validate([
            'customer_name'          => 'required|string|max:255',
            'customer_phone'         => 'nullable|string|max:50',
            'target_name'            => 'required|string|max:255',
            'target_identifier'      => 'nullable|string|max:255',
            'complaint_notes'        => 'required|string',
            'diagnosis_notes'        => 'nullable|string',
            'assigned_staff_id'      => 'nullable|exists:users,id',
            'down_payment'           => 'nullable|numeric|min:0',
            'estimated_completed_at' => 'nullable|date',
            'warranty_days'          => 'nullable|integer|min:0',
        ]);

        try {
            $order = DB::transaction(function () use ($request, $storeId) {
                // Generate Order Number: WO-YYYYMM-XXXX
                $prefix = 'WO-' . date('Ym') . '-';
                $lastOrder = ServiceOrder::where('store_id', $storeId)
                    ->where('order_number', 'like', $prefix . '%')
                    ->orderByDesc('id')
                    ->first();

                $lastNumber = 0;
                if ($lastOrder && preg_match('/-(\d+)$/', $lastOrder->order_number, $matches)) {
                    $lastNumber = (int) $matches[1];
                }
                $orderNumber = $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

                // Auto find or create customer
                $customerId = $request->customer_id;
                if (!$customerId && $request->customer_name) {
                    $customer = Customer::firstOrCreate(
                        ['store_id' => $storeId, 'name' => $request->customer_name],
                        ['phone' => $request->customer_phone]
                    );
                    $customerId = $customer->id;
                }

                $order = ServiceOrder::create([
                    'store_id'               => $storeId,
                    'order_number'           => $orderNumber,
                    'customer_id'            => $customerId,
                    'customer_name'          => $request->customer_name,
                    'customer_phone'         => $request->customer_phone,
                    'target_name'            => $request->target_name,
                    'target_identifier'      => $request->target_identifier,
                    'target_attributes'      => $request->target_attributes ?? [],
                    'complaint_notes'        => $request->complaint_notes,
                    'diagnosis_notes'        => $request->diagnosis_notes,
                    'assigned_staff_id'      => $request->assigned_staff_id,
                    'estimated_cost'         => $request->estimated_cost ?? 0,
                    'estimated_completed_at' => $request->estimated_completed_at,
                    'warranty_days'          => $request->warranty_days ?? 0,
                    'down_payment'           => $request->down_payment ?? 0,
                    'status'                 => 'received',
                    'payment_status'         => ($request->down_payment > 0) ? 'partial' : 'unpaid',
                    'created_by_user_id'     => auth()->id(),
                ]);

                return $order;
            });

            return redirect()
                ->route('service-orders.show', $order->id)
                ->with('success', "Tiket Servis #{$order->order_number} berhasil dibuat.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat tiket: ' . $e->getMessage())->withInput();
        }
    }

    public function show(ServiceOrder $serviceOrder)
    {
        $this->authorizeStore($serviceOrder);
        $serviceOrder->load(['customer', 'assignedStaff', 'items.staff', 'items.product', 'sale']);
        $storeId = session('store_id');
        $staffs = User::whereHas('stores', fn($q) => $q->where('stores.id', $storeId))->orderBy('name')->get();
        $serviceVariants = ProductVariant::with('product')
            ->where('store_id', $storeId)
            ->whereHas('product', fn($q) => $q->where('product_type', 'SERVICE'))
            ->orderBy('variant_name')
            ->get();
        $partVariants = ProductVariant::with('product')
            ->where('store_id', $storeId)
            ->whereHas('product', fn($q) => $q->where('product_type', '!=', 'SERVICE'))
            ->orderBy('variant_name')
            ->get();

        return view('service_orders.show', compact('serviceOrder', 'staffs', 'serviceVariants', 'partVariants'));
    }

    public function edit(ServiceOrder $serviceOrder)
    {
        $this->authorizeStore($serviceOrder);
        $storeId = session('store_id');
        $customers = Customer::where('store_id', $storeId)->orderBy('name')->get();
        $staffs = User::whereHas('stores', fn($q) => $q->where('stores.id', $storeId))->orderBy('name')->get();

        return view('service_orders.edit', compact('serviceOrder', 'customers', 'staffs'));
    }

    public function update(Request $request, ServiceOrder $serviceOrder)
    {
        $this->authorizeStore($serviceOrder);
        $request->validate([
            'customer_name'          => 'required|string|max:255',
            'customer_phone'         => 'nullable|string|max:50',
            'target_name'            => 'required|string|max:255',
            'target_identifier'      => 'nullable|string|max:255',
            'complaint_notes'        => 'required|string',
            'diagnosis_notes'        => 'nullable|string',
            'assigned_staff_id'      => 'nullable|exists:users,id',
            'status'                 => 'required|in:received,diagnosing,waiting_parts,in_progress,completed,delivered,cancelled',
            'estimated_completed_at' => 'nullable|date',
            'warranty_days'          => 'nullable|integer|min:0',
        ]);

        $serviceOrder->update([
            'customer_name'          => $request->customer_name,
            'customer_phone'         => $request->customer_phone,
            'target_name'            => $request->target_name,
            'target_identifier'      => $request->target_identifier,
            'complaint_notes'        => $request->complaint_notes,
            'diagnosis_notes'        => $request->diagnosis_notes,
            'assigned_staff_id'      => $request->assigned_staff_id,
            'status'                 => $request->status,
            'estimated_completed_at' => $request->estimated_completed_at,
            'warranty_days'          => $request->warranty_days ?? 0,
        ]);

        return redirect()
            ->route('service-orders.show', $serviceOrder->id)
            ->with('success', 'Data tiket servis berhasil diperbarui.');
    }

    public function updateStatus(Request $request, ServiceOrder $serviceOrder)
    {
        $this->authorizeStore($serviceOrder);
        $request->validate([
            'status' => 'required|in:received,diagnosing,waiting_parts,in_progress,completed,delivered,cancelled',
        ]);

        $serviceOrder->update(['status' => $request->status]);

        return redirect()->back()->with('success', "Status tiket diubah menjadi: {$request->status}");
    }

    public function addItem(Request $request, ServiceOrder $serviceOrder)
    {
        $this->authorizeStore($serviceOrder);
        $request->validate([
            'item_type'         => 'required|in:service,product',
            'name'              => 'required|string|max:255',
            'price'             => 'required|numeric|min:0',
            'qty'               => 'required|integer|min:1',
            'staff_user_id'     => 'nullable|exists:users,id',
            'commission_type'   => 'nullable|in:none,percentage,fixed',
            'commission_rate'   => 'nullable|numeric|min:0',
        ]);

        $price = (float) $request->price;
        $qty = (int) $request->qty;
        $subtotal = $price * $qty;

        $commType = $request->commission_type ?? 'none';
        $commRate = (float) ($request->commission_rate ?? 0);
        $commAmount = 0;
        if ($request->staff_user_id && $commType !== 'none') {
            if ($commType === 'percentage') {
                $commAmount = round(($subtotal * ($commRate / 100)), 2);
            } elseif ($commType === 'fixed') {
                $commAmount = round($commRate * $qty, 2);
            }
        }

        $serviceOrder->items()->create([
            'item_type'          => $request->item_type,
            'product_id'         => !empty($request->product_id) ? $request->product_id : null,
            'product_variant_id' => !empty($request->product_variant_id) ? $request->product_variant_id : null,
            'name'               => $request->name,
            'price'              => $price,
            'qty'                => $qty,
            'subtotal'           => $subtotal,
            'staff_user_id'      => $request->staff_user_id,
            'commission_type'    => $commType,
            'commission_rate'    => $commRate,
            'commission_amount'  => $commAmount,
            'notes'              => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Item berhasil ditambahkan ke tiket servis.');
    }

    public function destroyItem(ServiceOrder $serviceOrder, ServiceOrderItem $item)
    {
        $this->authorizeStore($serviceOrder);
        abort_if($item->service_order_id !== $serviceOrder->id, 403);

        $item->delete();

        return redirect()->back()->with('success', 'Item berhasil dihapus.');
    }

    public function printTicket(ServiceOrder $serviceOrder)
    {
        $this->authorizeStore($serviceOrder);
        $serviceOrder->load(['customer', 'assignedStaff', 'items.staff', 'store']);
        $store = $serviceOrder->store;

        return view('service_orders.print_ticket', compact('serviceOrder', 'store'));
    }

    protected function authorizeStore(ServiceOrder $serviceOrder)
    {
        if ($serviceOrder->store_id != session('store_id')) {
            abort(403, 'Akses ditolak.');
        }
    }
}
