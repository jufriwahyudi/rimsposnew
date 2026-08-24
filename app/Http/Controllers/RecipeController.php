<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Ingredient;
use App\Models\ProductRecipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function index()
    {
        $storeId = session('store_id');
        if (!$storeId) {
            return redirect()->route('select-store.index')->with('error', 'Silakan pilih toko terlebih dahulu.');
        }

        $products = Product::where('store_id', $storeId)
            ->with([
                'recipes.ingredient.baseUnit',
                'variants' => fn($q) => $q->where('is_active', 'Y')->with('recipes.ingredient.baseUnit')
            ])
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
            ->orderBy('name', 'asc')
            ->get();

        $product->load([
            'recipes.ingredient.baseUnit',
            'variants' => fn($q) => $q->where('is_active', 'Y')->with('recipes.ingredient.baseUnit')
        ]);

        $defaultRecipes = $product->recipes;
        $variants = $product->variants;

        return view('recipes.manage', compact('product', 'ingredients', 'defaultRecipes', 'variants'));
    }

    public function save(Request $request, Product $product)
    {
        $storeId = session('store_id');
        if ($product->store_id != $storeId) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'default_recipes'                     => 'nullable|array',
            'default_recipes.*.ingredient_id'     => 'required|exists:ingredients,id',
            'default_recipes.*.quantity_required' => 'required|numeric|min:0.0001',
            'variants'                            => 'nullable|array',
            'variants.*.has_custom_recipe'        => 'nullable|in:0,1',
            'variants.*.recipes'                  => 'nullable|array',
            'variants.*.recipes.*.ingredient_id'  => 'required|exists:ingredients,id',
            'variants.*.recipes.*.quantity_required' => 'required|numeric|min:0.0001',
        ]);

        DB::transaction(function () use ($request, $product) {
            // Delete all old recipes for this product (both default and variant level)
            ProductRecipe::where('product_id', $product->id)->delete();

            // 1. Save default product-level recipe
            $defaultRecipesData = $request->input('default_recipes', []);
            foreach ($defaultRecipesData as $row) {
                if (!empty($row['ingredient_id']) && !empty($row['quantity_required'])) {
                    ProductRecipe::create([
                        'product_id'         => $product->id,
                        'product_variant_id' => null,
                        'ingredient_id'      => $row['ingredient_id'],
                        'quantity_required'  => $row['quantity_required'],
                    ]);
                }
            }

            // 2. Save variant-level recipes (if custom is enabled)
            $variantsData = $request->input('variants', []);
            foreach ($variantsData as $variantId => $vData) {
                $hasCustom = !empty($vData['has_custom_recipe']) && (string)$vData['has_custom_recipe'] === '1';
                if ($hasCustom && !empty($vData['recipes'])) {
                    foreach ($vData['recipes'] as $row) {
                        if (!empty($row['ingredient_id']) && !empty($row['quantity_required'])) {
                            ProductRecipe::create([
                                'product_id'         => $product->id,
                                'product_variant_id' => $variantId,
                                'ingredient_id'      => $row['ingredient_id'],
                                'quantity_required'  => $row['quantity_required'],
                            ]);
                        }
                    }
                }
            }

            // 3. Update product_type
            $hasAnyRecipe = ProductRecipe::where('product_id', $product->id)->exists();
            if ($hasAnyRecipe) {
                $product->update(['product_type' => 'RECIPE']);
            } else {
                $product->update(['product_type' => 'SINGLE']);
            }
        });

        return redirect()->route('recipes.index')->with('success', 'Konfigurasi resep menu berhasil diperbarui.');
    }
}

