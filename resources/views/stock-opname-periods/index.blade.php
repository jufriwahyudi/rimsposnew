@extends('layouts.main.main')
@section('title', 'Stock Opname Periods')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stock Opname Periods</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Stock Opname Periods</a></li>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Stock Opname Periods</h5>
                            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                        </div>
                    </div>
                    <a href="javascript:void(0)" class="btn btn-success btn-sm mb-3" data-bs-toggle="modal"
                        data-bs-target="#createPeriodModal"><i class="bi bi-plus"></i>
                        Buat Periode Opname</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <table class="table table-bordered">
                        <tr>
                            <th width="12%">Code</th>
                            <th width="12%">Periode</th>
                            <th>Deskripsi</th>
                            <th class="text-center" width="10%">Status</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                        @foreach ($periods as $period)
                            <tr>
                                <td>{{ $period->code }}</td>
                                <td>{{ \Carbon\Carbon::parse($period->period_date)->format('d-m-Y') }}</td>
                                <td>{{ $period->description }}</td>
                                <td class="text-center">{{ $period->status }}</td>
                                <td class="text-center">
                                    <a href="{{ route('stock-opname-periods.show', $period) }}"
                                        class="btn btn-outline-primary btn-sm" title="Detail Periode Stock Opname"><i
                                            class="bi bi-eye"></i></a>
                                    @if ($period->status === 'OPEN')
                                        <form action="{{ route('stock-opname-periods.close', $period) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menutup periode ini?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                title="Tutup Periode Stock Opname"><i class="bi bi-lock"></i></button>
                                        </form>
                                    @else
                                        <form action="{{ route('stock-opname-periods.open', $period) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Apakah Anda yakin ingin membuka periode ini?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm"
                                                title="Buka Periode Stock Opname"><i class="bi bi-unlock"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal for creating a new period -->
    <div class="modal fade" id="createPeriodModal" tabindex="-1" aria-labelledby="createPeriodModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('stock-opname-periods.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createPeriodModalLabel">Buat Periode Opname Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="period_date" class="form-label">Periode</label>
                            <input type="date" class="form-control" id="period_date" name="period_date"
                                value="{{ old('period_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Buat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
