@extends('layouts.main.main')
@section('title', 'Update APK Kasir Settings')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Update APK Kasir</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 col-sm-12 mx-auto">
            <div class="card rounded-4 p-2 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 bg-transparent border-0">
                    <div class="d-flex align-items-start">
                        <div class="avatar avatar-md bg-light-primary text-primary rounded-3 me-2 p-1">
                            <i class="material-icons-outlined" style="font-size:28px">system_update_alt</i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-primary">In-App Update APK Kasir</h5>
                            <small class="text-muted">Kelola rilis APK baru dan status pembaruan aplikasi kasir</small>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form id="formAppVersion">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Versi Aplikasi (SemVer)</label>
                            <input type="text" class="form-control" name="version" id="version" value="{{ $appVersion->version }}" placeholder="Contoh: 1.0.1" required>
                            <small class="text-muted">Format versi menggunakan Semantic Versioning (contoh: 1.0.0, 1.0.1, dst).</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor Build (Build Number)</label>
                            <input type="number" class="form-control" name="build_number" id="build_number" value="{{ $appVersion->build_number }}" min="1" placeholder="Contoh: 2" required>
                            <small class="text-muted">Harus berupa angka integer berurutan (contoh: 1, 2, 3, dst).</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">URL Unduhan APK (Download URL)</label>
                            <input type="url" class="form-control" name="download_url" id="download_url" value="{{ $appVersion->download_url }}" placeholder="Contoh: https://pos.rimsdev.com/storage/app-releases/rimspos_v2.apk">
                            <small class="text-muted">Masukkan link URL langsung APK (opsional jika mengunggah berkas APK langsung di bawah).</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Unggah File APK Baru</label>
                            <input type="file" class="form-control" name="apk_file" id="apk_file" accept=".apk">
                            <small class="text-muted">Pilih berkas .apk baru untuk diunggah langsung ke server (Maksimal 100MB).</small>
                            @if(!empty($appVersion->download_url))
                                <div class="mt-2 p-2 bg-light rounded-3 d-flex align-items-center">
                                    <i class="material-icons-outlined text-primary me-2">attachment</i>
                                    <span class="text-truncate text-muted" style="font-size:12px">
                                        File rilis aktif: <a href="{{ $appVersion->download_url }}" target="_blank" class="fw-bold text-decoration-none">{{ basename($appVersion->download_url) }}</a>
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <label class="form-check-label fw-bold text-dark mb-1" for="mandatory">Pembaruan Wajib (Mandatory Update)</label>
                                    <br><small class="text-muted">Jika diaktifkan, pengguna tidak bisa menggunakan aplikasi kasir sebelum melakukan pembaruan.</small>
                                </div>
                                <input class="form-check-input ms-0" type="checkbox" id="mandatory" name="mandatory" value="1" {{ $appVersion->mandatory ? 'checked' : '' }} style="width: 2.5em; height: 1.3em;">
                            </div>
                        </div>

                        <div class="border-top pt-3 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnSaveAppVersion">
                                <i class="material-icons-outlined align-middle me-1" style="font-size: 18px">save</i> Simpan Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('formAppVersion').addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnSaveAppVersion');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mengunggah...';

            const formData = new FormData(this);
            if (!formData.has('mandatory')) {
                formData.append('mandatory', '0');
            }

            fetch('{{ route('settings.app-version.update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                btn.disabled = false;
                btn.innerHTML = '<i class="material-icons-outlined align-middle me-1" style="font-size:18px">save</i> Simpan Konfigurasi';

                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Gagal menyimpan konfigurasi.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="material-icons-outlined align-middle me-1" style="font-size:18px">save</i> Simpan Konfigurasi';
                alert('Terjadi kesalahan koneksi atau berkas terlalu besar.');
            });
        });
    </script>
@endpush
