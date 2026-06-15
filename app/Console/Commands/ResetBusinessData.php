<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetBusinessData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset-business-data 
                            {--store= : ID, Code, atau Nama Store yang ingin direset} 
                            {--force : Jalankan tanpa konfirmasi interaktif} 
                            {--dry-run : Simulasi pembersihan data tanpa menghapus database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset data transaksi, produk, biaya, member, dll. secara global atau untuk store tertentu.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeOption = $this->option('store');
        $store = null;

        if ($storeOption) {
            // Find store by ID, code, or name
            $store = DB::table('stores')
                ->where('id', $storeOption)
                ->orWhere('code', $storeOption)
                ->orWhere('name', 'like', "%{$storeOption}%")
                ->first();

            if (!$store) {
                $this->error("Store dengan ID/Code/Nama '{$storeOption}' tidak ditemukan.");
                return Command::FAILURE;
            }
        } else {
            // Interactive choice
            $stores = DB::table('stores')->select('id', 'name', 'code')->get();

            if ($stores->isEmpty()) {
                $this->error("Tidak ada store yang ditemukan di database.");
                return Command::FAILURE;
            }

            $options = [];
            $options[0] = 'SEMUA STORE (Reset Seluruh Database)';

            foreach ($stores as $s) {
                $options[$s->id] = "[ID: {$s->id}] {$s->name} (" . ($s->code ?? '-') . ")";
            }

            $choice = $this->choice('Pilih store mana yang mau direset datanya:', $options, 0);

            if ($choice !== 'SEMUA STORE (Reset Seluruh Database)') {
                // Find the key of the selected choice
                $selectedId = array_search($choice, $options);
                $store = $stores->firstWhere('id', $selectedId);
            }
        }

        $dryRun = $this->option('dry-run');

        if ($store) {
            $this->warn("Anda memilih untuk me-reset data pada store: [ID: {$store->id}] {$store->name} (" . ($store->code ?? '-') . ")");
            if ($dryRun) {
                $this->info("Menjalankan dalam mode SIMULASI (Dry Run). Tidak ada data yang akan dihapus.");
            } else {
                $this->warn("Tindakan ini hanya akan menghapus data transaksi, produk, stok, dan biaya yang terkait dengan store ini.");
                if (!$this->option('force')) {
                    if (!$this->confirm('Apakah Anda yakin ingin melanjutkan reset data untuk store ini? (Tindakan ini tidak dapat dibatalkan)')) {
                        $this->info('Operasi dibatalkan.');
                        return Command::FAILURE;
                    }
                }
            }

            $this->resetStoreData($store->id);
        } else {
            $this->warn("Anda memilih untuk me-reset data pada SELURUH store (Reset Seluruh Database).");
            if ($dryRun) {
                $this->info("Menjalankan dalam mode SIMULASI (Dry Run). Tidak ada data yang akan dihapus.");
            } else {
                $this->warn("Tindakan ini akan mengosongkan (truncate) semua data transaksi, produk, stok, dan biaya di database.");
                if (!$this->option('force')) {
                    if (!$this->confirm('Apakah Anda yakin ingin melanjutkan reset seluruh database? (Tindakan ini tidak dapat dibatalkan)')) {
                        $this->info('Operasi dibatalkan.');
                        return Command::FAILURE;
                    }
                }
            }

            $this->resetAllData();
        }

        return Command::SUCCESS;
    }

    /**
     * Get base query for a table filtered by store.
     */
    protected function getStoreTableQuery($table, $storeId)
    {
        switch ($table) {
            case 'sale_item_batches':
                return DB::table('sale_item_batches')
                    ->whereIn('sale_item_id', function ($query) use ($storeId) {
                        $query->select('id')->from('sale_items')
                              ->whereIn('sale_id', function ($q) use ($storeId) {
                                  $q->select('id')->from('sales')->where('store_id', $storeId);
                              });
                    });
            case 'sale_item_fnb_details':
                return DB::table('sale_item_fnb_details')
                    ->whereIn('sale_item_id', function ($query) use ($storeId) {
                        $query->select('id')->from('sale_items')
                              ->whereIn('sale_id', function ($q) use ($storeId) {
                                  $q->select('id')->from('sales')->where('store_id', $storeId);
                              });
                    });
            case 'sale_items':
                return DB::table('sale_items')
                    ->whereIn('sale_id', function ($query) use ($storeId) {
                        $query->select('id')->from('sales')->where('store_id', $storeId);
                    });
            case 'purchase_order_items':
                return DB::table('purchase_order_items')
                    ->whereIn('purchase_order_id', function ($query) use ($storeId) {
                        $query->select('id')->from('purchase_orders')->where('store_id', $storeId);
                    });
            case 'goods_receipt_items':
                return DB::table('goods_receipt_items')
                    ->whereIn('goods_receipt_id', function ($query) use ($storeId) {
                        $query->select('id')->from('goods_receipts')->where('store_id', $storeId);
                    });
            case 'stock_adjustment_items':
                return DB::table('stock_adjustment_items')
                    ->whereIn('stock_adjustment_id', function ($query) use ($storeId) {
                        $query->select('id')->from('stock_adjustments')->where('store_id', $storeId);
                    });
            case 'stock_opname_items':
                return DB::table('stock_opname_items')
                    ->whereIn('stock_opname_id', function ($query) use ($storeId) {
                        $query->select('id')->from('stock_opnames')->where('store_id', $storeId);
                    });
            case 'daily_audit_details':
                return DB::table('daily_audit_details')
                    ->whereIn('daily_audit_id', function ($query) use ($storeId) {
                        $query->select('id')->from('daily_audits')->where('store_id', $storeId);
                    });
            case 'subscribed_payments':
                return DB::table('subscribed_payments')
                    ->whereIn('subscribed_invoice_id', function ($query) use ($storeId) {
                        $query->select('id')->from('subscribed_invoices')->where('store_id', $storeId);
                    });
            case 'product_variant_barcodes':
                return DB::table('product_variant_barcodes')
                    ->whereIn('product_variant_id', function ($query) use ($storeId) {
                        $query->select('id')->from('product_variants')->where('store_id', $storeId);
                    });
            case 'variant_attributes':
                return DB::table('variant_attributes')
                    ->whereIn('product_variant_id', function ($query) use ($storeId) {
                        $query->select('id')->from('product_variants')->where('store_id', $storeId);
                    });
            case 'stock_batches':
                return DB::table('stock_batches')
                    ->whereIn('product_variant_id', function ($query) use ($storeId) {
                        $query->select('id')->from('product_variants')->where('store_id', $storeId);
                    });
            case 'stock_movements':
                return DB::table('stock_movements')
                    ->whereIn('product_variant_id', function ($query) use ($storeId) {
                        $query->select('id')->from('product_variants')->where('store_id', $storeId);
                    });
            case 'stock_transfer_items':
                return DB::table('stock_transfer_items')
                    ->whereIn('product_variant_id', function ($query) use ($storeId) {
                        $query->select('id')->from('product_variants')->where('store_id', $storeId);
                    });
            default:
                // For tables that have store_id directly
                return DB::table($table)->where('store_id', $storeId);
        }
    }

    /**
     * Reset data for a specific store.
     */
    protected function resetStoreData($storeId)
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info("=== DRY RUN SIMULATION ===");
            $this->info("Menghitung data yang akan dihapus untuk store ID: {$storeId}...\n");
        } else {
            $this->info("Memulai reset data untuk store ID: {$storeId}...");
        }

        Schema::disableForeignKeyConstraints();

        // 1. Hapus tabel anak yang tidak punya store_id melalui relasi parent yang punya store_id
        
        $childTablesViaParent = [
            'sale_item_batches' => 'sale_item_batches',
            'sale_item_fnb_details' => 'sale_item_fnb_details',
            'sale_items' => 'sale_items',
            'purchase_order_items' => 'purchase_order_items',
            'goods_receipt_items' => 'goods_receipt_items',
            'stock_adjustment_items' => 'stock_adjustment_items',
            'stock_opname_items' => 'stock_opname_items',
            'daily_audit_details' => 'daily_audit_details',
            'subscribed_payments' => 'subscribed_payments',
        ];

        foreach ($childTablesViaParent as $table) {
            if (Schema::hasTable($table)) {
                $query = $this->getStoreTableQuery($table, $storeId);
                $count = $query->count();
                if ($count > 0) {
                    if ($dryRun) {
                        $this->line("Tabel '{$table}': {$count} baris akan dihapus.");
                    } else {
                        $this->comment("Mengosongkan tabel: {$table} (Menghapus {$count} baris)");
                        $query->delete();
                    }
                }
            }
        }

        // 2. Hapus tabel anak yang tidak punya store_id melalui relasi ke product_variants (yang punya store_id)
        
        $childTablesViaVariants = [
            'product_variant_barcodes',
            'variant_attributes',
            'stock_batches',
            'stock_movements',
            'stock_transfer_items',
        ];

        foreach ($childTablesViaVariants as $table) {
            if (Schema::hasTable($table)) {
                $query = $this->getStoreTableQuery($table, $storeId);
                $count = $query->count();
                if ($count > 0) {
                    if ($dryRun) {
                        $this->line("Tabel '{$table}': {$count} baris akan dihapus.");
                    } else {
                        $this->comment("Mengosongkan tabel: {$table} (Menghapus {$count} baris)");
                        $query->delete();
                    }
                }
            }
        }

        // 3. Bersihkan stock_transfers & jurnal transfer yang kosong (tidak punya transfer items lagi)
        if (Schema::hasTable('stock_transfers')) {
            $transfersCount = DB::table('stock_transfers')
                ->whereNotExists(function ($query) use ($storeId) {
                    $query->select(DB::raw(1))->from('stock_transfer_items')
                          ->whereColumn('stock_transfer_items.stock_transfer_id', 'stock_transfers.id')
                          ->whereNotIn('product_variant_id', function ($sub) use ($storeId) {
                              $sub->select('id')->from('product_variants')->where('store_id', $storeId);
                          });
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))->from('stock_transfer_items')
                          ->whereColumn('stock_transfer_items.stock_transfer_id', 'stock_transfers.id');
                })->count();

            if ($transfersCount > 0) {
                if ($dryRun) {
                    $this->line("Tabel 'stok_transfer_jurnals': jurnal untuk {$transfersCount} transfer yang kosong akan dihapus.");
                    $this->line("Tabel 'stock_transfers': {$transfersCount} transfer yang kosong akan dihapus.");
                } else {
                    $this->comment("Mengosongkan tabel: stok_transfer_jurnals (Menghapus jurnal untuk {$transfersCount} transfer)");
                    DB::table('stok_transfer_jurnals')
                        ->whereIn('stock_transfer_id', function ($q) use ($storeId) {
                            $q->select('id')->from('stock_transfers')
                                ->whereNotExists(function ($query) use ($storeId) {
                                    $query->select(DB::raw(1))->from('stock_transfer_items')
                                          ->whereColumn('stock_transfer_items.stock_transfer_id', 'stock_transfers.id')
                                          ->whereNotIn('product_variant_id', function ($sub) use ($storeId) {
                                              $sub->select('id')->from('product_variants')->where('store_id', $storeId);
                                          });
                                })
                                ->whereExists(function ($query) {
                                    $query->select(DB::raw(1))->from('stock_transfer_items')
                                          ->whereColumn('stock_transfer_items.stock_transfer_id', 'stock_transfers.id');
                                });
                        })->delete();

                    $this->comment("Mengosongkan tabel: stock_transfers (Menghapus {$transfersCount} transfer kosong)");
                    DB::table('stock_transfers')
                        ->whereNotExists(function ($query) use ($storeId) {
                            $query->select(DB::raw(1))->from('stock_transfer_items')
                                  ->whereColumn('stock_transfer_items.stock_transfer_id', 'stock_transfers.id')
                                  ->whereNotIn('product_variant_id', function ($sub) use ($storeId) {
                                      $sub->select('id')->from('product_variants')->where('store_id', $storeId);
                                  });
                        })
                        ->whereExists(function ($query) {
                            $query->select(DB::raw(1))->from('stock_transfer_items')
                                  ->whereColumn('stock_transfer_items.stock_transfer_id', 'stock_transfers.id');
                        })->delete();
                }
            }
        }

        // 4. Hapus data dari tabel yang memiliki store_id secara langsung
        
        $tablesWithStoreId = [
            'sales',
            'cash_transactions',
            'purchase_orders',
            'goods_receipts',
            'stock_opname_periods',
            'stock_opnames',
            'stock_adjustments',
            'daily_audits',
            'expenses',
            'expense_categories',
            'member_point_histories',
            'member_redemptions',
            'point_settings',
            'product_variants',
            'products',
            'attribute_values',
            'attributes',
            'rekenings',
            'digital_newspapers',
            'store_qr_codes',
            'store_subscriptions',
            'subscribed_invoices',
        ];

        $hasOutput = false;
        foreach ($tablesWithStoreId as $table) {
            if (Schema::hasTable($table)) {
                $query = DB::table($table)->where('store_id', $storeId);
                $count = $query->count();
                if ($count > 0) {
                    $hasOutput = true;
                    if ($dryRun) {
                        $this->line("Tabel '{$table}': {$count} baris akan dihapus.");
                    } else {
                        $this->comment("Mengosongkan baris store di tabel: {$table} (Menghapus {$count} baris)");
                        $query->delete();
                    }
                }
            }
        }

        Schema::enableForeignKeyConstraints();

        if ($dryRun) {
            if (!$hasOutput) {
                $this->line("Tidak ada data transaksi atau produk yang ditemukan untuk store ini.");
            }
            $this->info("\n=== DRY RUN SIMULATION SELESAI ===");
            $this->info("Tidak ada data yang diubah di database.");
        } else {
            $this->info("Reset data untuk store ID: {$storeId} selesai successfully!");
        }
    }

    /**
     * Reset all business data in the database (truncate tables).
     */
    protected function resetAllData()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info("=== DRY RUN SIMULATION ===");
            $this->info("Menghitung data yang akan dikosongkan untuk seluruh database...\n");
        } else {
            $this->info('Memulai pembersihan seluruh database...');
        }
        
        $tablesToTruncate = [
            'sale_item_batches',
            'sale_item_fnb_details',
            'sale_items',
            'sales',
            'cash_transactions',
            'purchase_order_items',
            'purchase_orders',
            'goods_receipt_items',
            'goods_receipts',
            'stock_transfer_items',
            'stock_transfers',
            'stok_transfer_jurnals',
            'stock_batches',
            'stock_movements',
            'stock_opname_items',
            'stock_opname_periods',
            'stock_opnames',
            'stock_adjustments',
            'stock_adjustment_items',
            'daily_audit_details',
            'daily_audits',
            'jadwal_distribusi',
            'jadwal_seragam_siswa',
            'jadwal_sesi',
            'expenses',
            'expense_categories',
            'member_point_histories',
            'member_redemptions',
            'members',
            'point_settings',
            'reward_items',
            'product_variant_barcodes',
            'product_variants',
            'products',
            'variant_attributes',
            'attribute_values',
            'attributes',
            'rekenings',
            'digital_newspapers',
            'store_qr_codes',
            'store_subscriptions',
            'subscribed_invoices',
            'subscribed_payments',
        ];

        Schema::disableForeignKeyConstraints();

        $hasOutput = false;
        foreach ($tablesToTruncate as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    $hasOutput = true;
                    if ($dryRun) {
                        $this->line("Tabel '{$table}': seluruh data ({$count} baris) akan dikosongkan.");
                    } else {
                        $this->comment("Mengosongkan tabel: {$table} (Menghapus {$count} baris)");
                        DB::table($table)->truncate();
                    }
                }
            }
        }

        Schema::enableForeignKeyConstraints();

        if ($dryRun) {
            if (!$hasOutput) {
                $this->line("Seluruh database sudah kosong.");
            }
            $this->info("\n=== DRY RUN SIMULATION SELESAI ===");
            $this->info("Tidak ada data yang diubah di database.");
        } else {
            $this->info('Pembersihan seluruh database selesai successfully!');
        }
    }
}
