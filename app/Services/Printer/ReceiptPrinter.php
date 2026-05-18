<?php

namespace App\Services\Printer;

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\RawbtPrintConnector;
use Mike42\Escpos\CapabilityProfile;

class ReceiptPrinter
{
    protected Printer $printer;
    protected int $printerWidth = 48;

    /* ==============================
     *  CORE
     * ============================== */

    public function connect(): void
    {
        $profile   = CapabilityProfile::load("POS-5890");
        $connector = new RawbtPrintConnector();
        $this->printer = new Printer($connector, $profile);
    }

    public function close(): void
    {
        $this->printer->close();
    }

    /* ==============================
     *  HELPER TEXT
     * ============================== */

    protected function line(string $text = ''): void
    {
        $this->printer->setFont(Printer::FONT_C);
        $this->printer->text(wordwrap($text, $this->printerWidth) . "\n");
    }

    protected function separator(): void
    {
        $this->line(str_repeat('-', $this->printerWidth));
    }

    protected function rupiah($value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    /* ==============================
     *  HEADER
     * ============================== */

    protected function header(): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);

        $logo = public_path('img/logo.png');
        if (file_exists($logo)) {
            $img = EscposImage::load($logo);
            $this->printer->bitImage($img);
        }

        $this->printer->setTextSize(2, 1);
        $this->line('SEKOLAH ISLAM');
        $this->line('AL-AZHAR CAIRO');
        $this->line('BANDA ACEH');

        $this->printer->setTextSize(1, 1);
        $this->separator();
        $this->printer->setJustification();
    }

    /* ==============================
     *  FOOTER
     * ============================== */

    protected function footer(string $text = 'Terima kasih'): void
    {
        $this->line();
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->line($text);
        $this->printer->cut();
    }

    /* ==============================
     *  PRINT SALE
     * ============================== */

    public function printSale($sale): void
    {
        $this->connect();
        $this->header();

        $this->line("No Struk : {$sale->invoice_number}");
        $this->line("Tanggal  : " . $sale->created_at->format('d-m-Y H:i'));
        $this->line("Kasir    : " . optional($sale->cashier)->name);
        $this->separator();

        foreach ($sale->items as $item) {
            $this->line($item->product_name);
            $this->line(
                "{$item->qty} x " .
                    number_format($item->price) .
                    "   " .
                    number_format($item->subtotal)
            );
        }

        $this->separator();
        $this->line("TOTAL      : " . $this->rupiah($sale->grand_total));
        $this->line("BAYAR      : " . $this->rupiah($sale->paid_amount));
        $this->line("KEMBALI    : " . $this->rupiah($sale->change_amount));

        $this->footer('Barang yang sudah dibeli tidak dapat dikembalikan');
        $this->close();
    }

    /* ==============================
     *  PRINT REFUND
     * ============================== */

    public function printRefund($sale, $refundItems): void
    {
        $this->connect();
        $this->header();

        $this->line("REFUND PENJUALAN");
        $this->line("No Struk : {$sale->invoice_number}");
        $this->line("Tanggal  : " . now()->format('d-m-Y H:i'));
        $this->separator();

        $totalRefund = 0;

        foreach ($refundItems as $item) {
            $subtotal = $item->qty * $item->price;
            $totalRefund += $subtotal;

            $this->line($item->product_name);
            $this->line(
                "{$item->qty} x " .
                    number_format($item->price) .
                    "   " .
                    number_format($subtotal)
            );
        }

        $this->separator();
        $this->line("TOTAL REFUND : " . $this->rupiah($totalRefund));

        $this->footer('Refund berhasil');
        $this->close();
    }
}
