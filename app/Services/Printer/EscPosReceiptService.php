<?php

namespace App\Services\Printer;

use Illuminate\Support\Facades\Storage;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\PrintConnectors\RawbtPrintConnector;
use Mike42\Escpos\CapabilityProfile;

/**
 * Generate ESC/POS receipt data for RawBT Android app.
 *
 * Mendukung dua ukuran kertas:
 *  - 58mm  → 32 karakter per baris
 *  - 80mm  → 48 karakter per baris
 *
 * Usage:
 *   $svc  = new EscPosReceiptService('58mm');
 *   $uri  = $svc->intentUri($data);   // → redirect ke Android Intent
 *   $b64  = $svc->base64($data);      // → raw bytes untuk dikirim sendiri
 */
class EscPosReceiptService
{
    protected int $width;
    protected Printer $printer;

    public function __construct(string $paperSize = '80mm')
    {
        $this->width = ($paperSize === '58mm') ? 32 : 42;
    }

    /* =====================================================================
     * PUBLIC: generate Android Intent URI  (untuk window.location.href)
     * ===================================================================== */

    /**
     * Hasilkan Android Intent URI untuk dikirim ke RawBT.
     * Frontend cukup:  window.location.href = intentUri;
     */
    public function intentUri(array $data): string
    {
        ob_start();
        $connector = new RawbtPrintConnector();
        $this->buildPrinter($connector, $data);
        $this->printer->close(); // triggers finalize() → echoes intent URI
        return ob_get_clean();
        // return 'intent:base64,' . $this->base64($data)
        //     . '=#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;';
    }

    /* =====================================================================
     * PUBLIC: generate raw base64 bytes  (untuk WebPrint / custom JS)
     * ===================================================================== */

    /**
     * Hasilkan ESC/POS bytes di-encode base64.
     * Cocok untuk dikirim via fetch() ke RawBT WebPrint API.
     */
    public function base64(array $data): string
    {
        $connector = new DummyPrintConnector();
        $this->buildPrinter($connector, $data);
        $rawBytes = $connector->getData(); // ambil data SEBELUM close()
        $this->printer->close();           // finalize() nullifies buffer
        return base64_encode($rawBytes);
    }

    /**
     * Hasilkan byte ESC/POS base64 khusus untuk membuka laci kasir (Cash Drawer) saja.
     */
    public function openDrawerBase64(): string
    {
        $connector = new DummyPrintConnector();
        $profile   = CapabilityProfile::load('POS-5890');
        $printer   = new Printer($connector, $profile);
        $printer->pulse();
        $rawBytes  = $connector->getData();
        $printer->close();
        return base64_encode($rawBytes);
    }

    /**
     * Hasilkan byte ESC/POS base64 untuk 1 lembar panjang bersambung Laporan Tutup Kasir
     * (Mencakup Transaksi Penjualan dan Rincian Penjualan Menu).
     */
    public function fullShiftReportBase64(array $data): string
    {
        $connector = new DummyPrintConnector();
        $profile   = CapabilityProfile::load('POS-5890');
        $this->printer = new Printer($connector, $profile);

        $this->printFullShiftReport($data);

        $rawBytes = $connector->getData();
        $this->printer->close();
        return base64_encode($rawBytes);
    }

    /* =====================================================================
     * CORE BUILDER
     * ===================================================================== */

    protected function buildPrinter($connector, array $data): void
    {
        $profile       = CapabilityProfile::load('POS-5890');
        $this->printer = new Printer($connector, $profile);

        $isChecklist = $data['is_checklist'] ?? false;
        $isTenantSlip = $data['is_tenant_slip'] ?? false;

        if ($isTenantSlip) {
            $this->printTenantSlipHeader($data['store'] ?? [], $data['tenant'] ?? []);
            $this->printChecklistTransaction($data['transaction'] ?? []);
            $this->printChecklistItems($data['items'] ?? []);
            $this->printTenantSlipFooter($data['summary'] ?? []);
        } elseif ($isChecklist) {
            $this->printChecklistHeader($data['store'] ?? []);
            $this->printChecklistTransaction($data['transaction'] ?? []);
            $this->printChecklistItems($data['items'] ?? []);
            $this->printChecklistFooter();
        } else {
            $openDrawer = $data['open_drawer'] ?? true;
            $this->printHeader($data['store']            ?? []);
            $this->printTransaction($data['transaction'] ?? []);
            $this->printItems($data['items']             ?? []);
            $this->printSummary($data['summary']         ?? []);
            $this->printFooter($openDrawer);
        }
    }

