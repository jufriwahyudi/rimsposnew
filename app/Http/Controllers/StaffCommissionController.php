<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\StaffCommission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StaffCommissionController extends Controller
{
    public function index(Request $request)
    {
        $storeId = session('store_id');
        $staffs = User::whereHas('stores', fn($q) => $q->where('stores.id', $storeId))->orderBy('name')->get();

        $pendingTotal = StaffCommission::where('store_id', $storeId)
            ->where('status', 'pending')
            ->sum('commission_amount');

        $paidTotal = StaffCommission::where('store_id', $storeId)
            ->where('status', 'paid')
            ->sum('commission_amount');

        return view('staff_commissions.index', compact('staffs', 'pendingTotal', 'paidTotal'));
    }

    public function datatables(Request $request)
    {
        $storeId = session('store_id');
        $query = StaffCommission::with(['staff', 'sale', 'serviceOrder'])
            ->where('store_id', $storeId)
            ->orderByDesc('created_at');

        if ($request->staff_id) {
            $query->where('staff_user_id', $request->staff_id);
        }

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
            ->editColumn('created_at', fn($c) => $c->created_at->format('d/m/Y H:i'))
            ->addColumn('staff_name', fn($c) => $c->staff?->name ?? 'Staff')
            ->addColumn('ref_info', function ($c) {
                if ($c->serviceOrder) {
                    return "<span class='badge bg-info text-dark'>Tiket #{$c->serviceOrder->order_number}</span>";
                }
                if ($c->sale) {
                    return "<span class='badge bg-light text-dark border'>POS #{$c->sale->invoice_number}</span>";
                }
                return "-";
            })
            ->addColumn('rate_info', function ($c) {
                if ($c->commission_type === 'percentage') {
                    return number_format($c->commission_rate, 0) . '%';
                } elseif ($c->commission_type === 'fixed') {
                    return 'Rp ' . number_format($c->commission_rate, 0, ',', '.');
                }
                return '-';
            })
            ->editColumn('item_price', fn($c) => 'Rp ' . number_format($c->item_price, 0, ',', '.'))
            ->editColumn('commission_amount', fn($c) => '<strong>Rp ' . number_format($c->commission_amount, 0, ',', '.') . '</strong>')
            ->editColumn('status', function ($c) {
                if ($c->status === 'paid') {
                    return '<span class="badge bg-success">Sudah Dibayar</span>';
                } elseif ($c->status === 'cancelled') {
                    return '<span class="badge bg-danger">Batal</span>';
                }
                return '<span class="badge bg-warning text-dark">Belum Dibayar (Pending)</span>';
            })
            ->addColumn('action', function ($c) {
                if ($c->status === 'pending') {
                    return "<button class='btn btn-sm btn-success' onclick='settleSingle({$c->id}, {$c->commission_amount})'><i class='bi bi-cash'></i> Bayar</button>";
                }
                return "<span class='text-muted'><i class='bi bi-check-circle-fill text-success'></i> Lunas</span>";
            })
            ->rawColumns(['ref_info', 'commission_amount', 'status', 'action'])
            ->make(true);
    }

    public function settle(Request $request)
    {
        $storeId = session('store_id');
        $request->validate([
            'commission_ids'   => 'required|array|min:1',
            'commission_ids.*' => 'exists:staff_commissions,id',
            'payment_date'     => 'required|date',
            'payment_method'   => 'nullable|in:cash,transfer',
            'notes'            => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $storeId) {
                $commissions = StaffCommission::with('staff')
                    ->where('store_id', $storeId)
                    ->whereIn('id', $request->commission_ids)
                    ->where('status', 'pending')
                    ->get();

                if ($commissions->isEmpty()) {
                    throw new \Exception('Tidak ada komisi pending yang dipilih.');
                }

                $totalAmount = $commissions->sum('commission_amount');
                $staffNames = $commissions->pluck('staff.name')->filter()->unique()->implode(', ');
                $paymentMethod = $request->input('payment_method', 'cash');

                // Find or create Expense Category for Commission
                $category = ExpenseCategory::firstOrCreate(
                    ['store_id' => $storeId, 'name' => 'Komisi Staff & Teknisi'],
                    ['description' => 'Pengeluaran untuk pembayaran komisi / fee sharing teknisi & staff', 'is_active' => true]
                );

                $description = "Pencairan komisi " . ($staffNames ? "($staffNames) " : "") . $commissions->count() . " item layanan";

                // Create Expense record
                $expense = Expense::create([
                    'store_id'            => $storeId,
                    'expense_category_id' => $category->id,
                    'transaction_date'    => $request->payment_date,
                    'amount'              => $totalAmount,
                    'paid_amount'         => $totalAmount,
                    'payment_status'      => 'lunas',
                    'description'         => $description,
                    'payment_method'      => $paymentMethod,
                    'notes'               => $request->notes,
                    'user_id'             => auth()->id(),
                ]);

                // Create ExpensePayment record
                \App\Models\ExpensePayment::create([
                    'expense_id'     => $expense->id,
                    'payment_date'   => $request->payment_date,
                    'amount'         => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'notes'          => $request->notes ?: $description,
                    'user_id'        => auth()->id(),
                ]);

                // Update commissions to paid
                StaffCommission::whereIn('id', $commissions->pluck('id'))->update([
                    'status'     => 'paid',
                    'expense_id' => $expense->id,
                    'paid_at'    => now(),
                ]);
            });

            return redirect()->route('staff-commissions.index')->with('success', 'Komisi berhasil dicairkan dan dicatat ke Pengeluaran Toko.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pencairan komisi: ' . $e->getMessage());
        }
    }
}
