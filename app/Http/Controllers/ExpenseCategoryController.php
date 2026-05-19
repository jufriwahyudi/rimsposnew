<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('expense-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $category = ExpenseCategory::create([
            'store_id'    => session('store_id'),
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Kategori berhasil ditambahkan.', 'data' => $category]);
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return response()->json($expenseCategory);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
        ]);

        $expenseCategory->update([
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return response()->json(['success' => true, 'message' => 'Kategori berhasil diperbarui.']);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->exists()) {
            return response()->json(['success' => false, 'message' => 'Kategori tidak bisa dihapus karena sudah digunakan.'], 422);
        }

        $expenseCategory->delete();

        return response()->json(['success' => true, 'message' => 'Kategori berhasil dihapus.']);
    }
}
