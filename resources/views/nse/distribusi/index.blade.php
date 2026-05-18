@extends('layouts.main.main')
@section('title', 'POS - Point of Sale')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex align-items-center mb-3">
                    <img src="{{ asset('assets/images/alazca_logo.png') }}" width="35" class="me-2">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Distribusi Seragam NSE</h5>
                        <small class="text-muted">{{ session('store_name') }}</small>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Scan Siswa --}}
                    <input type="text" id="scan-siswa" class="form-control form-control-lg"
                        placeholder="Scan QR / ID / Ketik Nama Calon Siswa" readonly autofocus>
                    <div id="siswa-result" class="list-group position-absolute w-100 d-none" style="z-index:1000"></div>
                    <hr>

                    {{-- Info siswa --}}
                    <div id="info-siswa" class="d-none">
                        <h5 id="nama"></h5>
                        <p id="tmpttgl_lhr" class="mb-0"></p>
                        <p id="divisi"></p>

                        <input type="hidden" id="id_biodata">

                        {{-- Tanggal Transaksi --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small mb-1">Tanggal Transaksi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
                                <input type="date" id="transactionDate" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        {{-- Scan Barang --}}
                        <input type="text" id="scan-barang" class="form-control form-control-lg"
                            placeholder="Scan Barcode Barang" autocomplete="off">
                    </div>

                    <hr>

                    {{-- List Seragam --}}
                    <div id="chooser" class="alert alert-warning d-none"></div>

                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="5%">Status</th>
                                <th>Nama Seragam</th>
                                <th>SKU</th>
                                <th class="text-center">Qty</th>
                                {{-- <th class="text-end">Harga</th> --}}
                                {{-- <th class="text-end">Total</th> --}}
                                <th class="text-center" width="5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="list-seragam"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-end">
                                    <button class="btn btn-secondary" onclick="finishNse()">
                                        Selesai
                                    </button>
                                    <button class="btn btn-success" onclick="checkoutNse()">
                                        Simpan
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('initial-scripts')
    <script>
        window.AKUN_BANK = @json($akunkas);
        window.AKUN_KASIR = '{{ $akunkasir }}';
    </script>
@endpush
@push('scripts')
    <script>
        const id_biodata = {{ request()->get('id_biodata', 'null') }};
        if (id_biodata) {
            loadSiswaById(id_biodata);
            document.getElementById('scan-siswa').value = id_biodata;
        } else {
            document.getElementById('scan-siswa').removeAttribute('readonly');
            document.getElementById('scan-siswa').focus();
        }
        /* ===============================
         * SCAN / SEARCH SISWA
         * =============================== */
        const scanInput = document.getElementById('scan-siswa');
        const resultBox = document.getElementById('siswa-result');
        let typingTimer = null;
        let selectingSiswa = false;

        let chooserOptions = [];
        let chooserBarcode = null;
        let chooserActive = false;

        scanInput.addEventListener('input', function() {
            if (selectingSiswa) return;

            const val = this.value.trim();

            // 👉 Kalau angka → anggap scan QR / ID
            if (/^\d+$/.test(val)) {
                resultBox.classList.add('d-none');
                return;
            }

            clearTimeout(typingTimer);

            typingTimer = setTimeout(() => {
                if (val.length < 3) {
                    resultBox.classList.add('d-none');
                    return;
                }

                fetch('/nse/distribusi/search-siswa', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: new URLSearchParams({
                            q: val
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.length) {
                            resultBox.classList.add('d-none');
                            return;
                        }

                        let html = '';
                        data.forEach(s => {
                            html += `
                                <button class="list-group-item list-group-item-action"
                                    onclick="selectSiswa(${s.id_biodatadiri})">
                                    <strong>${s.nama_lengkap}</strong><br>
                                    <small class="text-muted">${s.tgl_lahir}</small>
                                </button>
                            `;
                        });

                        resultBox.innerHTML = html;
                        resultBox.classList.remove('d-none');
                    });
            }, 400);
        });

        function selectSiswa(id) {
            resultBox.classList.add('d-none');
            scanInput.value = id;

            loadSiswaById(id);
        }


        /* ===============================
         * LOAD SISWA
         * =============================== */
        scanInput.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;

            const val = this.value.trim();
            if (!val) return;

            // hanya angka = scan / input ID
            if (!/^\d+$/.test(val)) return;

            loadSiswaById(val);
        });


        function loadSiswaById(id) {
            fetch('/nse/distribusi/load-siswa', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new URLSearchParams({
                        id: id
                    })
                })
                .then(async r => {
                    const data = await r.json();
                    if (!r.ok) throw data;
                    return data;
                })
                .then(res => {
                    document.getElementById('info-siswa').classList.remove('d-none');

                    document.getElementById('nama').innerText = res.siswa.nama_lengkap;
                    document.getElementById('tmpttgl_lhr').innerText = res.siswa.tempat_lahir + ', ' +
                        formatTanggal(res.siswa.tgl_lahir);
                    document.getElementById('divisi').innerText = 'DIVISI ' + res.siswa.divisi.nama;
                    document.getElementById('id_biodata').value = res.siswa.id_biodatadiri;

                    renderList(res.items);
                    document.getElementById('scan-barang').focus();
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.message || 'Siswa tidak ditemukan'
                    });
                    scanInput.value = '';
                });
        }
        /* ===============================
         * SCAN BARANG
         * =============================== */
        document.getElementById('scan-barang').addEventListener('change', function() {
            const barcode = this.value.trim();
            if (!barcode) return;

            fetch('/nse/distribusi/scan-barang', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new URLSearchParams({
                        barcode,
                        id_biodata: document.getElementById('id_biodata').value
                    })
                })
                .then(async r => {
                    const data = await r.json();
                    if (!r.ok) throw data;
                    return data;
                })
                .then(res => {
                    if (res.mode === 'choose') {
                        showChooser(res);
                    } else {
                        renderList(res.items);
                        resetScan();
                    }
                })
                .catch(err => {
                    if (err.code === 'SLOT_ALREADY_FULFILLED') {
                        // Konfirmasi apakah ingin menambahkan item meskipun slot sudah terpenuhi
                        Swal.fire({
                            icon: 'warning',
                            title: 'Slot Sudah Terpenuhi',
                            text: err.message,
                            showCancelButton: true,
                            confirmButtonText: 'OK',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // User memilih untuk menambahkan item, lakukan request konfirmasi item
                                // chooseItem(err.id_seragam, err.productVariant.barcode, true);
                                return;
                            }
                        });
                    }
                    // apabila code=STOK_KURANG maka tampilkan pesan stok kurang, lalu ada konfirmasi apakah ingin menambahkan item dengan status pending meskipun stok kurang
                    else if (err.code === 'STOK_KURANG') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok Kurang',
                            text: err.message,
                            showCancelButton: true,
                            confirmButtonText: 'Tambah Item dengan Status Pending',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // User memilih untuk menambahkan item dengan status pending, lakukan request konfirmasi item
                                keepItem(err.slot_id, err.variant_id);
                            }
                        });
                    }
                    // error lainnya
                    else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.message || 'Scan gagal'
                        });
                    }
                    resetScan();
                });
        });

        /* ===============================
         * CHOOSER
         * =============================== */
        function showChooser(res) {
            chooserOptions = res.options;
            chooserBarcode = res.variant.barcode;
            chooserActive = true;

            let html = `
                <strong>${res.variant.sku}</strong><br><strong>${res.variant.nama_produk} (${res.variant.label_variant})</strong>
                <div class="mt-2">
            `;

            res.options.forEach((o, idx) => {
                html += `
                    <button class="btn btn-sm btn-primary me-2 mb-2"
                        onclick="chooseItem(${o.id_seragam}, '${res.variant.barcode}')">
                        [${idx + 1}] ${o.nama}
                    </button>
                `;
            });

            html += '</div>';

            const chooser = document.getElementById('chooser');
            chooser.innerHTML = html;
            chooser.classList.remove('d-none');

            document.addEventListener('keydown', chooserKeyHandler);
        }

        function chooserKeyHandler(e) {
            if (!chooserActive) return;

            // hanya angka 1–9
            if (e.key < '1' || e.key > '9') return;

            const index = parseInt(e.key, 10) - 1;

            if (!chooserOptions[index]) return;

            e.preventDefault();

            chooseItem(
                chooserOptions[index].id_seragam,
                chooserBarcode
            );
        }


        /* ===============================
         * KEEP ITEM
         * =============================== */
        function keepItem(slot_id, variant_id) {
            $.ajax({
                url: '{{ route('nse.distribusi.keep-item') }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    slot_id,
                    variant_id
                },
                success: function(res) {
                    renderList(res.items);
                    resetScan();
                },
                error: function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.responseJSON?.message || 'Gagal menyimpan item'
                    });
                    resetScan();
                }
            });
        }

        /* ===============================
         * CONFIRM ITEM
         * =============================== */
        function chooseItem(id_seragam, barcode, isAdditional = false) {
            chooserActive = false;
            document.removeEventListener('keydown', chooserKeyHandler);
            document.getElementById('chooser').classList.add('d-none');

            fetch('/nse/distribusi/confirm-item', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new URLSearchParams({
                        id_seragam,
                        barcode,
                        id_biodata: document.getElementById('id_biodata').value,
                        is_additional: isAdditional ? 'Y' : 'N'
                    })
                })
                .then(async r => {
                    const data = await r.json();
                    if (!r.ok) throw data;
                    return data;
                })
                .then(res => {
                    document.getElementById('chooser').classList.add('d-none');
                    renderList(res.items);
                    resetScan();
                })
                .catch(err => {
                    if (err.code === 'SLOT_ALREADY_FULFILLED') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Slot Sudah Terpenuhi',
                            text: err.message,
                            showCancelButton: true,
                            confirmButtonText: 'OK',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // User memilih untuk menambahkan item, lakukan request konfirmasi item
                                // chooseItem(err.id_seragam, err.productVariant.barcode, true);
                                return;
                            }
                        });
                    }
                    // apabila code=STOK_KURANG maka tampilkan pesan stok kurang, lalu ada konfirmasi apakah ingin menambahkan item dengan status pending meskipun stok kurang
                    else if (err.code === 'STOK_KURANG') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok Kurang',
                            text: err.message,
                            showCancelButton: true,
                            confirmButtonText: 'Tambah Item dengan Status Pending',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // User memilih untuk menambahkan item dengan status pending, lakukan request konfirmasi item
                                keepItem(err.slot_id, err.variant_id);
                            }
                        });
                    }
                    // error lainnya
                    else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.message || 'Stok tidak mencukupi'
                        });
                    }
                    resetScan();
                });
        }
        /* ===============================
         * UTIL
         * =============================== */
        function renderList(items) {
            let html = '';
            let grandTotal = 0;
            items.forEach(i => {
                let fulfilled = (i.status === 'fulfilled' || i.status === 'completed');
                let harga = i.product_variant?.batches?.[0]?.harga_beli ?? 0;
                let total = harga * i.qty;
                if (fulfilled) grandTotal += total;
                html += `<tr class="${fulfilled ? 'table-success' : ''}"> 
                    <td class="text-center"> ${fulfilled ? '✔' : '⬜'}</td> 
                    <td>${i.seragam.nama}</td> <td> ${i.product_variant?.sku ?? '-'}</td>
                    <td class="text-center">${i.qty}</td> 
                    <td class="text-center"> ${i.status === 'fulfilled' ? `<button class="btn btn-sm btn-danger" onclick="deleteItem(${i.id})"> <i class="bi bi-trash"></i></button>` : ''} </td> 
                    </tr>`;
            });
            document.getElementById('list-seragam').innerHTML = html;
        }

        function deleteItem(id) {
            const id_biodata = document.getElementById('id_biodata').value;
            fetch('/nse/distribusi/delete-item/' + id_biodata, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    delete_id: id,
                })
            }).then(r => r.json()).then(res => {
                renderList(res.items);
            }).catch(err => {
                alert('Hapus item gagal');
            });
        }

        function checkoutNse() {
            Swal.fire({
                title: 'Konfirmasi Checkout',
                text: 'Masukkan nama penerima',
                input: 'text',
                inputPlaceholder: 'Nama penerima...',
                showCancelButton: true,
                confirmButtonText: 'Checkout',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Nama penerima wajib diisi!';
                    }
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                const receiptName = result.value;
                Swal.fire({
                    title: 'Memproses checkout...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
                fetch('/nse/distribusi/checkout', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            id_biodata: document.getElementById('id_biodata').value,
                            transaction_date: document.getElementById('transactionDate').value,
                            receipt_name: receiptName
                        })
                    })
                    .then(async r => {
                        const data = await r.json();
                        if (!r.ok) throw data;
                        return data;
                    })
                    .then(res => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sukses',
                            text: res.message
                        }).then(() => {
                            window.open('/nse/distribusi/cetak/' + res.sale_id + '/download', '_blank');
                        });
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: err.message || 'Checkout gagal'
                        });
                    });
            });
        }

        function finishNse() {
            Swal.fire({
                title: 'Selesai Distribusi NSE',
                text: 'Fitur ini akan menandai distribusi NSE sebagai selesai walaupun beberapa item belum diproses. Apakah Anda yakin ingin melanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesai',
                cancelButtonText: 'Belum'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect ke halaman utama atau lakukan aksi lain sesuai kebutuhan
                    $.ajax({
                        url: '{{ route('nse.distribusi.finish') }}',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        data: {
                            id_biodata: document.getElementById('id_biodata').value
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: 'Memproses...',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading()
                                }
                            });
                        },
                        success: function(response) {
                            Swal.close();
                            Swal.fire({
                                icon: 'success',
                                title: 'Distribusi NSE Selesai',
                                text: response.message
                            }).then(() => {
                                window.location.href =
                                    '{{ route('nse.distribusi.list-siswa') }}';
                            });
                        },
                        error: function(xhr) {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Gagal menyelesaikan distribusi NSE'
                            });
                        }
                    });
                }
            });
        }


        function resetScan() {
            const s = document.getElementById('scan-barang');
            s.value = '';
            s.focus();
        }

        function formatTanggal(s) {
            if (!s) return '';
            const p = s.split('-');
            return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : s;
        }

        function rupiah(num) {
            return num ?
                'Rp ' + new Intl.NumberFormat('id-ID').format(num) :
                '-';
        }
    </script>
@endpush
