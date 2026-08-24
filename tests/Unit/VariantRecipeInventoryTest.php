<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Ingredient;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\Unit;
use App\Services\IngredientInventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VariantRecipeInventoryTest extends TestCase
{
    use DatabaseTransactions;

    protected IngredientInventoryService $service;
    protected Store $store;
    protected Unit $unitGram;
    protected Unit $unitPcs;
    protected Ingredient $chili;
    protected Ingredient $chicken;
    protected Product $product;
    protected ProductVariant $variantDefault;
    protected ProductVariant $variantCustom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new IngredientInventoryService();

        $this->store = Store::create([
            'name' => 'FnB Store Test',
            'code' => 'FNB-' . rand(100, 999),
            'is_active' => true,
            'business_type' => 'fnb',
        ]);

        $this->unitGram = Unit::create(['name' => 'Gram', 'symbol' => 'g']);
        $this->unitPcs = Unit::create(['name' => 'Piece', 'symbol' => 'pcs']);

        $this->chicken = Ingredient::create([
            'store_id' => $this->store->id,
            'name' => 'Ayam Potong',
            'sku' => 'ING-AYAM-' . rand(100, 999),
            'base_unit_id' => $this->unitPcs->id,
            'cost_per_unit' => 10000,
        ]);

        $this->chili = Ingredient::create([
            'store_id' => $this->store->id,
            'name' => 'Cabai Rawit',
            'sku' => 'ING-CABAI-' . rand(100, 999),
            'base_unit_id' => $this->unitGram->id,
            'cost_per_unit' => 50,
        ]);

        // Add initial stock in STORE
        InventoryStock::create([
            'ingredient_id' => $this->chicken->id,
            'location_type' => 'STORE',
            'location_id'   => $this->store->id,
            'qty_original'  => 50,
            'quantity'      => 50,
            'cost_per_unit' => 10000,
            'tanggal'       => now(),
        ]);

        InventoryStock::create([
            'ingredient_id' => $this->chili->id,
            'location_type' => 'STORE',
            'location_id'   => $this->store->id,
            'qty_original'  => 1000,
            'quantity'      => 1000,
            'cost_per_unit' => 50,
            'tanggal'       => now(),
        ]);

        // Create Product: Ayam Geprek
        $this->product = Product::create([
            'store_id'     => $this->store->id,
            'nama_produk'  => 'Ayam Geprek Sambal',
            'kode_produk'  => 'GPRK-' . rand(100, 999),
            'product_type' => 'RECIPE',
        ]);

        // Create Variants: Level 1 (Default) and Level 5 (Custom)
        $this->variantDefault = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $this->product->id,
            'variant_name' => 'Level 1',
            'sku'          => $this->product->kode_produk . '-L1',
            'barcode'      => 'BAR-L1-' . rand(10000, 99999),
            'harga_jual'   => 15000,
        ]);

        $this->variantCustom = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $this->product->id,
            'variant_name' => 'Level 5 (Extra Pedas)',
            'sku'          => $this->product->kode_produk . '-L5',
            'barcode'      => 'BAR-L5-' . rand(10000, 99999),
            'harga_jual'   => 20000,
        ]);

        // Default Product Recipe: 1 pcs chicken + 10g chili
        ProductRecipe::create([
            'product_id'         => $this->product->id,
            'product_variant_id' => null,
            'ingredient_id'      => $this->chicken->id,
            'quantity_required'  => 1.0,
        ]);
        ProductRecipe::create([
            'product_id'         => $this->product->id,
            'product_variant_id' => null,
            'ingredient_id'      => $this->chili->id,
            'quantity_required'  => 10.0,
        ]);

        // Custom Recipe for Level 5: 1 pcs chicken + 50g chili
        ProductRecipe::create([
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variantCustom->id,
            'ingredient_id'      => $this->chicken->id,
            'quantity_required'  => 1.0,
        ]);
        ProductRecipe::create([
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variantCustom->id,
            'ingredient_id'      => $this->chili->id,
            'quantity_required'  => 50.0,
        ]);
    }

    public function test_variant_without_custom_recipe_falls_back_to_product_default_recipe()
    {
        $this->assertFalse($this->variantDefault->has_custom_recipe);
        $this->assertTrue($this->variantCustom->has_custom_recipe);

        $sale = Sale::create([
            'store_id'       => $this->store->id,
            'invoice_number' => 'INV-DEF-001',
            'sale_date'      => now(),
            'subtotal'       => 30000,
            'grand_total'    => 30000,
            'status'         => 'paid',
        ]);

        $saleItem = SaleItem::create([
            'sale_id'            => $sale->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variantDefault->id,
            'sku'                => $this->variantDefault->sku,
            'product_name'       => $this->product->nama_produk,
            'price'              => 15000,
            'qty'                => 2,
            'subtotal'           => 30000,
        ]);

        // Deduct for 2 portions of Level 1 (Default: 2x 1 pcs chicken = 2 pcs, 2x 10g chili = 20g)
        $this->service->deductRecipeStock(
            $this->store->id,
            $this->product->id,
            2.0,
            $sale->invoice_number,
            $saleItem,
            $this->variantDefault->id
        );

        $chickenStock = InventoryStock::where('ingredient_id', $this->chicken->id)->where('location_id', $this->store->id)->sum('quantity');
        $chiliStock = InventoryStock::where('ingredient_id', $this->chili->id)->where('location_id', $this->store->id)->sum('quantity');

        $this->assertEquals(48.0, $chickenStock); // 50 - 2 = 48
        $this->assertEquals(980.0, $chiliStock);  // 1000 - 20 = 980

        // Cost per portion: (1 * 10000) + (10 * 50) = 10000 + 500 = 10500
        $saleItem->refresh();
        $this->assertEquals(10500.0, (float)$saleItem->cost_price);
    }

    public function test_variant_with_custom_recipe_deducts_custom_ingredients()
    {
        $sale = Sale::create([
            'store_id'       => $this->store->id,
            'invoice_number' => 'INV-CUST-002',
            'sale_date'      => now(),
            'subtotal'       => 40000,
            'grand_total'    => 40000,
            'status'         => 'paid',
        ]);

        $saleItem = SaleItem::create([
            'sale_id'            => $sale->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variantCustom->id,
            'sku'                => $this->variantCustom->sku,
            'product_name'       => $this->product->nama_produk,
            'price'              => 20000,
            'qty'                => 2,
            'subtotal'           => 40000,
        ]);

        // Deduct for 2 portions of Level 5 (Custom: 2x 1 pcs chicken = 2 pcs, 2x 50g chili = 100g)
        $this->service->deductRecipeStock(
            $this->store->id,
            $this->product->id,
            2.0,
            $sale->invoice_number,
            $saleItem,
            $this->variantCustom->id
        );

        $chickenStock = InventoryStock::where('ingredient_id', $this->chicken->id)->where('location_id', $this->store->id)->sum('quantity');
        $chiliStock = InventoryStock::where('ingredient_id', $this->chili->id)->where('location_id', $this->store->id)->sum('quantity');

        $this->assertEquals(48.0, $chickenStock); // 50 - 2 = 48
        $this->assertEquals(900.0, $chiliStock);  // 1000 - 100 = 900

        // Cost per portion: (1 * 10000) + (50 * 50) = 10000 + 2500 = 12500
        $saleItem->refresh();
        $this->assertEquals(12500.0, (float)$saleItem->cost_price);
    }

    public function test_restore_recipe_stock_for_custom_variant()
    {
        $saleItem = new SaleItem([
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variantCustom->id,
            'cost_price'         => 12500.0,
        ]);

        $this->service->restoreRecipeStock(
            $this->store->id,
            $this->product->id,
            1.0,
            'VOID-001',
            $saleItem,
            $this->variantCustom->id
        );

        $chickenStock = InventoryStock::where('ingredient_id', $this->chicken->id)->where('location_id', $this->store->id)->sum('quantity');
        $chiliStock = InventoryStock::where('ingredient_id', $this->chili->id)->where('location_id', $this->store->id)->sum('quantity');

        $this->assertEquals(51.0, $chickenStock);  // 50 + 1 = 51
        $this->assertEquals(1050.0, $chiliStock);  // 1000 + 50 = 1050
    }
}
