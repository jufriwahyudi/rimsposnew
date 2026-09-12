@extends('layouts.main.main')
@section('title', 'Data Diskon & Promosi')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Master Data</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Diskon & Promosi</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <!-- Header Card -->
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#0284c7">Pengaturan Diskon & Promosi</h5>
                            <small class="text-muted">{{ session('store_name', 'Semua Toko') }} &bull; Kelola promo terjadwal, voucher kupon & diskon bertarget</small>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Promo / Diskon
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="tbl-discounts">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">#</th>
                                    <th>Nama Promo</th>
                                    <th width="120">Kode Kupon</th>
                                    <th class="text-center" width="140">Besaran Diskon</th>
                                    <th>Syarat & Ketentuan</th>
                                    <th class="text-center" width="130">Target</th>
                                    <th class="text-center" width="160">Periode Berlaku</th>
                                    <th class="text-center" width="100">Dipakai</th>
                                    <th class="text-center" width="100">Status</th>
                                    <th class="text-center" width="110">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($discounts as $i => $d)
                                    @php
                                        $today = now()->toDateString();
                                        $isExpired = $d->end_date && $d->end_date->toDateString() < $today;
                                        $isUpcoming = $d->start_date && $d->start_date->toDateString() > $today;
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $d->name }}</div>
                                            @if($d->description)
                                                <small class="text-muted">{{ Str::limit($d->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($d->code)
                                                <span class="badge bg-secondary font-monospace"><i class="material-icons-outlined" style="font-size:11px;vertical-align:middle">confirmation_number</i> {{ $d->code }}</span>
                                            @else
                                                <span class="text-muted small">Otomatis di POS</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($d->discount_type === 'percentage')
                                                <span class="badge bg-primary fs-6">{{ $d->discount_value }}%</span>
                                                @if($d->max_discount_amount > 0)
                                                    <div class="text-muted small mt-1">Maks. Rp {{ number_format($d->max_discount_amount, 0, ',', '.') }}</div>
                                                @endif
                                            @else
                                                <span class="badge bg-success fs-6">Rp {{ number_format($d->discount_value, 0, ',', '.') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($d->min_purchase_amount > 0)
                                                <div class="small"><i class="material-icons-outlined text-primary" style="font-size:13px;vertical-align:middle">shopping_bag</i> Min. Belanja: <strong>Rp {{ number_format($d->min_purchase_amount, 0, ',', '.') }}</strong></div>
                                            @else
                                                <div class="small text-muted">Tanpa minimum belanja</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($d->target_type === 'member_only')
                                                <span class="badge bg-warning text-dark"><i class="material-icons-outlined" style="font-size:12px;vertical-align:middle">card_membership</i> Member Saja</span>
                                            @else
                                                <span class="badge bg-light text-dark border">Semua Pelanggan</span>
                                            @endif

                                            <div class="mt-1">
                                                @if($d->scope_type === 'product')
                                                    <span class="badge bg-info text-dark" style="font-size:10px;">
                                                        <i class="material-icons-outlined" style="font-size:11px;vertical-align:middle">inventory_2</i> {{ $d->variants->count() }} Produk
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border" style="font-size:10px;">Semua Produk</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center small">
                                            @if($d->start_date || $d->end_date)
                                                <div>{{ $d->start_date ? $d->start_date->format('d M Y') : 'Sekarang' }}</div>
                                                <div class="text-muted">s/d {{ $d->end_date ? $d->end_date->format('d M Y') : 'Seterusnya' }}</div>
                                            @else
                                                <span class="text-success fw-bold">Selalu Berlaku</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border">
                                                {{ $d->sales_count }}x
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if(!$d->is_active)
                                                <span class="badge bg-danger">Non-aktif</span>
                                            @elseif($isExpired)
                                                <span class="badge bg-secondary">Kedaluwarsa</span>
                                            @elseif($isUpcoming)
                                                <span class="badge bg-info text-dark">Mendatang</span>
                                            @else
                                                <span class="badge bg-success">Aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-outline-primary btn-sm p-1" onclick="openEditModal({{ $d->id }})" title="Edit">
                                                <i class="material-icons-outlined" style="font-size:16px">edit</i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm p-1" onclick="deleteDiscount({{ $d->id }}, '{{ addslashes($d->name) }}')" title="Hapus">
                                                <i class="material-icons-outlined" style="font-size:16px">delete</i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="material-icons-outlined font-35 text-secondary d-block mb-1">local_offer</i>
                                            Belum ada data promosi/diskon untuk toko ini.<br>
                                            Klik tombol <strong>Tambah Promo / Diskon</strong> untuk membuat promo baru.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Diskon -->
    <div class="modal fade" id="modal-discount" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="form-discount" onsubmit="submitForm(event)">
                    @csrf
                    <input type="hidden" id="discount_id" name="id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-title">Tambah Promosi / Diskon</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Nama Promosi / Diskon <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Contoh: Diskon Gajian 10%, Promo Hari Pelanggan">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Kode Kupon / Voucher</label>
                                <input type="text" class="form-control text-uppercase" id="code" name="code" placeholder="Cth: GAJIAN10 (Opsional)">
                                <small class="text-muted font-11">Kosongkan jika ingin tampil otomatis di kasir</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tipe Diskon <span class="text-danger">*</span></label>
                                <select class="form-select" id="discount_type" name="discount_type" required onchange="toggleDiscountType()">
                                    <option value="percentage">Persentase (%)</option>
                                    <option value="nominal">Potongan Nominal (Rp)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold" id="label-discount-value">Nilai Diskon (%) <span class="text-danger">*</span></label>
                                <input type="number" step="any" class="form-control" id="discount_value" name="discount_value" required min="0" placeholder="10">
                            </div>
                            <div class="col-md-4" id="col-max-discount">
                                <label class="form-label fw-bold">Maksimal Diskon (Rp)</label>
                                <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" min="0" placeholder="Cth: 50000">
                                <small class="text-muted font-11">Kosongkan jika tanpa batas maksimal</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Minimal Pembelian (Rp)</label>
                                <input type="number" class="form-control" id="min_purchase_amount" name="min_purchase_amount" min="0" value="0" placeholder="0">
                                <small class="text-muted font-11">Subtotal belanja minimum agar promo aktif</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Target Pelanggan <span class="text-danger">*</span></label>
                                <select class="form-select" id="target_type" name="target_type" required>
                                    <option value="all">Semua Pelanggan (Umum & Member)</option>
                                    <option value="member_only">Hanya Pelanggan Terdaftar (Member Saja)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cakupan Produk Promo <span class="text-danger">*</span></label>
                                <select class="form-select" id="scope_type" name="scope_type" required onchange="toggleScopeType()">
                                    <option value="all">Semua Produk (Total Transaksi)</option>
                                    <option value="product">Produk / Varian Tertentu Saja</option>
                                </select>
                            </div>

                            <div class="col-12 d-none" id="col-products">
                                <label class="form-label fw-bold">Pilih Produk / Varian Promo <span class="text-danger">*</span></label>
                                <select class="form-select" id="variant_ids" name="variant_ids[]" multiple style="width: 100%;">
                                    @if(isset($products))
                                        @foreach($products as $p)
                                            <optgroup label="{{ $p->nama_produk }}">
                                                @foreach($p->variants as $v)
                                                    <option value="{{ $v->id }}">
                                                        {{ $p->nama_produk }} {{ $v->variant_name ? '('.$v->variant_name.')' : '' }} - Rp {{ number_format($v->harga_jual, 0, ',', '.') }} {{ $v->sku ? '['.$v->sku.']' : '' }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="text-muted font-11">Pilih satu atau lebih barang. Diskon hanya akan memotong subtotal barang-barang terpilih.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal Mulai Berlaku</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                                <small class="text-muted font-11">Kosongkan jika langsung berlaku sekarang</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal Selesai Berlaku</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                                <small class="text-muted font-11">Kosongkan jika berlaku seterusnya</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Keterangan / Catatan Promo</label>
                                <textarea class="form-control" id="description" name="description" rows="2" placeholder="Catatan syarat & ketentuan promo (opsional)"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label fw-bold" for="is_active">Aktifkan Promosi Ini</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save">
                            <span class="spinner-border spinner-border-sm d-none" id="btn-spinner"></span>
                            Simpan Promo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let modalEl = null;

    $(document).ready(function() {
        modalEl = new bootstrap.Modal(document.getElementById('modal-discount'));
        if ($.fn.select2) {
            $('#variant_ids').select2({
                dropdownParent: $('#modal-discount'),
                placeholder: 'Pilih satu atau lebih produk/varian...',
                allowClear: true,
                width: '100%'
            });
        }
    });

    function toggleDiscountType() {
        const type = $('#discount_type').val();
        if (type === 'percentage') {
            $('#label-discount-value').html('Nilai Diskon (%) <span class="text-danger">*</span>');
            $('#discount_value').attr('placeholder', '10');
            $('#col-max-discount').show();
        } else {
            $('#label-discount-value').html('Nilai Potongan (Rp) <span class="text-danger">*</span>');
            $('#discount_value').attr('placeholder', '15000');
            $('#col-max-discount').hide();
            $('#max_discount_amount').val('');
        }
    }

    function toggleScopeType() {
        const scope = $('#scope_type').val();
        if (scope === 'product') {
            $('#col-products').removeClass('d-none');
        } else {
            $('#col-products').addClass('d-none');
        }
    }

    function openCreateModal() {
        $('#form-discount')[0].reset();
        $('#discount_id').val('');
        $('#modal-title').text('Tambah Promosi / Diskon');
        $('#is_active').prop('checked', true);
        $('#scope_type').val('all');
        toggleScopeType();
        if ($.fn.select2) {
            $('#variant_ids').val([]).trigger('change');
        }
        toggleDiscountType();
        modalEl.show();
    }

    function openEditModal(id) {
        $.ajax({
            url: `{{ url('discounts') }}/${id}/edit`,
            type: 'GET',
            dataType: 'json',
            success: function(d) {
                $('#discount_id').val(d.id);
                $('#name').val(d.name);
                $('#code').val(d.code);
                $('#discount_type').val(d.discount_type);
                $('#discount_value').val(d.discount_value);
                $('#min_purchase_amount').val(d.min_purchase_amount);
                $('#max_discount_amount').val(d.max_discount_amount);
                $('#target_type').val(d.target_type);
                $('#scope_type').val(d.scope_type || 'all');
                toggleScopeType();
                if ($.fn.select2) {
                    $('#variant_ids').val(d.variant_ids || []).trigger('change');
                } else {
                    $('#variant_ids').val(d.variant_ids || []);
                }
                $('#start_date').val(d.start_date ? d.start_date.substring(0, 10) : '');
                $('#end_date').val(d.end_date ? d.end_date.substring(0, 10) : '');
                $('#description').val(d.description);
                $('#is_active').prop('checked', d.is_active);

                toggleDiscountType();
                $('#modal-title').text('Edit Promosi / Diskon');
                modalEl.show();
            },
            error: function(xhr) {
                Swal.fire('Error', 'Gagal memuat data promosi: ' + (xhr.responseJSON?.message || xhr.statusText), 'error');
            }
        });
    }

    function submitForm(e) {
        e.preventDefault();
        const id = $('#discount_id').val();
        const isEdit = !!id;
        const url = isEdit ? `{{ url('discounts') }}/${id}` : `{{ route('discounts.store') }}`;

        const data = {
            _token: '{{ csrf_token() }}',
            name: $('#name').val(),
            code: $('#code').val(),
            discount_type: $('#discount_type').val(),
            discount_value: $('#discount_value').val(),
            min_purchase_amount: $('#min_purchase_amount').val(),
            max_discount_amount: $('#max_discount_amount').val(),
            target_type: $('#target_type').val(),
            scope_type: $('#scope_type').val(),
            variant_ids: $('#variant_ids').val() || [],
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            description: $('#description').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0,
        };

        if (isEdit) {
            data._method = 'PUT';
        }

        $('#btn-save').prop('disabled', true);
        $('#btn-spinner').removeClass('d-none');

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                modalEl.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false,
                }).then(() => {
                    window.location.reload();
                });
            },
            error: function(xhr) {
                $('#btn-save').prop('disabled', false);
                $('#btn-spinner').addClass('d-none');
                let msg = xhr.responseJSON?.message || 'Terjadi kesalahan sistem.';
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    html: msg,
                });
            }
        });
    }

    function deleteDiscount(id, name) {
        Swal.fire({
            title: 'Hapus Promosi?',
            text: `Apakah Anda yakin ingin menghapus promo "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('discounts') }}/${id}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    dataType: 'json',
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menghapus data promo.', 'error');
                    }
                });
            }
        });
    }
</script>
@endpush
