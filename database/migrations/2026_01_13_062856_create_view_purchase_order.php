<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW view_purchase_order AS
            SELECT 
                p.id,
                p.vendor_id,
                p.request_date AS tanggal,
                p.po_number AS nota,
                p.subtotal AS harga,
                p.discount_total AS diskon,
                p.tax_total AS pajak,
                p.grand_total AS total,
                p.status AS stts,

                (
                    SELECT COUNT(*) 
                    FROM purchase_order_items 
                    WHERE purchase_order_id = p.id
                ) AS jlhbarang,

                (
                    SELECT CONCAT(
                        '[',
                        GROUP_CONCAT(
                            JSON_OBJECT(
                                'code', c.sku,
                                'produk', (
                                    SELECT nama_produk 
                                    FROM products 
                                    WHERE id = c.product_id
                                ),
                                'qty', b.qty_order
                            )
                            SEPARATOR ','
                        ),
                        ']'
                    )
                    FROM purchase_order_items b
                    JOIN product_variants c 
                        ON b.product_variant_id = c.id
                    WHERE b.purchase_order_id = p.id
                ) AS listbarang

            FROM purchase_orders p
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_purchase_order");
    }
};
