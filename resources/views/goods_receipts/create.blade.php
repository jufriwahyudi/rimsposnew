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
                                <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
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

                            <table class="table table-bordered">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center" width="8%">Jumlah Order</th>
                                    <th class="text-center" width="8%">Sudah Diterima</th>
                                    <th class="text-center" width="8%">Belum Diterima</th>
                                    <th class="text-center" width="15%">Qty Terima</th>
                                </tr>
                                @foreach ($po->items as $item)
                                    <tr>
                                        <td>{{ $item->variant->product->nama_produk }} - {{ $item->variant->sku }}</td>
                                        <td class="text-end">{{ round($item->qty_order) }}</td>
                                        <td class="text-end">{{ round($item->qty_received) }}</td>
                                        <td class="text-end">{{ round($item->qty_order - $item->qty_received) }}</td>
                                        <td>
                                            <input type="hidden" name="items[{{ $loop->index }}][purchase_item_id]"
                                                value="{{ $item->id }}">
                                            <input type="number" name="items[{{ $loop->index }}][qty_received]"
                                                class="form-control" max="{{ $item->qty_order - $item->qty_received }}">
                                        </td>
                                    </tr>
                                @endforeach
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
                                <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
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
                                        <ul class="mb-0">
                                            @foreach ($gr->items as $item)
                                                <li>
                                                    {{ $item->purchaseOrderItem->variant->product->nama_produk }} -
                                                    {{ $item->purchaseOrderItem->variant->sku }} :
                                                    {{ number_format($item->qty_received) }}
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
                                         
                                            <a href="{{ route('gr.downloadBarcodes', $gr->id) }}" class="btn btn-success btn-sm" title="Download Barcode">
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
        // Validasi form sebelum submit
        document.getElementById('goodsReceiptForm').addEventListener('submit', function(event) {
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
                    title: 'Konfirmasi',
                    text: 'Apakah data sudah benar?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, simpan',
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
