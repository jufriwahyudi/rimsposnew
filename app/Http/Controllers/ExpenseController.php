<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ExpenseController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        return view('expenses.index', compact('categories'));
    }

    public function datatables(Request $request)
    {
        $query = Expense::with(['category', 'user'])
            ->select('expenses.*');

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('kategori', fn($e) => $e->category->name ?? '-')
            ->addColumn('jumlah', fn($e) => 'Rp ' . number_format($e->amount, 0, ',', '.'))
            ->addColumn('terbayar', fn($e) => 'Rp ' . number_format($e->paid_amount, 0, ',', '.'))
            ->addColumn('sisa_hutang', fn($e) => 'Rp ' . number_format($e->remaining_amount, 0, ',', '.'))
            ->addColumn('status_badge', function ($e) {
                if ($e->payment_status === 'lunas') {
                    return '<span class="badge bg-success">Lunas</span>';
                } elseif ($e->payment_status === 'sebagian') {
                    return '<span class="badge bg-warning text-dark">Sebagian</span>';
                } else {
                    return '<span class="badge bg-danger">Belum Dibayar</span>';
                }
            })
            ->addColumn('metode', fn($e) => ucfirst($e->payment_method))
            ->addColumn('dicatat_oleh', fn($e) => $e->user->name ?? '-')
            ->addColumn('aksi', function ($e) {
                $payOption = '';
                if ($e->payment_status !== 'lunas') {
                    $payOption = '<li>
                        <a class="dropdown-item text-success btn-pay" href="javascript:void(0)"
                            data-id="' . $e->id . '"
                            data-description="' . e($e->description) . '"
                            data-amount="' . $e->amount . '"
                            data-paid="' . $e->paid_amount . '"
                            data-remaining="' . $e->remaining_amount . '">
                            <i class="material-icons-outlined me-2" style="font-size:18px;vertical-align:middle">payments</i> Bayar / Cicil
                        </a>
                    </li>';
                }

                return '<div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Aksi
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item text-info btn-detail" href="javascript:void(0)" data-id="' . $e->id . '">
                                <i class="material-icons-outlined me-2" style="font-size:18px;vertical-align:middle">info</i> Detail & Riwayat
                            </a>
                        </li>'
                        . $payOption .
                        '<li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-warning btn-edit" href="javascript:void(0)"
                                data-id="' . $e->id . '"
                                data-category="' . $e->expense_category_id . '"
                                data-date="' . $e->transaction_date->format('Y-m-d') . '"
                                data-amount="' . $e->amount . '"
                                data-paid="' . $e->paid_amount . '"
                                data-description="' . e($e->description) . '"
                                data-payment="' . $e->payment_method . '"
                                data-notes="' . e($e->notes) . '">
                                <i class="material-icons-outlined me-2" style="font-size:18px;vertical-align:middle">edit</i> Edit
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger btn-delete" href="javascript:void(0)" data-id="' . $e->id . '">
                                <i class="material-icons-outlined me-2" style="font-size:18px;vertical-align:middle">delete</i> Hapus
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns(['status_badge', 'aksi'])
            ->make(true);
    }

    public function show(Expense $expense)
    {
        $expense->load(['category', 'user', 'payments.user']);

        $payments = $expense->payments->map(function ($p) {
            return [
                'id'               => $p->id,
                'payment_date'     => $p->payment_date ? $p->payment_date->format('d/m/Y') : '-',
                'amount'           => $p->amount,
                'amount_formatted' => 'Rp ' . number_format($p->amount, 0, ',', '.'),
                'payment_method'   => ucfirst($p->payment_method),
                'notes'            => $p->notes ?: '-',
                'user'             => optional($p->user)->name ?? '-',
            ];
        });

        return response()->json([
            'id'                  => $expense->id,
            'category_name'       => optional($expense->category)->name ?? '-',
            'transaction_date'    => $expense->transaction_date ? $expense->transaction_date->format('d/m/Y') : '-',
            'description'         => $expense->description,
            'amount'              => $expense->amount,
            'amount_formatted'    => 'Rp ' . number_format($expense->amount, 0, ',', '.'),
            'paid_amount'         => $expense->paid_amount,
            'paid_formatted'      => 'Rp ' . number_format($expense->paid_amount, 0, ',', '.'),
            'remaining_amount'    => $expense->remaining_amount,
            'remaining_formatted' => 'Rp ' . number_format($expense->remaining_amount, 0, ',', '.'),
            'payment_status'      => $expense->payment_status,
            'payment_method'      => ucfirst($expense->payment_method),
            'notes'               => $expense->notes ?: '-',
            'user_name'           => optional($expense->user)->name ?? '-',
            'payments'            => $payments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'transaction_date'    => 'required|date',
            'amount'              => 'required|numeric|min:1',
            'paid_amount'         => 'nullable|numeric|min:0',
            'description'         => 'required|string|max:255',
            'payment_method'      => 'required|in:cash,transfer',
            'notes'               => 'nullable|string|max:500',
        ]);

        $amount = (float) $request->amount;
        $paidInput = $request->has('paid_amount') && $request->paid_amount !== null ? (float) $request->paid_amount : $amount;
        $paidAmount = min($amount, max(0, $paidInput));

        if ($paidAmount >= $amount) {
            $status = 'lunas';
        } elseif ($paidAmount > 0) {
            $status = 'sebagian';
        } else {
            $status = 'belum_dibayar';
        }

        $expense = Expense::create([
            'store_id'            => session('store_id'),
            'expense_category_id' => $request->expense_category_id,
            'transaction_date'    => $request->transaction_date,
            'amount'              => $amount,
            'paid_amount'         => $paidAmount,
            'payment_status'      => $status,
            'description'         => $request->description,
            'payment_method'      => $request->payment_method,
            'notes'               => $request->notes,
            'user_id'             => Auth::id(),
        ]);

        if ($paidAmount > 0) {
            ExpensePayment::create([
                'expense_id'     => $expense->id,
                'payment_date'   => $request->transaction_date,
                'amount'         => $paidAmount,
                'payment_method' => $request->payment_method,
                'notes'          => 'Pembayaran awal saat pencatatan biaya',
                'user_id'        => Auth::id(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Biaya operasional berhasil disimpan.', 'data' => $expense]);
    }

    public function edit(Expense $expense)
    {
        return response()->json($expense);
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'transaction_date'    => 'required|date',
            'amount'              => 'required|numeric|min:1',
            'paid_amount'         => 'nullable|numeric|min:0',
            'description'         => 'required|string|max:255',
            'payment_method'      => 'required|in:cash,transfer',
            'notes'               => 'nullable|string|max:500',
        ]);

        $amount = (float) $request->amount;
        $paidInput = $request->has('paid_amount') && $request->paid_amount !== null ? (float) $request->paid_amount : $expense->paid_amount;
        $paidAmount = min($amount, max(0, $paidInput));

        if ($paidAmount >= $amount) {
            $status = 'lunas';
        } elseif ($paidAmount > 0) {
            $status = 'sebagian';
        } else {
            $status = 'belum_dibayar';
        }

        $expense->update([
            'expense_category_id' => $request->expense_category_id,
            'transaction_date'    => $request->transaction_date,
            'amount'              => $amount,
            'paid_amount'         => $paidAmount,
            'payment_status'      => $status,
            'description'         => $request->description,
            'payment_method'      => $request->payment_method,
            'notes'               => $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => 'Biaya operasional berhasil diperbarui.']);
    }

    public function pay(Request $request, Expense $expense)
    {
        $request->validate([
            'payment_date'   => 'required|date',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,transfer',
            'notes'          => 'nullable|string|max:500',
        ]);

        $payAmount = (float) $request->amount;
        $remaining = $expense->remaining_amount;

        if ($payAmount > $remaining + 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Nominal pembayaran melebihi sisa hutang (Rp ' . number_format($remaining, 0, ',', '.') . ').'
            ], 422);
        }

        ExpensePayment::create([
            'expense_id'     => $expense->id,
            'payment_date'   => $request->payment_date,
            'amount'         => $payAmount,
            'payment_method' => $request->payment_method,
            'notes'          => $request->notes ?? 'Pelunasan / Cicilan biaya',
            'user_id'        => Auth::id(),
        ]);

        $newPaidAmount = $expense->paid_amount + $payAmount;
        $newStatus = $newPaidAmount >= $expense->amount ? 'lunas' : 'sebagian';

        $expense->update([
            'paid_amount'    => $newPaidAmount,
            'payment_status' => $newStatus,
        ]);

        return response()->json(['success' => true, 'message' => 'Pembayaran biaya operasional berhasil dicatat.']);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->json(['success' => true, 'message' => 'Biaya operasional berhasil dihapus.']);
    }
}
