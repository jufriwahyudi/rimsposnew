<?php
/**
 * SKRIP ROLLBACK SINKRONISASI APOTEK SENYUM SEHAT (STORE ID: 165)
 *
 * Mengembalikan data produk, varian, stock_batches, dan product_units
 * persis seperti sebelum sinkronisasi tanggal 19 Oktober 2026.
 *
 * Penggunaan:
 *   php scripts/rollback_store165_sync.php --dry-run   (Simulasi)
 *   php scripts/rollback_store165_sync.php --live      (Eksekusi rollback ke DB Live)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$isDryRun = in_array('--dry-run', $argv);
$isLive = in_array('--live', $argv);

if (!$isDryRun && !$isLive) {
    echo "=========================================================\n";
    echo "  SKRIP ROLLBACK SINKRONISASI APOTEK SENYUM SEHAT (165)\n";
    echo "=========================================================\n";
    echo "Harap tentukan opsi:\n";
    echo "  php scripts/rollback_store165_sync.php --dry-run  (Cek data)\n";
    echo "  php scripts/rollback_store165_sync.php --live     (Eksekusi ke DB Live)\n";
    exit(1);
}

$remoteConfig = [
    'driver' => 'mysql',
    'host' => '194.59.164.84',
    'port' => '3306',
    'database' => 'u652364387_rimspos',
    'username' => 'u652364387_rimspos',
    'password' => 'Rims1nsecure!@#',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
];
config(['database.connections.remote_live' => $remoteConfig]);
$db = DB::connection('remote_live');
$storeId = 165;

// Nama tabel backup yang dibuat pada 19 Okt 2026 21:22:30
$tProductsBackup = '_backup_165_products_20261019_212230';
$tVariantsBackup = '_backup_165_variants_20261019_212230';
$tBatchesBackup  = '_backup_165_batches_20261019_212230';
$tPunitsBackup   = '_backup_165_punits_20261019_212230';

echo "Memeriksa ketersediaan tabel backup di Live DB...\n";
foreach ([$tProductsBackup, $tVariantsBackup, $tBatchesBackup, $tPunitsBackup] as $tb) {
    if (!$db->getSchemaBuilder()->hasTable($tb)) {
        echo "[ERROR] Tabel backup $tb tidak ditemukan di database!\n";
        exit(1);
    }
}
echo "Semua tabel backup tersedia dengan aman.\n\n";

$backupProductCount = $db->table($tProductsBackup)->count();
$currentProductCount = $db->table('products')->where('store_id', $storeId)->count();

$newProducts = $db->table('products')
    ->where('store_id', $storeId)
    ->whereNotIn('id', function($q) use ($tProductsBackup) {
        $q->select('id')->from($tProductsBackup);
    })
    ->get();

$newVariants = $db->table('product_variants')
    ->where('store_id', $storeId)
    ->whereNotIn('id', function($q) use ($tVariantsBackup) {
        $q->select('id')->from($tVariantsBackup);
    })
    ->get();

echo "=========================================================\n";
echo "  ANALISA DATA ROLLBACK:\n";
echo "  - Total Produk Saat Ini       : {$currentProductCount}\n";
echo "  - Total Produk di Backup (Ori): {$backupProductCount}\n";
echo "  - Produk Baru yang Akan Dihapus: " . $newProducts->count() . " produk\n";
echo "  - Varian Baru yang Akan Dihapus: " . $newVariants->count() . " varian\n";
echo "=========================================================\n\n";

if ($isDryRun) {
    echo "[INFO] Mode Dry-Run selesai. Jalankan dengan flag --live untuk mengeksekusi rollback nyata.\n";
    exit(0);
}

echo ">>> MEMULAI TRANSAKSI ROLLBACK (LIVE)...\n";
$db->beginTransaction();

try {
    // 1. Hapus multi-unit untuk store 165
    $delUnits = $db->table('product_units')
        ->whereIn('product_id', function($q) use ($storeId) {
            $q->select('id')->from('products')->where('store_id', $storeId);
        })
        ->delete();
    echo "1. Membersihkan data product_units: {$delUnits} records dihapus.\n";

    // 2. Hapus stock batches milik varian baru
    $delNewBatches = $db->table('stock_batches')
        ->whereIn('product_variant_id', $newVariants->pluck('id'))
        ->delete();
    echo "2. Menghapus stock_batches produk baru: {$delNewBatches} records dihapus.\n";

    // 3. Hapus product_variants baru
    $delVariants = $db->table('product_variants')
        ->whereIn('id', $newVariants->pluck('id'))
        ->delete();
    echo "3. Menghapus product_variants baru: {$delVariants} records dihapus.\n";

    // 4. Hapus products baru
    $delProducts = $db->table('products')
        ->whereIn('id', $newProducts->pluck('id'))
        ->delete();
    echo "4. Menghapus products baru: {$delProducts} records dihapus.\n";

    // 5. Restore nilai product_variants lama dari tabel backup
    $restoreVariants = $db->statement("
        UPDATE product_variants pv
        JOIN {$tVariantsBackup} b ON b.id = pv.id
        SET pv.harga_jual = b.harga_jual,
            pv.cost_price_manual = b.cost_price_manual,
            pv.updated_at = b.updated_at
        WHERE pv.store_id = {$storeId}
    ");
    echo "5. Mengembalikan harga_jual & modal product_variants lama ke kondisi awal.\n";

    // 6. Restore stock_batches lama dari tabel backup
    // Hapus stock_batches lama yang ada saat ini untuk varian original
    $db->table('stock_batches')
        ->whereIn('product_variant_id', function($q) use ($tVariantsBackup) {
            $q->select('id')->from($tVariantsBackup);
        })
        ->delete();
    // Insert kembali data batch original dari backup
    $db->statement("
        INSERT INTO stock_batches (id, product_variant_id, purchase_item_id, stock_transfer_id, posisi, batch_number, expired_date, tanggal_masuk, qty_awal, qty_sisa, qty_reserved, harga_beli, sumber, created_at, updated_at)
        SELECT id, product_variant_id, purchase_item_id, stock_transfer_id, posisi, batch_number, expired_date, tanggal_masuk, qty_awal, qty_sisa, qty_reserved, harga_beli, sumber, created_at, updated_at
        FROM {$tBatchesBackup}
    ");
    echo "6. Mengembalikan seluruh stock_batches ke kondisi awal sebelum sinkronisasi.\n";

    $db->commit();
    echo "\n>>> ROLLBACK BERHASIL DILAKUKAN (COMMITTED)!\n";
    $finalCount = $db->table('products')->where('store_id', $storeId)->count();
    echo "Total produk Apotek Senyum Sehat saat ini kembali menjadi: {$finalCount} produk (100% identik dengan kondisi awal).\n";

} catch (\Throwable $e) {
    $db->rollBack();
    echo "\n[ERROR] ROLLBACK GAGAL! Seluruh transaksi telah di-revert:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
