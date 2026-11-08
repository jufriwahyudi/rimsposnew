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
                            <small class="text-muted">{{ session('store_name') }}</small>
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
                                        <td>{{ $item->productVariant->variant_label }}</td>
                                        <td class="text-center text-nowrap">
                                            <span class="system-qty fw-semibold">{{ round($item->system_qty) }}</span>
                                            @if (!empty($item->productVariant->product->base_unit))
                                                <small class="text-muted ms-1">{{ $item->productVariant->product->base_unit }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $baseUnitName = $item->productVariant->product->base_unit ?: 'Pcs';
                                                $variantUnits = $item->productVariant->units && $item->productVariant->units->isNotEmpty()
                                                    ? $item->productVariant->units
                                                    : ($item->productVariant->product->units ?? collect());
                                                $calcUnits = $variantUnits
                                                    ->where('is_active', true)
                                                    ->sortByDesc('multiplier')
                                                    ->map(fn($u) => [
                                                        'name' => $u->name,
                                                        'multiplier' => (int) $u->multiplier,
                                                    ])
                                                    ->values()
                                                    ->toArray();
                                                // Pastikan satuan terkecil (multiplier = 1) selalu ada di urutan paling bawah
                                                $hasBase = collect($calcUnits)->contains(fn($u) => $u['multiplier'] === 1);
                                                if (!$hasBase) {
                                                    $calcUnits[] = [
                                                        'name' => $baseUnitName,
                                                        'multiplier' => 1,
                                                    ];
                                                }
                                            @endphp
                                            <div class="input-group input-group-sm">
                                                @if (!in_array($stockOpname->status, ['APPROVED', 'CANCELLED']))
                                                    <button type="button"
                                                        class="btn {{ count($calcUnits) > 1 ? 'btn-outline-primary' : 'btn-outline-secondary' }}"
                                                        title="Kalkulator Pintar Multi-Satuan"
                                                        data-item-id="{{ $item->id }}"
                                                        data-sku="{{ $item->productVariant->sku }}"
                                                        data-product-name="{{ $item->productVariant->product->nama_produk }} ({{ $item->productVariant->variant_label }})"
                                                        data-base-unit="{{ $baseUnitName }}"
                                                        data-units='@json($calcUnits)'
                                                        onclick="openUnitCalculator(this)">
                                                        <i class="bi bi-calculator"></i>
                                                    </button>
                                                @endif
                                                <input type="number" min="0"
                                                    name="items[{{ $item->id }}][physical_qty]"
                                                    value="{{ round($item->physical_qty) }}"
                                                    class="form-control text-center physical-input">
                                                @if (!in_array($stockOpname->status, ['APPROVED', 'CANCELLED']))
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        id="button-addon{{ $item->id }}"
                                                        title="Simpan Fisik"
                                                        onclick="savePhysicalQty('{{ $item->id }}')">
                                                        <i class="bi bi-save"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm text-center"
                                                name="items[{{ $item->id }}][harga_beli]"
                                                value="{{ $item->harga_beli ?? 0 }}" step="any" min="0">
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
    <div class="toast fade position-fixed top-0 end-0 m-3" id="notifyToast" role="status" aria-live="polite"
        aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000" style="z-index:2000;">
        <div class="toast-header">
            <strong class="me-auto">{{ config('app.name') }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Data berhasil disimpan
        </div>
    </div>

    {{-- Modal Kalkulator Pintar Multi-Satuan --}}
    <div class="modal fade" id="unitCalculatorModal" tabindex="-1" aria-labelledby="unitCalculatorModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-light rounded-top-4">
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="unitCalculatorModalLabel">
                            <i class="bi bi-calculator text-primary me-1"></i> Kalkulator Stok Multi-Satuan
                        </h6>
                        <small class="text-muted" id="calcProductTitle"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="text-muted">Masukkan jumlah fisik per satuan yang dihitung:</small>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="breakdownCurrentPhysical()" title="Pecah angka fisik saat ini ke satuan">
                                <i class="bi bi-diagram-3"></i> Pecah Fisik Saat Ini
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="resetUnitCalculator()">
                                Reset
                            </button>
                        </div>
                    </div>
                    <div id="calcUnitFieldsContainer"></div>
                    <div class="alert alert-primary bg-opacity-10 border-primary border-opacity-25 rounded-3 mt-3 mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Total Konversi Satuan Terkecil:</div>
                                <div class="small text-secondary" id="calcBreakdownFormula">-</div>
                            </div>
                            <div class="text-end">
                                <span class="fs-4 fw-bold text-primary" id="calcTotalBaseQty">0</span>
                                <span class="fw-semibold text-primary" id="calcBaseUnitLabel"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="applyUnitCalculator(false)">
                        Isi ke Kolom Fisik
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="applyUnitCalculator(true)">
                        <i class="bi bi-check2-circle me-1"></i> Terapkan & Simpan
                    </button>
                </div>
            </div>
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
                    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                        const toast = new bootstrap.Toast(toastEl);
                        toast.show();
                    } else {
                        // Fallback: toggle show class if Bootstrap toast not available
                        toastEl.classList.add('show');
                        setTimeout(() => toastEl.classList.remove('show'), 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Terjadi kesalahan saat menyimpan.');
                });
        }

        let currentCalcState = {
            itemId: null,
            baseUnit: 'Pcs',
            units: []
        };

        function openUnitCalculator(btn) {
            const itemId = btn.dataset.itemId;
            const sku = btn.dataset.sku || '';
            const productName = btn.dataset.productName || '';
            const baseUnit = btn.dataset.baseUnit || 'Pcs';
            let units = [];
            try {
                units = JSON.parse(btn.dataset.units || '[]');
            } catch (e) {
                units = [{
                    name: baseUnit,
                    multiplier: 1
                }];
            }

            currentCalcState = {
                itemId,
                baseUnit,
                units
            };

            document.getElementById('calcProductTitle').textContent = `${sku} — ${productName}`;
            document.getElementById('calcBaseUnitLabel').textContent = baseUnit;

            const container = document.getElementById('calcUnitFieldsContainer');
            container.innerHTML = '';

            units.forEach((unit, idx) => {
                const isBase = parseInt(unit.multiplier) === 1;
                const helperText = isBase
                    ? `Satuan Terkecil (1 ${unit.name})`
                    : `1 ${unit.name} = ${unit.multiplier} ${baseUnit}`;

                const row = document.createElement('div');
                row.className = 'row align-items-center mb-2';
                row.innerHTML = `
                    <div class="col-6">
                        <label class="form-label mb-0 fw-semibold">${unit.name}</label>
                        <div class="text-muted" style="font-size: 0.75rem;">${helperText}</div>
                    </div>
                    <div class="col-6">
                        <div class="input-group input-group-sm">
                            <input type="number" min="0" step="1"
                                class="form-control text-center calc-unit-input"
                                data-unit-name="${unit.name}"
                                data-multiplier="${unit.multiplier}"
                                placeholder="0"
                                oninput="recalcUnitModalTotal()"
                                onkeydown="if(event.key === 'Enter'){ event.preventDefault(); applyUnitCalculator(true); }">
                            <span class="input-group-text">${unit.name}</span>
                        </div>
                    </div>
                `;
                container.appendChild(row);
            });

            recalcUnitModalTotal();

            const modalEl = document.getElementById('unitCalculatorModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            setTimeout(() => {
                const firstInput = container.querySelector('.calc-unit-input');
                if (firstInput) firstInput.focus();
            }, 250);
        }

        function recalcUnitModalTotal() {
            let total = 0;
            const parts = [];
            document.querySelectorAll('#calcUnitFieldsContainer .calc-unit-input').forEach(input => {
                const qty = parseInt(input.value) || 0;
                const mult = parseInt(input.dataset.multiplier) || 1;
                const uName = input.dataset.unitName || '';
                if (qty > 0) {
                    const sub = qty * mult;
                    total += sub;
                    parts.push(`${qty} ${uName} (${sub})`);
                }
            });

            document.getElementById('calcTotalBaseQty').textContent = total;
            document.getElementById('calcBreakdownFormula').textContent = parts.length > 0
                ? parts.join(' + ')
                : 'Belum ada angka diinput';
        }

        function resetUnitCalculator() {
            document.querySelectorAll('#calcUnitFieldsContainer .calc-unit-input').forEach(input => {
                input.value = '';
            });
            recalcUnitModalTotal();
            const firstInput = document.querySelector('#calcUnitFieldsContainer .calc-unit-input');
            if (firstInput) firstInput.focus();
        }

        function breakdownCurrentPhysical() {
            if (!currentCalcState.itemId) return;
            const physInput = document.querySelector(`input[name="items[${currentCalcState.itemId}][physical_qty]"]`);
            let remaining = parseInt(physInput ? physInput.value : 0) || 0;

            document.querySelectorAll('#calcUnitFieldsContainer .calc-unit-input').forEach(input => {
                const mult = parseInt(input.dataset.multiplier) || 1;
                if (mult > 0 && remaining >= mult) {
                    const count = Math.floor(remaining / mult);
                    input.value = count > 0 ? count : '';
                    remaining = remaining % mult;
                } else {
                    input.value = '';
                }
            });
            recalcUnitModalTotal();
        }

        function applyUnitCalculator(autoSave = false) {
            if (!currentCalcState.itemId) return;
            const total = parseInt(document.getElementById('calcTotalBaseQty').textContent) || 0;
            const physInput = document.querySelector(`input[name="items[${currentCalcState.itemId}][physical_qty]"]`);

            if (physInput) {
                physInput.value = total;
                physInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            }

            const modalEl = document.getElementById('unitCalculatorModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            if (autoSave) {
                savePhysicalQty(currentCalcState.itemId);
            }
        }
    </script>
@endpush
