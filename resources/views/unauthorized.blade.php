<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak</title>
    @vite(['resources/sass/app.scss'])
</head>

<body>
    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col col-lg-6 col-6">
                <div class="card">
                    <div class="card-header">
                        <h5>403 - Akses Ditolak</h5>
                    </div>
                    <div class="card-body">
                        <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>

</html>
