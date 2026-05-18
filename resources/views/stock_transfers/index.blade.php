@extends('layouts.main.main')
@section('title', 'Stock Transfer')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stock Transfer</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Stock Transfer</a></li>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Stock Transfer</h5>
                            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                        </div>
                    </div>
                    <a href="{{ route('stock-transfers.create') }}" class="btn btn-success btn-sm mb-3"><i
                            class="bi bi-plus"></i>
                        Transfer Baru</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center" width="10%">Tanggal</th>
                                <th>Kode Transfer</th>
                                <th width="15%">Jenis Transfer</th>
                                <th>Dari</th>
                                <th>Ke</th>
                                <th class="text-center" width="10%">Status</th>
                                <th class="text-center" width="8%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfers as $transfer)
                                <tr>
                                    <td class="text-center">{{ $transfer->created_at->format('d-m-Y') }}</td>
                                    <td>{{ $transfer->transfer_code }}</td>
                                    <td>{{ $transfer->transfer_type_label }}</td>
                                    <td width="15%">{{ strtoupper($transfer->from_position) }}</td>
                                    <td width="15%">{{ strtoupper($transfer->to_position) }}</td>
                                    <td class="text-center">
                                        {{-- bedakan warna badge berdasarkan status REQUESTED,APPROVED,REJECTED,IN_TRANSIT,PARTIAL_RECEIVED,RECEIVED,CANCELLED --}}
                                        <span class="badge bg-{{ $transfer->status_badge_color }}">
                                            {{ $transfer->status }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('stock-transfers.show', $transfer) }}"
                                            class="btn btn-sm btn-secondary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $transfers->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
