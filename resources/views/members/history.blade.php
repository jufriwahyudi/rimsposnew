@extends('layouts.main.main')
@section('title', 'Riwayat Poin Member')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pelanggan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('members.index') }}">Daftar Member</a></li>
                    <li class="breadcrumb-item active">Riwayat Poin</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <div class="avatar avatar-md bg-light-info text-info rounded-3 me-2 p-1">
                            <i class="material-icons-outlined" style="font-size:28px">history</i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Riwayat Poin: {{ $member->name }}</h5>
                            <small class="text-muted">No. HP: {{ $member->phone }} | Saldo Saat Ini: <strong class="text-primary font-monospace">{{ number_format($member->total_points) }} Poin</strong></small>
                        </div>
                    </div>
                    <a href="{{ route('members.index') }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">arrow_back</i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <table id="tbl-history" class="table table-bordered w-100 table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal & Waktu</th>
                                <th>Cabang</th>
                                <th>Nota Transaksi</th>
                                <th>Tipe Mutasi</th>
                                <th class="text-end">Jumlah Poin</th>
                                <th class="text-end">Saldo Akhir</th>
                                <th>Keterangan</th>
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
            $('#tbl-history').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: '{{ route('members.history', $member->id) }}',
                },
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'store_name', name: 'store.name', searchable: true },
                    { data: 'sale_invoice', name: 'sale.invoice_number', className: 'text-center' },
                    { 
                        data: 'mutation_type', 
                        name: 'mutation_type', 
                        className: 'text-center',
                        render: function(data) {
                            let badgeClass = 'bg-secondary';
                            let text = data.toUpperCase();
                            if (data === 'earn') { badgeClass = 'bg-success'; text = 'BELANJA'; }
                            else if (data === 'redeem') { badgeClass = 'bg-danger'; text = 'REDEMPTION'; }
                            else if (data === 'adjust') { badgeClass = 'bg-primary'; text = 'ADJUSTMENT'; }
                            else if (data === 'expire') { badgeClass = 'bg-warning text-dark'; text = 'EXPIRED'; }
                            
                            return `<span class="badge ${badgeClass}">${text}</span>`;
                        }
                    },
                    { data: 'points', name: 'points', className: 'text-end font-monospace' },
                    { data: 'balance_after', name: 'balance_after', className: 'text-end font-monospace fw-bold' },
                    { data: 'notes', name: 'notes' },
                ],
                order: [[0, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                }
            });
        });
    </script>
@endpush
