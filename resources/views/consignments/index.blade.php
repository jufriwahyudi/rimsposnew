@extends('layouts.main.main')

@section('title', 'Data Penitip & Item Konsinyasi')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="material-icons-outlined me-2 align-middle text-primary">handshake</i>Data Penitip & Item Titipan</h4>
                <p class="text-muted mb-0 small">Kelola data penitip (vendor konsinyasi) dan daftar item/kue yang biasa dititipkan.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('consignments.daily') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="material-icons-outlined me-1 align-middle" style="font-size: 16px;">today</i> Titipan Harian (Pagi/Sore)
                </a>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" onclick="openConsignorModal()">
                    <i class="material-icons-outlined me-1 align-middle" style="font-size: 16px;">add</i> Tambah Penitip Baru
                </button>
            </div>
        </div>

        @if($consignors->isEmpty())
        <div class="card shadow-sm border-0 rounded-3 p-5 text-center">
            <div class="my-4">
                <span class="material-icons-outlined text-muted" style="font-size: 64px;">storefront</span>
            </div>
            <h5 class="fw-semibold text-dark">Belum Ada Data Penitip</h5>
            <p class="text-muted mx-auto" style="max-width: 450px;">
                Daftarkan penitip pertama Anda (misal: Bu Siti, Pak Joko, atau Vendor Eskrim) beserta item/kue yang biasa mereka titipkan.
            </p>
            <div>
                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="openConsignorModal()">
                    <i class="material-icons-outlined me-1 align-middle">add</i> Tambah Penitip Pertama
                </button>
            </div>
        </div>
        @else
        <div class="row g-3">
            @foreach($consignors as $consignor)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card shadow-sm border-0 rounded-3 h-100 hover-shadow transition-all" style="border-top: 4px solid #4f46e5 !important;">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge {{ $consignor->tipe === 'daily' ? 'bg-light-success text-success' : 'bg-light-info text-info' }} fw-semibold px-2 py-1 mb-1">
                                        {{ $consignor->tipe === 'daily' ? 'Titipan Harian (Kue Basah)' : 'Konsinyasi Periodik (Freezer)' }}
                                    </span>
                                    <h5 class="fw-bold text-dark mb-0">{{ $consignor->nama_penitip }}</h5>
                                    @if($consignor->kode_penitip)
                                    <small class="text-muted">Kode: {{ $consignor->kode_penitip }}</small>
                                    @endif
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown">
                                        <i class="material-icons-outlined">more_vert</i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="editConsignor({{ json_encode($consignor) }})">
                                                <i class="material-icons-outlined me-2 align-middle text-warning" style="font-size: 18px;">edit</i> Edit Penitip
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteConsignor({{ $consignor->id }}, '{{ $consignor->nama_penitip }}')">
                                                <i class="material-icons-outlined me-2 align-middle" style="font-size: 18px;">delete</i> Hapus
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="small text-muted mb-3">
                                <div><i class="material-icons-outlined me-1 align-middle" style="font-size: 14px;">call</i> {{ $consignor->telepon ?: 'Tidak ada telepon' }}</div>
                                @if($consignor->nomor_rekening)
                                <div><i class="material-icons-outlined me-1 align-middle" style="font-size: 14px;">account_balance</i> {{ $consignor->nama_bank }} - {{ $consignor->nomor_rekening }}</div>
                                @endif
                            </div>

                            <div class="border-top pt-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small">Daftar Menu Titipan ({{ $consignor->items->count() }})</span>
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 rounded-pill" style="font-size: 11px;" onclick="openAddItemModal({{ $consignor->id }}, '{{ $consignor->nama_penitip }}')">
                                        + Tambah Menu
                                    </button>
                                </div>

                                @if($consignor->items->isEmpty())
                                <div class="text-center py-2 text-muted small bg-light rounded">
                                    Belum ada menu terdaftar.<br>
                                    <a href="javascript:void(0)" class="text-primary fw-semibold" onclick="openAddItemModal({{ $consignor->id }}, '{{ $consignor->nama_penitip }}')">+ Tambah Item Sekarang</a>
                                </div>
                                @else
                                <ul class="list-group list-group-flush small">
                                    @foreach($consignor->items as $item)
                                    <li class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center bg-transparent border-bottom">
                                        <div>
                                            <strong class="text-dark">{{ $item->nama_item }}</strong>
                                            @if($item->variant)
                                            <span class="badge bg-light text-muted border px-1" style="font-size: 9px;">POS: {{ $item->variant->variant_name }}</span>
                                            @endif
                                            <div class="text-muted" style="font-size: 10px;">
                                                Setor: <span class="text-success fw-semibold">Rp {{ number_format($item->harga_setor, 0, ',', '.') }}</span> | Jual: Rp {{ number_format($item->harga_jual, 0, ',', '.') }}
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button class="btn btn-link text-secondary p-0 hover-primary" title="Edit item titipan" onclick="editItem({{ json_encode($item) }}, '{{ $consignor->nama_penitip }}')">
                                                <i class="material-icons-outlined" style="font-size: 16px;">edit</i>
                                            </button>
                                            <button class="btn btn-link text-danger p-0" title="Hapus item" onclick="deleteItem({{ $item->id }}, '{{ $item->nama_item }}')">
                                                <i class="material-icons-outlined" style="font-size: 16px;">close</i>
                                            </button>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-2 d-flex justify-content-between align-items-center text-muted small">
                            <span>Riwayat Sesi: <strong>{{ $consignor->daily_consignments_count }} kali</strong></span>
                            <a href="{{ route('consignments.daily', ['consignor_id' => $consignor->id]) }}" class="text-primary fw-semibold text-decoration-none">
                                Catat Titip <i class="material-icons-outlined align-middle" style="font-size: 14px;">arrow_forward</i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- MODAL TAMBAH / EDIT PENITIP --}}
