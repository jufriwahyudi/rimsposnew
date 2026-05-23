<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f8f9fa;
            text-align: center;
            padding: 2rem;
            gap: 0.75rem;
        }

        .spinner {
            width: 2.5rem;
            height: 2.5rem;
            border: 4px solid #dee2e6;
            border-top-color: #0d6efd;
            border-radius: 50%;
            animation: spin 0.75s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        h5 {
            font-size: 1.1rem;
            color: #212529;
        }

        p {
            font-size: 0.875rem;
            color: #6c757d;
            max-width: 280px;
        }

        .back-link {
            margin-top: 0.5rem;
            display: inline-block;
            font-size: 0.875rem;
            color: #0d6efd;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="spinner"></div>
    <h5>Mengirim ke RawBT&hellip;</h5>
    <p>Pastikan aplikasi <strong>RawBT</strong> sudah terinstall dan printer sudah terhubung.</p>
    <a href="{{ $backUrl }}" class="back-link">← Kembali</a>

    <script>
        /**
         * Navigasi langsung ke Android Intent URI.
         * Dilakukan di dalam <script> (synchronous page load),
         * bukan di dalam fetch() callback — agar Chrome Android
         * mengenali ini sebagai navigasi yang sah dan membuka
         * app RawBT yang terinstall, bukan membuka Play Store.
         */
        window.location.href = @json($intentUri);
    </script>
</body>

</html>
