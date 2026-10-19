<?php

namespace App\Exports;

use App\Exports\Sheets\LaporanHarianCategorySheet;
use App\Exports\Sheets\LaporanHarianProductSheet;
use App\Exports\Sheets\LaporanHarianVariantSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanHarianExport implements WithMultipleSheets
{
    protected $mulai;
    protected $akhir;
    protected $categoryId;
    protected $viewType;

    public function __construct($mulai, $akhir, $categoryId = null, $viewType = 'all')
    {
        $this->mulai = $mulai;
        $this->akhir = $akhir;
        $this->categoryId = $categoryId;
        $this->viewType = $viewType ?: 'all';
    }

    public function sheets(): array
    {
        if ($this->viewType === 'produk') {
            return [
                new LaporanHarianProductSheet($this->mulai, $this->akhir, $this->categoryId),
            ];
        }

        if ($this->viewType === 'kategori') {
            return [
                new LaporanHarianCategorySheet($this->mulai, $this->akhir, $this->categoryId),
            ];
        }

        if ($this->viewType === 'varian') {
            return [
                new LaporanHarianVariantSheet($this->mulai, $this->akhir, $this->categoryId),
            ];
        }

        // Default: Export all 3 sheets in one comprehensive Excel workbook
        return [
            new LaporanHarianProductSheet($this->mulai, $this->akhir, $this->categoryId),
            new LaporanHarianCategorySheet($this->mulai, $this->akhir, $this->categoryId),
            new LaporanHarianVariantSheet($this->mulai, $this->akhir, $this->categoryId),
        ];
    }
}
