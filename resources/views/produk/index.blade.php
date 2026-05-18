@extends('layouts.main.main')
@section('title', 'Manajemen Produk')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan Produk</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Pengaturan</a></li>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Pengaturan Produk</h5>
                            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                        </div>
                    </div>
                    <a href="{{ route('produk.create') }}" class="btn btn-success btn-sm mb-3">
                        <i class="bi bi-plus"></i> Tambah Produk
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table id="tbl-produk" class="table table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Produk</th>
                                <th class="text-center" width="8%">Varian</th>
                                <th class="text-center" width="10%">Stok Gudang</th>
                                <th class="text-center" width="10%">Stok Toko</th>
                                <th class="text-center" width="12%">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#tbl-produk').DataTable({
                serverSide: true,
                processing: true,
                ajax: '{{ route('produk.datatables') }}',
                columns: [{
                        data: 'kode_produk',
                        name: 'kode_produk'
                    },
                    {
                        data: 'nama_produk',
                        name: 'nama_produk'
                    },
                    {
                        data: 'variants_count',
                        name: 'variants_count',
                        searchable: false,
                        className: 'text-end'
                    },
                    {
                        data: 'stock_warehouse',
                        name: 'stock_warehouse',
                        searchable: false,
                        className: 'text-end',
                        render: d => d ?? 0
                    },
                    {
                        data: 'stock_store',
                        name: 'stock_store',
                        searchable: false,
                        className: 'text-end',
                        render: d => d ?? 0
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                ],
                order: [
                    [1, 'asc']
                ],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ – _END_ dari _TOTAL_ produk',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '(difilter dari _MAX_ total)',
                    zeroRecords: 'Produk tidak ditemukan',
                    paginate: {
                        previous: '&laquo;',
                        next: '&raquo;'
                    },
                    processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Memuat...',
                },
            });
        });
    </script>
@endpush
