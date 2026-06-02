<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="min-width: 250px;">Komponen Laporan Finansial</th>
                @foreach ($stores as $store)
                    <th class="text-end" style="min-width: 150px;">{{ $store->name }}</th>
                @endforeach
                <th class="text-end bg-indigo-subtle text-indigo fw-bold" style="min-width: 180px;">Total Konsolidasi</th>
            </tr>
        </thead>
        <tbody>
            {{-- OMSET / PENJUALAN --}}
            <tr class="fw-semibold">
                <td class="text-slate-800"><i class="bx bx-plus-circle text-success me-1"></i> Pendapatan Penjualan (Omset)</td>
                @php $globalOmset = 0; @endphp
                @foreach ($stores as $store)
                    @php $omset = $report['omset'][$store->id] ?? 0; $globalOmset += $omset; @endphp
                    <td class="text-end text-success">Rp {{ number_format($omset, 0, ',', '.') }}</td>
                @endforeach
                <td class="text-end bg-indigo-subtle text-success fw-bold">Rp {{ number_format($globalOmset, 0, ',', '.') }}</td>
            </tr>

            {{-- HPP --}}
            <tr class="fw-semibold text-muted">
                <td><i class="bx bx-minus-circle text-danger me-1"></i> Harga Pokok Penjualan (HPP)</td>
                @php $globalHpp = 0; @endphp
                @foreach ($stores as $store)
                    @php $hpp = $report['hpp'][$store->id] ?? 0; $globalHpp += $hpp; @endphp
                    <td class="text-end text-danger">Rp {{ number_format($hpp, 0, ',', '.') }}</td>
                @endforeach
                <td class="text-end bg-indigo-subtle text-danger fw-bold">Rp {{ number_format($globalHpp, 0, ',', '.') }}</td>
            </tr>

            {{-- LABA KOTOR --}}
            <tr class="fw-bold bg-light" style="border-top: 2px solid #ddd; border-bottom: 2px solid #ddd;">
                <td class="text-indigo"><i class="bx bx-calculator me-1"></i> PENDAPATAN KOTOR (GROSS PROFIT)</td>
                @php $globalGross = 0; @endphp
                @foreach ($stores as $store)
                    @php $gross = $report['pendapatan_kotor'][$store->id] ?? 0; $globalGross += $gross; @endphp
                    <td class="text-end text-indigo">Rp {{ number_format($gross, 0, ',', '.') }}</td>
                @endforeach
                <td class="text-end bg-indigo-subtle text-indigo fw-bold">Rp {{ number_format($globalGross, 0, ',', '.') }}</td>
            </tr>

            {{-- BIAYA OPERASIONAL TITLE --}}
            <tr class="table-secondary fw-semibold">
                <td colspan="{{ count($stores) + 2 }}">Biaya Operasional (Expenses)</td>
            </tr>

            {{-- BIAYA PER KATEGORI --}}
            @foreach ($categories as $cat)
                @php
                    // Check if this category has any expense in the selected stores
                    $hasExpense = false;
                    $rowTotal = 0;
                    foreach ($stores as $store) {
                        $amt = $report['biaya'][$cat->id][$store->id] ?? 0;
                        if ($amt > 0) $hasExpense = true;
                        $rowTotal += $amt;
                    }
                @endphp
                @if ($hasExpense)
                    <tr>
                        <td class="ps-4 text-slate-700">{{ $cat->name }}</td>
                        @foreach ($stores as $store)
                            @php $amt = $report['biaya'][$cat->id][$store->id] ?? 0; @endphp
                            <td class="text-end text-muted">Rp {{ number_format($amt, 0, ',', '.') }}</td>
                        @endforeach
                        <td class="text-end bg-indigo-subtle text-slate-800 fw-semibold">Rp {{ number_format($rowTotal, 0, ',', '.') }}</td>
                    </tr>
                @endif
            @endforeach

            {{-- TOTAL BIAYA --}}
            <tr class="fw-semibold text-muted" style="border-top: 1px solid #eee;">
                <td><i class="bx bx-minus-circle text-danger me-1"></i> Total Biaya Operasional</td>
                @php $globalBiaya = 0; @endphp
                @foreach ($stores as $store)
                    @php $biaya = $report['total_biaya'][$store->id] ?? 0; $globalBiaya += $biaya; @endphp
                    <td class="text-end text-danger">Rp {{ number_format($biaya, 0, ',', '.') }}</td>
                @endforeach
                <td class="text-end bg-indigo-subtle text-danger fw-bold">Rp {{ number_format($globalBiaya, 0, ',', '.') }}</td>
            </tr>

            {{-- LABA BERSIH --}}
            <tr class="fw-bold table-info" style="border-top: 3px double #4f46e5; border-bottom: 3px double #4f46e5;">
                <td class="text-indigo-emphasis"><i class="bx bx-money me-1"></i> LABA / (RUGI) BERSIH (NET PROFIT)</td>
                @php $globalNet = 0; @endphp
                @foreach ($stores as $store)
                    @php $net = $report['laba_rugi'][$store->id] ?? 0; $globalNet += $net; @endphp
                    <td class="text-end {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($net, 0, ',', '.') }}
                    </td>
                @endforeach
                <td class="text-end bg-indigo-subtle {{ $globalNet >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                    Rp {{ number_format($globalNet, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="p-3 border-top bg-light text-muted" style="font-size: 12px;">
    <span><i class="bx bx-info-circle me-1"></i> Laporan Laba Rugi di atas ditarik untuk rentang tanggal <strong>{{ \Carbon\Carbon::parse($mulai)->format('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($akhir)->format('d M Y') }}</strong>.</span>
</div>
