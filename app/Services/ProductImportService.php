<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantBarcode;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportService
{
    public function import(array $params)
    {
        $file = $params['file'];
        $storeId = $params['store_id'];
        $isFnB = $params['is_fnb'];
        $showRewardPoints = $params['show_reward_points'];
        $isDryRun = $params['dry_run'] ?? false;

        $array = Excel::toArray([], $file);
        $sheet = $array[0] ?? [];

        if (empty($sheet)) {
            throw new \Exception("File Excel kosong atau tidak valid.");
        }

        $headers = array_map(function ($header) {
            return strtolower(trim($header));
        }, $sheet[0]);

        // Map header names to column index
        $columnMapping = [
            'kode_produk' => array_search('kode produk', $headers),
            'nama_produk' => array_search('nama produk', $headers),
            'deskripsi' => array_search('deskripsi', $headers),
            'variant_name' => array_search('nama varian', $headers),
            'barcode' => array_search('barcode', $headers),
            'harga_jual' => array_search('harga jual', $headers),
        ];

        if ($showRewardPoints) {
            $columnMapping['reward_points'] = array_search('poin reward', $headers);
        }

        if ($isFnB) {
            $columnMapping['tenant'] = array_search('nama/kode tenant', $headers);
            $columnMapping['track_stock'] = array_search('lacak stok (ya/tidak)', $headers);
            $columnMapping['cost_price_manual'] = array_search('harga beli manual', $headers);
            $columnMapping['commission_type'] = array_search('tipe komisi (global/percentage/nominal)', $headers);
            $columnMapping['commission_rate'] = array_search('rate komisi', $headers);
        }

        // Validate headers
        if ($columnMapping['kode_produk'] === false || $columnMapping['nama_produk'] === false || $columnMapping['harga_jual'] === false) {
            throw new \Exception("Format file tidak sesuai template. Pastikan header kolom 'Kode Produk', 'Nama Produk', dan 'Harga Jual' tersedia.");
        }

        $rows = array_slice($sheet, 1);
        $results = [];
        $totalRows = 0;
        $validCount = 0;
        $invalidCount = 0;

        // Tracks barcodes duplicated within the same sheet
        $seenBarcodes = [];

        // Tracks product code details within the same sheet (to verify consistency)
        $seenProducts = [];

        // Get active tenants if F&B
        $tenants = collect();
        if ($isFnB) {
            $tenants = Tenant::where('store_id', $storeId)->where('stts', 'Y')->get();
        }

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // Row number in Excel starts from 1, and header is row 1

            // Check if row is completely empty
            $nonEmptyCells = array_filter($row, function ($val) {
                return !is_null($val) && trim($val) !== '';
            });
            if (empty($nonEmptyCells)) {
                continue; // skip empty rows
            }

            $totalRows++;

            // Extract row values based on column mapping
            $kodeProduk = isset($row[$columnMapping['kode_produk']]) ? strtoupper(trim($row[$columnMapping['kode_produk']])) : '';
            $namaProduk = isset($row[$columnMapping['nama_produk']]) ? trim($row[$columnMapping['nama_produk']]) : '';
            $deskripsi = isset($row[$columnMapping['deskripsi']]) ? trim($row[$columnMapping['deskripsi']]) : '';
            $variantName = isset($row[$columnMapping['variant_name']]) ? trim($row[$columnMapping['variant_name']]) : '';
            $barcode = isset($row[$columnMapping['barcode']]) ? strtoupper(trim($row[$columnMapping['barcode']])) : '';
            $hargaJual = isset($row[$columnMapping['harga_jual']]) ? trim($row[$columnMapping['harga_jual']]) : 0;
            
            $rewardPoints = 0;
            if ($showRewardPoints && isset($columnMapping['reward_points']) && $columnMapping['reward_points'] !== false) {
                $rewardPoints = isset($row[$columnMapping['reward_points']]) ? trim($row[$columnMapping['reward_points']]) : 0;
            }

            $errors = [];

            // 1. Validation: Kode Produk
            if (empty($kodeProduk)) {
                $errors[] = "Kode Produk tidak boleh kosong.";
            } elseif (strlen($kodeProduk) > 50) {
                $errors[] = "Kode Produk maksimal 50 karakter.";
            }

            // 2. Validation: Nama Produk
            if (empty($namaProduk)) {
                $errors[] = "Nama Produk tidak boleh kosong.";
            } elseif (strlen($namaProduk) > 150) {
                $errors[] = "Nama Produk maksimal 150 karakter.";
            }

            // 3. Validation: Harga Jual
            if (trim($hargaJual) === '') {
                $errors[] = "Harga Jual tidak boleh kosong.";
            } elseif (!is_numeric($hargaJual)) {
                $errors[] = "Harga Jual harus berupa angka.";
            } elseif ($hargaJual < 0) {
                $errors[] = "Harga Jual tidak boleh negatif.";
            }

            // 4. Validation: Poin Reward
            if ($showRewardPoints && $rewardPoints !== '') {
                if (!is_numeric($rewardPoints)) {
                    $errors[] = "Poin Reward harus berupa angka.";
                } elseif ($rewardPoints < 0) {
                    $errors[] = "Poin Reward tidak boleh negatif.";
                }
            }

            // 5. Validation: Barcode
            if (!empty($barcode)) {
                if (strlen($barcode) > 100) {
                    $errors[] = "Barcode maksimal 100 karakter.";
                }

                // Check duplication within this import file
                if (in_array($barcode, $seenBarcodes)) {
                    $errors[] = "Barcode '{$barcode}' duplikat dalam file impor.";
                } else {
                    $seenBarcodes[] = $barcode;
                    // Check duplication in DB
                    $barcodeExists = ProductVariantBarcode::where('barcode', $barcode)->exists();
                    if ($barcodeExists) {
                        $errors[] = "Barcode '{$barcode}' sudah digunakan di database.";
                    }
                }
            }

            // 6. Inconsistency check for the same Kode Produk in the file
            if (!empty($kodeProduk)) {
                if (isset($seenProducts[$kodeProduk])) {
                    // Check if Nama Produk matches
                    if ($seenProducts[$kodeProduk]['nama'] !== $namaProduk) {
                        $errors[] = "Konsistensi data salah: Kode Produk '{$kodeProduk}' memiliki Nama Produk berbeda di baris sebelumnya.";
                    }
                } else {
                    $seenProducts[$kodeProduk] = [
                        'nama' => $namaProduk,
                        'deskripsi' => $deskripsi,
                        'variants_count' => 0,
                    ];

                    // Check if the product code already exists in DB for a different product name
                    $existingProduct = Product::where('store_id', $storeId)
                        ->where('kode_produk', $kodeProduk)
                        ->first();
                    if ($existingProduct && $existingProduct->nama_produk !== $namaProduk) {
                        $errors[] = "Kode Produk '{$kodeProduk}' sudah terdaftar di database dengan nama berbeda ('{$existingProduct->nama_produk}').";
                    }
                }
                $seenProducts[$kodeProduk]['variants_count']++;
            }

            // 7. F&B Specific Validations
            $tenantId = null;
            $trackStock = true;
            $costPriceManual = 0;
            $commissionType = 'global';
            $commissionRate = 0;

            if ($isFnB) {
                $tenantVal = isset($columnMapping['tenant']) && $columnMapping['tenant'] !== false && isset($row[$columnMapping['tenant']]) ? trim($row[$columnMapping['tenant']]) : '';
                $trackStockVal = isset($columnMapping['track_stock']) && $columnMapping['track_stock'] !== false && isset($row[$columnMapping['track_stock']]) ? strtolower(trim($row[$columnMapping['track_stock']])) : 'ya';
                $costPriceManualVal = isset($columnMapping['cost_price_manual']) && $columnMapping['cost_price_manual'] !== false && isset($row[$columnMapping['cost_price_manual']]) ? trim($row[$columnMapping['cost_price_manual']]) : 0;
                $commissionTypeVal = isset($columnMapping['commission_type']) && $columnMapping['commission_type'] !== false && isset($row[$columnMapping['commission_type']]) ? strtolower(trim($row[$columnMapping['commission_type']])) : 'global';
                $commissionRateVal = isset($columnMapping['commission_rate']) && $columnMapping['commission_rate'] !== false && isset($row[$columnMapping['commission_rate']]) ? trim($row[$columnMapping['commission_rate']]) : 0;

                // Tenant lookup
                if (!empty($tenantVal)) {
                    $matchedTenant = $tenants->first(function ($t) use ($tenantVal) {
                        return strtolower($t->nama_tenant) === strtolower($tenantVal) || strtolower($t->kode_tenant) === strtolower($tenantVal);
                    });
                    if ($matchedTenant) {
                        $tenantId = $matchedTenant->id;
                        // Save tenant details for consistency check of same product
                        if (!empty($kodeProduk) && isset($seenProducts[$kodeProduk])) {
                            if (isset($seenProducts[$kodeProduk]['tenant_id']) && $seenProducts[$kodeProduk]['tenant_id'] !== $tenantId) {
                                $errors[] = "Konsistensi data salah: Kode Produk '{$kodeProduk}' diatur untuk Tenant berbeda.";
                            }
                            $seenProducts[$kodeProduk]['tenant_id'] = $tenantId;
                        }
                    } else {
                        $errors[] = "Tenant '{$tenantVal}' tidak ditemukan atau tidak aktif.";
                    }
                }

                // Track stock mapping
                if ($trackStockVal === 'ya' || $trackStockVal === 'yes' || $trackStockVal === '1' || $trackStockVal === 'true') {
                    $trackStock = true;
                } elseif ($trackStockVal === 'tidak' || $trackStockVal === 'no' || $trackStockVal === '0' || $trackStockVal === 'false') {
                    $trackStock = false;
                } else {
                    $errors[] = "Lacak Stok harus berisi 'Ya' atau 'Tidak'.";
                }

                // Cost price manual validation
                if ($costPriceManualVal !== '' && !is_numeric($costPriceManualVal)) {
                    $errors[] = "Harga Beli Manual harus berupa angka.";
                } elseif ($costPriceManualVal < 0) {
                    $errors[] = "Harga Beli Manual tidak boleh negatif.";
                } else {
                    $costPriceManual = (float) $costPriceManualVal;
                }

                // Commission type validation
                if (!in_array($commissionTypeVal, ['global', 'percentage', 'nominal'])) {
                    $errors[] = "Tipe Komisi harus bernilai 'global', 'percentage', atau 'nominal'.";
                } else {
                    $commissionType = $commissionTypeVal;
                }

                // Commission rate validation
                if ($commissionRateVal !== '' && !is_numeric($commissionRateVal)) {
                    $errors[] = "Rate Komisi harus berupa angka.";
                } elseif ($commissionRateVal < 0) {
                    $errors[] = "Rate Komisi tidak boleh negatif.";
                } else {
                    $commissionRate = (float) $commissionRateVal;
                }
            }

            $status = empty($errors) ? 'valid' : 'error';
            if ($status === 'valid') {
                $validCount++;
            } else {
                $invalidCount++;
            }

            $results[] = [
                'row' => $rowNum,
                'kode_produk' => $kodeProduk,
                'nama_produk' => $namaProduk,
                'deskripsi' => $deskripsi,
                'variant_name' => $variantName,
                'barcode' => $barcode,
                'harga_jual' => (float)$hargaJual,
                'reward_points' => (int)$rewardPoints,
                'tenant_id' => $tenantId,
                'track_stock' => $trackStock,
                'cost_price_manual' => $costPriceManual,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'status' => $status,
                'errors' => $errors,
            ];
        }

        // Process Database insertions inside a transaction
        $hasErrors = ($invalidCount > 0);
        $importedCount = 0;

        if ($totalRows > 0) {
            DB::beginTransaction();
            try {
                // We only do database insertions if there are no validation errors, or if we want to run dry-run validation.
                // In dry run, we insert to test DB constraints, but we ALWAYS rollback.
                // In actual import, if there are any errors, we roll back anyway and don't commit.
                if (!$hasErrors || $isDryRun) {
                    // Cache of newly created products in this transaction to avoid redundant queries/inserts
                    $createdProducts = [];

                    foreach ($results as $item) {
                        if ($item['status'] === 'error') {
                            continue; // Skip invalid rows if they exist (though if there are errors, we will roll back anyway)
                        }

                        $kode = $item['kode_produk'];
                        
                        if (isset($createdProducts[$kode])) {
                            $product = $createdProducts[$kode];
                        } else {
                            $product = Product::where('store_id', $storeId)
                                ->where('kode_produk', $kode)
                                ->first();

                            if (!$product) {
                                $product = Product::create([
                                    'store_id' => $storeId,
                                    'tenant_id' => $item['tenant_id'],
                                    'kode_produk' => $kode,
                                    'nama_produk' => $item['nama_produk'],
                                    'deskripsi' => $item['deskripsi'],
                                ]);
                            }
                            $createdProducts[$kode] = $product;
                        }

                        // Generate barcode if empty
                        $barcode = $item['barcode'];
                        if (empty($barcode)) {
                            do {
                                $barcode = strtoupper(Str::random(8));
                            } while (ProductVariant::where('barcode', $barcode)->exists() || ProductVariantBarcode::where('barcode', $barcode)->exists());
                        }

                        // Generate SKU
                        $existingCount = $product->variants()->count();
                        $sku = $product->kode_produk . '-' . str_pad($existingCount + 1, 3, '0', STR_PAD_LEFT);

                        $variantData = [
                            'store_id' => $storeId,
                            'product_id' => $product->id,
                            'variant_name' => $item['variant_name'],
                            'sku' => $sku,
                            'barcode' => $barcode,
                            'harga_jual' => $item['harga_jual'],
                            'reward_points' => $item['reward_points'],
                        ];

                        if ($isFnB) {
                            $variantData['track_stock'] = $item['track_stock'];
                            $variantData['cost_price_manual'] = $item['cost_price_manual'];
                            $variantData['commission_type'] = $item['commission_type'];
                            $variantData['commission_rate'] = $item['commission_rate'];
                        }

                        $pv = ProductVariant::create($variantData);

                        // Create active barcode record
                        $pv->barcodes()->create([
                            'barcode' => $barcode,
                            'is_active' => 'Y',
                        ]);

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
                throw new \Exception("Gagal melakukan impor ke database: " . $e->getMessage());
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
}