<div class="modal fade" id="modalConsignor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalConsignorTitle">Tambah Penitip Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formConsignor">
                <div class="modal-body p-4">
                    <input type="hidden" id="consignorId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Penitip / Vendor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_penitip" required placeholder="Contoh: Bu Siti (Aneka Risol)">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Kode / Inisial</label>
                            <input type="text" class="form-control text-uppercase" id="kode_penitip" placeholder="Contoh: BST">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">No. WhatsApp / HP</label>
                            <input type="text" class="form-control" id="telepon" placeholder="08xxxxxxxx">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Titipan</label>
                        <select class="form-select" id="tipe">
                            <option value="daily">Harian (Kue Basah / Nasi Bungkus / Bayar Sore)</option>
                            <option value="periodic">Periodik (Eskrim / Freezer / Minuman / Bayar Mingguan)</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label class="form-label fw-semibold">Bank</label>
                            <input type="text" class="form-control" id="nama_bank" placeholder="BCA / BRI / BSI">
                        </div>
                        <div class="col-7">
                            <label class="form-label fw-semibold">Nomor Rekening</label>
                            <input type="text" class="form-control" id="nomor_rekening" placeholder="Opsional jika bayar transfer">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat / Catatan</label>
                        <textarea class="form-control" id="alamat" rows="2" placeholder="Alamat atau keterangan tambahan"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnSaveConsignor">Simpan Penitip</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH ITEM TITIPAN --}}
