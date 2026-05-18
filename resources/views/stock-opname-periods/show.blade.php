@extends('layouts.main.main')
@section('title', 'Detail Stock Opname Period')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Periode Opname</h5>
                        <small class="text-muted">Periode
                            {{ \Carbon\Carbon::parse($stockOpnamePeriod->period_date)->format('d M Y') }}</small>
                        <span class="badge bg-secondary">
                            {{ $stockOpnamePeriod->status }}
                        </span>
                    </div>
                    <div>
                        @if ($stockOpnamePeriod->status === 'OPEN')
                            <a href="javascript:void(0);" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal"
                                data-bs-target="#createOpnameModal"><i class="bi bi-plus"></i> Buat Lokasi Opname</a>
                        @endif
                        <a href="{{ route('stock-opname-periods.index') }}" class="btn btn-secondary btn-sm">
                            Kembali
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    {{-- ALERT --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <table class="table table-striped">
                        <tr>
                            <th>Periode</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                        @foreach ($opnames as $opname)
                            <tr>
                                <th>{{ \Carbon\Carbon::parse($stockOpnamePeriod->period_date)->format('d M Y') }}</th>
                                <td>{{ strtoupper($opname->posisi) }}</td>
                                <td>{{ $opname->status }}</td>
                                <td class="text-center">
                                    <a href="{{ route('stock-opnames.edit', $opname) }}"
                                        class="btn btn-outline-primary btn-sm">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </table>

                </div>
            </div>
        </div>
    </div>
    {{-- MODAL CREATE OPNAME --}}
    <div class="modal fade" id="createOpnameModal" tabindex="-1" aria-labelledby="createOpnameModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('stock-opnames.store', ['period' => $stockOpnamePeriod->id]) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createOpnameModalLabel">Buat Lokasi Opname Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="posisi" class="form-label">Tipe Lokasi</label>
                            <select class="form-select" id="posisi" name="posisi" required>
                                <option value="" disabled selected>Pilih Tipe Lokasi</option>
                                <option value="warehouse" {{ old('posisi') == 'warehouse' ? 'selected' : '' }}>
                                    Warehouse</option>
                                <option value="store" {{ old('posisi') == 'store' ? 'selected' : '' }}>Store
                                </option>
                            </select>
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
