<?php

namespace App\Services;

use App\Models\Expense;
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
        string $tanggal = null,
        float $totalCost = 0.0
    ): InventoryStock {
        return DB::transaction(function () use ($ingredientId, $conversionId, $qtyPurchased, $storeId, $referenceId, $notes, $tanggal, $totalCost) {
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
            $costPerBaseUnit = $baseQtyAdded > 0 ? ($totalCost / $baseQtyAdded) : 0.0;

            // Create a new batch for this purchase (parent_id is null as it is the root)
            $stock = InventoryStock::create([
                'ingredient_id' => $ingredientId,
                'location_type' => 'WAREHOUSE',
                'location_id'   => $storeId,
                'qty_original'  => $baseQtyAdded,
                'quantity'      => $baseQtyAdded,
                'cost_per_unit' => $costPerBaseUnit,
                'tanggal'       => $tanggal ?? now(),
                'reference_id'  => $referenceId,
                'notes'         => $notes ?? "Pembelian supplier (Batch)",
                'parent_id'     => null,
            ]);

            // Update average price on ingredients table as a fallback estimate
            $totalRemainingQty = InventoryStock::where('ingredient_id', $ingredientId)->sum('quantity');
            if ($totalRemainingQty > 0) {
                $totalValuation = InventoryStock::where('ingredient_id', $ingredientId)
                    ->select(DB::raw('SUM(quantity * cost_per_unit) as total_val'))
                    ->value('total_val');
                $newAvgCost = $totalValuation / $totalRemainingQty;
                $ingredient->update(['cost_per_unit' => $newAvgCost]);
            } else {
                $ingredient->update(['cost_per_unit' => $costPerBaseUnit]);
            }

            // Log movement (linked to the batch)
            IngredientStockMovement::create([
                'ingredient_id'      => $ingredientId,
                'location_type'      => 'WAREHOUSE',
                'location_id'        => $storeId,
                'type'               => 'PURCHASE',
                'quantity_change'    => $baseQtyAdded,
                'reference_id'       => $referenceId,
                'inventory_stock_id' => $stock->id,
                'notes'              => $notes ?? "Pembelian bahan baku (Stock In). Harga beli riil: Rp " . number_format($costPerBaseUnit, 2, ',', '.') . " per satuan dasar.",
                'tanggal'            => $tanggal ?? now(),
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
        string $notes = null,
        bool $asExpense = false,
        ?int $expenseCategoryId = null,
        ?int $userId = null,
        ?string $tanggal = null
    ): array {
        return DB::transaction(function () use ($ingredientId, $qtyToTransfer, $sourceStoreId, $targetStoreId, $referenceId, $notes, $asExpense, $expenseCategoryId, $userId, $tanggal) {
            $ingredient = Ingredient::with('baseUnit')->findOrFail($ingredientId);

            // 1. Get warehouse batches in FIFO order
            $batches = InventoryStock::lockForUpdate()
                ->where([
                    'ingredient_id' => $ingredientId,
                    'location_type' => 'WAREHOUSE',
                    'location_id'   => $sourceStoreId,
                ])
                ->where('quantity', '>', 0)
                ->orderBy('tanggal', 'asc')
                ->get();

            $available = $batches->sum('quantity');
            if ($available < $qtyToTransfer) {
                throw new Exception("Stok di Gudang tidak mencukupi untuk mentransfer " . $ingredient->name . " (Tersedia: " . number_format($available, 2) . ")");
            }

            $remainingToTransfer = $qtyToTransfer;
            $storeStocksCreated = [];
            $totalCost = 0.0;
            $txDate = $tanggal ? \Carbon\Carbon::parse($tanggal) : now();

            // 2. Deduct warehouse batches
            foreach ($batches as $batch) {
                if ($remainingToTransfer <= 0) break;

                $deduct = min($batch->quantity, $remainingToTransfer);
                $batch->quantity -= $deduct;
                $batch->save();

                $batchCost = $deduct * (float) $batch->cost_per_unit;
                $totalCost += $batchCost;

                // Log TRANSFER_OUT for this specific warehouse batch
                $movementNotes = $asExpense
                    ? ($notes ? $notes . ' (Dibebankan ke Biaya Operasional Toko)' : "Transfer keluar dibebankan ke Biaya Operasional Toko")
                    : ($notes ?? "Transfer keluar ke Toko");

                IngredientStockMovement::create([
                    'ingredient_id'      => $ingredientId,
                    'location_type'      => 'WAREHOUSE',
                    'location_id'        => $sourceStoreId,
                    'type'               => 'TRANSFER_OUT',
                    'quantity_change'    => -$deduct,
                    'reference_id'       => $referenceId,
                    'inventory_stock_id' => $batch->id,
                    'notes'              => $movementNotes,
                    'tanggal'            => $txDate,
                ]);

                if (!$asExpense) {
                    // Mode A: Masuk ke persediaan Toko
                    // Create a store batch with parent_id pointing to the warehouse batch
                    $storeStock = InventoryStock::create([
                        'ingredient_id' => $ingredientId,
                        'location_type' => 'STORE',
                        'location_id'   => $targetStoreId,
                        'qty_original'  => $deduct,
                        'quantity'      => $deduct,
                        'cost_per_unit' => $batch->cost_per_unit, // HPP follows!
                        'tanggal'       => $txDate,
                        'reference_id'  => $referenceId,
                        'parent_id'     => $batch->id, // Linked genealogy!
                        'notes'         => "Transfer dari Gudang batch ID: " . $batch->id,
                    ]);

                    // Log TRANSFER_IN for this specific store batch
                    IngredientStockMovement::create([
                        'ingredient_id'      => $ingredientId,
                        'location_type'      => 'STORE',
                        'location_id'        => $targetStoreId,
                        'type'               => 'TRANSFER_IN',
                        'quantity_change'    => $deduct,
                        'reference_id'       => $referenceId,
                        'inventory_stock_id' => $storeStock->id,
                        'notes'              => $notes ?? "Transfer masuk dari Gudang",
                        'tanggal'            => $txDate,
                    ]);

                    $storeStocksCreated[] = $storeStock;
                }

                $remainingToTransfer -= $deduct;
            }

            // If recognized directly as expense, create an Expense entry
            $createdExpense = null;
            if ($asExpense) {
                $unitSymbol = $ingredient->baseUnit->symbol ?? '';
                $desc = "Bahan Baku: {$ingredient->name} (" . number_format($qtyToTransfer, 2, ',', '.') . " {$unitSymbol})";

                $createdExpense = Expense::create([
                    'store_id'            => $targetStoreId,
                    'expense_category_id' => $expenseCategoryId,
                    'transaction_date'    => $txDate->toDateString(),
                    'amount'              => $totalCost,
                    'paid_amount'         => $totalCost,
                    'payment_status'      => 'lunas',
                    'payment_method'      => 'transfer',
                    'description'         => $desc,
                    'notes'               => "Ref Transfer: " . ($referenceId ?? '-') . ($notes ? ". Catatan: {$notes}" : ""),
                    'user_id'             => $userId,
                ]);
            }

            return [
                'store_stocks' => $storeStocksCreated,
                'expense'      => $createdExpense,
                'total_cost'   => $totalCost,
            ];
        });
    }

    /**
     * POS CHECKOUT / ORDER EXECUTION (Auto-Deduction via Recipe & Actual Cost Assignment)
     */
    public function deductRecipeStock(int $storeId, int $productId, float $salesQty, string $referenceId, $saleItem = null): void
    {
        DB::transaction(function () use ($storeId, $productId, $salesQty, $referenceId, $saleItem) {
            $recipes = ProductRecipe::where('product_id', $productId)->with('ingredient')->get();
            $totalRecipeCost = 0.0;

            foreach ($recipes as $recipe) {
                $totalDeductQty = $recipe->quantity_required * $salesQty;

                // 1. Get store batches in FIFO order
                $batches = InventoryStock::lockForUpdate()
                    ->where([
                        'ingredient_id' => $recipe->ingredient_id,
                        'location_type' => 'STORE',
                        'location_id'   => $storeId,
                    ])
                    ->where('quantity', '>', 0)
                    ->orderBy('tanggal', 'asc')
                    ->get();

                $remainingToDeduct = $totalDeductQty;
                $deductedCost = 0.0;

                // 2. Deduct from store batches
                foreach ($batches as $batch) {
                    if ($remainingToDeduct <= 0) break;

                    $deduct = min($batch->quantity, $remainingToDeduct);
                    $batch->quantity -= $deduct;
                    $batch->save();

                    $deductedCost += $deduct * (float) $batch->cost_per_unit;
                    $remainingToDeduct -= $deduct;

                    // Log SALE movement per store batch
                    IngredientStockMovement::create([
                        'ingredient_id'      => $recipe->ingredient_id,
                        'location_type'      => 'STORE',
                        'location_id'        => $storeId,
                        'type'               => 'SALE',
                        'quantity_change'    => -$deduct,
                        'reference_id'       => $referenceId,
                        'inventory_stock_id' => $batch->id,
                        'notes'              => "Penjualan resep. Ref ID: " . $referenceId,
                        'tanggal'            => now(),
                    ]);
                }

                // 3. Fallback if stock is insufficient (stok minus)
                if ($remainingToDeduct > 0) {
                    $fallbackCost = 0.0;
                    $lastBatch = InventoryStock::where([
                        'ingredient_id' => $recipe->ingredient_id,
                        'location_type' => 'STORE',
                        'location_id'   => $storeId,
                    ])->orderBy('tanggal', 'desc')->first();

                    if ($lastBatch) {
                        $fallbackCost = (float) $lastBatch->cost_per_unit;
                    } else {
                        $fallbackCost = (float) ($recipe->ingredient->cost_per_unit ?? 0);
                    }

                    // Create negative batch row to represent stock deficit
                    $negativeStock = InventoryStock::create([
                        'ingredient_id' => $recipe->ingredient_id,
                        'location_type' => 'STORE',
                        'location_id'   => $storeId,
                        'qty_original'  => -$remainingToDeduct,
                        'quantity'      => -$remainingToDeduct,
                        'cost_per_unit' => $fallbackCost,
                        'tanggal'       => now(),
                        'reference_id'  => $referenceId,
                        'notes'         => 'Defisit stok kasir (stok minus)',
                    ]);

                    $deductedCost += $remainingToDeduct * $fallbackCost;

                    // Log SALE movement for deficit
                    IngredientStockMovement::create([
                        'ingredient_id'      => $recipe->ingredient_id,
                        'location_type'      => 'STORE',
                        'location_id'        => $storeId,
                        'type'               => 'SALE',
                        'quantity_change'    => -$remainingToDeduct,
                        'reference_id'       => $referenceId,
                        'inventory_stock_id' => $negativeStock->id,
                        'notes'              => "Penjualan resep (Defisit). Ref ID: " . $referenceId,
                        'tanggal'            => now(),
                    ]);
                }

                // Add to recipe cost total
                $totalRecipeCost += $deductedCost;
            }

            // 4. Update the sale item's cost_price with the actual portion cost!
            if ($saleItem && $salesQty > 0) {
                $costPricePerPortion = $totalRecipeCost / $salesQty;
                $saleItem->update([
                    'cost_price' => $costPricePerPortion
                ]);
            }
        });
    }

    /**
     * RESTORE STOCK ON VOID
     */
    public function restoreRecipeStock(int $storeId, int $productId, float $salesQty, string $referenceId, $saleItem = null): void
    {
        DB::transaction(function () use ($storeId, $productId, $salesQty, $referenceId, $saleItem) {
            $recipes = ProductRecipe::where('product_id', $productId)->get();

            // If saleItem has a saved cost_price, we can calculate the restoring HPP
            $costPricePerPortion = $saleItem ? (float) $saleItem->cost_price : 0.0;

            foreach ($recipes as $recipe) {
                $totalRestoreQty = $recipe->quantity_required * $salesQty;

                // Estimate cost for this ingredient during restoration
                $fallbackCost = 0.0;
                if ($costPricePerPortion > 0) {
                    $lastBatch = InventoryStock::where([
                        'ingredient_id' => $recipe->ingredient_id,
                        'location_type' => 'STORE',
                        'location_id'   => $storeId,
                    ])->orderBy('tanggal', 'desc')->first();
                    $fallbackCost = $lastBatch ? (float)$lastBatch->cost_per_unit : (float)($recipe->ingredient->cost_per_unit ?? 0);
                } else {
                    $fallbackCost = (float) ($recipe->ingredient->cost_per_unit ?? 0);
                }

                // Add back to store stock as a fresh batch
                $restoredStock = InventoryStock::create([
                    'ingredient_id' => $recipe->ingredient_id,
                    'location_type' => 'STORE',
                    'location_id'   => $storeId,
                    'qty_original'  => $totalRestoreQty,
                    'quantity'      => $totalRestoreQty,
                    'cost_per_unit' => $fallbackCost,
                    'tanggal'       => now(),
                    'reference_id'  => $referenceId,
                    'notes'         => 'Restorasi stok dari transaksi void kasir',
                ]);

                // Log movement
                IngredientStockMovement::create([
                    'ingredient_id'      => $recipe->ingredient_id,
                    'location_type'      => 'STORE',
                    'location_id'        => $storeId,
                    'type'               => 'ADJUSTMENT',
                    'quantity_change'    => $totalRestoreQty,
                    'reference_id'       => $referenceId,
                    'inventory_stock_id' => $restoredStock->id,
                    'notes'              => "Pembatalan/Void penjualan resep. Ref ID: " . $referenceId,
                    'tanggal'            => now(),
                ]);
            }
        });
    }

    /**
     * STOCK WASTAGE & ADJUSTMENT (FIFO Deduction or Surplus Addition)
     */
    public function adjustStock(
        string $ingredientId,
        float $actualQuantity,
        int $locationId,
        string $locationType, // WAREHOUSE | STORE
        string $reason,
        string $referenceId = null
    ): void {
        DB::transaction(function () use ($ingredientId, $actualQuantity, $locationId, $locationType, $reason, $referenceId) {
            $ingredient = Ingredient::findOrFail($ingredientId);

            // Calculate current total quantity at this location
            $currentQty = (float) InventoryStock::where([
                'ingredient_id' => $ingredientId,
                'location_type' => $locationType,
                'location_id'   => $locationId,
            ])->sum('quantity');

            $difference = $actualQuantity - $currentQty;
            if ($difference == 0) return;

            // Determine movement type
            $type = 'ADJUSTMENT';
            if (str_contains(strtolower($reason), ['rusak', 'busuk', 'gosong', 'hilang', 'wastage'])) {
                $type = 'WASTAGE';
            }

            if ($difference < 0) {
                // Shortage/Wastage: Deduct from active batches via FIFO
                $shortageAmount = abs($difference);
                $batches = InventoryStock::lockForUpdate()
                    ->where([
                        'ingredient_id' => $ingredientId,
                        'location_type' => $locationType,
                        'location_id'   => $locationId,
                    ])
                    ->where('quantity', '>', 0)
                    ->orderBy('tanggal', 'asc')
                    ->get();

                $remainingToDeduct = $shortageAmount;
                foreach ($batches as $batch) {
                    if ($remainingToDeduct <= 0) break;

                    $deduct = min($batch->quantity, $remainingToDeduct);
                    $batch->quantity -= $deduct;
                    $batch->save();

                    // Log movement per batch
                    IngredientStockMovement::create([
                        'ingredient_id'      => $ingredientId,
                        'location_type'      => $locationType,
                        'location_id'        => $locationId,
                        'type'               => $type,
                        'quantity_change'    => -$deduct,
                        'reference_id'       => $referenceId,
                        'inventory_stock_id' => $batch->id,
                        'notes'              => $reason ?? "Penyesuaian stok fisik (Opname minus)",
                        'tanggal'            => now(),
                    ]);

                    $remainingToDeduct -= $deduct;
                }
            } else {
                // Surplus: Create a new adjusting batch
                $fallbackCost = 0.0;
                $lastBatch = InventoryStock::where([
                    'ingredient_id' => $ingredientId,
                    'location_type' => $locationType,
                    'location_id'   => $locationId,
                ])->orderBy('tanggal', 'desc')->first();

                if ($lastBatch) {
                    $fallbackCost = (float)$lastBatch->cost_per_unit;
                } else {
                    $fallbackCost = (float)($ingredient->cost_per_unit ?? 0);
                }

                $newBatch = InventoryStock::create([
                    'ingredient_id' => $ingredientId,
                    'location_type' => $locationType,
                    'location_id'   => $locationId,
                    'qty_original'  => $difference,
                    'quantity'      => $difference,
                    'cost_per_unit' => $fallbackCost,
                    'tanggal'       => now(),
                    'reference_id'  => $referenceId,
                    'notes'         => $reason ?? 'Penyesuaian stok (Surplus)',
                ]);

                // Log movement
                IngredientStockMovement::create([
                    'ingredient_id'      => $ingredientId,
                    'location_type'      => $locationType,
                    'location_id'        => $locationId,
                    'type'               => $type,
                    'quantity_change'    => $difference,
                    'reference_id'       => $referenceId,
                    'inventory_stock_id' => $newBatch->id,
                    'notes'              => $reason ?? "Penyesuaian stok fisik (Opname plus)",
                    'tanggal'            => now(),
                ]);
            }
        });
    }
}