    protected function printTenantSlipHeader(array $store, array $tenant): void
    {
        $this->printer->initialize();
        $this->printer->setFont(Printer::FONT_A);

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setTextSize(2, 1);
        $this->printer->setEmphasis(true);
        $this->writeLine('SLIP PESANAN TENANT');
        $this->printer->setTextSize(1, 1);
        if (!empty($tenant['name'])) {
            $this->writeLine('[ TENANT: ' . strtoupper($tenant['name']) . ' ]');
        }
        $this->printer->setEmphasis(false);
        $this->writeLine($store['name'] ?? 'RIMS POS');

        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->separator();
    }

    protected function printTenantSlipFooter(array $summary): void
    {
        $this->separator();
        if (isset($summary['total_qty'])) {
            $this->printer->setEmphasis(true);
            $this->writeLine($this->cols('Total Menu:', $summary['total_qty'] . ' Porsi'));
            $this->printer->setEmphasis(false);
            $this->separator();
        }
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->writeLine('[ Dicetak: ' . date('d/m/Y H:i') . ' ]');
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->feed(3);
        $this->printer->cut();
    }

    protected function printChecklistHeader(array $store): void
    {
        $this->printer->initialize();
        $this->printer->setFont(Printer::FONT_A);

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setTextSize(2, 1);
        $this->printer->setEmphasis(true);
        $this->writeLine('ORDER KITCHEN / KDS');
        $this->printer->setEmphasis(false);
        $this->printer->setTextSize(1, 1);
        $this->writeLine($store['name'] ?? 'RIMS POS');

        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->separator();
    }

    protected function printChecklistTransaction(array $trx): void
    {
        if (!empty($trx['table_number'])) {
            $this->printer->setTextSize(2, 1);
            $this->printer->setEmphasis(true);
            $this->writeLine('MEJA: ' . $trx['table_number']);
            $this->printer->setEmphasis(false);
            $this->printer->setTextSize(1, 1);
            $this->separator();
        }
        $this->writeLine('No   : ' . ($trx['invoice']  ?? '-'));
        $this->writeLine('Tgl  : ' . ($trx['date']     ?? '-'));
        $this->writeLine('Kasir: ' . ($trx['cashier']  ?? '-'));
        $this->writeLine('Cust : ' . ($trx['customer'] ?? 'Umum'));
        $this->separator();
    }

    protected function printChecklistItems(array $items): void
    {
        foreach ($items as $item) {
            $name     = (string) ($item['name']  ?? '');
            $qty      = (int)    ($item['qty']   ?? 0);
            $notes    = (string) ($item['notes'] ?? '');

            $strQty = '[ ] ' . $qty . ' x ';

            if ($this->width >= 40) {
                // 80mm
                $chunks = $this->wrapText($name, $this->width - 8);
                $firstChunk = array_shift($chunks);
                $this->writeLine($this->mbPad($strQty, 8) . $firstChunk);

                foreach ($chunks as $chunk) {
                    $this->writeLine(str_repeat(' ', 8) . $chunk);
                }

                if ($notes !== '') {
                    $this->writeLine(str_repeat(' ', 8) . '* Catatan: ' . $notes);
                }
            } else {
                // 58mm
                $qtyCol = $strQty;
                // Baris 1: qty + name
                $firstLine = $qtyCol . $name;
                foreach ($this->wrapText($firstLine, $this->width) as $chunk) {
                    $this->writeLine($chunk);
                }

                if ($notes !== '') {
                    $this->writeLine('    * Catatan: ' . $notes);
                }
            }
        }
    }

    protected function printChecklistFooter(): void
    {
        $this->separator();
        $this->printer->feed(3);
        $this->printer->cut();
    }

    /* =====================================================================
     * SECTIONS
     * ===================================================================== */

    protected function printHeader(array $store): void
    {
        $this->printer->initialize();
        $this->printer->setFont(Printer::FONT_A);

        // Logo store (jika ada)
        $logoPath = $this->resolveLogoPath($store['logo'] ?? null);
        \Log::debug('[EscPosReceiptService] Logo path: ' . $logoPath);
        if ($logoPath && file_exists($logoPath)) {
            try {
                $img = EscposImage::load($logoPath, false);
                $this->printer->setJustification(Printer::JUSTIFY_CENTER);
                $this->printer->bitImage($img);
                \Log::debug('[EscPosReceiptService] Logo loaded successfully');
            } catch (\Exception $e) {
                // Abaikan error logo
            }
        }

        // Nama toko
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setTextSize(2, 1);
        $this->writeLine($store['name'] ?? 'RIMS POS');
        $this->printer->setTextSize(1, 1);
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);

