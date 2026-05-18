@extends('layouts.main.main')
@section('title', 'Detail Stock Transfer')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card rounded-4 p-2">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Stock Transfer</h5>
                        <small class="text-muted">{{ $transfer->transfer_code }}</small>
                        <span class="badge bg-secondary">
                            {{ $transfer->status }}
                        </span>
                    </div>
                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary btn-sm">
                        Kembali
                    </a>
                </div>

                <div class="card-body">

                    {{-- ALERT --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- INFO --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Dari</strong><br>
                            {{ ucfirst($transfer->from_position) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Ke</strong><br>
                            {{ ucfirst($transfer->to_position) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Tanggal Request</strong><br>
                            {{ $transfer->request_date }}
                        </div>
                    </div>

                    <hr>

                    {{-- =========================
                 | APPROVE / REJECT / CANCEL
                 ========================= --}}
                    @if ($transfer->status === 'REQUESTED')
                        <form method="POST" action="{{ route('stock-transfers.update-status', $transfer->id) }}">
                            @csrf
                            @method('PUT')

                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th width="15%" class="text-center">
                                            Stok {{ ucfirst($transfer->from_position) }}
                                        </th>
                                        <th width="15%" class="text-center">Qty Request</th>

                                        @if (isWarehouse() || isAdmin())
                                            <th width="15%" class="text-center">Qty Approve</th>
                                        @endif
                                        @if (isStore() && $transfer->status !== 'REQUESTED')
                                            <th width="15%" class="text-center">Qty Approve</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transfer->items as $item)
                                        <tr>
                                            <td>
                                                {{ $item->variant->product->nama_produk }}<br>
                                                <small class="text-muted">
                                                    SKU: {{ $item->variant->sku }}
                                                </small>
                                            </td>

                                            <td class="text-center">
                                                {{ $transfer->from_position === 'warehouse' ? $item->variant->stok_warehouse : $item->variant->stok_store }}
                                            </td>

                                            <td class="text-center">
                                                {{ round($item->qty_requested) }}
                                            </td>

                                            @if (isWarehouse() || isAdmin())
                                                <td class="text-center">
                                                    <input type="number" name="items[{{ $item->id }}]"
                                                        class="form-control" min="0"
                                                        max="{{ round($item->qty_requested) }}"
                                                        value="{{ round($item->qty_requested) }}" required>
                                                </td>
                                            @endif
                                            @if (isStore() && $transfer->status !== 'REQUESTED')
                                                {{ round($item->qty_approved) }}
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="text-end">
                                <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary me-2">
                                    Kembali
                                </a>
                                @if (isWarehouse() || isAdmin())
                                    <button type="submit" name="status" value="APPROVED" class="btn btn-success">
                                        Setujui Transfer
                                    </button>

                                    <button type="submit" name="status" value="REJECTED" class="btn btn-danger">
                                        Tolak Transfer
                                    </button>
                                @endif
                                @if (isStore())
                                    <button type="submit" name="status" value="CANCELLED" class="btn btn-warning">
                                        Batalkan Pengajuan
                                    </button>
                                @endif
                            </div>
                        </form>
                    @endif

                    {{-- =========================
                    | RECEIVE
                    ========================= --}}
                    @if (in_array($transfer->status, ['APPROVED', 'PARTIAL_RECEIVED']))
                        <hr>
                        <h6 class="fw-bold mb-3">Penerimaan Stok</h6>

                        <form method="POST" action="{{ route('stock-transfers.receive', $transfer->id) }}">
                            @csrf
                            @method('PUT')

                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th width="15%" class="text-center">Qty Approved</th>
                                        <th width="15%" class="text-center">Qty Received</th>
                                        <th width="15%" class="text-center">Qty Receive</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transfer->items as $item)
                                        <tr>
                                            <td>
                                                {{ $item->variant->product->nama_produk }}<br>
                                                <small class="text-muted">
                                                    SKU: {{ $item->variant->sku }}
                                                </small>
                                            </td>

                                            <td class="text-center">
                                                {{ round($item->qty_approved) }}
                                            </td>
                                            <td class="text-center">
                                                {{ round($item->qty_received) }}
                                            </td>

                                            <td class="text-center">
                                                @if (isStore() && $transfer->transfer_type === 'REQUEST')
                                                    <input type="number" name="items[{{ $item->id }}]"
                                                        class="form-control" min="0"
                                                        max="{{ round($item->qty_approved - $item->qty_received) }}"
                                                        value="{{ round($item->qty_approved - $item->qty_received) }}"
                                                        required>
                                                @elseif (isWarehouse() && $transfer->transfer_type === 'RETURN')
                                                    <input type="number" name="items[{{ $item->id }}]"
                                                        class="form-control" min="0"
                                                        max="{{ round($item->qty_approved - $item->qty_received) }}"
                                                        value="{{ round($item->qty_approved - $item->qty_received) }}"
                                                        required>
                                                @else
                                                    {{ $item->qty_received ?? 0 }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="text-end">
                                @if (isStore() && $transfer->transfer_type === 'REQUEST')
                                    <button type="submit" class="btn btn-primary">
                                        Terima & Update Stok
                                    </button>
                                @endif
                                @if (isWarehouse() && $transfer->transfer_type === 'RETURN')
                                    <button type="submit" class="btn btn-primary">
                                        Terima & Update Stok
                                    </button>
                                @endif
                            </div>
                        </form>
                    @endif

                    {{-- =========================
                    | READ ONLY (RECEIVED)
                    ========================= --}}
                    @if ($transfer->status === 'RECEIVED')
                        <hr>
                        <h6 class="fw-bold mb-3">Detail Penerimaan</h6>

                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th width="15%" class="text-center">Qty Approve</th>
                                    <th width="15%" class="text-center">Qty Receive</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transfer->items as $item)
                                    <tr>
                                        <td>
                                            {{ $item->variant->product->nama_produk }}<br>
                                            <small class="text-muted">
                                                SKU: {{ $item->variant->sku }}
                                            </small>
                                        </td>
                                        <td class="text-center">{{ round($item->qty_approved) }}</td>
                                        <td class="text-center">{{ round($item->qty_received) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    @if (in_array($transfer->status, ['REJECTED', 'CANCELLED']))
                        <hr>
                        <h6 class="fw-bold mb-3">Detail Penerimaan</h6>

                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th width="15%" class="text-center">Qty Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transfer->items as $item)
                                    <tr>
                                        <td>
                                            {{ $item->variant->product->nama_produk }}<br>
                                            <small class="text-muted">
                                                SKU: {{ $item->variant->sku }}
                                            </small>
                                        </td>
                                        <td class="text-center">{{ round($item->qty_requested) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                </div>
            </div>
        </div>
        @if (in_array($transfer->status, ['APPROVED', 'PARTIAL_RECEIVED', 'RECEIVED']) && $transfer->stockBatches->count() > 0)
            <div class="col-12 mt-3">
                <div class="card rounded-4 p-2">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0">Batch Stok Transfer</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th width="15%" class="text-center">Batch ID</th>
                                    <th width="15%" class="text-center">Posisi</th>
                                    <th width="15%" class="text-center">Tanggal Masuk</th>
                                    <th width="15%" class="text-center">Qty Awal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transfer->stockBatches as $batch)
                                    <tr>
                                        <td>
                                            {{ $batch->variant->product->nama_produk }}<br>
                                            <small class="text-muted">
                                                SKU: {{ $batch->variant->sku }}
                                            </small>
                                        </td>
                                        <td class="text-center">StockBatch#{{ $batch->id }}</td>
                                        <td class="text-center">{{ ucfirst($batch->posisi) }}</td>
                                        <td class="text-center">{{ $batch->tanggal_masuk->format('Y-m-d') }}</td>
                                        <td class="text-center">{{ round($batch->qty_awal) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (isStore())
                        <div class="card-footer text-end">
                            <form method="POST" action="{{ route('stock-transfers.rollback', $transfer->id) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Apakah Anda yakin ingin melakukan rollback stock transfer ini? Stok akan dikembalikan ke posisi semula.')">
                                    Rollback Stock Transfer
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
