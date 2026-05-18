@extends('layouts.main.main')
@section('title', 'Purchase Orders')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Setoran Kasir</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Setoran Kasir</a></li>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Setoran Kasir</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <a href="{{ route('frontliner.create') }}" class="btn btn-success btn-sm mb-3"><i
                            class="bi bi-plus"></i>
                        Tambah Setoran</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table id="tabel-setoran" class="table table-bordered table-striped"></table>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        $('#tabel-setoran').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('frontliner.setoran.data') }}",
            columns: [{
                    data: 'DT_RowIndex',
                    title: 'No',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'tanggal',
                    title: 'Tanggal'
                },
                {
                    data: 'namakasir',
                    title: 'Kasir'
                },
                {
                    data: 'ketdebet',
                    title: 'Akun Debet'
                },
                {
                    data: 'ketkredit',
                    title: 'Akun Kredit'
                },
                {
                    data: 'amount',
                    title: 'Jumlah',
                    className: 'text-right'
                },
                {
                    data: 'stts',
                    className: 'text-center',
                    title: 'Status',
                    render: function(data, type, row) {
                        switch (data) {
                            case '0':
                                return '<div class="badge bg-secondary">Pending</div>';
                            case '1':
                                return '<div class="badge bg-success">Approved</div>';
                            case '2':
                                return '<div class="badge bg-danger">Reject</div>';
                        }
                    },
                },
                {
                    data: 'view',
                    title: 'Aksi',
                    className: 'text-center',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [1, 'desc']
            ]
        });

        function hapusPengajuan(id) {
            Swal.fire({
                title: 'Hapus bukti setoran?',
                text: "Anda yakin akan menghapus bukti setor!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('frontliner.hapus') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            id
                        },
                        type: "POST",
                        dataType: "JSON",
                        success: function(data) {
                            Swal.fire({
                                title: 'Berhasil!',
                                html: data.msg,
                                icon: data.status ? 'success' : 'error'
                            }).then((result) => {
                                /* Read more about handling dismissals below */
                                if (data.status)
                                    $('#tabel-setoran').DataTable().ajax.reload();
                            });
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire("error");
                        }
                    });
                }
            });
        }
    </script>
@endpush
