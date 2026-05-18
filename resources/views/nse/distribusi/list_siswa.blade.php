@extends('layouts.main.main')
@section('title', 'Distribusi Seragam NSE')

@push('styles')
    <style>
        .page-header-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .page-title {
            color: #7c3aed;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .page-subtitle {
            color: #9ca3af;
            font-size: 0.8rem;
        }

        .stat-card-sm {
            border: none;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform 0.2s, box-shadow 0.2s;
            color: #fff;
        }

        .stat-card-sm:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-card-sm .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-card-sm .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-card-sm .stat-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.85;
        }

        .stat-card-sm.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card-sm.belum {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card-sm.sebagian {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }

        .stat-card-sm.terjadwal {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .filter-card {
            border: none;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-card .form-select,
        .filter-card .form-control {
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            font-size: 0.85rem;
        }

        .filter-card .form-select:focus,
        .filter-card .form-control:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .filter-card label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .data-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .data-card .card-header-custom {
            padding: 16px 24px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        #dataTableSiswa thead th {
            background: #f8f7ff;
            color: #4a4a6a;
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #ede9fe;
            padding: 12px 14px;
            white-space: nowrap;
        }

        #dataTableSiswa tbody td {
            vertical-align: middle;
            padding: 10px 14px;
            font-size: 0.85rem;
            border-bottom: 1px solid #f3f4f6;
        }

        #dataTableSiswa tbody tr:hover {
            background-color: #faf8ff;
        }

        .badge-gender {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-gender.lk {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-gender.pr {
            background: #fce7f3;
            color: #be185d;
        }

        .badge-status-siswa {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.73rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-status-siswa.belum {
            background: #f3f4f6;
            color: #6b7280;
        }

        .badge-status-siswa.sebagian {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-status-siswa.sudah {
            background: #d1fae5;
            color: #059669;
        }

        .badge-jadwal {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-jadwal.terjadwal {
            background: #ecfdf5;
            color: #059669;
        }

        .badge-jadwal.belum {
            background: #f9fafb;
            color: #9ca3af;
            border: 1px dashed #d1d5db;
        }

        .nik-text {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #374151;
            font-size: 0.85rem;
        }

        .meta-text {
            font-size: 0.72rem;
            color: #9ca3af;
            margin-top: 2px;
        }

        .meta-badge {
            display: inline-block;
            background: #f3f0ff;
            color: #7c3aed;
            padding: 1px 7px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .btn-action-sm {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 500;
            border: 1.5px solid;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-action-sm.jadwal {
            border-color: #7c3aed;
            color: #7c3aed;
            background: #faf5ff;
        }

        .btn-action-sm.jadwal:hover {
            background: #7c3aed;
            color: #fff;
        }

        .btn-action-sm.pilih {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }

        .btn-action-sm.wa {
            border-color: #25d366;
            color: #25d366;
            background: #dcf8c6;
        }

        .btn-action-sm.pilih:hover {
            background: #2563eb;
            color: #fff;
        }

        .active-filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #ede9fe;
            color: #7c3aed;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.73rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
        }

        .active-filter-badge:hover {
            background: #ddd6fe;
        }

        .modal-custom .modal-content {
            border: none;
            border-radius: 16px;
        }

        .modal-custom .modal-header {
            border-bottom: 1px solid #f3f4f6;
        }

        .modal-custom .modal-footer {
            border-top: 1px solid #f3f4f6;
        }

        .sesi-option {
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sesi-option:hover {
            border-color: #7c3aed;
            background: #faf5ff;
        }

        .sesi-option.selected {
            border-color: #7c3aed;
            background: #f3f0ff;
        }

        .sesi-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .sesi-option .sesi-time {
            font-weight: 600;
            font-size: 0.9rem;
            color: #374151;
        }

        .sesi-option .sesi-quota {
            font-size: 0.78rem;
            color: #6b7280;
        }
    </style>
@endpush

@section('content')
    {{-- Page Header --}}
    <div class="card page-header-card mb-3">
        <div class="card-body py-3 px-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div
                        style="width:46px; height:46px; background:linear-gradient(135deg, #7c3aed, #a78bfa); border-radius:13px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-box-seam-fill text-white" style="font-size:1.2rem;"></i>
                    </div>
                    <div>
                        <h5 class="page-title mb-0">Distribusi Seragam NSE</h5>
                        <span class="page-subtitle"><i class="bi bi-building me-1"></i>{{ session('store_name') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card-sm total" id="statTotal">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-value">-</div>
                    <div class="stat-label">Total Siswa</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card-sm belum" id="statBelum">
                <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
                <div>
                    <div class="stat-value">-</div>
                    <div class="stat-label">Belum Ambil</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card-sm sebagian" id="statSebagian">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-value">-</div>
                    <div class="stat-label">Sebagian</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card-sm terjadwal" id="statTerjadwal">
                <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
                <div>
                    <div class="stat-value">-</div>
                    <div class="stat-label">Terjadwal</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card filter-card mb-3">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-funnel-fill" style="color:#7c3aed;"></i>
                <span class="fw-semibold" style="font-size:0.88rem; color:#374151;">Filter Data</span>
                <button class="btn btn-link btn-sm text-decoration-none p-0 ms-auto" id="btnResetFilter"
                    style="font-size:0.78rem; color:#7c3aed;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Semua
                </button>
            </div>
            <div class="row g-2">
                <div class="col-md">
                    <label>Divisi</label>
                    <select class="form-select" id="filterDivisi">
                        <option value="">Semua Divisi</option>
                        @foreach ($divisis as $divisi)
                            <option value="{{ $divisi->id }}">{{ $divisi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md">
                    <label>Tahun Ajaran</label>
                    <select class="form-select" id="filterTahunAjaran">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach ($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" data-divisi_id="{{ $ta->id_divisi }}">{{ $ta->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md">
                    <label>Gelombang</label>
                    <select class="form-select" id="filterGelombang">
                        <option value="">Semua Gelombang</option>
                        @foreach ($gelombangs as $g)
                            <option value="{{ $g->id }}" data-divisi_id="{{ $g->id_divisi }}"
                                data-tahun_ajaran_id="{{ $g->id_tahun_ajaran }}">{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md">
                    <label>Gender</label>
                    <select class="form-select" id="filterGender">
                        <option value="">Semua Gender</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="col-md">
                    <label>Jadwal</label>
                    <select class="form-select" id="filterJadwal">
                        <option value="">Semua Jadwal</option>
                        @foreach ($jadwal as $j)
                            <option value="{{ $j->id }}">
                                {{ \Carbon\Carbon::parse($j->tanggal)->translatedFormat('d M Y') }}
                                ({{ $j->sesi->count() }}
                                sesi)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-2 d-flex flex-wrap gap-1" id="activeFilters"></div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card data-card">
        <div class="card-header-custom">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table" style="color:#7c3aed;"></i>
                <span class="fw-semibold" style="font-size:0.9rem;">Daftar Siswa</span>
                <span class="meta-badge" id="recordCount">0 data</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0" id="dataTableSiswa" width="100%">
                    <thead>
                        <tr>
                            <th class="text-center" width="4%">No</th>
                            <th style="min-width:150px;">NIK / Info</th>
                            <th>Divisi</th>
                            <th style="min-width:160px;">Nama Lengkap</th>
                            <th class="text-center" width="8%">Gender</th>
                            <th style="min-width:160px;">TTL</th>
                            <th class="text-center" width="9%">Status</th>
                            <th class="text-center" style="min-width:120px;">Jadwal</th>
                            <th class="text-center" width="14%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Pilih Jadwal --}}
    <div class="modal fade modal-custom" id="modalPilihJadwal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-calendar-event me-2" style="color:#7c3aed;"></i>Pilih Jadwal Seragam
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="siswa_id">
                    <div class="d-flex align-items-center gap-2 mb-3 p-3 rounded-3" style="background:#f8f7ff;">
                        <i class="bi bi-person-fill" style="color:#7c3aed; font-size:1.2rem;"></i>
                        <div>
                            <div class="text-muted" style="font-size:0.75rem;">Nama Siswa</div>
                            <div id="nama_siswa" class="fw-bold" style="color:#7c3aed;"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">Tanggal Distribusi</label>
                        <select id="jadwal_select" class="form-select" style="border-radius:10px;">
                            <option value="">-- Pilih Tanggal --</option>
                        </select>
                    </div>
                    <div id="sesi_container">
                        <div class="text-muted text-center py-3" style="font-size:0.85rem;">
                            <i class="bi bi-calendar3 d-block mb-1" style="font-size:1.5rem;"></i>
                            Pilih tanggal terlebih dahulu
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary rounded-3 px-4" id="btnSimpanJadwal" disabled>
                        <i class="bi bi-check-circle me-1"></i> Simpan Jadwal
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection



@push('scripts')
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(function() {
            let table = $('#dataTableSiswa').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,

                dom: 'Bfrtip', // <-- ini penting untuk tombol

                buttons: [{
                    text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                    className: 'btn btn-success btn-sm ms-2',
                    action: function(e, dt, node, config) {
                        let params = $.param({
                            divisi: $('#filterDivisi').val(),
                            jadwal: $('#filterJadwal').val(),
                            gelombang: $('#filterGelombang').val(),
                            tahun_ajaran: $('#filterTahunAjaran').val(),
                            gender: $('#filterGender').val()
                        });

                        window.open("/nse/distribusi/export_excel?" + params, '_blank');
                    }
                }],


                ajax: {
                    url: "{{ route('nse.distribusi.list-siswa') }}",
                    data: function(d) {
                        d.divisi = $('#filterDivisi').val();
                        d.jadwal = $('#filterJadwal').val();
                        d.gelombang = $('#filterGelombang').val();
                        d.tahun_ajaran = $('#filterTahunAjaran').val();
                        d.gender = $('#filterGender').val();
                    }
                },
                order: [
                    [1, 'asc']
                ],
                columns: [{
                        data: 'DT_RowIndex',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nik',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `<div class="nik-text">${data}</div>
                            <div class="meta-text">
                                <span class="meta-badge">${row.namagelombang}</span>
                                <span class="meta-badge">${row.tahunajaran}</span>
                            </div>`;
                        }
                    },
                    {
                        data: 'namadivisi',
                        searchable: false,
                        render: function(data) {
                            return `<span class="fw-medium">${data || '-'}</span>`;
                        }
                    },
                    {
                        data: 'nama_lengkap',
                        render: function(data) {
                            return `<span class="fw-semibold" style="color:#374151;">${data}</span>`;
                        }
                    },
                    {
                        data: 'jk',
                        className: 'text-center',
                        render: function(data) {
                            if (data === 'L' || data === 'Laki-laki') {
                                return '<span class="badge-gender lk"><i class="bi bi-gender-male"></i> L</span>';
                            } else {
                                return '<span class="badge-gender pr"><i class="bi bi-gender-female"></i> P</span>';
                            }
                        }
                    },
                    {
                        data: 'tgl_lahir'
                    },
                    {
                        data: 'ambil_seragam',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'jadwal',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    }
                ],
                drawCallback: function(settings) {
                    let info = this.api().page.info();
                    $('#recordCount').text(info.recordsDisplay + ' data');

                    // Update stat cards from response
                    let json = this.api().ajax.json();
                    if (json && json.stats) {
                        $('#statTotal .stat-value').text(json.stats.total ?? '0');
                        $('#statBelum .stat-value').text(json.stats.belum ?? '0');
                        $('#statSebagian .stat-value').text(json.stats.sebagian ?? '0');
                        $('#statTerjadwal .stat-value').text(json.stats.terjadwal ?? '0');
                    }
                },
                language: {
                    processing: '<div class="d-flex align-items-center gap-2"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat data...</div>',
                    emptyTable: '<div class="text-center py-4 text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>Tidak ada data ditemukan</div>',
                    zeroRecords: '<div class="text-center py-4 text-muted"><i class="bi bi-search d-block mb-2" style="font-size:2rem;"></i>Tidak ada data yang cocok</div>'
                }
            });

            // === Cascading Filter Logic ===
            function filterCascadeTahunAjaran() {
                const divisiId = $('#filterDivisi').val();

                // Filter Tahun Ajaran berdasarkan Divisi
                $('#filterTahunAjaran option').each(function() {
                    const optDivisi = $(this).data('divisi_id');
                    if (!$(this).val()) return; // skip "Semua" option
                    $(this).toggle(!divisiId || String(optDivisi) === String(divisiId));
                });
                // Reset jika pilihan saat ini tersembunyi
                if ($('#filterTahunAjaran option:selected').is(':hidden')) {
                    $('#filterTahunAjaran').val('');
                }
            }

            function filterCascadeGelombang() {
                const divisiId = $('#filterDivisi').val();
                const currentTahunAjaran = $('#filterTahunAjaran').val();

                // Filter Gelombang berdasarkan Divisi & Tahun Ajaran
                $('#filterGelombang option').each(function() {
                    const optDivisi = $(this).data('divisi_id');
                    const optTa = $(this).data('tahun_ajaran_id');
                    if (!$(this).val()) return; // skip "Semua" option
                    const matchDivisi = !divisiId || String(optDivisi) === String(divisiId);
                    const matchTa = !currentTahunAjaran || String(optTa) === String(currentTahunAjaran);
                    $(this).toggle(matchDivisi && matchTa);
                });
                // Reset jika pilihan saat ini tersembunyi
                if ($('#filterGelombang option:selected').is(':hidden')) {
                    $('#filterGelombang').val('');
                }
            }

            // Divisi berubah → cascade ke Tahun Ajaran & Gelombang
            $('#filterDivisi').change(function() {
                filterCascadeTahunAjaran();
                filterCascadeGelombang();
                table.ajax.reload();
                updateActiveFilters();
            });

            // Tahun Ajaran berubah → cascade hanya Gelombang (tidak reset Tahun Ajaran)
            $('#filterTahunAjaran').change(function() {
                filterCascadeGelombang();
                table.ajax.reload();
                updateActiveFilters();
            });

            // Filter lain langsung reload
            $('#filterGelombang, #filterJadwal, #filterGender').change(function() {
                table.ajax.reload();
                updateActiveFilters();
            });

            // Reset all filters
            $('#btnResetFilter').click(function() {
                $('#filterDivisi, #filterJadwal, #filterGelombang, #filterTahunAjaran, #filterGender').val(
                    '');
                // Show all options again
                $('#filterTahunAjaran option, #filterGelombang option').show();
                table.ajax.reload();
                updateActiveFilters();
            });

            function updateActiveFilters() {
                let html = '';
                const filters = [{
                        el: '#filterTahunAjaran',
                        label: 'Tahun Ajaran'
                    },
                    {
                        el: '#filterGelombang',
                        label: 'Gelombang'
                    },
                    {
                        el: '#filterDivisi',
                        label: 'Divisi'
                    },
                    {
                        el: '#filterGender',
                        label: 'Gender'
                    },
                    {
                        el: '#filterJadwal',
                        label: 'Jadwal'
                    }
                ];
                filters.forEach(f => {
                    const sel = $(f.el);
                    if (sel.val()) {
                        const text = sel.find('option:selected').text();
                        html += `<span class="active-filter-badge" data-target="${f.el}">
                    <i class="bi bi-tag-fill" style="font-size:0.6rem;"></i>
                    ${f.label}: ${text}
                    <i class="bi bi-x-lg" style="font-size:0.6rem;"></i>
                </span>`;
                    }
                });
                $('#activeFilters').html(html);

                // Click to remove individual filter
                $('.active-filter-badge').click(function() {
                    const target = $(this).data('target');
                    $(target).val('');
                    filterCascadeTahunAjaran();
                    filterCascadeGelombang();
                    table.ajax.reload();
                    updateActiveFilters();
                });
            }
        });
    </script>
    <script>
        let selectedSesi = null;

        // klik tombol "Pilih Jadwal"
        $(document).on('click', '.pilih-jadwal', function() {
            const siswaId = $(this).data('id');
            const nama = $(this).data('nama');

            $('#siswa_id').val(siswaId);
            $('#nama_siswa').text(nama);
            $('#jadwal_select').html('<option value="">Loading...</option>');
            $('#sesi_container').html('');
            $('#btnSimpanJadwal').prop('disabled', true);

            $('#modalPilihJadwal').modal('show');

            loadJadwal(siswaId);
        });

        // load jadwal aktif
        function loadJadwal(siswaId) {
            $.post("{{ route('nse.distribusi.jadwal-aktif') }}", {
                _token: '{{ csrf_token() }}',
                siswa_id: siswaId
            }, function(res) {
                let opt = '<option value="">-- Pilih Tanggal --</option>';
                res.forEach(j => {
                    opt += `<option value="${j.id}">${j.tanggal}</option>`;
                });
                $('#jadwal_select').html(opt);
            });
        }

        // saat pilih tanggal
        $('#jadwal_select').on('change', function() {
            const jadwalId = $(this).val();
            $('#btnSimpanJadwal').prop('disabled', true);
            selectedSesi = null;

            if (!jadwalId) return;

            $('#sesi_container').html(
                '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat sesi...</div>'
            );

            $.post("{{ route('nse.distribusi.jadwal-aktif') }}", {
                _token: '{{ csrf_token() }}',
                siswa_id: $('#siswa_id').val()
            }, function(res) {
                const jadwal = res.find(j => j.id == jadwalId);
                let html = '';

                jadwal.sesi.forEach(s => {
                    const penuh = s.peserta_count >= s.kuota_sesi;
                    const badge = penuh ?
                        '<span class="badge bg-danger" style="font-size:0.7rem;">Penuh</span>' :
                        '<span class="badge bg-success" style="font-size:0.7rem;">Tersedia</span>';

                    html += `
                    <div class="sesi-option ${penuh ? 'disabled' : ''}" data-sesi-id="${s.id}" ${penuh ? '' : 'onclick="selectSesi(this)"'}>
                        <input class="form-check-input sesi-radio" type="radio" name="sesi" value="${s.id}" ${penuh ? 'disabled' : ''} style="display:none;">
                        <div class="flex-grow-1">
                            <div class="sesi-time"><i class="bi bi-clock me-1"></i>${s.jam_mulai} - ${s.jam_selesai}</div>
                            <div class="sesi-quota">${s.peserta_count} / ${s.kuota_sesi} peserta ${badge}</div>
                        </div>
                        <div>
                            <div class="progress" style="width:80px; height:6px;">
                                <div class="progress-bar ${penuh ? 'bg-danger' : 'bg-success'}" style="width:${Math.min(100, (s.peserta_count/s.kuota_sesi)*100)}%"></div>
                            </div>
                        </div>
                    </div>
                `;
                });

                $('#sesi_container').html(html);
            });
        });

        function selectSesi(el) {
            document.querySelectorAll('.sesi-option').forEach(s => s.classList.remove('selected'));
            el.classList.add('selected');
            const radio = el.querySelector('.sesi-radio');
            radio.checked = true;
            selectedSesi = radio.value;
            $('#btnSimpanJadwal').prop('disabled', false);
        }

        // simpan jadwal
        $('#btnSimpanJadwal').on('click', function() {
            const siswaId = $('#siswa_id').val();

            if (!selectedSesi) return;

            $(this).prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

            $.post("{{ route('nse.distribusi.jadwal-book') }}", {
                    _token: '{{ csrf_token() }}',
                    siswa_id: siswaId,
                    sesi_id: selectedSesi
                })
                .done(() => {
                    $('#modalPilihJadwal').modal('hide');
                    $('.dataTable').DataTable().ajax.reload(null, false);
                    Swal.fire('Jadwal berhasil disimpan');
                })
                .fail(res => {
                    Swal.fire('Gagal menyimpan jadwal', res.responseJSON?.message ?? '', 'error');
                })
                .always(() => {
                    $('#btnSimpanJadwal').prop('disabled', false).html(
                        '<i class="bi bi-check-circle me-1"></i> Simpan Jadwal');
                });
        });
    </script>
@endpush
