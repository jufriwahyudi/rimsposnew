<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientUnitConversion;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IngredientController extends Controller
{
    public function index()
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('select-store.index')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        // Auto-seed units if empty
        if (Unit::count() === 0) {
            Unit::insert([
                ['name' => 'Pack', 'symbol' => 'Pack'],
                ['name' => 'Piece', 'symbol' => 'Pcs'],
                ['name' => 'Kilogram', 'symbol' => 'Kg'],
                ['name' => 'Gram', 'symbol' => 'Gram'],
                ['name' => 'Liter', 'symbol' => 'Ltr'],
                ['name' => 'Milliliter', 'symbol' => 'Ml'],
            ]);
        }

        $ingredients = Ingredient::where('store_id', $storeId)
            ->with(['baseUnit', 'conversions.purchaseUnit'])
            ->get();

        $units = Unit::all();

        return view('ingredients.index', compact('ingredients', 'units'));
    }

    public function store(Request $request)
    {
        $storeId = session('store_id');
        $request->validate([
            'sku'           => 'required|string|max:50|unique:ingredients,sku',
            'name'          => 'required|string|max:100',
            'base_unit_id'  => 'required|exists:units,id',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        Ingredient::create([
            'store_id'      => $storeId,
            'sku'           => $request->sku,
            'name'          => $request->name,
            'base_unit_id'  => $request->base_unit_id,
            'cost_per_unit' => $request->cost_per_unit,
        ]);

        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $request->validate([
            'sku'           => 'required|string|max:50|unique:ingredients,sku,' . $ingredient->id,
            'name'          => 'required|string|max:100',
            'base_unit_id'  => 'required|exists:units,id',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        $ingredient->update([
            'sku'           => $request->sku,
            'name'          => $request->name,
            'base_unit_id'  => $request->base_unit_id,
            'cost_per_unit' => $request->cost_per_unit,
        ]);

        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil diperbarui.');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil dihapus.');
    }

    public function storeConversion(Request $request, Ingredient $ingredient)
    {
        $request->validate([
            'purchase_unit_id'  => 'required|exists:units,id',
            'code'              => 'required|string|max:50',
            'conversion_factor' => 'required|numeric|min:0.0001',
        ]);

        // Custom validation check manually since different_unit is custom
        if ($request->purchase_unit_id === $ingredient->base_unit_id) {
            return back()->withErrors(['purchase_unit_id' => 'Satuan pembelian tidak boleh sama dengan satuan dasar.']);
        }

        // Check duplicate conversion (same ingredient, same unit, same code)
        $exists = IngredientUnitConversion::where([
            'ingredient_id'    => $ingredient->id,
            'purchase_unit_id' => $request->purchase_unit_id,
            'code'             => $request->code,
        ])->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Konversi dengan kode ini sudah terdaftar untuk satuan ini.']);
        }

        IngredientUnitConversion::create([
            'ingredient_id'     => $ingredient->id,
            'purchase_unit_id'  => $request->purchase_unit_id,
            'code'              => $request->code,
            'conversion_factor' => $request->conversion_factor,
        ]);

        return redirect()->route('ingredients.index')->with('success', 'Konversi satuan berhasil ditambahkan.');
    }

    public function destroyConversion(IngredientUnitConversion $conversion)
    {
        $conversion->delete();
        return redirect()->route('ingredients.index')->with('success', 'Konversi satuan berhasil dihapus.');
    }
}
