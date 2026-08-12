<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientUnitConversion;
use App\Models\InventoryStock;
use App\Models\ProductRecipe;
use App\Models\IngredientStockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class IngredientInventoryService
{
    /**
     * PURCHASING / STOCK IN (Supplier -> Gudang)
     */
    public function purchaseStock(
        string $ingredientId,
        ?int $conversionId,
        float $qtyPurchased,
        int $storeId,
        string $referenceId = null,
        string $notes = null,
        string $tanggal = null
    ): InventoryStock {
        return DB::transaction(function () use ($ingredientId, $conversionId, $qtyPurchased, $storeId, $referenceId, $notes, $tanggal) {
            $ingredient = Ingredient::findOrFail($ingredientId);

            $conversionFactor = 1.0;
            if ($conversionId) {
                $conversion = IngredientUnitConversion::where('ingredient_id', $ingredientId)
                    ->where('id', $conversionId)
                    ->first();

                if (!$conversion) {
                    throw new Exception("Faktor konversi satuan tidak ditemukan.");
                }
                $conversionFactor = (float) $conversion->conversion_factor;
            }

            $baseQtyAdded = $qtyPurchased * $conversionFactor;

            $stock = InventoryStock::lockForUpdate()->firstOrCreate(
                [
                    'ingredient_id' => $ingredientId,
                    'location_type' => 'WAREHOUSE',
                    'location_id'   => $storeId,
                ],
                [
                    'quantity' => 0.0000,
                ]
            );

            $stock->quantity += $baseQtyAdded;
            $stock->save();

            IngredientStockMovement::create([
                'ingredient_id'   => $ingredientId,
                'location_type'   => 'WAREHOUSE',
                'location_id'     => $storeId,
                'type'            => 'PURCHASE',
                'quantity_change' => $baseQtyAdded,
                'reference_id'    => $referenceId,
                'notes'           => $notes ?? "Pembelian bahan baku (Stock In)",
                'tanggal'         => $tanggal ?? now(),
            ]);

            return $stock;
        });
    }

    /**
     * STOCK TRANSFER (Gudang -> Toko)
     */
    public function transferStock(
        string $ingredientId,
        float $qtyToTransfer,
        int $sourceStoreId,
        int $targetStoreId,
        string $referenceId = null,
        string $notes = null
    ): array {
        return DB::transaction(function () use ($ingredientId, $qtyToTransfer, $sourceStoreId, $targetStoreId, $referenceId, $notes) {
            $ingredient = Ingredient::findOrFail($ingredientId);

            // 1. Cek kecukupan stok di source_location_id (Gudang)
            $sourceStock = InventoryStock::lockForUpdate()->where([
                'ingredient_id' => $ingredientId,
                'location_type' => 'WAREHOUSE',
                'location_id'   => $sourceStoreId,
            ])->first();

            if (!$sourceStock || $sourceStock->quantity < $qtyToTransfer) {
                $available = $sourceStock ? $sourceStock->quantity : 0;
                throw new Exception("Stok di Gudang tidak mencukupi untuk mentransfer " . $ingredient->name . " (Tersedia: " . number_format($available, 2) . ")");
            }

            // 2. Kurangi stok Gudang
            $sourceStock->quantity -= $qtyToTransfer;
            $sourceStock->save();

            // 3. Tambahkan ke stok Toko
            $targetStock = InventoryStock::lockForUpdate()->firstOrCreate(
                [
                    'ingredient_id' => $ingredientId,
                    'location_type' => 'STORE',
                    'location_id'   => $targetStoreId,
                ],
                [
                    'quantity' => 0.0000,
                ]
            );
            $targetStock->quantity += $qtyToTransfer;
            $targetStock->save();

            // 4. Catat logs
            IngredientStockMovement::create([
                'ingredient_id'   => $ingredientId,
                'location_type'   => 'WAREHOUSE',
                'location_id'     => $sourceStoreId,
                'type'            => 'TRANSFER_OUT',
                'quantity_change' => -$qtyToTransfer,
                'reference_id'    => $referenceId,
                'notes'           => $notes ?? "Transfer keluar ke toko",
                'tanggal'         => now(),
            ]);

            IngredientStockMovement::create([
                'ingredient_id'   => $ingredientId,
                'location_type'   => 'STORE',
                'location_id'     => $targetStoreId,
                'type'            => 'TRANSFER_IN',
                'quantity_change' => $qtyToTransfer,
                'reference_id'    => $referenceId,
                'notes'           => $notes ?? "Transfer masuk dari gudang",
                'tanggal'         => now(),
            ]);

            return [$sourceStock, $targetStock];
        });
    }

    /**
     * POS CHECKOUT / ORDER EXECUTION (Auto-Deduction via Recipe)
     */
    public function deductRecipeStock(int $storeId, int $productId, float $salesQty, string $referenceId): void
    {
        DB::transaction(function () use ($storeId, $productId, $salesQty, $referenceId) {
            $recipes = ProductRecipe::where('product_id', $productId)->get();

            foreach ($recipes as $recipe) {
                $totalDeductQty = $recipe->quantity_required * $salesQty;

                $stock = InventoryStock::lockForUpdate()->firstOrCreate(
                    [
                        'ingredient_id' => $recipe->ingredient_id,
                        'location_type' => 'STORE',
                        'location_id'   => $storeId,
                    ],
                    [
                        'quantity' => 0.0000,
                    ]
                );

                // Deduct from store stock
                $stock->quantity -= $totalDeductQty;
                $stock->save();

                // Log movement
                IngredientStockMovement::create([
                    'ingredient_id'   => $recipe->ingredient_id,
                    'location_type'   => 'STORE',
                    'location_id'     => $storeId,
                    'type'            => 'SALE',
                    'quantity_change' => -$totalDeductQty,
                    'reference_id'    => $referenceId,
                    'notes'           => "Penjualan menu produk resep. Ref ID: " . $referenceId,
                    'tanggal'         => now(),
                ]);
            }
        });
    }

    /**
     * RESTORE STOCK ON VOID
     */
    public function restoreRecipeStock(int $storeId, int $productId, float $salesQty, string $referenceId): void
    {
        DB::transaction(function () use ($storeId, $productId, $salesQty, $referenceId) {
            $recipes = ProductRecipe::where('product_id', $productId)->get();

            foreach ($recipes as $recipe) {
                $totalRestoreQty = $recipe->quantity_required * $salesQty;

                $stock = InventoryStock::lockForUpdate()->firstOrCreate(
                    [
                        'ingredient_id' => $recipe->ingredient_id,
                        'location_type' => 'STORE',
                        'location_id'   => $storeId,
                    ],
                    [
                        'quantity' => 0.0000,
                    ]
                );

                // Add back to store stock
                $stock->quantity += $totalRestoreQty;
                $stock->save();

                // Log movement
                IngredientStockMovement::create([
                    'ingredient_id'   => $recipe->ingredient_id,
                    'location_type'   => 'STORE',
                    'location_id'     => $storeId,
                    'type'            => 'ADJUSTMENT',
                    'quantity_change' => $totalRestoreQty,
                    'reference_id'    => $referenceId,
                    'notes'           => "Pembatalan/Void penjualan resep. Ref ID: " . $referenceId,
                    'tanggal'         => now(),
                ]);
            }
        });
    }

    /**
     * STOCK WASTAGE & ADJUSTMENT
     */
    public function adjustStock(
        string $ingredientId,
        float $actualQuantity,
        int $locationId,
        string $locationType, // WAREHOUSE | STORE
        string $reason,
        string $referenceId = null
    ): InventoryStock {
        return DB::transaction(function () use ($ingredientId, $actualQuantity, $locationId, $locationType, $reason, $referenceId) {
            $stock = InventoryStock::lockForUpdate()->firstOrCreate(
                [
                    'ingredient_id' => $ingredientId,
                    'location_type' => $locationType,
                    'location_id'   => $locationId,
                ],
                [
                    'quantity' => 0.0000,
                ]
            );

            $currentSystemQuantity = (float) $stock->quantity;
            $difference = $actualQuantity - $currentSystemQuantity;

            $stock->quantity = $actualQuantity;
            $stock->save();

            // Determine if it's WASTAGE or general ADJUSTMENT based on difference or reason
            $type = 'ADJUSTMENT';
            if (str_contains(strtolower($reason), ['rusak', 'busuk', 'gosong', 'hilang', 'wastage'])) {
                $type = 'WASTAGE';
            }

            IngredientStockMovement::create([
                'ingredient_id'   => $ingredientId,
                'location_type'   => $locationType,
                'location_id'     => $locationId,
                'type'            => $type,
                'quantity_change' => $difference,
                'reference_id'    => $referenceId,
                'notes'           => $reason ?? "Opname / Penyesuaian stok",
                'tanggal'         => now(),
            ]);

            return $stock;
        });
    }
}
