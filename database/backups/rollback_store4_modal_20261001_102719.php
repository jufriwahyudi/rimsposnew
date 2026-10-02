<?php
// Rollback script otomatis untuk Store 4 Modal
$jsonFile = __DIR__ . '/backup_store4_modal_20261001_102719.json';
if (!file_exists($jsonFile)) { die('Backup JSON not found.'); }
$data = json_decode(file_get_contents($jsonFile), true);
$pdo = new PDO('mysql:host=194.59.164.84;port=3306;dbname=u652364387_rimspos;charset=utf8mb4', 'u652364387_rimspos', 'Rims1nsecure!@#', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->beginTransaction();
try {
    foreach ($data['sale_item_batches'] as $r) { $pdo->exec("UPDATE sale_item_batches SET cost_price = {$r['cost_price']} WHERE id = {$r['id']}"); }
    foreach ($data['stock_batches'] as $r) { $pdo->exec("UPDATE stock_batches SET harga_beli = {$r['harga_beli']} WHERE id = {$r['id']}"); }
    foreach ($data['stock_adjustment_items'] as $r) { $pdo->exec("UPDATE stock_adjustment_items SET cost = {$r['cost']}, total_value = {$r['total_value']} WHERE id = {$r['id']}"); }
    foreach ($data['purchase_order_items'] as $r) { $pdo->exec("UPDATE purchase_order_items SET price = {$r['price']}, subtotal = {$r['subtotal']} WHERE id = {$r['id']}"); }
    foreach ($data['purchase_orders'] as $r) { $pdo->exec("UPDATE purchase_orders SET subtotal = {$r['subtotal']}, grand_total = {$r['grand_total']} WHERE id = {$r['id']}"); }
    $pdo->commit();
    echo 'Rollback berhasil dieksekusi!
';
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'Rollback gagal: ' . $e->getMessage() . "
";
}
