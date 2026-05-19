<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
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
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('kategori', fn($e) => $e->category->name ?? '-')
            ->addColumn('jumlah', fn($e) => 'Rp ' . number_format($e->amount, 0, ',', '.'))
            ->addColumn('metode', fn($e) => ucfirst($e->payment_method))
            ->addColumn('dicatat_oleh', fn($e) => $e->user->name ?? '-')
            ->addColumn('aksi', function ($e) {
                return '<button class="btn btn-sm btn-warning me-1 btn-edit"
                            data-id="' . $e->id . '"
                            data-category="' . $e->expense_category_id . '"
                            data-date="' . $e->transaction_date->format('Y-m-d') . '"
                            data-amount="' . $e->amount . '"
                            data-description="' . e($e->description) . '"
                            data-payment="' . $e->payment_method . '"
                            data-notes="' . e($e->notes) . '">
                            <i class="material-icons-outlined" style="font-size:16px">edit</i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-delete" data-id="' . $e->id . '">
                            <i class="material-icons-outlined" style="font-size:16px">delete</i>
                        </button>';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'transaction_date'    => 'required|date',
            'amount'              => 'required|numeric|min:1',
            'description'         => 'required|string|max:255',
            'payment_method'      => 'required|in:cash,transfer',
            'notes'               => 'nullable|string|max:500',
        ]);

        $expense = Expense::create([
            'store_id'            => session('store_id'),
            'expense_category_id' => $request->expense_category_id,
            'transaction_date'    => $request->transaction_date,
            'amount'              => $request->amount,
            'description'         => $request->description,
            'payment_method'      => $request->payment_method,
            'notes'               => $request->notes,
            'user_id'             => Auth::id(),
        ]);

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
            'description'         => 'required|string|max:255',
            'payment_method'      => 'required|in:cash,transfer',
            'notes'               => 'nullable|string|max:500',
        ]);

        $expense->update([
            'expense_category_id' => $request->expense_category_id,
            'transaction_date'    => $request->transaction_date,
            'amount'              => $request->amount,
            'description'         => $request->description,
            'payment_method'      => $request->payment_method,
            'notes'               => $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => 'Biaya operasional berhasil diperbarui.']);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->json(['success' => true, 'message' => 'Biaya operasional berhasil dihapus.']);
    }
}
