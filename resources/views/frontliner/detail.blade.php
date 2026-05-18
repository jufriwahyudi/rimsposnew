@extends('layouts.main.main')
@section('title', 'Purchase Orders')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Detail Setoran Frontliner</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Detail Setoran Frontliner</a>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <div class="d-flex align-items-start">
                        <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                            style="width: 35px; height: 35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Detail Setoran Frontliner</h5>
                            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-text">
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Tanggal:</dt>
                                    <dd class="col-sm-9"><?= date('d/m/Y', strtotime($detail->tanggal)) ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Frontliner:</dt>
                                    <dd class="col-sm-9"><?= $detail->namakasir ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Catatan:</dt>
                                    <dd class="col-sm-9"><?= !empty($detail->catatan) ? $detail->catatan : '-' ?></dd>
                                </dl>
                                <?php if ($detail->stts !== '0') : ?>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Approval:</dt>
                                    <dd class="col-sm-9"><?= $detail->namaapv ?></dd>
                                </dl>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card-text">
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Debet:</dt>
                                    <dd class="col-sm-9"><?= $detail->ketdebet ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Kredit:</dt>
                                    <dd class="col-sm-9"><?= $detail->ketkredit ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Saldo:</dt>
                                    <dd class="col-sm-9"><?= number_format($detail->amount) ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Status:</dt>
                                    <dd class="col-sm-9">
                                        <?php
                                        switch ($detail->stts) {
                                            case '0':
                                                echo '<div class="badge bg-secondary">Pending</div>';
                                                break;
                                            case '1':
                                                echo '<div class="badge bg-success">Approved</div>';
                                                break;
                                            case '2':
                                                echo '<div class="badge bg-danger">Reject</div>';
                                                break;
                                        }
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-md-12 d-flex justify-content-end">
                            <a href="{{ route('frontliner.index') }}" class="btn btn-outline-secondary btn-sm"><i
                                    class="bx bx-chevron-left"></i> Kembali</a>
                            @if ($detail->stts !== '0')
                                <a href="javascript:void(0);"
                                    onclick="batalApproval('{{ Crypt::encryptString($detail->Id) }}')"
                                    class="btn btn-outline-danger btn-sm ms-1"><i class="bx bx-trash"></i> Batalkan
                                    Approval</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if ($detail->stts === '1')
            <div class="col-12">
                <!-- Description lists horizontal -->
                <div class="card">
                    <div class="card-header pb-0">
                        <h5 class="card-title">Data Jurnal</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mt-1">
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Tanggal Posting:</dt>
                                    <dd class="col-sm-9"><?= date('d/m/Y', strtotime($detail->voucher->tanggal)) ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">No.Jurnal:</dt>
                                    <dd class="col-sm-9"><?= $detail->voucher->voucer ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Uraian:</dt>
                                    <dd class="col-sm-9"><?= $detail->voucher->uraian ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Petugas:</dt>
                                    <dd class="col-sm-9"><?= $detail->voucher->userinput ?></dd>
                                </dl>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Jenis:</dt>
                                    <dd class="col-sm-9"><?= $detail->voucher->jnstrx ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-striped table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" class="text-center">Akun</th>
                                            <th rowspan="2">Keterangan</th>
                                            <th rowspan="2" class="text-center">D/K</th>
                                            <th colspan="2" class="text-center">Saldo</th>
                                        </tr>
                                        <tr>
                                            <th class="text-center">Debet</th>
                                            <th class="text-center">Kredit</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @foreach ($detail->jurnal as $dt)
                                            <tr>
                                                <td class="text-center">{{ $dt->kode }}</td>
                                                <td>{{ $dt->nama }}</td>
                                                <td class="text-center">{{ $dt->post }}</td>
                                                {!! $dt->post === 'D'
                                                    ? '<td class="text-end">' . number_format($dt->amount) . '</td><td></td>'
                                                    : '<td></td><td class="text-end">' . number_format($dt->amount) . '</td>' !!}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection
@push('scripts')
    <script>
        function batalApproval(idref) {
            Swal.fire({
                title: 'Batalkan approval setoran kasir?',
                text: "Approval pengajuan setoran frontliner akan dibatalkan dan dikembalikan ke status Pending!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Batalkan!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('frontliner.hapus.approval') }}",
                        data: {
                            idref
                        },
                        type: "POST",
                        dataType: "JSON",
                        beforeSend: function() {
                            Swal.fire({
                                title: "Loading!",
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading()
                                }
                            });
                        },
                        success: function(data) {
                            Swal.fire("Response", data.message, data.status ? "success" : "error").then(
                                function() {
                                    location.reload();
                                })
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire("Peringatan", "Jaringan Bermasalah", "warning");
                        }
                    });
                }
            });
        }
    </script>
@endpush
