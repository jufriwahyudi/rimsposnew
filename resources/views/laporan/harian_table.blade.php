@php
    $sumQtyProduct = $rowsProduct->sum('total_qty');
    $sumDiskonProduct = $rowsProduct->sum('total_diskon');
    $sumSubtotalProduct = $rowsProduct->sum('total_subtotal');
    $sumModalProduct = $rowsProduct->sum('total_modal');
    $sumLabaProduct = $rowsProduct->sum('laba_rugi');
    $sumTrxProduct = $rowsProduct->sum('jumlah_trx');

    $grandTotal = $sumSubtotalProduct - ($totalTransDiscount ?? 0) - ($totalVoucherDiscount ?? 0) - ($totalPointDiscount ?? 0);
    $labaBersih = $grandTotal - $sumModalProduct;
    $overallMargin = $grandTotal > 0 ? round(($labaBersih / $grandTotal) * 100, 1) : 0;
@endphp

<div class="card rounded-4 border-0 shadow-sm p-3">
    {{-- Header & Export Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1" style="color: #7c3aed">
                <i class="bx bx-bar-chart-alt-2 me-1"></i> Laporan Stok Terjual
            </h5>
            <div class="text-muted small">
                <span><i class="bx bx-calendar me-1"></i>Periode: <strong>{{ $mulai === $akhir ? $mulai : $mulai . ' s/d ' . $akhir }}</strong></span>
                @if(!empty($selectedCategory))
                    <span class="ms-2 badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                        <i class="bx bx-category me-1"></i>Kategori: {{ $selectedCategory->name }}
                    </span>
                @endif
            </div>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-success dropdown-toggle fw-semibold rounded-pill px-3 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li>
                    <a class="dropdown-item py-2 fw-semibold" href="{{ route('laporan.harian.export', ['mulai' => $mulai, 'akhir' => $akhir, 'category_id' => $categoryId, 'view' => 'all']) }}">
                        <i class="bi bi-file-spreadsheet text-success me-2"></i> Export Semua Sheet (Lengkap)
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item py-2" href="{{ route('laporan.harian.export', ['mulai' => $mulai, 'akhir' => $akhir, 'category_id' => $categoryId, 'view' => 'produk']) }}">
                        <i class="bx bx-package text-primary me-2"></i> Sheet Per Produk
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="{{ route('laporan.harian.export', ['mulai' => $mulai, 'akhir' => $akhir, 'category_id' => $categoryId, 'view' => 'kategori']) }}">
                        <i class="bx bx-folder text-warning me-2"></i> Sheet Per Kategori
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="{{ route('laporan.harian.export', ['mulai' => $mulai, 'akhir' => $akhir, 'category_id' => $categoryId, 'view' => 'varian']) }}">
                        <i class="bx bx-list-check text-info me-2"></i> Sheet Per Varian (Detail)
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Ringkasan Indikator Keuangan (KPI Mini Cards) --}}
    <div class="row g-2 mb-3">
        <div class="col-md-3 col-6">
            <div class="p-2 rounded-3 border bg-light h-100">
                <small class="text-muted d-block fw-semibold">Grand Total Omset</small>
                <span class="fs-6 fw-bold text-dark">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 rounded-3 border bg-light h-100">
                <small class="text-muted d-block fw-semibold">Total Item Terjual</small>
                <span class="fs-6 fw-bold text-primary">{{ number_format($sumQtyProduct) }} <small class="text-muted">qty</small></span>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 rounded-3 border bg-light h-100">
                <small class="text-muted d-block fw-semibold">Total Modal (HPP)</small>
                <span class="fs-6 fw-bold text-muted">Rp {{ number_format($sumModalProduct, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 rounded-3 border {{ $labaBersih >= 0 ? 'bg-success bg-opacity-10 border-success border-opacity-25' : 'bg-danger bg-opacity-10 border-danger border-opacity-25' }} h-100">
                <small class="{{ $labaBersih >= 0 ? 'text-success' : 'text-danger' }} d-block fw-semibold">
                    Laba Bersih ({{ $overallMargin }}%)
                </small>
                <span class="fs-6 fw-bold {{ $labaBersih >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($labaBersih, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Nav Tabs Pengelompokan Laporan --}}
    <ul class="nav nav-pills mb-3 border-bottom pb-2 gap-2" id="harianTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill fw-semibold px-3 py-1 text-sm" id="produk-tab" data-bs-toggle="tab" data-bs-target="#tab-produk" type="button" role="tab" aria-controls="tab-produk" aria-selected="true">
                <i class="bx bx-package me-1"></i> Berdasarkan Produk
                <span class="badge bg-primary text-white rounded-pill ms-1">{{ count($rowsProduct) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-semibold px-3 py-1 text-sm" id="kategori-tab" data-bs-toggle="tab" data-bs-target="#tab-kategori" type="button" role="tab" aria-controls="tab-kategori" aria-selected="false">
                <i class="bx bx-folder me-1"></i> Berdasarkan Kategori
                <span class="badge bg-secondary text-white rounded-pill ms-1">{{ count($rowsCategory) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-semibold px-3 py-1 text-sm" id="varian-tab" data-bs-toggle="tab" data-bs-target="#tab-varian" type="button" role="tab" aria-controls="tab-varian" aria-selected="false">
                <i class="bx bx-list-check me-1"></i> Berdasarkan Varian (Detail)
                <span class="badge bg-secondary text-white rounded-pill ms-1">{{ count($rowsVariant) }}</span>
            </button>
        </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content" id="harianTabsContent">

        {{-- TAB 1: BERDASARKAN PRODUK --}}
        <div class="tab-pane fade show active" id="tab-produk" role="tabpanel" aria-labelledby="produk-tab">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0 text-sm">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th style="width: 45px;">No</th>
                            <th style="width: 120px;">Kode / SKU</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th style="width: 90px;">Qty Terjual</th>
                            <th style="width: 100px;">Diskon</th>
                            <th style="width: 130px;">Total Penjualan</th>
                            <th style="width: 120px;">Modal (HPP)</th>
                            <th style="width: 120px;">Laba / Rugi</th>
                            <th style="width: 80px;">Margin</th>
                            <th style="width: 75px;">Jml Trx</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($rowsProduct) && count($rowsProduct))
                            @foreach ($rowsProduct as $i => $row)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td><code>{{ $row->kode_produk }}</code></td>
                                    <td class="fw-semibold text-dark">{{ $row->product_name }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $row->category_name }}</span>
                                    </td>
                                    <td class="text-center fw-bold">{{ number_format($row->total_qty) }}</td>
                                    <td class="text-end text-muted">{{ number_format($row->total_diskon, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-dark">{{ number_format($row->total_subtotal, 0, ',', '.') }}</td>
                                    <td class="text-end text-muted">{{ number_format($row->total_modal, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ $row->laba_rugi >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($row->laba_rugi, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $row->margin_pct >= 30 ? 'bg-success' : ($row->margin_pct > 0 ? 'bg-warning text-dark' : 'bg-danger') }} bg-opacity-75">
                                            {{ $row->margin_pct }}%
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $row->jumlah_trx }}</td>
                                </tr>
                            @endforeach
                            {{-- Baris Rekapitulasi Produk --}}
                            <tr class="table-secondary fw-bold">
                                <td colspan="4" class="text-center">SUBTOTAL ITEM</td>
                                <td class="text-center">{{ number_format($sumQtyProduct) }}</td>
                                <td class="text-end">{{ number_format($sumDiskonProduct, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sumSubtotalProduct, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sumModalProduct, 0, ',', '.') }}</td>
                                <td class="text-end {{ $sumLabaProduct >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($sumLabaProduct, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    {{ $sumSubtotalProduct > 0 ? round(($sumLabaProduct / $sumSubtotalProduct) * 100, 1) : 0 }}%
                                </td>
                                <td class="text-center">{{ number_format($sumTrxProduct) }}</td>
                            </tr>
                            @if(isset($totalTransDiscount) && $totalTransDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="6" class="text-end text-muted">DISKON TRANSAKSI</td>
                                <td class="text-end text-danger">-{{ number_format($totalTransDiscount, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            @endif
                            @if (isset($totalVoucherDiscount) && $totalVoucherDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="6" class="text-end text-muted">DISKON VOUCHER</td>
                                <td class="text-end text-danger">-{{ number_format($totalVoucherDiscount, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            @endif
                            @if (isset($totalPointDiscount) && $totalPointDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="6" class="text-end text-muted">DISKON POINT</td>
                                <td class="text-end text-danger">-{{ number_format($totalPointDiscount, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            @endif
                            <tr class="table-warning fw-bold">
                                <td colspan="6" class="text-center">GRAND TOTAL (OMSET)</td>
                                <td class="text-end">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="7" class="text-center">TOTAL MODAL (HPP)</td>
                                <td class="text-end">{{ number_format($sumModalProduct, 0, ',', '.') }}</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr class="{{ $labaBersih >= 0 ? 'table-success' : 'table-danger' }} fw-bold">
                                <td colspan="8" class="text-center">LABA / RUGI BERSIH</td>
                                <td class="text-end {{ $labaBersih >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($labaBersih, 0, ',', '.') }}
                                </td>
                                <td class="text-center">{{ $overallMargin }}%</td>
                                <td></td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 d-block mb-1"></i>
                                    Tidak ada data produk terjual pada periode ini
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 2: BERDASARKAN KATEGORI --}}
        <div class="tab-pane fade" id="tab-kategori" role="tabpanel" aria-labelledby="kategori-tab">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0 text-sm">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th style="width: 45px;">No</th>
                            <th>Nama Kategori</th>
                            <th style="width: 140px;">Jml Menu Terjual</th>
                            <th style="width: 100px;">Qty Terjual</th>
                            <th style="width: 110px;">Diskon</th>
                            <th style="width: 140px;">Total Penjualan</th>
                            <th style="width: 130px;">Modal (HPP)</th>
                            <th style="width: 130px;">Laba / Rugi</th>
                            <th style="width: 100px;">Kontribusi (%)</th>
                            <th style="width: 80px;">Jml Trx</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($rowsCategory) && count($rowsCategory))
                            @php
                                $sumQtyCat = $rowsCategory->sum('total_qty');
                                $sumDiskonCat = $rowsCategory->sum('total_diskon');
                                $sumSubtotalCat = $rowsCategory->sum('total_subtotal');
                                $sumModalCat = $rowsCategory->sum('total_modal');
                                $sumLabaCat = $rowsCategory->sum('laba_rugi');
                                $sumTrxCat = $rowsCategory->sum('jumlah_trx');
                            @endphp
                            @foreach ($rowsCategory as $i => $row)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="fw-bold text-dark">
                                        <i class="bx bx-folder text-warning me-1"></i> {{ $row->category_name }}
                                    </td>
                                    <td class="text-center">{{ $row->jumlah_produk }} menu</td>
                                    <td class="text-center fw-bold">{{ number_format($row->total_qty) }}</td>
                                    <td class="text-end text-muted">{{ number_format($row->total_diskon, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-dark">{{ number_format($row->total_subtotal, 0, ',', '.') }}</td>
                                    <td class="text-end text-muted">{{ number_format($row->total_modal, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ $row->laba_rugi >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($row->laba_rugi, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 16px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min($row->kontribusi_pct, 100) }}%;" aria-valuenow="{{ $row->kontribusi_pct }}" aria-valuemin="0" aria-valuemax="100">
                                                {{ $row->kontribusi_pct }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $row->jumlah_trx }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-secondary fw-bold">
                                <td colspan="3" class="text-center">SUBTOTAL ITEM</td>
                                <td class="text-center">{{ number_format($sumQtyCat) }}</td>
                                <td class="text-end">{{ number_format($sumDiskonCat, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sumSubtotalCat, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sumModalCat, 0, ',', '.') }}</td>
                                <td class="text-end {{ $sumLabaCat >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($sumLabaCat, 0, ',', '.') }}
                                </td>
                                <td class="text-center">100%</td>
                                <td class="text-center">{{ number_format($sumTrxCat) }}</td>
                            </tr>
                            @if(isset($totalTransDiscount) && $totalTransDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end text-muted">DISKON TRANSAKSI</td>
                                <td class="text-end text-danger">-{{ number_format($totalTransDiscount, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            @endif
                            @if (isset($totalVoucherDiscount) && $totalVoucherDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end text-muted">DISKON VOUCHER</td>
                                <td class="text-end text-danger">-{{ number_format($totalVoucherDiscount, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            @endif
                            @if (isset($totalPointDiscount) && $totalPointDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end text-muted">DISKON POINT</td>
                                <td class="text-end text-danger">-{{ number_format($totalPointDiscount, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            @endif
                            <tr class="table-warning fw-bold">
                                <td colspan="5" class="text-center">GRAND TOTAL (OMSET)</td>
                                <td class="text-end">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="6" class="text-center">TOTAL MODAL (HPP)</td>
                                <td class="text-end">{{ number_format($sumModalCat, 0, ',', '.') }}</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr class="{{ $labaBersih >= 0 ? 'table-success' : 'table-danger' }} fw-bold">
                                <td colspan="7" class="text-center">LABA / RUGI BERSIH</td>
                                <td class="text-end {{ $labaBersih >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($labaBersih, 0, ',', '.') }}
                                </td>
                                <td class="text-center">{{ $overallMargin }}%</td>
                                <td></td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 d-block mb-1"></i>
                                    Tidak ada data kategori terjual pada periode ini
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 3: BERDASARKAN VARIAN (DETAIL LAMA) --}}
        <div class="tab-pane fade" id="tab-varian" role="tabpanel" aria-labelledby="varian-tab">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0 text-sm">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th style="width: 45px;">No</th>
                            <th style="width: 120px;">SKU</th>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th>Kategori</th>
                            <th style="width: 100px;">Harga Jual</th>
                            <th style="width: 90px;">Qty Terjual</th>
                            <th style="width: 100px;">Diskon</th>
                            <th style="width: 130px;">Total Penjualan</th>
                            <th style="width: 120px;">Modal (HPP)</th>
                            <th style="width: 120px;">Laba / Rugi</th>
                            <th style="width: 75px;">Jml Trx</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($rowsVariant) && count($rowsVariant))
                            @php
                                $sumQtyVar = $rowsVariant->sum('total_qty');
                                $sumDiskonVar = $rowsVariant->sum('total_diskon');
                                $sumSubtotalVar = $rowsVariant->sum('total_subtotal');
                                $sumModalVar = $rowsVariant->sum('total_modal');
                                $sumLabaVar = $rowsVariant->sum('laba_rugi');
                                $sumTrxVar = $rowsVariant->sum('jumlah_trx');
                            @endphp
                            @foreach ($rowsVariant as $i => $row)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td><code>{{ $row->sku }}</code></td>
                                    <td class="fw-semibold text-dark">{{ $row->product_name }}</td>
                                    <td>{{ $row->variant_label ?: '-' }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $row->category_name }}</span></td>
                                    <td class="text-end">{{ number_format($row->harga_jual, 0, ',', '.') }}</td>
                                    <td class="text-center fw-bold">{{ number_format($row->total_qty) }}</td>
                                    <td class="text-end text-muted">{{ number_format($row->total_diskon, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-dark">{{ number_format($row->total_subtotal, 0, ',', '.') }}</td>
                                    <td class="text-end text-muted">{{ number_format($row->total_modal, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ $row->laba_rugi >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($row->laba_rugi, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">{{ $row->jumlah_trx }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-secondary fw-bold">
                                <td colspan="6" class="text-center">SUBTOTAL ITEM</td>
                                <td class="text-center">{{ number_format($sumQtyVar) }}</td>
                                <td class="text-end">{{ number_format($sumDiskonVar, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sumSubtotalVar, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sumModalVar, 0, ',', '.') }}</td>
                                <td class="text-end {{ $sumLabaVar >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($sumLabaVar, 0, ',', '.') }}
                                </td>
                                <td class="text-center">{{ number_format($sumTrxVar) }}</td>
                            </tr>
                            @if(isset($totalTransDiscount) && $totalTransDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="8" class="text-end text-muted">DISKON TRANSAKSI</td>
                                <td class="text-end text-danger">-{{ number_format($totalTransDiscount, 0, ',', '.') }}</td>
                                <td colspan="3"></td>
                            </tr>
                            @endif
                            @if (isset($totalVoucherDiscount) && $totalVoucherDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="8" class="text-end text-muted">DISKON VOUCHER</td>
                                <td class="text-end text-danger">-{{ number_format($totalVoucherDiscount, 0, ',', '.') }}</td>
                                <td colspan="3"></td>
                            </tr>
                            @endif
                            @if (isset($totalPointDiscount) && $totalPointDiscount > 0)
                            <tr class="fw-bold">
                                <td colspan="8" class="text-end text-muted">DISKON POINT</td>
                                <td class="text-end text-danger">-{{ number_format($totalPointDiscount, 0, ',', '.') }}</td>
                                <td colspan="3"></td>
                            </tr>
                            @endif
                            <tr class="table-warning fw-bold">
                                <td colspan="8" class="text-center">GRAND TOTAL (OMSET)</td>
                                <td class="text-end">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="9" class="text-center">TOTAL MODAL (HPP)</td>
                                <td class="text-end">{{ number_format($sumModalVar, 0, ',', '.') }}</td>
                                <td colspan="2"></td>
                            </tr>
                            <tr class="{{ $labaBersih >= 0 ? 'table-success' : 'table-danger' }} fw-bold">
                                <td colspan="10" class="text-center">LABA / RUGI BERSIH</td>
                                <td class="text-end {{ $labaBersih >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($labaBersih, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 d-block mb-1"></i>
                                    Tidak ada data varian terjual pada periode ini
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
