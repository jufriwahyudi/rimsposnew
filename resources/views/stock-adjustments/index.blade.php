@extends('layouts.main.main')
@section('title', 'Stock Adjustment Draft')

@section('content')
    <div class="card rounded-4">
        <div class="card-header d-flex align-items-center justify-content-between my-2">
            <h5 class="fw-bold">Stock Adjustment ({{ $status }})</h5>
            @if ($status === 'DRAFT')
                <a href="{{ route('stock-adjustments.history-posted') }}" class="btn btn-sm btn-primary">
                    Riwayat Stock Adjustment
                </a>
            @else
                <a href="{{ route('stock-adjustments.index') }}" class="btn btn-sm btn-primary">
                    Draft Stock Adjustment
                </a>
            @endif
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal Efektif</th>
                        <th>Posisi</th>
                        <th>Source</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($adjustments as $adj)
                        <tr>
                            <td>{{ $adj->code }}</td>
                            <td>{{ $adj->effective_date }}</td>
                            <td>{{ ucfirst($adj->posisi) }}</td>
                            <td>{{ optional($adj->opname)->code }}</td>
                            <td class="text-center"><span class="badge bg-warning">{{ $adj->status }}</span></td>
                            <td class="text-center">
                                <a href="{{ route('stock-adjustments.show', $adj) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $adjustments->links() }}
        </div>
    </div>
@endsection
