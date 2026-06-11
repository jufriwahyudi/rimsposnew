<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th width="15%">Tanggal</th>
                <th class="text-center" width="10%">Tipe</th>
                <th class="text-end" width="10%">Qty</th>
                <th class="text-end" width="10%">Posisi</th>
                <th class="text-end" width="10%">Harga Modal</th>
                <th>Referensi</th>
            </tr>
        </thead>
        <tbody>
            @php $saldo = 0; @endphp

            @forelse ($movements as $row)
                @php
                    $effect = 0;

                    switch ($row->tipe) {
                        case 'in':
                            $effect = $row->qty;
                            break;
                        case 'out':
                            $effect = -$row->qty;
                            break;
                        case 'adjust':
                            $effect = $row->direction === 'in' ? $row->qty : -$row->qty;
                            break;
                        case 'transfer':
                            $effect = $row->direction === 'in' ? $row->qty : -$row->qty;
                            break;
                    }

                    $saldo += $effect;
                @endphp

                <tr>
                    <td>{{ $row->tanggal->format('d-m-Y H:i') }}</td>

                    <td class="text-center">
                        <span
                            class="badge
                            @if ($row->tipe === 'in') bg-success
                            @elseif ($row->tipe === 'out') bg-danger
                            @elseif ($row->tipe === 'adjust') bg-secondary
                            @else bg-warning @endif
                        ">
                            {{ strtoupper($row->tipe) }}
                        </span>
                    </td>

                    <td class="text-end">
                        <span class="{{ $effect >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $effect > 0 ? '+' : '' }}{{ number_format($effect) }}
                        </span>
                    </td>

                    <td class="text-end fw-bold">
                        {{ number_format($saldo) }}
                    </td>

                    <td class="text-end">
                        {{ number_format($row->batch->harga_beli ?? 0) }}
                    </td>

                    <td>
                        {{ $row->ref_type }}
                        @if ($row->ref_id)
                            #{{ $row->ref_id }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Tidak ada mutasi
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
