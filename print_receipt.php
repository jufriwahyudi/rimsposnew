<?php
/**
 * Script cepat cetak struk langsung ke COM5 (RPP02N Thermal Printer via Bluetooth)
 * Usage: php print_receipt.php [SALE_ID]
 * Contoh: php print_receipt.php 18196
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Printer\EscPosReceiptService;

$saleId = isset($argv[1]) ? (int) $argv[1] : 18195;
$portName = "COM5";

$host = '194.59.164.84';
$db   = 'u652364387_rimspos';
$user = 'u652364387_rimspos';
$pass = 'Rims1nsecure!@#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\Exception $e) {
    echo "Error koneksi DB: " . $e->getMessage() . "\n";
    exit(1);
}

$stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->execute([$saleId]);
$sale = $stmt->fetch();

if (!$sale) {
    echo "Transaksi ID $saleId tidak ditemukan di database live!\n";
    exit(1);
}

$stmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$stmt->execute([$saleId]);
$saleItems = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->execute([$sale['store_id']]);
$store = $stmt->fetch();

$items = array_map(function ($item) {
    return [
        'name' => $item['product_name'],
        'sku' => $item['sku'],
        'qty' => (int) $item['qty'],
        'price' => round($item['price']),
        'discount_amount' => round($item['discount_amount'] ?? 0),
        'subtotal' => round($item['subtotal'] ?? ($item['qty'] * $item['price'])),
        'notes' => $item['notes'] ?? null,
    ];
}, $saleItems);

$grossSubtotal = collect($items)->sum(fn ($i) => ($i['qty'] ?? 0) * ($i['price'] ?? 0));
if ($grossSubtotal <= 0) {
    $grossSubtotal = round($sale['subtotal'] + ($sale['discount_total'] ?? 0));
}

$data = [
    'is_checklist' => false,
    'checklist_title' => 'ORDER KITCHEN / KDS',
    'trigger_buzzer' => false,
    'open_drawer' => true,
    'store' => [
        'name' => $store['name'] ?? 'RimsPos',
        'address' => $store['address'],
        'city' => $store['city'],
        'phone' => $store['phone'],
        'receipt_header' => $store['receipt_header'],
        'receipt_footer' => $store['receipt_footer'],
        'logo' => $store['logo'] ?? null,
        'show_receipt_logo' => (bool) ($store['show_receipt_logo'] ?? false),
        'qris_image' => $store['qris_image'] ?? null,
    ],
    'transaction' => [
        'invoice' => $sale['invoice_number'],
        'date' => date('d-m-Y H:i', strtotime($sale['sale_date'])),
        'cashier' => 'Kasir',
        'customer' => $sale['customer_name'] ?? 'Umum',
        'status' => strtoupper($sale['status']),
        'payment_status' => strtoupper($sale['payment_status']),
        'table_number' => $sale['table_number'] ?? null,
    ],
    'items' => $items,
    'summary' => [
        'subtotal' => $grossSubtotal,
        'discount' => round(($sale['discount_total'] ?? 0) + ($sale['trans_discount'] ?? 0)),
        'item_discount' => round($sale['discount_total'] ?? 0),
        'trans_discount' => round($sale['trans_discount'] ?? 0),
        'discount_name' => $sale['discount_name'] ?? null,
        'total' => round($sale['grand_total']),
        'paid' => round($sale['paid_amount']),
        'change' => round($sale['change_amount']),
        'tip' => round($sale['tip_amount'] ?? 0),
        'payment_status' => $sale['payment_status'],
        'remaining_debt' => round($sale['grand_total'] - $sale['paid_amount']),
        'voucher_code' => $sale['voucher_code'] ?? null,
        'voucher_discount_amount' => round($sale['voucher_discount_amount'] ?? 0),
        'points_redeemed' => (int) ($sale['points_redeemed'] ?? 0),
        'point_discount_amount' => round($sale['point_discount_amount'] ?? 0),
    ],
];

echo "Menghasilkan format struk thermal 58mm untuk Invoice: {$sale['invoice_number']} (ID: $saleId)...\n";
$service = new EscPosReceiptService('58mm');
$b64 = $service->base64($data);
$rawBytes = base64_decode($b64);

$tmpFile = __DIR__ . '/receipt_temp.bin';
file_put_contents($tmpFile, $rawBytes);

echo "Mengirim data ke printer Bluetooth ($portName)...\n";
$psScript = "\$port = New-Object System.IO.Ports.SerialPort '$portName', 9600, 'None', 8, 'One'; \$port.WriteTimeout = 5000; \$port.Open(); \$bytes = [System.IO.File]::ReadAllBytes('$tmpFile'); \$port.Write(\$bytes, 0, \$bytes.Length); Start-Sleep -Milliseconds 1500; \$port.Close();";
$cmd = 'powershell -NoProfile -Command "' . $psScript . '"';
exec($cmd, $out, $ret);

if (file_exists($tmpFile)) {
    @unlink($tmpFile);
}

if ($ret === 0) {
    echo ">> SUKSES! Struk untuk transaksi ID $saleId ({$sale['invoice_number']}) berhasil dicetak ke RPP02N!\n";
} else {
    echo ">> Gagal mengirim data ke port $portName. Pastikan printer menyala dan terhubung Bluetooth.\n";
}
