<?php

namespace App\Http\Controllers;

use App\Models\SalesPerson;
use App\Models\Store;
use Illuminate\Http\Request;

class SalesPersonController extends Controller
{
    /**
     * Display a listing of sales persons.
     */
    public function index()
    {
        $storeId = session('store_id');
        $store = $storeId ? Store::find($storeId) : null;

        $salesPersons = SalesPerson::where('store_id', $storeId)
            ->withCount('sales')
            ->orderBy('name')
            ->get();

        return view('sales_persons.index', compact('salesPersons', 'store'));
    }

    /**
     * Store a newly created sales person in storage.
     */
    public function store(Request $request)
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'Pilih toko terlebih dahulu.'], 422);
        }

        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'code'            => 'nullable|string|max:30',
            'phone'           => 'nullable|string|max:30',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active'       => 'nullable|boolean',
        ]);

        $validated['store_id'] = $storeId;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;

        $salesPerson = SalesPerson::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data pramuniaga berhasil ditambahkan.',
            'data'    => $salesPerson,
        ]);
    }

    /**
     * Show the specified sales person for editing (JSON).
     */
    public function edit(SalesPerson $salesPerson)
    {
        return response()->json($salesPerson);
    }

    /**
     * Update the specified sales person in storage.
     */
    public function update(Request $request, SalesPerson $salesPerson)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'code'            => 'nullable|string|max:30',
            'phone'           => 'nullable|string|max:30',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active'       => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;

        $salesPerson->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data pramuniaga berhasil diperbarui.',
            'data'    => $salesPerson,
        ]);
    }

    /**
     * Remove the specified sales person from storage.
     */
    public function destroy(SalesPerson $salesPerson)
    {
        // Check if sales person has sales
        if ($salesPerson->sales()->exists()) {
            // Soft deactivate instead of hard delete to preserve historical integrity
            $salesPerson->update(['is_active' => false]);
            return response()->json([
                'success' => true,
                'message' => 'Pramuniaga memiliki riwayat transaksi, status diubah menjadi Non-aktif.',
            ]);
        }

        $salesPerson->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pramuniaga berhasil dihapus.',
        ]);
    }

    /**
     * API endpoint for mobile POS: get active sales persons for a store.
     */
    public function apiIndex(Request $request)
    {
        $storeId = $request->input('store_id');
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'store_id diperlukan.'], 422);
        }

        $salesPersons = SalesPerson::where('store_id', $storeId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'store_id', 'code', 'name', 'phone', 'commission_rate', 'is_active']);

        return response()->json([
            'success' => true,
            'data'    => $salesPersons,
        ]);
    }
}
