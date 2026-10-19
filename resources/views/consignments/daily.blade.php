@extends('layouts.main.main')

@section('title', 'Titipan Harian (Pagi & Sore)')

@section('content')
<div class="row">
    <div class="col-12">
        {{-- Header & Date Picker --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="material-icons-outlined me-2 align-middle text-warning">today</i>Titipan Harian (Pagi & Sore)</h4>
                <p class="text-muted mb-0 small">Catat penerimaan titipan kue pagi hari dan lakukan rekonsiliasi sisa & pembayaran tunai sore hari.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="{{ route('consignments.daily') }}" class="d-flex gap-2">
                    <input type="date" name="date" class="form-control form-control-sm rounded-pill" value="{{ $date }}" onchange="this.form.submit()">
                </form>
                <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 fw-semibold shadow-sm text-dark" onclick="openMorningIntakeModal()">
                    <i class="material-icons-outlined me-1 align-middle" style="font-size: 16px;">add_circle</i> Terima Titipan Pagi
                </button>
            </div>
        </div>

        {{-- Mini KPI Ribbon --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 rounded-3 p-3 text-center border-start border-primary border-4">
                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Total Kue Masuk Pagi</small>
                    <h3 class="fw-bold text-dark mb-0 mt-1">{{ number_format($totalTitipToday, 0, ',', '.') }} <small class="fs-6 fw-normal text-muted">pcs</small></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 rounded-3 p-3 text-center border-start border-success border-4">
                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Total Terjual (Laku)</small>
                    <h3 class="fw-bold text-success mb-0 mt-1">{{ number_format($totalLakuToday, 0, ',', '.') }} <small class="fs-6 fw-normal text-muted">pcs</small></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 rounded-3 p-3 text-center border-start border-danger border-4">
                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Total Hak Penitip Dibayar</small>
                    <h3 class="fw-bold text-danger mb-0 mt-1">Rp {{ number_format($totalPayoutToday, 0, ',', '.') }}</h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 rounded-3 p-3 text-center border-start border-warning border-4">
                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Laba Bersih Kantin</small>
                    <h3 class="fw-bold text-warning mb-0 mt-1">Rp {{ number_format($totalMarginToday, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>

        {{-- Session Table --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="material-icons-outlined me-1 align-middle text-primary">view_list</i> Daftar Sesi Titipan: {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</h6>
                <span class="badge bg-light text-muted border px-2 py-1">{{ $sessions->count() }} Sesi Terdaftar</span>
            </div>
            <div class="card-body p-0">
                @if($sessions->isEmpty())
                    <div class="p-5 text-center text-muted">
                        <span class="material-icons-outlined text-muted mb-2" style="font-size: 48px;">receipt_long</span>
                        <h6>Belum ada titipan dicatat pada tanggal ini.</h6>
                        <p class="small mb-3">Klik tombol "Terima Titipan Pagi" untuk mencatat kue yang masuk dari ibu-ibu penitip.</p>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="openMorningIntakeModal()">
                            <i class="material-icons-outlined me-1 align-middle" style="font-size: 16px;">add</i> Terima Titipan Pagi
                        </button>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Penitip</th>
                                    <th>Rincian Item</th>
                                    <th class="text-center">Titip</th>
                                    <th class="text-center">Sisa</th>
                                    <th class="text-center">Laku</th>
                                    <th class="text-end">Dibayar (Penitip)</th>
                                    <th class="text-end">Laba Kantin</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $session)
                                    <tr>
                                        <td class="ps-3">
                                            <strong class="text-dark">{{ $session->consignor->nama_penitip ?? '-' }}</strong>
                                            @if($session->consignor->telepon)
                                                <div class="small text-muted">{{ $session->consignor->telepon }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small">
                                                @foreach($session->items as $idx => $item)
                                                    <span>{{ $item->consignorItem->nama_item ?? 'Item' }} ({{ $item->qty_titip }})</span>{{ !$loop->last ? ', ' : '' }}
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold">{{ $session->total_titip }}</td>
                                        <td class="text-center">{{ $session->status === 'settled' ? $session->total_sisa : '-' }}</td>
                                        <td class="text-center fw-bold {{ $session->status === 'settled' ? 'text-success' : 'text-muted' }}">
                                            {{ $session->status === 'settled' ? $session->total_laku : '-' }}
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            {{ $session->status === 'settled' ? 'Rp ' . number_format($session->total_payout, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            {{ $session->status === 'settled' ? 'Rp ' . number_format($session->total_kantin_margin, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-center">
                                            @if($session->status === 'settled')
                                                <span class="badge bg-light-success text-success px-2 py-1"><i class="material-icons-outlined align-middle" style="font-size: 12px;">check_circle</i> Selesai & Lunas</span>
                                            @elseif($session->status === 'open')
                                                <span class="badge bg-light-warning text-warning px-2 py-1"><i class="material-icons-outlined align-middle" style="font-size: 12px;">hourglass_top</i> Menunggu Rekonsiliasi</span>
                                            @else
                                                <span class="badge bg-light-secondary text-muted px-2 py-1">Dibatalkan</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            @if($session->status === 'open')
                                                <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark shadow-sm" onclick="openSettlementModal({{ json_encode($session->load('items.consignorItem')) }})">
                                                    <i class="material-icons-outlined me-1 align-middle" style="font-size: 14px;">payments</i> Rekonsiliasi Sore
                                                </button>
                                            @elseif($session->status === 'settled' && $session->settlement)
                                                <a href="{{ route('consignments.receipt', $session->settlement->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="Cetak Ulang Struk">
                                                    <i class="material-icons-outlined me-1 align-middle" style="font-size: 14px;">print</i> Struk
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- MODAL 1: TERIMA TITIPAN PAGI --}}
<div class="modal fade" id="modalMorningIntake" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold"><i class="material-icons-outlined me-2 text-warning align-middle">wb_sunny</i>Terima Titipan Pagi Hari</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formMorningIntake">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Pilih Penitip <span class="text-danger">*</span></label>
                            <select class="form-select" id="intakeConsignorSelect" required onchange="onConsignorSelected(this.value)">
                                <option value="">-- Pilih Nama Penitip --</option>
                                @foreach($consignors as $consignor)
                                    <option value="{{ $consignor->id }}" data-items="{{ json_encode($consignor->items) }}">
                                        {{ $consignor->nama_penitip }} ({{ $consignor->items->count() }} menu terdaftar)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Tanggal Titip</label>
                            <input type="date" class="form-control" id="intakeTanggal" value="{{ $date }}" required>
                        </div>
                    </div>

                    {{-- Dynamic Items Table --}}
                    <div id="intakeItemsContainer" style="display: none;">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Daftar Kue yang Dititipkan</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Kue / Menu</th>
                                        <th style="width: 130px;">Harga Setor</th>
                                        <th style="width: 130px;">Harga Jual</th>
                                        <th style="width: 140px;" class="text-center">Qty Masuk Pagi</th>
                                    </tr>
                                </thead>
                                <tbody id="intakeItemsTbody">
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan Sesi (Opsional)</label>
                            <input type="text" class="form-control" id="intakeCatatan" placeholder="Contoh: Bu Siti titip lebih awal">
                        </div>
                    </div>

                    <div id="intakeEmptyState" class="text-center py-4 text-muted bg-light rounded-3">
                        Pilih nama penitip di atas untuk memunculkan daftar kuenya.
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-sm" id="btnSaveIntake" disabled>
                        Simpan Titipan Pagi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 2: REKONSILIASI & SETTLEMENT SORE --}}
<div class="modal fade" id="modalSettlement" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-bottom bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="material-icons-outlined me-1 align-middle text-primary">receipt</i> Rekonsiliasi & Pembayaran Sore</h5>
                    <small class="text-muted" id="settlementConsignorSubtitle">Penitip: -</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSettlement">
                <input type="hidden" id="settleDailyId">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 small mb-3">
                        <i class="material-icons-outlined align-middle me-1">info</i>
                        Cukup hitung sisa fisik kue yang tidak habis di piring/wadah dan masukkan ke kolom <strong>Sisa Fisik</strong>. Sistem otomatis menghitung kue yang laku dan total uang yang wajib dibayar.
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Kue</th>
                                    <th class="text-center" style="width: 90px;">Titip Pagi</th>
                                    <th class="text-center" style="width: 120px;">Sisa Fisik</th>
                                    <th class="text-center" style="width: 90px;">Laku</th>
                                    <th class="text-end" style="width: 110px;">Harga Setor</th>
                                    <th class="text-end" style="width: 130px;">Subtotal Bayar</th>
                                </tr>
                            </thead>
                            <tbody id="settlementItemsTbody">
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">TOTAL HAK PENITIP:</td>
                                    <td class="text-center text-success" id="settleTotalLaku">0</td>
                                    <td></td>
                                    <td class="text-end text-danger fs-6" id="settleTotalPayout">Rp 0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Metode Pembayaran</label>
                            <select class="form-select" id="settlePaymentMethod">
                                <option value="CASH">Tunai (Potong Laci Kasir Shift)</option>
                                <option value="TRANSFER">Transfer Bank</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Jika pilih Tunai, uang laci kasir otomatis berkurang di rekonsiliasi shift.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Catatan</label>
                            <input type="text" class="form-control" id="settleCatatan" placeholder="Catatan pembayaran (opsional)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btnSubmitSettlement">
                        <i class="material-icons-outlined me-1 align-middle" style="font-size: 16px;">print</i> Bayar & Cetak Struk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';

    function openMorningIntakeModal() {
        document.getElementById('formMorningIntake').reset();
        document.getElementById('intakeItemsContainer').style.display = 'none';
        document.getElementById('intakeEmptyState').style.display = 'block';
        document.getElementById('btnSaveIntake').disabled = true;
        new bootstrap.Modal(document.getElementById('modalMorningIntake')).show();
    }

    function onConsignorSelected(consignorId) {
        if (!consignorId) {
            document.getElementById('intakeItemsContainer').style.display = 'none';
            document.getElementById('intakeEmptyState').style.display = 'block';
            document.getElementById('btnSaveIntake').disabled = true;
            return;
        }

        const select = document.getElementById('intakeConsignorSelect');
        const selectedOption = select.options[select.selectedIndex];
        const items = JSON.parse(selectedOption.getAttribute('data-items') || '[]');

        const tbody = document.getElementById('intakeItemsTbody');
        tbody.innerHTML = '';

        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted small py-3">Penitip ini belum memiliki menu terdaftar. Silakan daftarkan menu di halaman Data Penitip terlebih dahulu.</td></tr>`;
            document.getElementById('btnSaveIntake').disabled = true;
        } else {
            items.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <strong>${item.nama_item}</strong>
                        <input type="hidden" name="items[${index}][consignor_item_id]" value="${item.id}">
                    </td>
                    <td class="text-muted">Rp ${Number(item.harga_setor).toLocaleString('id-ID')}</td>
                    <td class="text-muted">Rp ${Number(item.harga_jual).toLocaleString('id-ID')}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm text-center fw-bold" name="items[${index}][qty_titip]" value="0" min="0" required onfocus="this.select()">
                    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('btnSaveIntake').disabled = false;
        }

        document.getElementById('intakeItemsContainer').style.display = 'block';
        document.getElementById('intakeEmptyState').style.display = 'none';
    }

    document.getElementById('formMorningIntake').addEventListener('submit', function(e) {
        e.preventDefault();
        const consignorId = document.getElementById('intakeConsignorSelect').value;
        const tanggal = document.getElementById('intakeTanggal').value;
        const catatan = document.getElementById('intakeCatatan').value;

        const items = [];
        const rows = document.querySelectorAll('#intakeItemsTbody tr');
        rows.forEach(tr => {
            const idInput = tr.querySelector('input[type="hidden"]');
            const qtyInput = tr.querySelector('input[type="number"]');
            if (idInput && qtyInput) {
                const qty = parseInt(qtyInput.value) || 0;
                if (qty > 0) {
                    items.push({
                        consignor_item_id: idInput.value,
                        qty_titip: qty
                    });
                }
            }
        });

        if (items.length === 0) {
            alert('Silakan isi jumlah kue yang masuk (minimal 1 item bertambah).');
            return;
        }

        const payload = {
            consignor_id: consignorId,
            tanggal: tanggal,
            catatan: catatan,
            items: items,
            _token: csrfToken
        };

        fetch('{{ route("consignments.daily.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Gagal menyimpan titipan pagi.');
            }
        })
        .catch(err => alert('Terjadi kesalahan koneksi.'));
    });

    // ── REKONSILIASI SORE ──────────────────────────────────────
    let currentSessionData = null;

    function openSettlementModal(session) {
        currentSessionData = session;
        document.getElementById('settleDailyId').value = session.id;
        document.getElementById('settlementConsignorSubtitle').textContent = `Penitip: ${session.consignor ? session.consignor.nama_penitip : '-'}`;

        const tbody = document.getElementById('settlementItemsTbody');
        tbody.innerHTML = '';

        session.items.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <strong>${item.consignor_item ? item.consignor_item.nama_item : 'Item'}</strong>
                    <input type="hidden" class="ditem-id" value="${item.id}">
                    <input type="hidden" class="ditem-titip" value="${item.qty_titip}">
                    <input type="hidden" class="ditem-setor" value="${item.harga_setor}">
                </td>
                <td class="text-center fw-bold text-muted">${item.qty_titip}</td>
                <td>
                    <input type="number" class="form-control form-control-sm text-center fw-bold ditem-sisa" value="0" min="0" max="${item.qty_titip}" oninput="recalcSettlement()" onfocus="this.select()">
                </td>
                <td class="text-center fw-bold text-success ditem-laku">${item.qty_titip}</td>
                <td class="text-end text-muted small">Rp ${Number(item.harga_setor).toLocaleString('id-ID')}</td>
                <td class="text-end fw-bold text-danger ditem-subtotal">Rp ${Number(item.qty_titip * item.harga_setor).toLocaleString('id-ID')}</td>
            `;
            tbody.appendChild(tr);
        });

        recalcSettlement();
        new bootstrap.Modal(document.getElementById('modalSettlement')).show();
    }

    function recalcSettlement() {
        const rows = document.querySelectorAll('#settlementItemsTbody tr');
        let totalLaku = 0;
        let totalPayout = 0;

        rows.forEach(tr => {
            const titip = parseInt(tr.querySelector('.ditem-titip').value) || 0;
            const setor = parseFloat(tr.querySelector('.ditem-setor').value) || 0;
            const sisaInput = tr.querySelector('.ditem-sisa');
            let sisa = parseInt(sisaInput.value) || 0;

            if (sisa > titip) {
                sisa = titip;
                sisaInput.value = titip;
            }

            const laku = Math.max(0, titip - sisa);
            const subtotal = laku * setor;

            tr.querySelector('.ditem-laku').textContent = laku;
            tr.querySelector('.ditem-subtotal').textContent = 'Rp ' + Number(subtotal).toLocaleString('id-ID');

            totalLaku += laku;
            totalPayout += subtotal;
        });

        document.getElementById('settleTotalLaku').textContent = totalLaku;
        document.getElementById('settleTotalPayout').textContent = 'Rp ' + Number(totalPayout).toLocaleString('id-ID');
    }

    document.getElementById('formSettlement').addEventListener('submit', function(e) {
        e.preventDefault();
        const dailyId = document.getElementById('settleDailyId').value;
        const method = document.getElementById('settlePaymentMethod').value;
        const catatan = document.getElementById('settleCatatan').value;

        const items = [];
        const rows = document.querySelectorAll('#settlementItemsTbody tr');
        rows.forEach(tr => {
            items.push({
                id: tr.querySelector('.ditem-id').value,
                qty_sisa: parseInt(tr.querySelector('.ditem-sisa').value) || 0
            });
        });

        const payload = {
            items: items,
            payment_method: method,
            catatan: catatan,
            _token: csrfToken
        };

        if (!confirm('Pastikan sisa kue fisik sudah sesuai. Lanjutkan pembayaran dan cetak struk?')) return;

        fetch(`/consignments/daily/${dailyId}/settle`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Open thermal print popup
                if (data.data && data.data.print_url) {
                    window.open(data.data.print_url, '_blank', 'width=400,height=600');
                }
                location.reload();
            } else {
                alert(data.message || 'Gagal rekonsiliasi.');
            }
        })
        .catch(err => alert('Terjadi kesalahan koneksi.'));
    });
</script>
@endpush