<div class="modal fade" id="modalItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalItemTitle">Tambah Menu Titipan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formItem">
                <div class="modal-body p-4">
                    <input type="hidden" id="itemConsignorId">
                    <input type="hidden" id="editItemId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Item / Kue <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="item_nama" required placeholder="Contoh: Risol Mayo">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga Setor (Hak Penitip) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" class="form-control" id="item_harga_setor" required min="0" placeholder="2000">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Nominal yang dibayarkan ke ibu penitip.</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga Jual Resmi Kasir <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" class="form-control" id="item_harga_jual" required min="0" placeholder="2500">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Harga yang dibayar mahasiswa di kasir.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tombol Produk POS Kasir Terkait <span class="text-danger">*</span></label>
                        @if($variants->isEmpty())
                        <div class="alert alert-warning py-2 px-3 small mb-2 border-0 rounded-3">
                            <i class="material-icons-outlined align-middle me-1" style="font-size: 16px;">warning</i>
                            Belum ada produk di Master Produk Toko. Silakan buat produk kasir terlebih dahulu di menu <a href="{{ route('produk.index') }}" target="_blank" class="fw-bold text-dark text-decoration-underline">Produk &gt; Pengaturan Produk</a> (misal: "Kue Basah Kantin").
                        </div>
                        @endif
                        <select class="form-select" id="item_product_variant_id" required onchange="onVariantSelected(this)">
                            <option value="" disabled selected>-- Pilih Tombol Produk Kasir (Wajib) --</option>
                            @foreach($variants as $variant)
                            <option value="{{ $variant->id }}" data-price="{{ (int) $variant->harga_jual }}" data-name="{{ $variant->variant_name }}">
                                {{ $variant->product->nama_produk ?? 'Produk' }} - {{ $variant->variant_name }} (Rp {{ number_format($variant->harga_jual, 0, ',', '.') }})
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size: 11px;">Tombol kasir yang ditekan kasir di layar POS saat ada mahasiswa membeli item ini.</small>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';

    function openConsignorModal() {
        document.getElementById('consignorId').value = '';
        document.getElementById('formConsignor').reset();
        document.getElementById('modalConsignorTitle').textContent = 'Tambah Penitip Baru';
        new bootstrap.Modal(document.getElementById('modalConsignor')).show();
    }

    function editConsignor(consignor) {
        document.getElementById('consignorId').value = consignor.id;
        document.getElementById('nama_penitip').value = consignor.nama_penitip;
        document.getElementById('kode_penitip').value = consignor.kode_penitip || '';
        document.getElementById('telepon').value = consignor.telepon || '';
        document.getElementById('tipe').value = consignor.tipe;
        document.getElementById('nama_bank').value = consignor.nama_bank || '';
        document.getElementById('nomor_rekening').value = consignor.nomor_rekening || '';
        document.getElementById('alamat').value = consignor.alamat || '';
        document.getElementById('modalConsignorTitle').textContent = 'Edit Data Penitip';
        new bootstrap.Modal(document.getElementById('modalConsignor')).show();
    }

    document.getElementById('formConsignor').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('consignorId').value;
        const url = id ? `/consignments/consignors/${id}` : '{{ route("consignments.consignors.store") }}';
        const method = id ? 'PUT' : 'POST';

        const payload = {
            nama_penitip: document.getElementById('nama_penitip').value,
            kode_penitip: document.getElementById('kode_penitip').value,
            telepon: document.getElementById('telepon').value,
            tipe: document.getElementById('tipe').value,
            nama_bank: document.getElementById('nama_bank').value,
            nomor_rekening: document.getElementById('nomor_rekening').value,
            alamat: document.getElementById('alamat').value,
            _token: csrfToken
        };

        fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Terjadi kesalahan.');
                }
            })
            .catch(err => alert('Gagal menyimpan penitip.'));
    });

    function deleteConsignor(id, name) {
        if (!confirm(`Hapus penitip ${name}?`)) return;

        fetch(`/consignments/consignors/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message);
            });
    }

    function onVariantSelected(selectEl) {
        const selected = selectEl.options[selectEl.selectedIndex];
        const price = selected.getAttribute('data-price');
        const hargaJualInput = document.getElementById('item_harga_jual');
        if (price) {
            hargaJualInput.value = price;
        }
    }

    function openAddItemModal(consignorId, consignorName) {
        document.getElementById('editItemId').value = '';
        document.getElementById('itemConsignorId').value = consignorId;
        document.getElementById('formItem').reset();
        document.getElementById('modalItemTitle').textContent = `Tambah Menu Titipan: ${consignorName}`;
        new bootstrap.Modal(document.getElementById('modalItem')).show();
    }

    function editItem(item, consignorName) {
        document.getElementById('editItemId').value = item.id;
        document.getElementById('itemConsignorId').value = item.consignor_id;
        document.getElementById('item_nama').value = item.nama_item;
        document.getElementById('item_harga_setor').value = Math.round(item.harga_setor);
        document.getElementById('item_harga_jual').value = Math.round(item.harga_jual);
        document.getElementById('item_product_variant_id').value = item.product_variant_id || '';
        document.getElementById('modalItemTitle').textContent = `Edit Menu Titipan: ${item.nama_item}`;
        new bootstrap.Modal(document.getElementById('modalItem')).show();
    }

    document.getElementById('formItem').addEventListener('submit', function(e) {
        e.preventDefault();
        const consignorId = document.getElementById('itemConsignorId').value;
        const editItemId = document.getElementById('editItemId').value;

        const payload = {
            nama_item: document.getElementById('item_nama').value,
            harga_setor: document.getElementById('item_harga_setor').value,
            harga_jual: document.getElementById('item_harga_jual').value,
            product_variant_id: document.getElementById('item_product_variant_id').value,
            _token: csrfToken
        };

        const url = editItemId ? `/consignments/items/${editItemId}` : `/consignments/consignors/${consignorId}/items`;
        const method = editItemId ? 'PUT' : 'POST';

        fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message || 'Terjadi kesalahan.');
            })
            .catch(err => alert('Gagal menyimpan menu titipan.'));
    });

    function deleteItem(itemId, name) {
        if (!confirm(`Hapus menu titipan ${name}?`)) return;

        fetch(`/consignments/items/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message);
            });
    }
</script>
@endpush