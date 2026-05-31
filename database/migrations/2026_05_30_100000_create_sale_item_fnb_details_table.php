<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create sale_item_fnb_details table
        Schema::create('sale_item_fnb_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')
                ->constrained('sale_items')
                ->cascadeOnDelete();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->string('commission_type')->nullable();
            $table->decimal('commission_rate', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->integer('kitchen_printed_qty')->default(0);
            $table->enum('kds_status', ['pending', 'cooking', 'ready', 'served'])->default('pending');
            $table->timestamps();
        });

        // 2. Migrate existing data if columns exist on sale_items
        if (Schema::hasColumn('sale_items', 'kds_status')) {
            $items = DB::table('sale_items')->get();
            foreach ($items as $item) {
                // Find store of this sale
                $sale = DB::table('sales')->where('id', $item->sale_id)->first();
                $store = $sale ? DB::table('stores')->where('id', $sale->store_id)->first() : null;

                if ($store && $store->business_type === 'fnb') {
                    // Fetch cost_price_manual from variant
                    $costPrice = 0;
                    if ($item->product_variant_id) {
                        $variant = DB::table('product_variants')->where('id', $item->product_variant_id)->first();
                        $costPrice = $variant->cost_price_manual ?? 0;
                    }

                    DB::table('sale_item_fnb_details')->insert([
                        'sale_item_id' => $item->id,
                        'cost_price' => $costPrice,
                        'commission_type' => $item->commission_type ?? null,
                        'commission_rate' => $item->commission_rate ?? 0,
                        'commission_amount' => $item->commission_amount ?? 0,
                        'kitchen_printed_qty' => $item->kitchen_printed_qty ?? 0,
                        'kds_status' => $item->kds_status ?? 'pending',
                        'created_at' => $item->created_at ?? now(),
                        'updated_at' => $item->updated_at ?? now(),
                    ]);
                }
            }

            // 3. Drop columns from sale_items
            Schema::table('sale_items', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('sale_items', 'commission_type')) {
                    $columnsToDrop[] = 'commission_type';
                }
                if (Schema::hasColumn('sale_items', 'commission_rate')) {
                    $columnsToDrop[] = 'commission_rate';
                }
                if (Schema::hasColumn('sale_items', 'commission_amount')) {
                    $columnsToDrop[] = 'commission_amount';
                }
                if (Schema::hasColumn('sale_items', 'kitchen_printed_qty')) {
                    $columnsToDrop[] = 'kitchen_printed_qty';
                }
                if (Schema::hasColumn('sale_items', 'kds_status')) {
                    $columnsToDrop[] = 'kds_status';
                }
                
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add columns to sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('commission_type')->nullable()->after('subtotal');
            $table->decimal('commission_rate', 15, 2)->default(0)->after('commission_type');
            $table->decimal('commission_amount', 15, 2)->default(0)->after('commission_rate');
            $table->integer('kitchen_printed_qty')->default(0)->after('qty');
            $table->enum('kds_status', ['pending', 'cooking', 'ready', 'served'])->default('pending')->after('status');
        });

        // 2. Restore data from sale_item_fnb_details
        $details = DB::table('sale_item_fnb_details')->get();
        foreach ($details as $detail) {
            DB::table('sale_items')
                ->where('id', $detail->sale_item_id)
                ->update([
                    'commission_type' => $detail->commission_type,
                    'commission_rate' => $detail->commission_rate,
                    'commission_amount' => $detail->commission_amount,
                    'kitchen_printed_qty' => $detail->kitchen_printed_qty,
                    'kds_status' => $detail->kds_status,
                ]);
        }

        // 3. Drop sale_item_fnb_details table
        Schema::dropIfExists('sale_item_fnb_details');
    }
};
