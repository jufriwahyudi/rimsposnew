<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Ingredient;
use App\Models\ProductRecipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    public function index()
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('select-store.index')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $products = Product::where('store_id', $storeId)
            ->withCount('recipes')
            ->get();

        return view('recipes.index', compact('products'));
    }

    public function manage(Product $product)
    {
        $storeId = session('store_id');
        if ($product->store_id != $storeId) {
            abort(403, 'Akses ditolak.');
        }

        $ingredients = Ingredient::where('store_id', $storeId)
            ->with('baseUnit')
            ->get();

        $currentRecipes = ProductRecipe::where('product_id', $product->id)
            ->with('ingredient.baseUnit')
            ->get();

        return view('recipes.manage', compact('product', 'ingredients', 'currentRecipes'));
    }

    public function save(Request $request, Product $product)
    {
        $storeId = session('store_id');
        if ($product->store_id != $storeId) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'recipes'                     => 'nullable|array',
            'recipes.*.ingredient_id'     => 'required|exists:ingredients,id',
            'recipes.*.quantity_required' => 'required|numeric|min:0.0001',
        ]);

        DB::transaction(function () use ($request, $product) {
            // Delete old recipe lines
            ProductRecipe::where('product_id', $product->id)->delete();

            $recipesData = $request->input('recipes', []);

            if (empty($recipesData)) {
                // If no recipes, product returns to SINGLE type
                $product->update(['product_type' => 'SINGLE']);
            } else {
                // Save new recipe lines
                foreach ($recipesData as $row) {
                    ProductRecipe::create([
                        'product_id'        => $product->id,
                        'ingredient_id'     => $row['ingredient_id'],
                        'quantity_required' => $row['quantity_required'],
                    ]);
                }
                // Set product type to RECIPE
                $product->update(['product_type' => 'RECIPE']);
            }
        });

        return redirect()->route('recipes.index')->with('success', 'Resep menu berhasil diperbarui.');
    }
}
