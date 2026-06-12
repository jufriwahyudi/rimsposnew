<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected $isFnB;
    protected $showRewardPoints;

    public function __construct(bool $isFnB, bool $showRewardPoints)
    {
        $this->isFnB = $isFnB;
        $this->showRewardPoints = $showRewardPoints;
    }

    public function headings(): array
    {
        $headings = [
            'Kode Produk',
            'Nama Produk',
            'Deskripsi',
            'Nama Varian',
            'Barcode',
            'Harga Jual',
        ];

        if ($this->showRewardPoints) {
            $headings[] = 'Poin Reward';
        }

        if ($this->isFnB) {
            $headings = array_merge($headings, [
                'Nama/Kode Tenant',
                'Lacak Stok (Ya/Tidak)',
                'Harga Beli Manual',
                'Tipe Komisi (global/percentage/nominal)',
                'Rate Komisi',
            ]);
        }

        return $headings;
    }

    public function array(): array
    {
        if ($this->isFnB) {
            $row1 = [
                'FB001',
                'Kopi Susu Gula Aren',
                'Espresso dengan susu dan gula aren premium',
                'Regular',
                '',
                '18000',
            ];
            if ($this->showRewardPoints) {
                $row1[] = '5';
            }
            $row1 = array_merge($row1, [
                'Tenant Utama',
                'Ya',
                '8000',
                'percentage',
                '10',
            ]);

            $row2 = [
                'FB001',
                'Kopi Susu Gula Aren',
                'Espresso dengan susu dan gula aren premium',
                'Large',
                '',
                '22000',
            ];
            if ($this->showRewardPoints) {
                $row2[] = '5';
            }
            $row2 = array_merge($row2, [
                'Tenant Utama',
                'Ya',
                '10000',
                'percentage',
                '10',
            ]);

            $row3 = [
                'FB002',
                'Roti Bakar Cokelat',
                'Roti bakar manis toping cokelat melimpah',
                '',
                '',
                '15000',
            ];
            if ($this->showRewardPoints) {
                $row3[] = '0';
            }
            $row3 = array_merge($row3, [
                '',
                'Tidak',
                '0',
                'global',
                '0',
            ]);

            return [$row1, $row2, $row3];
        }

        // Retail template examples
        $row1 = [
            'PRD001',
            'Kemeja Flanel',
            'Kemeja flanel lengan panjang bahan katun tebal',
            'S',
            '88812345',
            '150000',
        ];
        if ($this->showRewardPoints) {
            $row1[] = '10';
        }

        $row2 = [
            'PRD001',
            'Kemeja Flanel',
            'Kemeja flanel lengan panjang bahan katun tebal',
            'M',
            '88812346',
            '150000',
        ];
        if ($this->showRewardPoints) {
            $row2[] = '10';
        }

        $row3 = [
            'PRD002',
            'Sepatu Sneaker',
            'Sepatu kasual warna putih',
            '',
            '',
            '250000',
        ];
        if ($this->showRewardPoints) {
            $row3[] = '0';
        }

        return [$row1, $row2, $row3];
    }
}
