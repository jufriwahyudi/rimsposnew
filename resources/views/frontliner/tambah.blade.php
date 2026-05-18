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
                </div>
                <div class="card-body">
                    <form class="form" id="formSetoran">
                        @csrf
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">{{ $data_title }}</h5>
                            </div>

                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4 form-group">
                                        <label>Tanggal Transaksi</label>
                                        <input type="date" name="tgltrx" class="form-control"
                                            value="{{ $tanggal }}">
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>Nama Kasir</label>
                                        <input type="text" class="form-control" value="{{ Auth::user()->name }}"
                                            readonly>
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>Saldo Kasir</label>
                                        <small>Saldo Akhir Posisi
                                            {{ date('d/m/Y', strtotime($saldo->tanggal ?? $tanggal)) }}</small>
                                        <input type="text" class="form-control" readonly
                                            value="{{ number_format($saldo->saldo_akhir ?? 0) }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4 form-group">
                                        <label>Akun Setor</label>
                                        <select name="akundebet" class="form-select">
                                            @foreach ($kas as $dt)
                                                <option value="{{ $dt->kode }}">
                                                    {{ $dt->kode }} - {{ $dt->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>Jumlah Setoran</label>
                                        <input type="text" name="amount" id="amount" class="form-control"
                                            value="0">
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>Keterangan</label>
                                        <textarea name="ket" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 d-flex justify-content-end">
                                        <a href="{{ url('/frontliner') }}" class="btn btn-secondary me-2">Kembali</a>
                                        <button type="button" class="btn btn-success"
                                            onclick="simpanTransaksi()">Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Detail Transaksi --}}
                    @include('frontliner.detailtrx', ['detailtrx' => $detailtrx])
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        function simpanTransaksi() {
            let formData = new FormData(document.getElementById('formSetoran'));
            // Loading swal
            Swal.fire({
                title: 'Menyimpan transaksi...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            fetch("{{ route('frontliner.store') }}", {
                    method: "POST",
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status) {
                        Swal.fire("Sukses", res.msg, "success").then(() => {
                            location.href = "{{ route('frontliner.index') }}";
                        });
                    } else {
                        Swal.fire("Gagal", res.msg, "error");
                    }
                });
        }
    </script>
@endpush
