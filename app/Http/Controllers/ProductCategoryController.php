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

    public function edit(ProductCategory $productCategory)
    {
        return response()->json($productCategory);
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
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
            'data'    => $productCategory,
        ]);
    }

    public function destroy(ProductCategory $productCategory)
    {
        if ($productCategory->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih terhubung dengan ' . $productCategory->products()->count() . ' produk.',
            ], 422);
        }

        $productCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori produk berhasil dihapus.',
        ]);
    }
}
