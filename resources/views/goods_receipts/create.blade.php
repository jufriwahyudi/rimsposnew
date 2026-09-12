@extends('layouts.main.main')
@section('title', 'Purchase Orders')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Purchase Orders</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Purchase Orders</a></li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        @if (in_array($po->status, ['APPROVED', 'PARTIAL_RECEIVED']))
            <div class="col-sm-12">
                <div class="card rounded-4 p-2">
                    <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="d-flex align-items-start">
                            <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                                style="width: 35px; height: 35px;" class="me-2 mt-1">
                            <div>
                                <h5 class="fw-bold mb-0" style="color: #7c3aed">Goods Receipt - {{ $po->po_number }}</h5>
                                <small class="text-muted">{{ session('store_name') }}</small>
                            </div>
                        </div>
                        <a href="{{ route('po.create') }}" class="btn btn-success btn-sm mb-3"><i class="bi bi-plus"></i>
                            Tambah PO</a>
                    </div>
                    <div class="card-body">
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

                        <form id="goodsReceiptForm" method="post" action="{{ route('gr.store') }}">
                            @csrf
                            <input type="hidden" name="purchase_order_id" value="{{ $po->id }}">

                            <label>Tanggal Terima</label>
                            <input type="date" name="receipt_date" class="form-control mb-3" value="{{ date('Y-m-d') }}"
                                required>

                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="28%">Produk</th>
                                        <th class="text-center" width="12%">Satuan Beli</th>
                                        <th class="text-center" width="8%">Jumlah Order</th>
                                        <th class="text-center" width="8%">Sudah Diterima</th>
                                        <th class="text-center" width="8%">Belum Diterima</th>
                                        <th class="text-center" width="10%">Qty Terima</th>
                                        <th class="text-center" width="13%">No. Batch</th>
                                        <th class="text-center" width="13%">Tgl Expired</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($po->items as $item)
                                        @php
                                            $multiplier = ($item->unit_multiplier && $item->unit_multiplier > 0) ? $item->unit_multiplier : 1;
                                            $unitName = $item->unit_name ?? $item->variant->product->base_unit ?? 'Pcs';
                                            $baseUnit = $item->variant->product->base_unit ?? 'Pcs';
                                            $remainingQty = round($item->qty_order - $item->qty_received, 2);
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="fw-semibold text-dark">{{ $item->variant->variant_label }}</span><br>
                                                <small class="text-muted">SKU: {{ $item->variant->sku }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    {{ $unitName }}
                                                </span>
                                                @if ($multiplier > 1)
                                                    <br><small class="text-muted" style="font-size: 11px;">(1 {{ $unitName }} = {{ $multiplier }} {{ $baseUnit }})</small>
                                                @endif
                                            </td>
                                            <td class="text-end fw-semibold">{{ round($item->qty_order, 2) }}</td>
                                            <td class="text-end text-success">{{ round($item->qty_received, 2) }}</td>
                                            <td class="text-end text-danger fw-semibold">{{ $remainingQty }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $loop->index }}][purchase_item_id]"
                                                    value="{{ $item->id }}">
                                                <input type="number" step="any" name="items[{{ $loop->index }}][qty_received]"
                                                    class="form-control form-control-sm text-end gr-qty-input"
                                                    data-multiplier="{{ $multiplier }}"
                                                    data-base-unit="{{ $baseUnit }}"
                                                    data-target-note="#note_{{ $loop->index }}"
                                                    max="{{ $remainingQty }}"
                                                    value="{{ $remainingQty }}">
                                                @if ($multiplier > 1)
                                                    <small id="note_{{ $loop->index }}" class="text-primary d-block mt-1" style="font-size: 10.5px;">
                                                        = {{ $remainingQty * $multiplier }} {{ $baseUnit }} stok dasar
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $loop->index }}][batch_number]"
                                                    class="form-control form-control-sm"
                                                    placeholder="No. Batch (Opsional)">
                                            </td>
                                            <td>
                                                <input type="date" name="items[{{ $loop->index }}][expired_date]"
                                                    class="form-control form-control-sm">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <a href="{{ route('po.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i>
                                Kembali</a>
                            @if (in_array($po->status, ['APPROVED', 'PARTIAL_RECEIVED']))
                                <button class="btn btn-success"><i class="bi bi-save"></i> Simpan</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        @endif
        <!-- History Goods Receipt -->
        @if ($po->goodsReceipts->isNotEmpty())
            <div class="col-sm-12">
                <div class="card rounded-4 p-2 mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="d-flex align-items-start">
                            <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                                style="width: 35px; height: 35px;" class="me-2 mt-1">
                            <div>
                                <h5 class="fw-bold mb-0" style="color: #7c3aed">History Goods Receipt -
                                    {{ $po->po_number }}
                                </h5>
                                <small class="text-muted">{{ session('store_name') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th width="5%">No</th>
                                <th>Tanggal Terima</th>
                                <th class="text-center" width="10%">Total Item Diterima</th>
                                <th>Detail Item</th>
                                <th>Penerima</th>
                                <th class="text-center" width="5%">Aksi</th>
                            </tr>
                            @forelse ($po->goodsReceipts as $index => $gr)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ date('d-m-Y', strtotime($gr->receipt_date)) }}</td>
                                    <td class="text-end">
                                        {{ number_format($gr->items->sum('qty_received')) }}
                                    </td>
                                    <td>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($gr->items as $item)
                                                @php
                                                    $poIt = $item->purchaseOrderItem;
                                                    $pUnitName = $item->unit_name ?? $poIt->unit_name ?? $poIt->variant->product->base_unit ?? 'Pcs';
                                                    $bUnitName = $poIt->variant->product->base_unit ?? 'Pcs';
                                                    $bMultiplier = $item->unit_multiplier ?? $poIt->unit_multiplier ?? 1;
                                                    $baseTotal = $item->base_qty_received > 0 ? $item->base_qty_received : ($item->qty_received * $bMultiplier);
                                                @endphp
                                                <li class="mb-1">
                                                    <strong>{{ $poIt->variant->variant_label }}</strong> <small class="text-muted">({{ $poIt->variant->sku }})</small> :
                                                    <span class="fw-bold">{{ round($item->qty_received, 2) }} {{ $pUnitName }}</span>
                                                    @if ($bMultiplier > 1)
                                                        <span class="badge bg-light text-primary border ms-1">
                                                            = {{ round($baseTotal, 2) }} {{ $bUnitName }}
                                                        </span>
                                                    @endif
                                                    @if ($item->batch_number)
                                                        <span class="badge bg-secondary ms-1">Batch: {{ $item->batch_number }}</span>
                                                    @endif
                                                    @if ($item->expired_date)
                                                        <span class="badge bg-warning text-dark ms-1">ED: {{ \Carbon\Carbon::parse($item->expired_date)->format('d/m/Y') }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td>{{ $gr->receiver->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <form action="{{ route('gr.destroy', $gr->id) }}" method="POST" class="d-inline"
                                            onsubmit="confirmDelete(event)">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                        <a href="{{ route('gr.downloadBarcodes', $gr->id) }}"
                                            class="btn btn-success btn-sm" title="Download Barcode">
                                            <i class="fa-solid fa-barcode"></i>
                                        </a>

                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data goods receipt.</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection
@push('scripts')
    <script>
        // Real-time update live note stok dasar
        document.querySelectorAll('.gr-qty-input').forEach(input => {
            input.addEventListener('input', function() {
                const multiplier = parseFloat(this.dataset.multiplier) || 1;
                const baseUnit = this.dataset.baseUnit || '';
                const targetNote = document.querySelector(this.dataset.targetNote);
                if (targetNote && multiplier > 1) {
                    const qty = parseFloat(this.value) || 0;
                    const totalBase = Math.round(qty * multiplier);
                    targetNote.textContent = `= ${totalBase} ${baseUnit} stok dasar`;
                }
            });
        });

        // Validasi form sebelum submit
        document.getElementById('goodsReceiptForm')?.addEventListener('submit', function(event) {
            event.preventDefault();
            let valid = true;
            const qtyInputs = this.querySelectorAll('input[name^="items"][name$="[qty_received]"]');
            let totalReceived = 0;
            qtyInputs.forEach(input => {
                const max = parseFloat(input.getAttribute('max'));
                const value = parseFloat(input.value) || 0;
                totalReceived += value;

                if (value < 0 || value > max) {
                    valid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            if (totalReceived === 0) {
                valid = false;
            }

            if (!valid) {
                Swal.fire('Mohon periksa kembali jumlah qty terima yang diinput.');
            } else {
                Swal.fire({
                    title: 'Konfirmasi Penerimaan',
                    text: 'Apakah data barang, nomor batch, dan tanggal expired sudah benar?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, simpan ke stok',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            }
        });
        // Konfirmasi sebelum hapus goods receipt
        function confirmDelete(event) {
            event.preventDefault();
            const form = event.target;

            Swal.fire({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin menghapus goods receipt ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
@endpush
