<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::where('store_id', session('store_id'))
            ->with(['products', 'printer'])
            ->withCount('products')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $printers = \App\Models\StorePrinter::where('store_id', session('store_id'))
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        return view('kategori_produk.index', compact('categories', 'printers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'printer_id' => 'nullable|integer|exists:store_printers,id',
            'station'    => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $printer = $request->printer_id ? \App\Models\StorePrinter::find($request->printer_id) : null;
        $station = $printer ? ($printer->code ?: Str::slug($printer->name, '_')) : ($request->station ?: null);

        $category = ProductCategory::create([
            'store_id'   => session('store_id'),
            'name'       => $request->name,
            'slug'       => Str::slug($request->name),
            'printer_id' => $request->printer_id ?: null,
            'station'    => $station,
            'sort_order' => $request->integer('sort_order', 0),
            'is_active'  => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori produk berhasil ditambahkan.',
            'data'    => $category,
        ]);
    }

    public function edit($id)
    {
        $productCategory = ProductCategory::findOrFail($id);
        return response()->json($productCategory);
    }

    public function update(Request $request, $id)
    {
        $productCategory = ProductCategory::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:100',
            'printer_id' => 'nullable|integer|exists:store_printers,id',
            'station'    => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $printer = $request->printer_id ? \App\Models\StorePrinter::find($request->printer_id) : null;
        $station = $printer ? ($printer->code ?: Str::slug($printer->name, '_')) : ($request->station ?: null);

        $productCategory->update([
            'name'       => $request->name,
            'slug'       => Str::slug($request->name),
            'printer_id' => $request->printer_id ?: null,
            'station'    => $station,
            'sort_order' => $request->integer('sort_order', 0),
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori produk berhasil diperbarui.',
            'data'    => $productCategory->load('printer'),
        ]);
    }

    public function getProducts($id)
    {
        $category = ProductCategory::where('store_id', session('store_id'))->findOrFail($id);

        $products = $category->products()
            ->select('id', 'nama_produk', 'kode_produk')
            ->orderBy('nama_produk')
            ->get();

        return response()->json([
            'success'  => true,
            'category' => [
                'id'   => $category->id,
                'name' => $category->name,
            ],
            'products' => $products,
        ]);
    }

    public function moveProducts(Request $request, $id)
    {
        $sourceCategory = ProductCategory::where('store_id', session('store_id'))->findOrFail($id);

        $request->validate([
            'target_category_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($sourceCategory) {
                    if ($value == $sourceCategory->id) {
                        $fail('Kategori tujuan tidak boleh sama dengan kategori asal.');
                    }
                    $exists = ProductCategory::where('store_id', session('store_id'))
                        ->where('id', $value)
                        ->exists();
                    if (!$exists) {
                        $fail('Kategori tujuan tidak ditemukan di toko ini.');
                    }
                },
            ],
            'product_ids'   => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'move_all'      => 'nullable|boolean',
        ]);

        $targetCategory = ProductCategory::findOrFail($request->target_category_id);

        $query = \App\Models\Product::where('store_id', session('store_id'))
            ->where('category_id', $sourceCategory->id);

        if (!$request->boolean('move_all') && !empty($request->product_ids)) {
            $query->whereIn('id', $request->product_ids);
        }

        $count = $query->update(['category_id' => $targetCategory->id]);

        return response()->json([
            'success'     => true,
            'message'     => "Berhasil memindahkan {$count} produk ke kategori \"{$targetCategory->name}\".",
            'moved_count' => $count,
        ]);
    }

    public function destroy($id)
    {
        $productCategory = ProductCategory::findOrFail($id);

        if ($productCategory->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih terhubung dengan ' . $productCategory->products()->count() . ' produk. Silakan gunakan tombol "Pindah Produk" untuk memindahkan produk ke kategori lain terlebih dahulu.',
            ], 422);
        }

        $productCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori produk berhasil dihapus.',
        ]);
    }
}
