@php
    $storeData = $reportData['store'] ?? [
        'name' => $store->name ?? 'RIMS POS',
        'address' => $store->address ?? '',
        'phone' => $store->phone ?? '',
    ];
    $shiftData = $reportData['shift'] ?? [];
    $finData   = $reportData['financial'] ?? [];
    $menuData  = $reportData['menu_sales'] ?? [];
    $paperType = $store->printer_type ?? '80mm';
@endphp

<div class="thermal-receipt-wrapper">
    <div class="thermal-receipt {{ $paperType === '58mm' ? 'paper-58mm' : 'paper-80mm' }}" id="printableReceiptContent">
        {{-- HEADER TOKO --}}
        <div class="receipt-header text-center">
            <h5 class="store-title mb-1 fw-bold">{{ strtoupper($storeData['name']) }}</h5>
            @if(!empty($storeData['address']))
                <p class="store-sub mb-0">{{ $storeData['address'] }}</p>
            @endif
            @if(!empty($storeData['phone']))
                <p class="store-sub mb-0">Telp: {{ $storeData['phone'] }}</p>
            @endif
            <div class="receipt-divider-solid"></div>
            <h6 class="report-title mb-0 fw-bold">LAPORAN TUTUP KASIR</h6>
            <div class="report-subtitle">TRANSAKSI PENJUALAN</div>
            <small class="text-muted d-block mt-1">Shift #{{ $register->id }}</small>
        </div>

        <div class="receipt-divider-dashed"></div>

        {{-- INFO SHIFT --}}
        <div class="receipt-section">
            <div class="receipt-row">
                <span class="lbl">Kasir</span>
                <span class="val">{{ $shiftData['cashier_name'] ?? ($register->cashier?->name ?? '-') }}</span>
            </div>
            <div class="receipt-row">
                <span class="lbl">Waktu Buka</span>
                <span class="val">{{ $shiftData['opened_at'] ?? ($register->opened_at ? $register->opened_at->format('d/m/Y H:i') : '-') }}</span>
            </div>
            <div class="receipt-row">
                <span class="lbl">Waktu Tutup</span>
                <span class="val">
                    @if($register->status === 'open')
                        <span class="badge-status-open">[ MASIH BUKA ]</span>
                    @else
                        {{ $shiftData['closed_at'] ?? ($register->closed_at ? $register->closed_at->format('d/m/Y H:i') : '-') }}
                    @endif
                </span>
            </div>
            @if($register->closedBy)
                <div class="receipt-row">
                    <span class="lbl">Ditutup Oleh</span>
                    <span class="val">{{ $register->closedBy->name }}</span>
                </div>
            @endif
        </div>

        <div class="receipt-divider-dashed"></div>

        {{-- RINCIAN KAS & PENJUALAN --}}
        <div class="receipt-section">
            <div class="receipt-row">
                <span class="lbl">Modal Kas Awal</span>
                <span class="val">Rp {{ number_format($finData['opening_cash'] ?? $register->opening_cash, 0, ',', '.') }}</span>
            </div>
            <div class="receipt-row">
                <span class="lbl">Penjualan Tunai (+)</span>
                <span class="val">Rp {{ number_format($finData['cash_sales'] ?? $register->total_cash_sales, 0, ',', '.') }}</span>
            </div>
            <div class="receipt-row">
                <span class="lbl">Penjualan Non-Tunai (+)</span>
                <span class="val">Rp {{ number_format($finData['non_cash_sales'] ?? $register->total_non_cash_sales, 0, ',', '.') }}</span>
            </div>

            {{-- Breakdown Non-Cash jika ada --}}
            @if(!empty($finData['non_cash_breakdown']) && count($finData['non_cash_breakdown']) > 0)
                <div class="receipt-sub-section">
                    @foreach($finData['non_cash_breakdown'] as $nc)
                        <div class="receipt-row sub-row">
                            <span class="lbl ms-2">└ {{ $nc['name'] }}</span>
                            <span class="val">Rp {{ number_format($nc['amount'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($finData['cash_in']) && $finData['cash_in'] > 0)
                <div class="receipt-row">
                    <span class="lbl">Kas Masuk Manual (+)</span>
                    <span class="val">Rp {{ number_format($finData['cash_in'], 0, ',', '.') }}</span>
                </div>
            @endif

            @if(!empty($finData['cash_out']) && $finData['cash_out'] > 0)
                <div class="receipt-row">
                    <span class="lbl">Kas Keluar Toko (-)</span>
                    <span class="val">Rp {{ number_format($finData['cash_out'], 0, ',', '.') }}</span>
                </div>
            @endif

            @if(!empty($finData['refund_cash']) && $finData['refund_cash'] > 0)
                <div class="receipt-row">
                    <span class="lbl">Refund Tunai (-)</span>
                    <span class="val">Rp {{ number_format($finData['refund_cash'], 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="receipt-divider-dashed"></div>

            <div class="receipt-row bold">
                <span class="lbl">TOTAL PENERIMAAN</span>
                <span class="val">Rp {{ number_format($finData['total_received'] ?? (($finData['cash_sales'] ?? 0) + ($finData['non_cash_sales'] ?? 0)), 0, ',', '.') }}</span>
            </div>
            <div class="receipt-row bold">
                <span class="lbl">TOTAL KAS SEHARUSNYA</span>
                <span class="val">Rp {{ number_format($finData['final_cash_balance'] ?? $register->expected_cash, 0, ',', '.') }}</span>
            </div>

            @if($register->status === 'closed' || $register->actual_cash !== null)
                <div class="receipt-row bold">
                    <span class="lbl">KAS FISIK AKTUAL</span>
                    <span class="val">Rp {{ number_format($register->actual_cash ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="receipt-divider-solid"></div>
                <div class="receipt-row bold highlight-box">
                    <span class="lbl">SELISIH KAS</span>
                    <span class="val">
                        @if(($register->cash_difference ?? 0) == 0)
                            PAS (Rp 0)
                        @elseif(($register->cash_difference ?? 0) > 0)
                            +Rp {{ number_format($register->cash_difference, 0, ',', '.') }} (LEBIH)
                        @else
                            -Rp {{ number_format(abs($register->cash_difference), 0, ',', '.') }} (KURANG)
                        @endif
                    </span>
                </div>
            @endif
        </div>

        <div class="receipt-divider-dashed"></div>

        {{-- REKAP JUMLAH TRANSAKSI --}}
        <div class="receipt-section">
            <div class="receipt-row">
                <span class="lbl">Transaksi Selesai</span>
                <span class="val">{{ $finData['completed_sales'] ?? 0 }}</span>
            </div>
            <div class="receipt-row">
                <span class="lbl">Trx Belum Terbayar</span>
                <span class="val">{{ $finData['unpaid_sales'] ?? 0 }}</span>
            </div>
        </div>

        <div class="receipt-divider-double"></div>

        {{-- REKAP PENJUALAN PRODUK / MENU --}}
        <div class="receipt-header text-center my-2">
            <h6 class="report-title mb-0 fw-bold">LAPORAN TUTUP KASIR</h6>
            <div class="report-subtitle">PENJUALAN MENU</div>
        </div>

        <div class="receipt-divider-dashed"></div>

        <div class="receipt-section">
            <div class="receipt-row bold">
                <span class="lbl">PRODUK TERJUAL</span>
                <span class="val">QTY</span>
            </div>
            <div class="receipt-divider-dashed"></div>

            @php $items = $menuData['items'] ?? []; @endphp
            @if(empty($items))
                <div class="text-center py-2 text-muted" style="font-size: 11px;">(Tidak ada penjualan produk)</div>
            @else
                @foreach($items as $item)
                    <div class="receipt-row">
                        <span class="lbl text-truncate" style="max-width: 70%;">{{ $item['name'] }}</span>
                        <span class="val fw-bold">{{ number_format($item['qty'], 0) }}</span>
                    </div>
                @endforeach
            @endif

            <div class="receipt-divider-dashed"></div>
            <div class="receipt-row bold">
                <span class="lbl">TOTAL ITEM TERJUAL</span>
                <span class="val">{{ number_format($menuData['total_qty'] ?? 0, 0) }}</span>
            </div>
        </div>

        @if(!empty($register->notes))
            <div class="receipt-divider-dashed"></div>
            <div class="receipt-section notes-section">
                <span class="lbl fw-bold d-block">Catatan:</span>
                <p class="val-notes mb-0">{{ $register->notes }}</p>
            </div>
        @endif

        <div class="receipt-divider-solid"></div>

        {{-- FOOTER --}}
        <div class="receipt-footer text-center">
            <p class="mb-1">Dicetak: {{ date('d/m/Y H:i:s') }}</p>
            <p class="mb-0 fw-bold">-- TERIMA KASIH --</p>
        </div>
    </div>
</div>

<style>
    .thermal-receipt-wrapper {
        display: flex;
        justify-content: center;
        background-color: #f1f5f9;
        padding: 15px;
        border-radius: 8px;
    }

    .thermal-receipt {
        background: #ffffff;
        font-family: 'Courier New', Courier, monospace;
        color: #1e293b;
        padding: 16px 14px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        line-height: 1.35;
    }

    .paper-58mm {
        width: 100%;
        max-width: 320px;
        font-size: 11px;
    }

    .paper-80mm {
        width: 100%;
        max-width: 400px;
        font-size: 12px;
    }

    .store-title {
        font-size: 14px;
        letter-spacing: 0.5px;
        color: #0f172a;
    }

    .store-sub {
        font-size: 10.5px;
        color: #64748b;
    }

    .report-title {
        font-size: 12px;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }

    .report-subtitle {
        font-size: 11px;
        font-weight: 600;
        color: #475569;
    }

    .receipt-divider-dashed {
        border-top: 1px dashed #64748b;
        margin: 7px 0;
    }

    .receipt-divider-solid {
        border-top: 1.5px solid #1e293b;
        margin: 8px 0;
    }

    .receipt-divider-double {
        border-top: 3px double #1e293b;
        margin: 10px 0;
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 2.5px;
    }

    .receipt-row.bold {
        font-weight: bold;
    }

    .receipt-row.sub-row {
        font-size: 10.5px;
        color: #475569;
    }

    .highlight-box {
        background: #f8fafc;
        padding: 4px 6px;
        border: 1px dashed #94a3b8;
        border-radius: 3px;
        margin-top: 4px;
    }

    .val-notes {
        font-size: 11px;
        color: #334155;
        white-space: pre-wrap;
    }

    .badge-status-open {
        background: #fef3c7;
        color: #b45309;
        padding: 1px 4px;
        font-weight: bold;
        border-radius: 2px;
    }

    .receipt-footer {
        font-size: 10px;
        color: #64748b;
        margin-top: 10px;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .thermal-receipt-wrapper, .thermal-receipt-wrapper * {
            visibility: visible;
        }
        .thermal-receipt-wrapper {
            position: absolute;
            left: 0;
            top: 0;
            padding: 0;
            background: #fff;
        }
        .thermal-receipt {
            box-shadow: none;
            border: none;
            width: 100%;
            padding: 0;
        }
    }
</style>
