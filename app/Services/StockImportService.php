<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Vendor;
use App\Services\StockService;
use App\Services\StockAdjustmentPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class StockImportService
{
    public function import(array $params)
    {
        $file = $params['file'];
        $storeId = $params['store_id'];
        $transactionType = $params['transaction_type']; // 'purchase_order' or 'stock_adjustment'
        $isDryRun = $params['dry_run'] ?? false;

        $array = Excel::toArray([], $file);
        $sheet = $array[0] ?? [];

        if (empty($sheet)) {
            throw new \Exception("File Excel kosong atau tidak valid.");
        }

        $headers = array_map(function ($header) {
            return strtolower(trim($header));
        }, $sheet[0]);

        $columnMapping = [
            'sku' => array_search('sku', $headers),
            'posisi' => array_search('posisi (store/warehouse)', $headers),
            'jumlah_stok' => array_search('jumlah stok', $headers),
            'harga_beli' => array_search('harga beli/modal', $headers),
        ];

        if ($columnMapping['sku'] === false || $columnMapping['posisi'] === false || $columnMapping['jumlah_stok'] === false || $columnMapping['harga_beli'] === false) {
            throw new \Exception("Format file tidak sesuai template. Pastikan header kolom 'SKU', 'Posisi (store/warehouse)', 'Jumlah Stok', dan 'Harga Beli/Modal' tersedia.");
        }

        $rows = array_slice($sheet, 1);
        $results = [];
        $totalRows = 0;
        $validCount = 0;
        $invalidCount = 0;
        $skippedCount = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $nonEmptyCells = array_filter($row, function ($val) {
                return !is_null($val) && trim($val) !== '';
            });
            if (empty($nonEmptyCells)) {
                continue;
            }

            $sku = isset($row[$columnMapping['sku']]) ? strtoupper(trim($row[$columnMapping['sku']])) : '';
            $posisi = isset($row[$columnMapping['posisi']]) ? strtolower(trim($row[$columnMapping['posisi']])) : '';
            $qty = isset($row[$columnMapping['jumlah_stok']]) ? trim($row[$columnMapping['jumlah_stok']]) : '';
            $cost = isset($row[$columnMapping['harga_beli']]) ? trim($row[$columnMapping['harga_beli']]) : '';

            // If SKU is empty, check if other fields are empty. If all empty, skip.
            if (empty($sku) && empty($posisi) && empty($qty) && empty($cost)) {
                continue;
            }

            // If SKU is filled, but posisi, qty, and cost are all empty/blank, we skip this row
            if (!empty($sku) && $posisi === '' && $qty === '' && $cost === '') {
                $skippedCount++;
                continue; // skip
            }

            $totalRows++;
            $errors = [];
            $variant = null;

            // 1. Validate SKU
            if (empty($sku)) {
                $errors[] = "SKU tidak boleh kosong.";
            } else {
                $variant = ProductVariant::where('store_id', $storeId)
                    ->where('sku', $sku)
                    ->where('is_active', 'Y')
                    ->with('product')
                    ->first();
                if (!$variant) {
                    $errors[] = "SKU '{$sku}' tidak ditemukan atau tidak aktif di store ini.";
                } elseif (!$variant->track_stock) {
                    $errors[] = "Varian dengan SKU '{$sku}' diatur untuk tidak melacak stok (track_stock = false).";
                }
            }

            // 2. Validate Posisi
            if (empty($posisi)) {
                $errors[] = "Posisi tidak boleh kosong.";
            } elseif (!in_array($posisi, ['store', 'warehouse'])) {
                $errors[] = "Posisi harus diisi dengan 'store' atau 'warehouse'.";
            }

            // 3. Validate Jumlah Stok
            if ($qty === '') {
                $errors[] = "Jumlah Stok tidak boleh kosong.";
            } elseif (!is_numeric($qty)) {
                $errors[] = "Jumlah Stok harus berupa angka.";
            } elseif ($qty <= 0) {
                $errors[] = "Jumlah Stok harus lebih besar dari 0.";
            }

            // 4. Validate Harga Beli
            if ($cost === '') {
                $errors[] = "Harga Beli/Modal tidak boleh kosong.";
            } elseif (!is_numeric($cost)) {
                $errors[] = "Harga Beli/Modal harus berupa angka.";
            } elseif ($cost < 0) {
                $errors[] = "Harga Beli/Modal tidak boleh negatif.";
            }

            $status = empty($errors) ? 'valid' : 'error';
            if ($status === 'valid') {
                $validCount++;
            } else {
                $invalidCount++;
            }

            $results[] = [
                'row' => $rowNum,
                'sku' => $sku,
                'nama_produk' => $variant ? ($variant->product->nama_produk ?? '') : '',
                'nama_varian' => $variant ? ($variant->variant_name ?: ($variant->variant_label ?? '')) : '',
                'posisi' => $posisi,
                'qty' => (float)$qty,
                'cost' => (float)$cost,
                'variant_id' => $variant ? $variant->id : null,
                'status' => $status,
                'errors' => $errors,
            ];
        }

        $hasErrors = ($invalidCount > 0);
        $importedCount = 0;

        if ($totalRows > 0 && (!$hasErrors || $isDryRun)) {
            DB::beginTransaction();
            try {
                if ($transactionType === 'stock_adjustment') {
                    // Group by posisi because StockAdjustment is per posisi
                    $groupedByPosisi = [];
                    foreach ($results as $item) {
                        if ($item['status'] === 'error') continue;
                        $groupedByPosisi[$item['posisi']][] = $item;
                    }

                    foreach ($groupedByPosisi as $pos => $items) {
                        $code = $this->generateCode('SA',$storeId);
                        $adjustment = StockAdjustment::create([
                            'store_id' => $storeId,
                            'code' => $code,
                            'effective_date' => now()->toDateString(),
                            'posisi' => $pos,
                            'reason_type' => 'CORRECTION',
                            'notes' => 'Import stok awal via Excel',
                            'status' => 'DRAFT',
                            'created_by' => auth()->id(),
                        ]);

                        foreach ($items as $item) {
                            StockAdjustmentItem::create([
                                'stock_adjustment_id' => $adjustment->id,
                                'product_variant_id' => $item['variant_id'],
                                'qty' => $item['qty'],
                                'cost' => $item['cost'],
                                'total_value' => $item['qty'] * $item['cost'],
                            ]);
                            $importedCount++;
                        }

                        // Post adjustment using service
                        $postingService = new StockAdjustmentPostingService();
                        $postingService->post($adjustment);
                    }
                } else {
                    // Purchase Order + Goods Receipt
                    // Find or create default import vendor
                    $vendor = Vendor::where('store_id', $storeId)->first();
                    if (!$vendor) {
                        $vendor = Vendor::create([
                            'store_id' => $storeId,
                            'kode_vendor' => 'VND-IMP',
                            'nama_vendor' => 'Vendor Impor Stok',
                        ]);
                    }

                    $totalAmount = 0;
                    foreach ($results as $item) {
                        if ($item['status'] === 'error') continue;
                        $totalAmount += $item['qty'] * $item['cost'];
                    }

                    // Create PO
                    $po = PurchaseOrder::create([
                        'store_id' => $storeId,
                        'po_number' => 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                        'vendor_id' => $vendor->id,
                        'notes' => 'Import stok awal via PO Excel',
                        'request_date' => now(),
                        'expected_date' => now(),
                        'status' => 'RECEIVED',
                        'subtotal' => $totalAmount,
                        'tax_total' => 0,
                        'discount_total' => 0,
                        'grand_total' => $totalAmount,
                        'requested_by' => auth()->id(),
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                    // Create Goods Receipt
                    $receipt = GoodsReceipt::create([
                        'store_id' => $storeId,
                        'purchase_order_id' => $po->id,
                        'receipt_number' => 'GR-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                        'receipt_date' => now()->toDateString(),
                        'received_by' => auth()->id(),
                    ]);

                    foreach ($results as $item) {
                        if ($item['status'] === 'error') continue;

                        $poItem = PurchaseOrderItem::create([
                            'purchase_order_id' => $po->id,
                            'product_variant_id' => $item['variant_id'],
                            'qty_order' => $item['qty'],
                            'qty_received' => $item['qty'],
                            'price' => $item['cost'],
                            'subtotal' => $item['qty'] * $item['cost'],
                        ]);

                        GoodsReceiptItem::create([
                            'goods_receipt_id' => $receipt->id,
                            'purchase_order_item_id' => $poItem->id,
                            'qty_received' => $item['qty'],
                        ]);

                        // Add stock batch and movement via StockService
                        StockService::receiveFromPurchase(
                            $item['variant_id'],
                            $poItem->id,
                            $item['posisi'],
                            now()->toDateString(),
                            $item['qty'],
                            $item['cost'],
                            $receipt->id
                        );

                        $importedCount++;
                    }
                }

                if ($isDryRun || $hasErrors) {
                    DB::rollBack();
                } else {
                    DB::commit();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                throw new \Exception("Gagal melakukan impor stok ke database: " . $e->getMessage());
            }
        }

        return [
            'success' => !$hasErrors && !$isDryRun,
            'is_dry_run' => $isDryRun,
            'total_rows' => $totalRows,
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
            'imported_count' => $importedCount,
            'results' => $results,
        ];
    }

    private function generateCode($prefix, $storeId)
    {
        $datePart = date('Ymd');
        $count = StockAdjustment::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        do {
            $code = sprintf("%s-%s-%s-%04d", $prefix, $storeId, $datePart, $count);
            $exists = StockAdjustment::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->where('code', $code)
                ->exists();
            if ($exists) {
                $count++;
            }
        } while ($exists);

        return $code;
    }
}
