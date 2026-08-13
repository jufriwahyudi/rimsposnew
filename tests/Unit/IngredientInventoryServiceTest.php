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
use App\Models\Sale;
use App\Models\SaleItem;
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
        // Purchase 2 Packs for Rp 100.000 total (converts to 20 Pcs, HPP = 5000 / pcs)
        $stock = $this->service->purchaseStock(
            $this->ingredient->id,
            $this->conversion->id,
            2.0,
            $this->store->id,
            'PO-123',
            'Beli ayam dari supplier',
            null,
            100000.0
        );

        $this->assertEquals(20.0000, $stock->quantity);
        $this->assertEquals(5000.00, $stock->cost_per_unit);

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
        // 1. Setup initial Warehouse stock via two different purchases to create separate batches
        // Batch A: 20 Pcs at Rp 2.000/pcs cost (Total Rp 40.000)
        $purchaseA = $this->service->purchaseStock(
            $this->ingredient->id,
            $this->conversion->id,
            2.0,
            $this->store->id,
            'PO-BATCH-A',
            'Batch A',
            null,
            40000.0
        );

        // Batch B: 30 Pcs at Rp 3.000/pcs cost (Total Rp 90.000)
        $purchaseB = $this->service->purchaseStock(
            $this->ingredient->id,
            $this->conversion->id,
            3.0,
            $this->store->id,
            'PO-BATCH-B',
            'Batch B',
            null,
            90000.0
        );

        // 2. Transfer 30 Pcs to Toko (FIFO should fully deduct Batch A [20 Pcs] and deduct 10 Pcs from Batch B)
        $this->service->transferStock(
            $this->ingredient->id,
            30.0000,
            $this->store->id,
            $this->store->id,
            'TRF-123'
        );

        // Verify Warehouse stock is now 20 Pcs remaining (from Batch B)
        $warehouseTotal = InventoryStock::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'WAREHOUSE',
            'location_id' => $this->store->id,
        ])->sum('quantity');
        $this->assertEquals(20.0000, $warehouseTotal);

        // Verify Store stock has two corresponding batches transferred with correct costs
        $storeTotal = InventoryStock::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'location_id' => $this->store->id,
        ])->sum('quantity');
        $this->assertEquals(30.0000, $storeTotal);

        // Verify individual store batch costs and lineage
        $storeBatchA = InventoryStock::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'cost_per_unit' => 2000.00
        ])->first();
        $this->assertNotNull($storeBatchA);
        $this->assertEquals(20.0000, $storeBatchA->qty_original);
        $this->assertEquals($purchaseA->id, $storeBatchA->parent_id); // parent_id references warehouse batch!

        $storeBatchB = InventoryStock::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'cost_per_unit' => 3000.00
        ])->first();
        $this->assertNotNull($storeBatchB);
        $this->assertEquals(10.0000, $storeBatchB->qty_original);
        $this->assertEquals($purchaseB->id, $storeBatchB->parent_id); // parent_id references warehouse batch!

        // Verify movements contain the linked batch ID
        $movements = IngredientStockMovement::where('reference_id', 'TRF-123')->get();
        $this->assertGreaterThan(0, $movements->count());
        foreach ($movements as $m) {
            $this->assertNotNull($m->inventory_stock_id); // Verified link is present!
        }
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

        // 3. Setup Store stock with two separate batches via manual input
        // Batch A: 4 Pcs at Rp 2.000
        InventoryStock::create([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'location_id' => $this->store->id,
            'qty_original' => 4.0,
            'quantity' => 4.0,
            'cost_per_unit' => 2000.00,
            'tanggal' => now()->subMinutes(10),
        ]);
        // Batch B: 6 Pcs at Rp 3.000
        InventoryStock::create([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'location_id' => $this->store->id,
            'qty_original' => 6.0,
            'quantity' => 6.0,
            'cost_per_unit' => 3000.00,
            'tanggal' => now(),
        ]);

        // Create cashier user for sale requirement
        $user = \App\Models\User::first();
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => 'Cashier User',
                'username' => 'cashier_' . rand(100, 999),
                'email' => 'cashier_' . rand(100, 999) . '@test.com',
                'password' => bcrypt('secret'),
            ]);
        }

        // Create Sale & SaleItem
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'sale_date' => now(),
            'subtotal' => 60000,
            'grand_total' => 60000,
            'status' => 'paid',
            'invoice_number' => 'INV-' . rand(10000, 99999),
            'user_id' => $user->id,
        ]);
        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'sku' => 'TEST-SKU',
            'product_name' => 'Menu Resep Test',
            'price' => 20000,
            'qty' => 3.0,
            'discount_amount' => 0,
            'subtotal' => 60000,
        ]);

        // 4. Sell 3 portions (requires 3 * 2 = 6 Pcs)
        // FIFO: Takes 4 Pcs from Batch A (HPP = 8000) and 2 Pcs from Batch B (HPP = 6000) -> Total HPP = 14000
        // Cost per portion = 14000 / 3 = 4666.67
        $this->service->deductRecipeStock($this->store->id, $product->id, 3.0, 'SALE-INV-123', $saleItem);

        // Store stock should be 10 - 6 = 4 Pcs remaining
        $storeTotal = InventoryStock::where([
            'ingredient_id' => $this->ingredient->id,
            'location_type' => 'STORE',
            'location_id' => $this->store->id,
        ])->sum('quantity');
        $this->assertEquals(4.0000, $storeTotal);

        // Verify SaleItem cost_price is updated to 4666.67
        $saleItem->refresh();
        $this->assertEquals(4666.67, round($saleItem->cost_price, 2));
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
            $backdate,
            150000.0
        );

        $this->assertEquals(30.0000, $stock->quantity);
        $this->assertEquals(5000.00, $stock->cost_per_unit);

        $movement = IngredientStockMovement::where([
            'ingredient_id' => $this->ingredient->id,
            'reference_id' => 'PO-BACKDATE',
        ])->first();

        $this->assertNotNull($movement);
        $this->assertEquals($backdate, $movement->tanggal);
    }
}
