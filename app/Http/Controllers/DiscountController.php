<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    /**
     * Display a listing of discounts.
     */
    public function index()
    {
        $storeId = session('store_id');
        $store = $storeId ? Store::find($storeId) : null;

        $discounts = Discount::where('store_id', $storeId)
            ->withCount('sales')
            ->with('variants:id,product_id,variant_name')
            ->orderBy('created_at', 'desc')
            ->get();

        $products = Product::where('store_id', $storeId)
            ->with(['variants' => function ($q) {
                $q->where('is_active', true)->select('id', 'product_id', 'variant_name', 'sku', 'harga_jual');
            }])
            ->select('id', 'nama_produk')
            ->orderBy('nama_produk')
            ->get();

        return view('discounts.index', compact('discounts', 'store', 'products'));
    }

    /**
     * Store a newly created discount in storage.
     */
    public function store(Request $request)
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'Pilih toko terlebih dahulu.'], 422);
        }

        $validated = $request->validate([
            'name'                => 'required|string|max:150',
            'code'                => 'nullable|string|max:50',
            'discount_type'       => 'required|in:percentage,nominal',
            'discount_value'      => 'required|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'target_type'         => 'required|in:all,member_only',
            'scope_type'          => 'required|in:all,product',
            'variant_ids'         => 'nullable|array',
            'variant_ids.*'       => 'integer|exists:product_variants,id',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'is_active'           => 'nullable|boolean',
            'description'         => 'nullable|string',
        ]);

        $validated['store_id'] = $storeId;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['min_purchase_amount'] = $validated['min_purchase_amount'] ?? 0;

        if ($validated['discount_type'] === 'percentage') {
            if ($validated['discount_value'] > 100) {
                return response()->json(['success' => false, 'message' => 'Persentase diskon tidak boleh melebihi 100%.'], 422);
            }
        }

        if ($validated['scope_type'] === 'product' && empty($request->input('variant_ids'))) {
            return response()->json(['success' => false, 'message' => 'Pilih minimal satu produk/varian untuk diskon khusus produk.'], 422);
        }

        $discount = Discount::create($validated);

        if ($discount->scope_type === 'product' && $request->filled('variant_ids')) {
            $variantIds = $request->input('variant_ids', []);
            $variants = ProductVariant::whereIn('id', $variantIds)->get();
            $syncData = [];
            foreach ($variants as $v) {
                $syncData[$v->id] = ['product_id' => $v->product_id];
            }
            $discount->variants()->sync($syncData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data promosi/diskon berhasil ditambahkan.',
            'data'    => $discount,
        ]);
    }

    /**
     * Show the specified discount for editing (JSON).
     */
    public function edit(Discount $discount)
    {
        $discount->load('variants:id,product_id,variant_name');
        $variantIds = $discount->variants->pluck('id')->toArray();

        return response()->json(array_merge($discount->toArray(), [
            'variant_ids' => $variantIds,
        ]));
    }

    /**
     * Update the specified discount in storage.
     */
    public function update(Request $request, Discount $discount)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:150',
            'code'                => 'nullable|string|max:50',
            'discount_type'       => 'required|in:percentage,nominal',
            'discount_value'      => 'required|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'target_type'         => 'required|in:all,member_only',
            'scope_type'          => 'required|in:all,product',
            'variant_ids'         => 'nullable|array',
            'variant_ids.*'       => 'integer|exists:product_variants,id',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'is_active'           => 'nullable|boolean',
            'description'         => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['min_purchase_amount'] = $validated['min_purchase_amount'] ?? 0;

        if ($validated['discount_type'] === 'percentage') {
            if ($validated['discount_value'] > 100) {
                return response()->json(['success' => false, 'message' => 'Persentase diskon tidak boleh melebihi 100%.'], 422);
            }
        }

        if ($validated['scope_type'] === 'product' && empty($request->input('variant_ids'))) {
            return response()->json(['success' => false, 'message' => 'Pilih minimal satu produk/varian untuk diskon khusus produk.'], 422);
        }

        $discount->update($validated);

        if ($discount->scope_type === 'product' && $request->filled('variant_ids')) {
            $variantIds = $request->input('variant_ids', []);
            $variants = ProductVariant::whereIn('id', $variantIds)->get();
            $syncData = [];
            foreach ($variants as $v) {
                $syncData[$v->id] = ['product_id' => $v->product_id];
            }
            $discount->variants()->sync($syncData);
        } else {
            $discount->variants()->detach();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data promosi/diskon berhasil diperbarui.',
            'data'    => $discount,
        ]);
    }

    /**
     * Remove the specified discount from storage.
     */
    public function destroy(Discount $discount)
    {
        if ($discount->sales()->exists()) {
            $discount->update(['is_active' => false]);
            return response()->json([
                'success' => true,
                'message' => 'Promo memiliki riwayat transaksi, status diubah menjadi Non-aktif.',
            ]);
        }

        $discount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data promosi/diskon berhasil dihapus.',
            'data'    => null,
        ]);
    }

    /**
     * API endpoint for POS: get active discounts for a store.
     */
    public function apiIndex(Request $request)
    {
        $storeId = $request->input('store_id') ?: session('store_id');
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'store_id diperlukan.'], 422);
        }

        $discounts = Discount::where('store_id', $storeId)
            ->active()
            ->with('variants:id,product_id,variant_name')
            ->orderBy('min_purchase_amount', 'asc')
            ->get()
            ->map(function ($d) {
                $arr = $d->toArray();
                $arr['eligible_variant_ids'] = $d->variants->pluck('id')->values()->all();
                return $arr;
            });

        return response()->json([
            'success' => true,
            'data'    => $discounts,
        ]);
    }
}
