<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pemilihan Jadwal Distribusi Seragam</title>
    <link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            font-family: 'Noto Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #fdf2f8 100%);
            min-height: 100vh;
        }

        .main-card {
            max-width: 540px;
            margin: 0 auto;
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .06);
        }

        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 50;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 .75rem .75rem;
            max-height: 260px;
            overflow-y: auto;
            display: none;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
        }

        .autocomplete-list .ac-item {
            padding: .75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background .15s;
        }

        .autocomplete-list .ac-item:last-child {
            border-bottom: none;
        }

        .autocomplete-list .ac-item:hover,
        .autocomplete-list .ac-item.active {
            background: #f0f4ff;
        }

        .ac-item .ac-name {
            font-weight: 600;
            font-size: .9rem;
            color: #1e293b;
        }

        .ac-item .ac-meta {
            font-size: .75rem;
            color: #94a3b8;
        }

        .sesi-card {
            border: 2px solid #e2e8f0;
            border-radius: .75rem;
            padding: .875rem 1rem;
            cursor: pointer;
            transition: all .2s;
        }

        .sesi-card:hover {
            border-color: #a78bfa;
            background: #faf5ff;
        }

        .sesi-card.selected {
            border-color: #7c3aed;
            background: #f5f3ff;
        }

        .sesi-card.disabled {
            opacity: .5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Sesi items — visually distinct from jadwal */
        .sesi-item.sesi-card {
            border: 2px dashed #cbd5e1;
            border-radius: .5rem;
            background: #f8fafc;
            padding: .625rem 1rem;
            border-left: 4px solid #2563eb;
        }

        .sesi-item.sesi-card:hover {
            border-color: #60a5fa;
            background: #eff6ff;
            border-left-color: #2563eb;
        }

        .sesi-item.sesi-card.selected {
            border-color: #2563eb;
            background: #dbeafe;
            border-left-color: #1d4ed8;
        }

        .step-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .8rem;
            flex-shrink: 0;
        }

        .step-badge.active {
            background: #7c3aed;
            color: #fff;
        }

        .step-badge.done {
            background: #10b981;
            color: #fff;
        }

        .step-badge.pending {
            background: #e2e8f0;
            color: #94a3b8;
        }

        #siswaInfo {
            display: none;
        }

        #jadwalSection {
            display: none;
        }

        #successSection {
            display: none;
        }

        .fade-in {
            animation: fadeIn .3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container py-4 px-3">
        {{-- Header --}}
        <div class="text-center mb-4">
            <img src="{{ asset('assets/images/alazca_logo.png') }}" width="56" class="mb-2">
            <h5 class="fw-bold mb-0" style="color:#7c3aed">Pemilihan Jadwal Seragam</h5>
            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
        </div>

        <div class="card main-card">
            <div class="card-body p-4">

                {{-- Steps indicator --}}
                <div class="d-flex align-items-center gap-2 mb-4">
                    <span class="step-badge active" id="stepBadge1">1</span>
                    <small class="fw-semibold text-muted" id="stepLabel1">Cari Siswa</small>
                    <div class="flex-grow-1 mx-1" style="height:2px;background:#e2e8f0"></div>
                    <span class="step-badge pending" id="stepBadge2">2</span>
                    <small class="fw-semibold text-muted" id="stepLabel2">Pilih Jadwal</small>
                    <div class="flex-grow-1 mx-1" style="height:2px;background:#e2e8f0"></div>
                    <span class="step-badge pending" id="stepBadge3">
                        <i class="bi bi-check-lg"></i>
                    </span>
                </div>

                {{-- Step 1: Cari siswa --}}
                <div id="searchSection">
                    <label class="form-label fw-semibold mb-2">
                        <i class="bi bi-search me-1"></i> Cari berdasarkan No. Registrasi / Nama Siswa
                    </label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="searchInput" class="form-control form-control-lg"
                            placeholder="Ketik minimal 3 karakter..." autocomplete="off"
                            style="border-radius:.75rem;font-size:.95rem">
                        <div class="autocomplete-list" id="acList"></div>
                    </div>
                    <div class="form-text mt-1">
                        Contoh: <span class="text-primary">Ahmad Rafi</span> atau <span
                            class="text-primary">2401001</span>
                    </div>
                </div>

                {{-- Info siswa terpilih --}}
                <div id="siswaInfo" class="fade-in">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-person-fill text-primary me-1"></i> Data Siswa</h6>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnGantiSiswa">
                            <i class="bi bi-arrow-left-short"></i> Ganti
                        </button>
                    </div>
                    <div class="bg-light rounded-3 p-3 mb-3">
                        <div class="row g-2">
                            <div class="col-4"><small class="text-muted">Nama</small></div>
                            <div class="col-8"><strong id="infoNama">-</strong></div>
                            <div class="col-4"><small class="text-muted">NIK</small></div>
                            <div class="col-8"><span id="infoNik">-</span></div>
                            <div class="col-4"><small class="text-muted">Divisi</small></div>
                            <div class="col-8"><span id="infoDivisi">-</span></div>
                            {{-- Jadwal kalau sudah di pilih --}}
                            <div class="col-4"><small class="text-muted">Jadwal</small></div>
                            <div class="col-8"><span id="infoJadwal">-</span></div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Pilih jadwal --}}
                <div id="jadwalSection" class="fade-in">
                    <h6 class="fw-bold mb-3"><i class="bi bi-calendar-event text-primary me-1"></i> Pilih Tanggal</h6>

                    <div id="jadwalList" class="mb-3">
                        <div class="text-center text-muted py-3">
                            <div class="spinner-border spinner-border-sm me-1"></div> Memuat jadwal...
                        </div>
                    </div>

                    <div id="sesiSection" style="display:none" class="fade-in">
                        <h6 class="fw-bold mb-3"><i class="bi bi-clock text-primary me-1"></i> Pilih Sesi</h6>
                        <div id="sesiList" class="d-flex flex-column gap-2"></div>
                    </div>

                    <button class="btn btn-lg w-100 mt-4 fw-semibold" id="btnSimpan"
                        style="background:#7c3aed;color:#fff;border-radius:.75rem" disabled>
                        <i class="bi bi-check-circle me-1"></i> Simpan Jadwal
                    </button>
                </div>

                {{-- Success --}}
                <div id="successSection" class="text-center fade-in py-3">
                    <div class="mb-3">
                        <span
                            style="width:64px;height:64px;border-radius:50%;background:#d1fae5;display:inline-flex;align-items:center;justify-content:center">
                            <i class="bi bi-check-circle-fill text-success" style="font-size:2rem"></i>
                        </span>
                    </div>
                    <h5 class="fw-bold text-success">Jadwal Berhasil Disimpan!</h5>
                    <p class="text-muted mb-1" id="successDetail"></p>
                    <button class="btn btn-outline-primary rounded-pill px-4 mt-3" id="btnCariLagi">
                        <i class="bi bi-arrow-repeat me-1"></i> Cari Siswa Lain
                    </button>
                </div>

            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">&copy; {{ date('Y') }} Al-Azhar Cairo Banda Aceh</small>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let selectedSiswa = null;
        let selectedSesi = null;
        let debounceTimer = null;

        // Auto-select siswa jika id diberikan via URL
        const preloadSiswa = @json($siswa ?? null);
        if (preloadSiswa) {
            selectedSiswa = {
                id: preloadSiswa.id_biodatadiri,
                nama: preloadSiswa.nama_lengkap,
                nik: preloadSiswa.nik || '-',
                divisi: preloadSiswa.divisi || '-',
                jadwal: preloadSiswa.jadwal || null
            };
            showSiswaInfo();
            if (!selectedSiswa.jadwal) loadJadwal();
        }

        // ─── Autocomplete ──────────────────────────────────
        $('#searchInput').on('input', function() {
            const q = $(this).val().trim();
            clearTimeout(debounceTimer);

            if (q.length < 3) {
                $('#acList').hide().empty();
                return;
            }

            debounceTimer = setTimeout(() => {
                $.post("{{ route('nse.otm.search') }}", {
                    q: q
                }, function(res) {
                    const list = $('#acList');
                    list.empty();

                    if (res.length === 0) {
                        list.html(
                            '<div class="p-3 text-center text-muted"><i class="bi bi-emoji-frown me-1"></i> Siswa tidak ditemukan</div>'
                        ).show();
                        return;
                    }

                    res.forEach((s, i) => {
                        const jadwalBadge = s.jadwal ?
                            `<span class="badge bg-success rounded-pill ms-2" style="font-size:.65rem">Sudah ada jadwal</span>` :
                            '';
                        list.append(`
                            <div class="ac-item ${i === 0 ? 'active' : ''}"
                                 data-id="${s.id_biodatadiri}"
                                 data-nama="${s.nama_lengkap}"
                                 data-nik="${s.nik || '-'}"
                                 data-divisi="${s.divisi || '-'}"
                                 data-jadwal='${s.jadwal ? JSON.stringify(s.jadwal) : ""}'>
                                <div class="ac-name">${s.nama_lengkap} ${jadwalBadge}</div>
                                <div class="ac-meta">NIK: ${s.nik || '-'} &middot; ${s.divisi || ''}</div>
                            </div>
                        `);
                    });

                    list.show();
                });
            }, 300);
        });

        // keyboard navigation
        $('#searchInput').on('keydown', function(e) {
            const items = $('#acList .ac-item');
            if (!items.length) return;

            let idx = items.index($('.ac-item.active'));

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                idx = Math.min(idx + 1, items.length - 1);
                items.removeClass('active').eq(idx).addClass('active');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                idx = Math.max(idx - 1, 0);
                items.removeClass('active').eq(idx).addClass('active');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                items.filter('.active').trigger('click');
            }
        });

        // pilih siswa
        $(document).on('click', '.ac-item', function() {
            const jadwalData = $(this).data('jadwal');
            selectedSiswa = {
                id: $(this).data('id'),
                nama: $(this).data('nama'),
                nik: $(this).data('nik'),
                divisi: $(this).data('divisi'),
                jadwal: jadwalData || null
            };

            $('#acList').hide().empty();
            $('#searchInput').val('');

            showSiswaInfo();
            loadJadwal();
        });

        // hide dropdown on click outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.autocomplete-wrapper').length) {
                $('#acList').hide();
            }
        });

        // ─── Show siswa info ───────────────────────────────
        function showSiswaInfo() {
            $('#infoNama').text(selectedSiswa.nama);
            $('#infoNik').text(selectedSiswa.nik);
            $('#infoDivisi').text(selectedSiswa.divisi);
            $('#infoJadwal').text(selectedSiswa.jadwal ?
                `${selectedSiswa.jadwal.tanggal} (${selectedSiswa.jadwal.sesi.jam_mulai} - ${selectedSiswa.jadwal.sesi.jam_selesai})` :
                '-');

            $('#searchSection').hide();
            $('#siswaInfo').show();
            if (!selectedSiswa.jadwal)
                $('#jadwalSection').show();

            // update steps
            $('#stepBadge1').removeClass('active').addClass('done').html('<i class="bi bi-check-lg"></i>');
            $('#stepBadge2').removeClass('pending').addClass('active');
        }

        // ─── Ganti siswa ──────────────────────────────────
        $('#btnGantiSiswa').on('click', function() {
            resetAll();
        });

        $('#btnCariLagi').on('click', function() {
            resetAll();
        });

        function resetAll() {
            selectedSiswa = null;
            selectedSesi = null;

            $('#searchSection').show();
            $('#siswaInfo').hide();
            $('#jadwalSection').hide();
            $('#successSection').hide();
            $('#searchInput').val('').focus();
            $('#btnSimpan').prop('disabled', true);

            // reset steps
            $('#stepBadge1').removeClass('done').addClass('active').text('1');
            $('#stepBadge2').removeClass('active done').addClass('pending').text('2');
            $('#stepBadge3').removeClass('done').addClass('pending').html('<i class="bi bi-check-lg"></i>');
        }

        // ─── Load jadwal ──────────────────────────────────
        function loadJadwal() {
            $('#jadwalList').html(
                '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-1"></div> Memuat jadwal...</div>'
            );
            $('#sesiSection').hide();
            selectedSesi = null;
            $('#btnSimpan').prop('disabled', true);

            $.post("{{ route('nse.distribusi.jadwal.aktif') }}", {
                siswa_id: selectedSiswa.id
            }, function(res) {
                if (res.length === 0) {
                    $('#jadwalList').html(
                        '<div class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-3 d-block mb-2"></i>Belum ada jadwal yang tersedia</div>'
                    );
                    return;
                }

                let html = '<div class="d-flex flex-column gap-2">';
                res.forEach(j => {
                    const totalKuota = j.sesi.reduce((a, s) => a + s.kuota_sesi, 0);
                    const totalPeserta = j.sesi.reduce((a, s) => a + s.peserta_count, 0);
                    const penuh = totalPeserta >= totalKuota;

                    html += `
                        <div class="sesi-card jadwal-item ${penuh ? 'disabled' : ''}" data-id="${j.id}" data-sesi='${JSON.stringify(j.sesi)}'>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><i class="bi bi-calendar3 me-1 text-primary"></i> ${j.tanggal}</div>
                                    <small class="text-muted">${j.sesi.length} sesi tersedia</small>
                                </div>
                                <div>
                                    ${penuh
                                        ? '<span class="badge bg-danger rounded-pill">Penuh</span>'
                                        : '<span class="badge bg-success rounded-pill">Tersedia</span>'}
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                $('#jadwalList').html(html);
            });
        }

        // pilih tanggal → tampilkan sesi
        $(document).on('click', '.jadwal-item:not(.disabled)', function() {
            $('.jadwal-item').removeClass('selected');
            $(this).addClass('selected');

            const sesiData = $(this).data('sesi');
            selectedSesi = null;
            $('#btnSimpan').prop('disabled', true);

            let html = '';
            sesiData.forEach(s => {
                const penuh = s.peserta_count >= s.kuota_sesi;
                html += `
                    <div class="sesi-card sesi-item ${penuh ? 'disabled' : ''}" data-id="${s.id}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">
                                    <i class="bi bi-clock me-1 text-primary"></i>
                                    ${s.jam_mulai} - ${s.jam_selesai}
                                </div>
                                <small class="text-muted">Kuota: ${s.peserta_count} / ${s.kuota_sesi}</small>
                            </div>
                            <div>
                                ${penuh
                                    ? '<span class="badge bg-danger rounded-pill">Penuh</span>'
                                    : '<span class="badge bg-success rounded-pill">Tersedia</span>'}
                            </div>
                        </div>
                    </div>
                `;
            });

            $('#sesiList').html(html);
            $('#sesiSection').show();
        });

        // pilih sesi
        $(document).on('click', '.sesi-item:not(.disabled)', function() {
            $('.sesi-item').removeClass('selected');
            $(this).addClass('selected');
            selectedSesi = $(this).data('id');
            $('#btnSimpan').prop('disabled', false);
        });

        // ─── Simpan ───────────────────────────────────────
        $('#btnSimpan').on('click', function() {
            if (!selectedSiswa || !selectedSesi) return;

            const btn = $(this);
            btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

            $.post("{{ route('nse.distribusi.jadwal.book') }}", {
                    siswa_id: selectedSiswa.id,
                    sesi_id: selectedSesi
                })
                .done(function() {
                    // show success
                    $('#siswaInfo').hide();
                    $('#jadwalSection').hide();
                    $('#successSection').show();
                    $('#successDetail').text('Jadwal untuk ' + selectedSiswa.nama +
                        ' berhasil disimpan.');

                    // update steps
                    $('#stepBadge2').removeClass('active').addClass('done').html(
                        '<i class="bi bi-check-lg"></i>');
                    $('#stepBadge3').removeClass('pending').addClass('done');
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                    alert(msg);
                    btn.prop('disabled', false).html(
                        '<i class="bi bi-check-circle me-1"></i> Simpan Jadwal');
                });
        });
    </script>
</body>

</html>
