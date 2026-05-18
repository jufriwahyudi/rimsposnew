@extends('layouts.main.main')
@section('title', 'Stock Opname ' . ucfirst($stockOpname->posisi))

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stock Opname {{ ucfirst($stockOpname->posisi) }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Stock Opname
                            {{ ucfirst($stockOpname->posisi) }}</a></li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <div class="d-flex align-items-start">
                        <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                            style="width: 35px; height: 35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Stock Opname Edit</h5>
                            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                        </div>
                    </div>
                    <a href="{{ route('stock-opname-periods.show', $stockOpname->stock_opname_period_id) }}"
                        class="btn btn-outline-primary btn-sm mb-3"><i class="bi bi-arrow-left"></i>
                        Kembali</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" id="searchInput" class="form-control form-control-sm"
                                placeholder="Cari SKU / Nama Produk / Varian...">
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                <option value="MATCH">MATCH</option>
                                <option value="EXCESS">EXCESS</option>
                                <option value="SHORTAGE">SHORTAGE</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 65vh;">
                        <table class="table table-bordered align-middle table-sm" id="opnameTable">
                            <thead class="table-light sticky-top">
                                <tr class="text-center">
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th>Varian</th>
                                    <th>Sistem</th>
                                    <th>Fisik</th>
                                    <th>Harga Beli</th>
                                    <th>Selisih</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockOpname->items as $item)
                                    <tr data-status="{{ $item->status }}">
                                        <td class="fw-semibold">{{ $item->productVariant->sku }}</td>
                                        <td>{{ $item->productVariant->product->nama_produk }}</td>
                                        <td>
                                            @foreach ($item->productVariant->variantAttributes->sortBy('attribute.urutan') as $attr)
                                                <span class="badge bg-secondary">
                                                    {{ $attr->value->nama }}
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="text-center system-qty">{{ round($item->system_qty) }}</td>
                                        <td class="text-center">
                                            <div class="input-group input-group-sm">
                                                <input type="number" min="0"
                                                    name="items[{{ $item->id }}][physical_qty]"
                                                    value="{{ round($item->physical_qty) }}"
                                                    class="form-control text-center physical-input">
                                                @if (!in_array($stockOpname->status, ['APPROVED', 'CANCELLED']))
                                                    <button class="btn btn-outline-secondary"
                                                        onclick="savePhysicalQty({{ $item->id }}, this)">
                                                        <i class="bi bi-save"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm text-center"
                                                name="items[{{ $item->id }}][harga_beli]"
                                                value="{{ $item->harga_beli ?? 0 }}" step="100" min="0">
                                        </td>
                                        <td class="text-center difference-qty">{{ round($item->difference_qty) }}</td>
                                        <td class="text-center status-text">
                                            <span
                                                class="badge bg-{{ $item->status === 'MATCH' ? 'success' : ($item->status === 'EXCESS' ? 'warning' : 'danger') }}">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <!-- Tombol approve kalau sudah selesai -->
                        @if (!in_array($stockOpname->status, ['APPROVED', 'CANCELLED']) && $stockOpname->period->status === 'OPEN')
                            <form action="{{ route('stock-opnames.approve', $stockOpname->id) }}" method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Apakah Anda yakin ingin menyetujui stock opname ini?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-success mt-3">
                                    <i class="bi bi-check-circle"></i> Selesai Stock Opname
                                </button>
                            </form>
                            <form action="{{ route('stock-opnames.cancel', $stockOpname->id) }}" method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Apakah Anda yakin ingin membatalkan stock opname ini?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger mt-3">
                                    <i class="bi bi-x-circle"></i> Batalkan Stock Opname
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="toast position-fixed top-1 end-0 m-3" id="notifyToast">
        <div class="toast-header">
            <strong class="me-auto">ZAYN</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Data berhasil disimpan
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;

            document.querySelectorAll('#opnameTable tbody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowStatus = row.dataset.status;

                const matchSearch = text.includes(search);
                const matchStatus = !status || rowStatus === status;

                row.style.display = (matchSearch && matchStatus) ? '' : 'none';
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.physical-input').forEach(input => {
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    const systemQty = parseInt(row.querySelector('.system-qty').innerText) || 0;
                    const physicalQty = parseInt(this.value) || 0;
                    const diff = physicalQty - systemQty;

                    row.querySelector('.difference-qty').innerText = diff;

                    const statusEl = row.querySelector('.status-text span');
                    row.dataset.status = 'MATCH';

                    if (diff > 0) {
                        statusEl.textContent = 'EXCESS';
                        statusEl.className = 'badge bg-warning';
                        row.dataset.status = 'EXCESS';
                    } else if (diff < 0) {
                        statusEl.textContent = 'SHORTAGE';
                        statusEl.className = 'badge bg-danger';
                        row.dataset.status = 'SHORTAGE';
                    } else {
                        statusEl.textContent = 'MATCH';
                        statusEl.className = 'badge bg-success';
                    }
                });
            });
        });

        function savePhysicalQty(itemId) {
            const input = document.querySelector(`input[name="items[${itemId}][physical_qty]"]`);
            const hargaBeliInput = document.querySelector(`input[name="items[${itemId}][harga_beli]"]`);
            const physicalQty = input.value;
            const hargaBeli = hargaBeliInput.value;

            fetch("{{ route('stock-opnames.update', ':stockOpnameItem') }}".replace(':stockOpnameItem', itemId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        physical_qty: physicalQty,
                        harga_beli: hargaBeli
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('#notifyToast .toast-body').innerText = 'Jumlah fisik berhasil disimpan';
                        $('button#button-addon' + itemId).removeClass('btn-outline-secondary').addClass(
                            'btn-outline-warning');
                    } else {
                        document.querySelector('#notifyToast .toast-body').innerText = 'Gagal menyimpan jumlah fisik';
                    }
                    const toastEl = document.getElementById('notifyToast');
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Terjadi kesalahan saat menyimpan.');
                });
        }
    </script>
@endpush
