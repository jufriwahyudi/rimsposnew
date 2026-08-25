<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetTransactionsAndStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset-transactions 
                            {--store= : ID, Code, atau Nama Store yang ingin direset} 
                            {--force : Jalankan tanpa konfirmasi interaktif} 
                            {--dry-run : Simulasi pembersihan data tanpa menghapus database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset HANYA data transaksi, riwayat mutasi stok, dan stok bahan baku (Master produk, resep, bahan baku, & akun TETAP AMAN).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeOption = $this->option('store');
        $store = null;

        if ($storeOption) {
            if (strtolower($storeOption) !== 'all' && $storeOption !== '0') {
                $store = DB::table('stores')
                    ->where('id', $storeOption)
                    ->orWhere('code', $storeOption)
                    ->orWhere('name', 'like', "%{$storeOption}%")
                    ->first();

                if (!$store) {
                    $this->error("Store dengan ID/Code/Nama '{$storeOption}' tidak ditemukan.");
                    return Command::FAILURE;
                }
            }
        } elseif (!$this->option('force')) {
            $stores = DB::table('stores')->select('id', 'name', 'code')->get();

            if ($stores->isEmpty()) {
                $this->error("Tidak ada store yang ditemukan di database.");
                return Command::FAILURE;
            }

            $options = [];
            $options[0] = 'SEMUA STORE (Reset Seluruh Transaksi & Stok Database)';

            foreach ($stores as $s) {
                $options[$s->id] = "[ID: {$s->id}] {$s->name} (" . ($s->code ?? '-') . ")";
            }

            $choice = $this->choice('Pilih store mana yang ingin direset transaksi & stoknya:', $options, 0);

            if ($choice !== 'SEMUA STORE (Reset Seluruh Transaksi & Stok Database)') {
                $selectedId = array_search($choice, $options);
                $store = $stores->firstWhere('id', $selectedId);
            }
        }

        $dryRun = $this->option('dry-run');

        $this->newLine();
        $this->alert('RESET TRANSAKSI & STOK');
        $this->line('Command ini HANYA akan menghapus:');
        $this->line(' - Data Penjualan (Sales, Sale Items, Kas Transaksi, Shift Kasir, Service Orders)');
        $this->line(' - Data Mutasi & Batch Stok Produk Jadi (Stock Movements, Batches, PO, Goods Receipt, Opname, Adjustment, Transfer)');
        $this->line(' - Data Mutasi & Stok Bahan Baku (Ingredient Stock Movements & Inventory Stocks FIFO)');
        $this->line(' - Riwayat Pengeluaran / Beban Kasir & Poin Member');
        $this->newLine();
        $this->info('Master Produk, Varian, Master Bahan Baku, Resep BOM, Rekening, Satuan, dan Pengguna TIDAK AKAN DIHAPUS (TETAP AMAN).');
        $this->newLine();

        if ($store) {
            $this->warn("Store Terpilih: [ID: {$store->id}] {$store->name} (" . ($store->code ?? '-') . ")");
            if ($dryRun) {
                $this->info("Menjalankan dalam mode SIMULASI (Dry Run). Tidak ada data yang akan dihapus.");
            } else {
                if (!$this->option('force')) {
                    if (!$this->confirm("Apakah Anda YAKIN ingin me-reset seluruh transaksi & stok untuk store '{$store->name}'?")) {
                        $this->info('Operasi dibatalkan.');
                        return Command::FAILURE;
                    }
                }
            }

            $this->resetStoreTransactions($store->id);
        } else {
            $this->warn("Cakupan: SELURUH STORE / DATABASE.");
            if ($dryRun) {
                $this->info("Menjalankan dalam mode SIMULASI (Dry Run). Tidak ada data yang akan dihapus.");
            } else {
                if (!$this->option('force')) {
                    if (!$this->confirm('Apakah Anda YAKIN ingin me-reset seluruh transaksi & stok di SEMUA store? (Tindakan ini tidak dapat dibatalkan)')) {
                        $this->info('Operasi dibatalkan.');
                        return Command::FAILURE;
                    }
                }
            }

            $this->resetAllTransactions();
        }

        return Command::SUCCESS;
    }

    /**
     * Reset transaction & stock data for a specific store.
     */
    protected function resetStoreTransactions($storeId)
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info("=== DRY RUN SIMULATION ===");
            $this->info("Menghitung data transaksi & stok untuk Store ID: {$storeId}...\n");
        } else {
            $this->info("Memulai reset transaksi & stok untuk Store ID: {$storeId}...");
        }

        Schema::disableForeignKeyConstraints();

        // 1. Child sales & audit tables
        $childTablesViaSale = [
            'sale_item_batches' => function () use ($storeId) {
                return DB::table('sale_item_batches')->whereIn('sale_item_id', function ($q) use ($storeId) {
                    $q->select('id')->from('sale_items')->whereIn('sale_id', function ($sq) use ($storeId) {
                        $sq->select('id')->from('sales')->where('store_id', $storeId);
                    });
                });
            },
            'sale_item_fnb_details' => function () use ($storeId) {
                return DB::table('sale_item_fnb_details')->whereIn('sale_item_id', function ($q) use ($storeId) {
                    $q->select('id')->from('sale_items')->whereIn('sale_id', function ($sq) use ($storeId) {
                        $sq->select('id')->from('sales')->where('store_id', $storeId);
                    });
                });
            },
            'sale_items' => function () use ($storeId) {
                return DB::table('sale_items')->whereIn('sale_id', function ($q) use ($storeId) {
                    $q->select('id')->from('sales')->where('store_id', $storeId);
                });
            },
            'purchase_order_items' => function () use ($storeId) {
                return DB::table('purchase_order_items')->whereIn('purchase_order_id', function ($q) use ($storeId) {
                    $q->select('id')->from('purchase_orders')->where('store_id', $storeId);
                });
            },
            'goods_receipt_items' => function () use ($storeId) {
                return DB::table('goods_receipt_items')->whereIn('goods_receipt_id', function ($q) use ($storeId) {
                    $q->select('id')->from('goods_receipts')->where('store_id', $storeId);
                });
            },
            'stock_adjustment_items' => function () use ($storeId) {
                return DB::table('stock_adjustment_items')->whereIn('stock_adjustment_id', function ($q) use ($storeId) {
                    $q->select('id')->from('stock_adjustments')->where('store_id', $storeId);
                });
            },
            'stock_opname_items' => function () use ($storeId) {
                return DB::table('stock_opname_items')->whereIn('stock_opname_id', function ($q) use ($storeId) {
                    $q->select('id')->from('stock_opnames')->where('store_id', $storeId);
                });
            },
            'daily_audit_details' => function () use ($storeId) {
                return DB::table('daily_audit_details')->whereIn('daily_audit_id', function ($q) use ($storeId) {
                    $q->select('id')->from('daily_audits')->where('store_id', $storeId);
                });
            },
            'service_order_items' => function () use ($storeId) {
                return DB::table('service_order_items')->whereIn('service_order_id', function ($q) use ($storeId) {
                    $q->select('id')->from('service_orders')->where('store_id', $storeId);
                });
            },
            'expense_payments' => function () use ($storeId) {
                return DB::table('expense_payments')->whereIn('expense_id', function ($q) use ($storeId) {
                    $q->select('id')->from('expenses')->where('store_id', $storeId);
                });
            },
            'stock_batches' => function () use ($storeId) {
                return DB::table('stock_batches')->whereIn('product_variant_id', function ($q) use ($storeId) {
                    $q->select('id')->from('product_variants')->where('store_id', $storeId);
                });
            },
            'stock_movements' => function () use ($storeId) {
                return DB::table('stock_movements')->whereIn('product_variant_id', function ($q) use ($storeId) {
                    $q->select('id')->from('product_variants')->where('store_id', $storeId);
                });
            },
            'stock_transfer_items' => function () use ($storeId) {
                return DB::table('stock_transfer_items')->whereIn('product_variant_id', function ($q) use ($storeId) {
                    $q->select('id')->from('product_variants')->where('store_id', $storeId);
                });
            },
        ];

        foreach ($childTablesViaSale as $tableName => $queryBuilder) {
            if (Schema::hasTable($tableName)) {
                $query = $queryBuilder();
                $count = $query->count();
                if ($count > 0) {
                    if ($dryRun) {
                        $this->line("Tabel '{$tableName}': {$count} baris akan dihapus.");
                    } else {
                        $this->comment("Membersihkan tabel: {$tableName} ({$count} baris)");
                        $query->delete();
                    }
                }
            }
        }

        // 2. Stock Transfers for this store
        if (Schema::hasTable('stock_transfers')) {
            $transfersQuery = DB::table('stock_transfers')
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

            $transfersCount = $transfersQuery->count();
            if ($transfersCount > 0) {
                if ($dryRun) {
                    $this->line("Tabel 'stok_transfer_jurnals': jurnal untuk {$transfersCount} transfer kosong akan dihapus.");
                    $this->line("Tabel 'stock_transfers': {$transfersCount} transfer kosong akan dihapus.");
                } else {
                    if (Schema::hasTable('stok_transfer_jurnals')) {
                        DB::table('stok_transfer_jurnals')
                            ->whereIn('stock_transfer_id', (clone $transfersQuery)->pluck('id'))
                            ->delete();
                    }
                    (clone $transfersQuery)->delete();
                }
            }
        }

        // 3. FnB Ingredient stock movements & inventory stocks
        if (Schema::hasTable('ingredient_stock_movements')) {
            $ingMovQuery = DB::table('ingredient_stock_movements')->where('location_id', $storeId);
            $count = $ingMovQuery->count();
            if ($count > 0) {
                if ($dryRun) {
                    $this->line("Tabel 'ingredient_stock_movements': {$count} baris mutasi bahan baku akan dihapus.");
                } else {
                    $this->comment("Membersihkan mutasi bahan baku: ingredient_stock_movements ({$count} baris)");
                    $ingMovQuery->delete();
                }
            }
        }

        if (Schema::hasTable('inventory_stocks')) {
            $invStockQuery = DB::table('inventory_stocks')->where('location_id', $storeId);
            $count = $invStockQuery->count();
            if ($count > 0) {
                if ($dryRun) {
                    $this->line("Tabel 'inventory_stocks': {$count} batch stok bahan baku akan dihapus (stok di-reset 0).");
                } else {
                    $this->comment("Membersihkan stok bahan baku: inventory_stocks ({$count} baris)");
                    $invStockQuery->delete();
                }
            }
        }

        // 4. Tables with direct store_id (Transactions, Cashier, Expenses, Staff Commissions)
        $directStoreTables = [
            'sales',
            'cash_transactions',
            'cash_registers',
            'expenses',
            'staff_commissions',
            'service_orders',
            'purchase_orders',
            'goods_receipts',
            'stock_opname_periods',
            'stock_opnames',
            'stock_adjustments',
            'daily_audits',
            'member_point_histories',
            'member_redemptions',
        ];

        foreach ($directStoreTables as $table) {
            if (Schema::hasTable($table)) {
                $query = DB::table($table)->where('store_id', $storeId);
                $count = $query->count();
                if ($count > 0) {
                    if ($dryRun) {
                        $this->line("Tabel '{$table}': {$count} baris akan dihapus.");
                    } else {
                        $this->comment("Mengosongkan tabel transaksi: {$table} ({$count} baris)");
                        $query->delete();
                    }
                }
            }
        }

        // 5. Reset member total_points if table exists
        if (Schema::hasTable('members')) {
            $store = DB::table('stores')->where('id', $storeId)->first();
            if ($store && !empty($store->business_id)) {
                $memberQuery = DB::table('members')->where('business_id', $store->business_id)->where('total_points', '>', 0);
                $mCount = $memberQuery->count();
                if ($mCount > 0) {
                    if ($dryRun) {
                        $this->line("Tabel 'members': {$mCount} member bisnis ini akan di-reset poinnya menjadi 0.");
                    } else {
                        $memberQuery->update(['total_points' => 0]);
                        $this->comment("Reset poin {$mCount} member menjadi 0");
                    }
                }
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        if ($dryRun) {
            $this->info("=== DRY RUN SIMULATION SELESAI ===");
            $this->info("Tidak ada data yang dihapus.");
        } else {
            $this->info("Reset transaksi & stok untuk Store ID: {$storeId} BERHASIL SELESAI!");
            $this->info("Master produk, resep, dan bahan baku tetap utuh dan siap digunakan untuk transaksi baru.");
        }
    }

    /**
     * Reset all transaction & stock data globally across all stores (Truncate).
     */
    protected function resetAllTransactions()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info("=== DRY RUN SIMULATION (SELURUH DATABASE) ===");
            $this->info("Menghitung data transaksi & stok yang akan dikosongkan...\n");
        } else {
            $this->info("Memulai reset transaksi & stok di SELURUH database...");
        }

        $tablesToTruncate = [
            // Penjualan & Kasir
            'sale_item_batches',
            'sale_item_fnb_details',
            'sale_items',
            'sales',
            'cash_transactions',
            'cash_registers',
            'service_order_items',
            'service_orders',
            'staff_commissions',

            // Beban & Biaya
            'expense_payments',
            'expenses',

            // Mutasi & Stok Bahan Baku (FnB)
            'ingredient_stock_movements',
            'inventory_stocks',

            // Mutasi & Stok Retail / PO / Opname / Transfer
            'stock_batches',
            'stock_movements',
            'stock_transfer_items',
            'stock_transfers',
            'stok_transfer_jurnals',
            'purchase_order_items',
            'purchase_orders',
            'goods_receipt_items',
            'goods_receipts',
            'stock_opname_items',
            'stock_opname_periods',
            'stock_opnames',
            'stock_adjustment_items',
            'stock_adjustments',
            'daily_audit_details',
            'daily_audits',
            'jadwal_distribusi',
            'jadwal_seragam_siswa',
            'jadwal_sesi',

            // Riwayat Loyalty Member
            'member_point_histories',
            'member_redemptions',
        ];

        Schema::disableForeignKeyConstraints();

        $hasOutput = false;
        foreach ($tablesToTruncate as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    $hasOutput = true;
                    if ($dryRun) {
                        $this->line("Tabel '{$table}': seluruh {$count} baris akan dikosongkan.");
                    } else {
                        $this->comment("Mengosongkan tabel: {$table} (Menghapus {$count} baris)");
                        DB::table($table)->truncate();
                    }
                }
            }
        }

        // Reset points on members table to 0
        if (Schema::hasTable('members')) {
            $memberCount = DB::table('members')->where('total_points', '>', 0)->count();
            if ($memberCount > 0) {
                if ($dryRun) {
                    $this->line("Tabel 'members': {$memberCount} member akan di-reset total_points menjadi 0.");
                } else {
                    DB::table('members')->update(['total_points' => 0]);
                    $this->comment("Reset poin {$memberCount} member menjadi 0");
                }
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        if ($dryRun) {
            if (!$hasOutput) {
                $this->line("Seluruh tabel transaksi & mutasi stok sudah kosong.");
            }
            $this->info("=== DRY RUN SIMULATION SELESAI ===");
            $this->info("Tidak ada data yang dihapus di database.");
        } else {
            $this->info("Pembersihan seluruh data transaksi & mutasi stok BERHASIL SELESAI!");
            $this->info("Master produk, resep BOM, dan bahan baku tetap tersimpan aman.");
        }
    }
}
