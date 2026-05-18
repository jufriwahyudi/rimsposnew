@extends('layouts.main.main')
@section('title', 'Daily Audits')

@section('content')
    <div class="row">
        <div class="col-md-6">

            <div class="card rounded-4">
                <div class="card-header">
                    <h5>Audit Harian</h5>
                </div>

                <form method="POST" action="{{ route('audits.store') }}">
                    @csrf

                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">Tanggal Audit</label>
                            <input type="date" name="audit_date" class="form-control" value="{{ now()->format('Y-m-d') }}"
                                required>
                        </div>

                        <div class="alert alert-info">
                            <strong>Yang akan diaudit:</strong>
                            <ul class="mb-0">
                                <li>Stok Batch vs Stock Movement</li>
                                <li>Penjualan vs Uang Masuk</li>
                                <li>Kas Masuk & Keluar</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" rows="3" placeholder="Catatan audit..."></textarea>
                        </div>

                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <a href="{{ route('audits.index') }}" class="btn btn-light me-2">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Jalankan Audit
                        </button>
                    </div>

                </form>

            </div>

        </div>

        <!-- INFO PANEL -->
        <div class="col-md-6">
            <div class="card rounded-4 border-warning">
                <div class="card-body">
                    <h6>⚠️ Perhatian</h6>
                    <ul class="mb-0">
                        <li>Audit hanya boleh dilakukan <strong>1x per hari</strong></li>
                        <li>Pastikan semua transaksi sudah selesai</li>
                        <li>Adjustment setelah audit akan tercatat sebagai anomali</li>
                        <li>Audit tidak bisa dihapus</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
@endsection
