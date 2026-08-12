<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Unit;
use App\Models\Ingredient;
use App\Models\IngredientUnitConversion;
use App\Models\InventoryStock;
use App\Models\IngredientStockMovement;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Store;
use App\Services\IngredientInventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IngredientInventoryServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected IngredientInventoryService $service;
    protected Store $store;
    protected Unit $baseUnit;
    protected Unit $purchaseUnit;
    protected Ingredient $ingredient;
    protected IngredientUnitConversion $conversion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new IngredientInventoryService();

        // Create base store
        $this->store = Store::create([
            'name' => 'Toko Test',
            'code' => 'T-TEST-' . rand(100, 999),
            'is_active' => true,
        ]);

        // Create units
        $this->baseUnit = Unit::create([
            'name' => 'Piece',
            'symbol' => 'Pcs',
        ]);

        $this->purchaseUnit = Unit::create([
            'name' => 'Pack',
            'symbol' => 'Pack',
        ]);

        // Create raw material (Ingredient)
        $this->ingredient = Ingredient::create([
            'store_id' => $this->store->id,
            'sku' => 'TEST-ING-' . rand(100, 999),
            'name' => 'Bahan Baku Test',
            'base_unit_id' => $this->baseUnit->id,
            'cost_per_unit' => 1000.00,
        ]);

        // Create conversion rate: 1 Pack = 10 Pcs
        $this->conversion = IngredientUnitConversion::create([
            'ingredient_id' => $this->ingredient->id,
            'purchase_unit_id' => $this->purchaseUnit->id,
            'code' => 'Pack10',
            'conversion_factor' => 10.0000,
        ]);
    }

    /** @test */
    public function it_can_purchase_and_auto_convert_stock()
    {
        // Purchase 2 Packs (which should convert to 2 * 10 = 20 Pcs in base unit)
        $stock = $this->service->purchaseStock(
            $this->ingredient->id,
            $this->conversion->id,
            2.0,
            $this->store->id,
            'PO-123',
            'Beli ayam dari supplier'
        );

        $this->assertEquals(20.0000, $stock->quantity);

        // Verify movement logs
        $movement = IngredientStockMovement::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'WAREHOUSE',
            'location_id' => $this->store->id,
            'type' => 'PURCHASE',
        ])->first();

        $this->assertNotNull($movement);
        $this->assertEquals(20.0000, $movement->quantity_change);
        $this->assertEquals('PO-123', $movement->reference_id);
    }

    /** @test */
    public function it_can_transfer_stock_from_warehouse_to_store()
    {
        // 1. Setup initial Warehouse stock
        InventoryStock::create([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'WAREHOUSE',
            'location_id' => $this->store->id,
            'quantity' => 50.0000,
        ]);

        // 2. Transfer 20 Pcs to Toko
        $this->service->transferStock(
            $this->ingredient->id,
            20.0000,
            $this->store->id,
            $this->store->id,
            'TRF-123'
        );

        // Verify Warehouse stock is now 30 Pcs
        $warehouseStock = InventoryStock::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'WAREHOUSE',
            'location_id' => $this->store->id,
        ])->value('quantity');
        $this->assertEquals(30.0000, $warehouseStock);

        // Verify Store stock is now 20 Pcs
        $storeStock = InventoryStock::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'location_id' => $this->store->id,
        ])->value('quantity');
        $this->assertEquals(20.0000, $storeStock);

        // Verify movement logs
        $outMovement = IngredientStockMovement::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'WAREHOUSE',
            'type' => 'TRANSFER_OUT',
        ])->first();
        $this->assertEquals(-20.0000, $outMovement->quantity_change);

        $inMovement = IngredientStockMovement::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'type' => 'TRANSFER_IN',
        ])->first();
        $this->assertEquals(20.0000, $inMovement->quantity_change);
    }

    /** @test */
    public function it_fails_transfer_if_insufficient_warehouse_stock()
    {
        $this->expectException(\Exception::class);

        // Try transferring 5 Pcs (Warehouse has 0 Pcs)
        $this->service->transferStock(
            $this->ingredient->id,
            5.0000,
            $this->store->id,
            $this->store->id
        );
    }

    /** @test */
    public function it_can_deduct_recipe_stock_during_checkout()
    {
        // 1. Create a Product
        $product = Product::create([
            'store_id' => $this->store->id,
            'kode_produk' => 'PRD-RECIPE-' . rand(100, 999),
            'nama_produk' => 'Menu Resep Test',
            'product_type' => 'RECIPE',
        ]);

        // 2. Map recipe: 1 portion of Product requires 2 Pcs of Ingredient
        ProductRecipe::create([
            'product_id' => $product->id,
            'ingredient_id' => $this->ingredient->id,
            'quantity_required' => 2.0000,
        ]);

        // 3. Setup Store stock
        InventoryStock::create([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'location_id' => $this->store->id,
            'quantity' => 10.0000,
        ]);

        // 4. Sell 3 portions (should deduct 3 * 2 = 6 Pcs)
        $this->service->deductRecipeStock($this->store->id, $product->id, 3.0, 'SALE-INV-123');

        // Store stock should be 10 - 6 = 4 Pcs
        $storeStock = InventoryStock::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'location_id' => $this->store->id,
        ])->value('quantity');
        $this->assertEquals(4.0000, $storeStock);

        // Verify SALE movement logs
        $movement = IngredientStockMovement::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'type' => 'SALE',
        ])->first();
        $this->assertEquals(-6.0000, $movement->quantity_change);
    }

    /** @test */
    public function it_can_purchase_with_backdate()
    {
        $backdate = '2026-08-01 12:00:00';

        $stock = $this->service->purchaseStock(
            $this->ingredient->id,
            $this->conversion->id,
            3.0,
            $this->store->id,
            'PO-BACKDATE',
            'Beli ayam backdate',
            $backdate
        );

        $this->assertEquals(30.0000, $stock->quantity);

        $movement = IngredientStockMovement::where([
            'ingredient_id' => $this->ingredient->id,
            'reference_id' => 'PO-BACKDATE',
        ])->first();

        $this->assertNotNull($movement);
        $this->assertEquals($backdate, $movement->tanggal);
    }
}
