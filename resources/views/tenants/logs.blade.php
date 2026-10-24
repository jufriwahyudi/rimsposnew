@extends('layouts.main.main')
@section('title', 'Live Monitor Notifikasi Tenant')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenants.index') }}">Tenant</a></li>
                    <li class="breadcrumb-item active">Live Monitor Log</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="{{ route('tenants.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="material-icons-outlined" style="font-size:16px;">arrow_back</i> Kembali
            </a>
            <button id="btnManualRefresh" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <i class="material-icons-outlined" style="font-size:16px;" id="iconRefresh">refresh</i> Refresh Sekarang
            </button>
        </div>
    </div>
@endsection

@section('content')
    <!-- Stat Cards -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100" style="background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); border-left: 5px solid #10B981 !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-success fw-bold small text-uppercase mb-1">Pesanan Baru (Terkirim)</div>
                        <h3 class="fw-bolder mb-0 text-success" id="statSent">{{ $stats['sent'] ?? 0 }}</h3>
                        <small class="text-muted">Notifikasi order ke HP tenant</small>
                    </div>
                    <div class="rounded-circle bg-white p-3 shadow-sm text-success d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                        <i class="material-icons-outlined" style="font-size:24px;">notifications_active</i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100" style="background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); border-left: 5px solid #F59E0B !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-warning fw-bold small text-uppercase mb-1" style="color:#B45309 !important;">Di-Skip (Pelunasan Kasir)</div>
                        <h3 class="fw-bolder mb-0" style="color:#B45309;" id="statSkipped">{{ $stats['skipped'] ?? 0 }}</h3>
                        <small class="text-muted">Diblokir agar tenant tidak dobel</small>
                    </div>
                    <div class="rounded-circle bg-white p-3 shadow-sm d-flex align-items-center justify-content-center" style="color:#B45309;width:48px;height:48px;">
                        <i class="material-icons-outlined" style="font-size:24px;">verified_user</i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100" style="background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%); border-left: 5px solid #EF4444 !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-danger fw-bold small text-uppercase mb-1">Gagal / Masalah Token</div>
                        <h3 class="fw-bolder mb-0 text-danger" id="statError">{{ $stats['error'] ?? 0 }}</h3>
                        <small class="text-muted">Error / tenant tanpa FCM token</small>
                    </div>
                    <div class="rounded-circle bg-white p-3 shadow-sm text-danger d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                        <i class="material-icons-outlined" style="font-size:24px;">error_outline</i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card Log Table -->
    <div class="card rounded-4 p-3 shadow-sm border-0">
        <div class="card-header bg-transparent border-0 p-0 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1 d-flex align-items-center gap-2" style="color:#7c3aed;">
                    <i class="material-icons-outlined">receipt_long</i> Riwayat Aktivitas & Push Notifikasi Tenant
                </h5>
                <small class="text-muted">Data otomatis diambil langsung dari log transaksi backend secara realtime</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="switchAutoRefresh" checked>
                    <label class="form-check-label small fw-semibold" for="switchAutoRefresh">
                        Auto-refresh (<span id="countdown">5</span>s)
                    </label>
                </div>
                <div class="position-relative" style="width: 220px;">
                    <input type="text" id="inputSearch" class="form-control form-control-sm ps-4 rounded-3" placeholder="Cari invoice / meja / tenant...">
                    <i class="material-icons-outlined position-absolute top-50 start-0 translate-middle-y ms-2 text-muted" style="font-size:16px;">search</i>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableLogs">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase">
                            <th width="160">Waktu</th>
                            <th width="190">Status Aksi</th>
                            <th>Rincian Log / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyLogs">
                        @forelse ($rawLogs as $log)
                            <tr>
                                <td class="text-nowrap small text-muted">
                                    <i class="material-icons-outlined align-middle" style="font-size:14px;">schedule</i> {{ $log['time'] }}
                                </td>
                                <td>
                                    <span class="badge {{ $log['badge'] }} rounded-pill px-3 py-1 text-nowrap d-inline-flex align-items-center gap-1">
                                        <i class="material-icons-outlined" style="font-size:13px;">{{ $log['icon'] ?? 'info' }}</i>
                                        {{ $log['type'] }}
                                    </span>
                                </td>
                                <td class="small font-monospace" style="word-break: break-all;">
                                    {{ $log['message'] }}
                                </td>
                            </tr>
                        @empty
                            <tr id="rowEmpty">
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="material-icons-outlined d-block mb-2 text-secondary" style="font-size:40px;">assignment_late</i>
                                    Belum ada catatan aktivitas notifikasi tenant di log.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('tbodyLogs');
    const inputSearch = document.getElementById('inputSearch');
    const switchAuto = document.getElementById('switchAutoRefresh');
    const countdownEl = document.getElementById('countdown');
    const btnRefresh = document.getElementById('btnManualRefresh');
    const iconRefresh = document.getElementById('iconRefresh');

    const statSent = document.getElementById('statSent');
    const statSkipped = document.getElementById('statSkipped');
    const statError = document.getElementById('statError');

    let timer = null;
    let secondsLeft = 5;

    function filterTable() {
        const query = (inputSearch.value || '').toLowerCase().trim();
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(r => {
            if (r.id === 'rowEmpty') return;
            const text = r.innerText.toLowerCase();
            r.style.display = text.includes(query) ? '' : 'none';
        });
    }

    inputSearch.addEventListener('input', filterTable);

    function fetchLogs() {
        iconRefresh.classList.add('bx-spin');
        fetch("{{ route('tenants.logs') }}", {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && Array.isArray(res.logs)) {
                renderLogs(res.logs);
                if (res.stats) {
                    statSent.innerText = res.stats.sent ?? 0;
                    statSkipped.innerText = res.stats.skipped ?? 0;
                    statError.innerText = res.stats.error ?? 0;
                }
            }
        })
        .catch(err => {
            console.error('Failed to refresh tenant logs:', err);
        })
        .finally(() => {
            setTimeout(() => {
                iconRefresh.classList.remove('bx-spin');
            }, 300);
        });
    }

    function renderLogs(logs) {
        if (!logs.length) {
            tbody.innerHTML = `
                <tr id="rowEmpty">
                    <td colspan="3" class="text-center py-5 text-muted">
                        <i class="material-icons-outlined d-block mb-2 text-secondary" style="font-size:40px;">assignment_late</i>
                        Belum ada catatan aktivitas notifikasi tenant di log.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        logs.forEach(log => {
            html += `
                <tr>
                    <td class="text-nowrap small text-muted">
                        <i class="material-icons-outlined align-middle" style="font-size:14px;">schedule</i> ${escapeHtml(log.time)}
                    </td>
                    <td>
                        <span class="badge ${log.badge} rounded-pill px-3 py-1 text-nowrap d-inline-flex align-items-center gap-1">
                            <i class="material-icons-outlined" style="font-size:13px;">${log.icon || 'info'}</i>
                            ${escapeHtml(log.type)}
                        </span>
                    </td>
                    <td class="small font-monospace" style="word-break: break-all;">
                        ${escapeHtml(log.message)}
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        filterTable();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    btnRefresh.addEventListener('click', function () {
        secondsLeft = 5;
        countdownEl.innerText = secondsLeft;
        fetchLogs();
    });

    function startTimer() {
        if (timer) clearInterval(timer);
        secondsLeft = 5;
        countdownEl.innerText = secondsLeft;
        timer = setInterval(() => {
            if (!switchAuto.checked) return;
            secondsLeft--;
            countdownEl.innerText = secondsLeft;
            if (secondsLeft <= 0) {
                secondsLeft = 5;
                fetchLogs();
            }
        }, 1000);
    }

    switchAuto.addEventListener('change', function () {
        if (this.checked) {
            startTimer();
        } else {
            countdownEl.innerText = '-';
        }
    });

    startTimer();
});
</script>
@endpush
