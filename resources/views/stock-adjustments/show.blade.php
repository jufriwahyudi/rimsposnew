@extends('layouts.main.main')
@section('title', 'Detail Stock Adjustment')

@section('content')
    <div class="card rounded-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold">{{ $stockAdjustment->code }}</h5>
                <small class="text-muted">
                    {{ $stockAdjustment->notes }}
                </small>
            </div>
            <a href="{{ route('stock-adjustments.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
        </div>

        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Varian</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Cost</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockAdjustment->items as $item)
                        <tr class="{{ $item->cost <= 0 ? 'table-danger' : '' }}">
                            <td>{{ $item->productVariant->product->nama_produk }}</td>
                            <td>
                                @foreach ($item->productVariant->variantAttributes as $attr)
                                    <span class="badge bg-secondary">{{ $attr->value->nama }}</span>
                                @endforeach
                            </td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-end">
                                @if ($item->cost > 0)
                                    {{ number_format($item->cost, 0) }}
                                @else
                                    <form action="{{ route('stock-adjustments.update-item-cost', $item) }}" method="POST"
                                        class="d-flex">
                                        <div class="input-group input-group-sm">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" name="cost" class="form-control form-control-sm"
                                                placeholder="Masukkan Cost" required>
                                            <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
                                        </div>
                                    </form>
                                @endif
                            </td>
                            <td class="text-end">
                                {{ number_format($item->total_value, 0) }}
                            </td>
                            <td class="text-center">
                                @if ($item->cost <= 0)
                                    <span class="badge bg-danger">INVALID COST</span>
                                @else
                                    <span class="badge bg-success">OK</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="text-end mt-3">
                @if ($stockAdjustment->status !== 'POSTED')
                    <form action="{{ route('stock-adjustments.post', $stockAdjustment) }}" method="POST">
                        @csrf
                        <button class="btn btn-primary" {{ $hasZeroCost ? 'disabled' : '' }}>
                            Posting Adjustment
                        </button>
                    </form>
                @endif

                @if ($hasZeroCost)
                    <small class="text-danger d-block mt-1">
                        Tidak bisa diposting karena masih ada cost = 0
                    </small>
                @endif
            </div>

        </div>
    </div>
@endsection