        if (!empty($store['address'])) {
            $this->writeCentered($store['address']);
        }
        if (!empty($store['city'])) {
            $this->writeCentered($store['city']);
        }
        if (!empty($store['phone'])) {
            $this->writeCentered('Telp: ' . $store['phone']);
        }

        $this->separator();
    }

    protected function resolveLogoPath(?string $logo): ?string
    {
        if (!$logo) {
            return null;
        }

        $logo = trim($logo);
        if ($logo === '') {
            return null;
        }

        if (file_exists($logo)) {
            return $logo;
        }

        $urlPath = parse_url($logo, PHP_URL_PATH) ?: $logo;
        $publicPath = public_path(ltrim($urlPath, '/\\'));
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        $storagePath = ltrim($urlPath, '/\\');
        if (str_starts_with($storagePath, 'storage/')) {
            $storagePath = substr($storagePath, strlen('storage/'));
        }

        if (Storage::disk('public')->exists($storagePath)) {
            return Storage::disk('public')->path($storagePath);
        }

        if (Storage::exists($storagePath)) {
            return Storage::path($storagePath);
        }

        return null;
    }

    protected function printTransaction(array $trx): void
    {
        $this->writeLine('No   : ' . ($trx['invoice']  ?? '-'));
        $this->writeLine('Tgl  : ' . ($trx['date']     ?? '-'));
        $this->writeLine('Kasir: ' . ($trx['cashier']  ?? '-'));
        $this->writeLine('Cust : ' . ($trx['customer'] ?? 'Umum'));

        $status = $trx['status'] ?? 'PAID';
        if ($status !== 'PAID') {
            $this->separator();
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $statusLabel = ($status === 'HOLD') ? 'TAGIHAN (PRE-BILL)' : $status;
            $this->writeLine('*** ' . $statusLabel . ' ***');
            $this->printer->setEmphasis(false);
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        } else {
            $payLabel = strtoupper($trx['payment_status'] ?? 'LUNAS');
            $payLabel = preg_replace('/[\x00-\x1F\x7F]+/', '', $payLabel); // strip control chars (\r, \n, etc.)
            $this->writeLine('PaySt: ' . trim($payLabel));
        }

        $this->separator();
    }

    protected function printItems(array $items): void
    {
        foreach ($items as $item) {
            $name     = (string) ($item['name']  ?? '');
            $qty      = (int)    ($item['qty']   ?? 0);
            $price    = (int)    ($item['price'] ?? 0);
            $subtotal = $qty * $price;
            $notes    = (string) ($item['notes'] ?? '');

            $strDetail   = $qty . ' x ' . $this->rupiah($price);
            $strSubtotal = $this->rupiah($subtotal);

            // Baris 1: nama produk (wrap sesuai lebar kertas: 32 char utk 58mm, 48 char utk 80mm)
            foreach ($this->wrapText($name, $this->width) as $chunk) {
                $this->writeLine($chunk);
            }

            // Baris 2: "  qty x harga            subtotal" (rata kanan)
            $lineDetail = '  ' . $strDetail;
            $spaces     = $this->width - mb_strlen($lineDetail) - mb_strlen($strSubtotal);
            $this->writeLine($lineDetail . str_repeat(' ', max(1, $spaces)) . $strSubtotal);

            if ($notes !== '') {
                $this->writeLine('    * Catatan: ' . $notes);
            }
        }
    }

    protected function printSummary(array $summary): void
    {
        $subtotal = (int) ($summary['subtotal'] ?? 0);
        $discount = (int) ($summary['discount'] ?? 0);
        $total    = (int) ($summary['total']    ?? 0);
        $paid     = (int) ($summary['paid']     ?? 0);
        $change   = (int) ($summary['change']   ?? 0);
        $tip      = (int) ($summary['tip']      ?? 0);
        $pointDisc = (int) ($summary['point_discount_amount'] ?? 0);
        $voucherDisc = (int) ($summary['voucher_discount_amount'] ?? 0);
        $remDebt   = (int) ($summary['remaining_debt'] ?? 0);
        $payStatus = (string) ($summary['payment_status'] ?? '');

        $this->separator();
        $this->writeLine($this->cols('Subtotal',  $this->rupiah($subtotal)));

        if ($discount > 0) {
            $this->writeLine($this->cols('Diskon', '-' . $this->rupiah($discount)));
        }

        if ($pointDisc > 0) {
            $this->writeLine($this->cols('Poin Diskon', '-' . $this->rupiah($pointDisc)));
        }

        if ($voucherDisc > 0) {
            $this->writeLine($this->cols('Voucher Diskon', '-' . $this->rupiah($voucherDisc)));
        }

        $this->printer->setEmphasis(true);
        $this->writeLine($this->cols('TOTAL', $this->rupiah($total)));
        $this->printer->setEmphasis(false);

        $this->separator();
        $this->writeLine($this->cols('Bayar',   $this->rupiah($paid)));
        if ($tip > 0) {
            $this->writeLine($this->cols('Tip',     $this->rupiah($tip)));
        }
        $this->writeLine($this->cols('Kembali', $this->rupiah($change)));

        if ($payStatus === 'hutang' || $remDebt > 0) {
            $this->printer->setEmphasis(true);
            $this->writeLine($this->cols('SISA HUTANG', $this->rupiah($remDebt)));
            $this->printer->setEmphasis(false);
        }
    }

    protected function printFooter(bool $openDrawer = true): void
    {
        $this->separator();
        $this->writeCentered('Terima Kasih!');
        $this->writeCentered('Barang yg sudah dibeli');
        $this->writeCentered('tidak dapat dikembalikan');

        if ($openDrawer) {
            // Trigger sinyal pulse untuk membuka cash drawer (laci kasir) via port RJ11 printer
            $this->printer->pulse();
        }

        // Feed 3-4 baris agar seluruh teks footer keluar melewati pisau pemotong / gerigi sobek kertas
        $this->printer->feed(3);
        $this->printer->cut();
    }

    /* =====================================================================
     * HELPERS
     * ===================================================================== */

    protected function writeLine(string $text = ''): void
    {
        $this->printer->text($text . "\n");
    }

    protected function writeCentered(string $text): void
    {
        $len = mb_strlen($text);
        if ($len >= $this->width) {
            $this->writeLine($text);
            return;
        }
        $spaces = (int) floor(($this->width - $len) / 2);
        $this->writeLine(str_repeat(' ', max(0, $spaces)) . $text);
    }

    protected function separator(string $char = '-'): void
    {
        $this->writeLine(str_repeat($char, $this->width));
    }

    protected function rupiah(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    /**
     * Dua kolom: kiri rata kiri, kanan rata kanan, total = $this->width.
     */
    protected function cols(string $left, string $right, ?int $width = null): string
    {
        $w      = $width ?? $this->width;
        $rLen   = mb_strlen($right);
        $lMax   = $w - $rLen - 1;
        $left   = mb_substr($left, 0, $lMax);
        $spaces = $w - mb_strlen($left) - $rLen;
        return $left . str_repeat(' ', max(1, $spaces)) . $right;
    }

    /**
     * mb-safe str_pad (STR_PAD_RIGHT).
     */
    protected function mbPad(string $str, int $len): string
    {
        $pad = $len - mb_strlen($str);
        return $str . ($pad > 0 ? str_repeat(' ', $pad) : '');
    }

    /**
     * Word-wrap dengan mb_strlen awareness.
     * @return string[]
     */
    protected function wrapText(string $text, int $width): array
    {
        $lines  = [];
        $words  = explode(' ', $text);
        $cur    = '';

        foreach ($words as $word) {
            if ($cur === '') {
                $cur = $word;
            } elseif (mb_strlen($cur) + 1 + mb_strlen($word) <= $width) {
                $cur .= ' ' . $word;
            } else {
                $lines[] = $cur;
                $cur     = $word;
            }
        }

        if ($cur !== '') {
            $lines[] = $cur;
        }

        return $lines ?: [''];
    }

    protected function printFullShiftReport(array $data): void
    {
        $store = $data['store'] ?? [];
        $shift = $data['shift'] ?? [];
        $fin   = $data['financial'] ?? [];
        $menu  = $data['menu_sales'] ?? [];

        $this->printer->initialize();
        $this->printer->setFont(Printer::FONT_A);

        // 1. Store Header
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setEmphasis(true);
        $this->writeLine($store['name'] ?? 'RIMS POS');
        $this->printer->setEmphasis(false);

        if (!empty($store['address'])) {
            foreach ($this->wrapText($store['address'], $this->width) as $line) {
                $this->writeLine($line);
            }
        }
        if (!empty($store['phone'])) {
            $this->writeLine('Telp: ' . $store['phone']);
        }
        $this->separator();

        // 2. Section 1 Title: Laporan Tutup Kasir - Transaksi Penjualan
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setEmphasis(true);
        $this->writeLine('LAPORAN TUTUP KASIR');
        $this->writeLine('TRANSAKSI PENJUALAN');
        $this->printer->setEmphasis(false);
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->separator();

        // Shift Meta Info
        $this->writeLine('Kasir       : ' . ($shift['cashier_name'] ?? '-'));
        $this->writeLine('Waktu Buka  : ' . ($shift['opened_at'] ?? '-'));
        $this->writeLine('Waktu Tutup : ' . ($shift['closed_at'] ?? '-'));
        $this->separator();

        // Financial Breakdown
        $this->writeLine($this->cols('Modal Awal', number_format($fin['opening_cash'] ?? 0, 0, ',', '.')));
        $this->writeLine($this->cols('Tunai', number_format($fin['cash_sales'] ?? 0, 0, ',', '.')));

        $nonCashTotal = (float) ($fin['non_cash_sales'] ?? 0);
        $this->writeLine($this->cols('Transfer', number_format($nonCashTotal, 0, ',', '.')));

        if (!empty($fin['cash_in']) && $fin['cash_in'] > 0) {
            $this->writeLine($this->cols('Kas Masuk (Petty)', number_format($fin['cash_in'], 0, ',', '.')));
        }
        if (!empty($fin['cash_out']) && $fin['cash_out'] > 0) {
            $this->writeLine($this->cols('Kas Keluar (Petty)', number_format($fin['cash_out'], 0, ',', '.')));
        }
        if (!empty($fin['refund_cash']) && $fin['refund_cash'] > 0) {
            $this->writeLine($this->cols('Refund Tunai', number_format($fin['refund_cash'], 0, ',', '.')));
        }

        $this->writeLine($this->cols('Total Penerimaan', number_format($fin['total_received'] ?? 0, 0, ',', '.')));
        $this->printer->setEmphasis(true);
        $this->writeLine($this->cols('Saldo Akhir', number_format($fin['final_cash_balance'] ?? 0, 0, ',', '.')));
        $this->printer->setEmphasis(false);
        $this->separator();

        // Transaction Counts
        $this->writeLine($this->cols('Transaksi Selesai', (string) ($fin['completed_sales'] ?? 0)));
        $this->writeLine($this->cols('Trx Belum Terbayar', (string) ($fin['unpaid_sales'] ?? 0)));
        $this->separator('=');

        // 3. Section 2 Title: Laporan Tutup Kasir - Penjualan Menu (Continuously in the same paper strip)
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setEmphasis(true);
        $this->writeLine('LAPORAN TUTUP KASIR');
        $this->writeLine('PENJUALAN MENU');
        $this->printer->setEmphasis(false);
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->separator();

        $this->writeLine('Kasir       : ' . ($shift['cashier_name'] ?? '-'));
        $this->writeLine('Waktu Buka  : ' . ($shift['opened_at'] ?? '-'));
        $this->writeLine('Waktu Tutup : ' . ($shift['closed_at'] ?? '-'));
        $this->separator();

        $this->printer->setEmphasis(true);
        $this->writeLine('Produk Terjual');
        $this->printer->setEmphasis(false);
        $this->separator();

        $items = $menu['items'] ?? [];
        if (empty($items)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->writeLine('(Tidak ada penjualan produk)');
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        } else {
            foreach ($items as $item) {
                $pName = (string) ($item['name'] ?? '-');
                $pQty  = (string) ($item['qty'] ?? 0);
                $this->writeLine($this->cols($pName, $pQty));
            }
        }

        $this->separator();
        $this->printer->setEmphasis(true);
        $this->writeLine($this->cols('Total', (string) ($menu['total_qty'] ?? 0)));
        $this->printer->setEmphasis(false);
        $this->separator('=');

        // Optional footer notes
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->writeLine('Dicetak: ' . date('d/m/Y H:i'));
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->feed(3);
        $this->printer->cut();
    }
}
