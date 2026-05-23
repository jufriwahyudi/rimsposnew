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
        $this->width = ($paperSize === '58mm') ? 32 : 48;
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

    /* =====================================================================
     * CORE BUILDER
     * ===================================================================== */

    protected function buildPrinter($connector, array $data): void
    {
        $profile       = CapabilityProfile::load('POS-5890');
        $this->printer = new Printer($connector, $profile);

        $this->printHeader($data['store']            ?? []);
        $this->printTransaction($data['transaction'] ?? []);
        $this->printItems($data['items']             ?? []);
        $this->printSummary($data['summary']         ?? []);
        $this->printFooter();
        // close() dipanggil oleh masing-masing metode publik
    }

    /* =====================================================================
     * SECTIONS
     * ===================================================================== */

    protected function printHeader(array $store): void
    {
        $this->printer->initialize();
        $this->printer->setFont(Printer::FONT_A);

        // Logo store (jika ada)
        $logoPath = $store['logo'];
        if (file_exists($logoPath)) {
            try {
                $img = EscposImage::load($logoPath, false);
                $this->printer->setJustification(Printer::JUSTIFY_CENTER);
                $this->printer->bitImage($img);
            } catch (\Exception $e) {
                // Abaikan error logo
            }
        }

        // Nama toko
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setTextSize(2, 1);
        $this->writeLine($store['name'] ?? 'RIMS POS');
        $this->printer->setTextSize(1, 1);

        if (!empty($store['address'])) {
            $this->writeLine($store['address']);
        }
        if (!empty($store['city'])) {
            $this->writeLine($store['city']);
        }
        if (!empty($store['phone'])) {
            $this->writeLine('Telp: ' . $store['phone']);
        }

        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->separator();
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
            $this->writeLine('*** ' . $status . ' ***');
            $this->printer->setEmphasis(false);
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
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

            $strDetail   = $qty . ' x ' . $this->rupiah($price);
            $strSubtotal = $this->rupiah($subtotal);

            if ($this->width >= 48) {
                // ── 80mm: satu baris per item ──────────────────────────────
                // | Nama produk (25)   | qty x harga (14) | subtotal (9) |
                $nameCol   = $this->mbPad(mb_substr($name, 0, 25), 25);
                $detailCol = str_pad($strDetail,   14, ' ', STR_PAD_LEFT);
                $totalCol  = str_pad($strSubtotal,  9, ' ', STR_PAD_LEFT);
                $this->writeLine($nameCol . $detailCol . $totalCol);

                // Jika nama > 25 karakter, cetak sisa di baris berikutnya (indent)
                if (mb_strlen($name) > 25) {
                    $rest = mb_substr($name, 25);
                    foreach ($this->wrapText($rest, 25) as $chunk) {
                        $this->writeLine('  ' . $chunk);
                    }
                }
            } else {
                // ── 58mm: dua baris per item ───────────────────────────────
                // Baris 1: nama (wrap)
                foreach ($this->wrapText($name, $this->width) as $chunk) {
                    $this->writeLine($chunk);
                }
                // Baris 2: "  detail          subtotal" (right-aligned)
                $lineDetail = '  ' . $strDetail;
                $spaces     = $this->width - mb_strlen($lineDetail) - mb_strlen($strSubtotal);
                $this->writeLine($lineDetail . str_repeat(' ', max(1, $spaces)) . $strSubtotal);
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

        $this->separator();
        $this->writeLine($this->cols('Subtotal',  $this->rupiah($subtotal)));

        if ($discount > 0) {
            $this->writeLine($this->cols('Diskon', '-' . $this->rupiah($discount)));
        }

        $this->printer->setEmphasis(true);
        $this->writeLine($this->cols('TOTAL', $this->rupiah($total)));
        $this->printer->setEmphasis(false);

        $this->separator();
        $this->writeLine($this->cols('Bayar',   $this->rupiah($paid)));
        $this->writeLine($this->cols('Kembali', $this->rupiah($change)));
    }

    protected function printFooter(): void
    {
        $this->separator();
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->writeLine('Terima Kasih!');
        $this->writeLine('Barang yg sudah dibeli');
        $this->writeLine('tidak dapat dikembalikan');
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);

        // Feed & cut
        // $this->printer->feed(1);
        // deteksi otomatis: jika printer mendukung, ini akan memotong kertas setelah print selesai
        $this->printer->cut();
    }

    /* =====================================================================
     * HELPERS
     * ===================================================================== */

    protected function writeLine(string $text = ''): void
    {
        $this->printer->text($text . "\n");
    }

    protected function separator(): void
    {
        $this->writeLine(str_repeat('-', $this->width));
    }

    protected function rupiah(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    /**
     * Dua kolom: kiri rata kiri, kanan rata kanan, total = $this->width.
     */
    protected function cols(string $left, string $right): string
    {
        $rLen   = mb_strlen($right);
        $lMax   = $this->width - $rLen - 1;
        $left   = mb_substr($left, 0, $lMax);
        $spaces = $this->width - mb_strlen($left) - $rLen;
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
}
