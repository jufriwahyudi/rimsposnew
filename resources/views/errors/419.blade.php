<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Kedaluwarsa (419) - {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Outfit', sans-serif;
            background: #070913;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .icon-circle {
            width: 72px;
            height: 72px;
            background: rgba(239, 68, 68, 0.15);
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #ef4444;
            font-size: 28px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 10px; color: #fff; }
        p { font-size: 14px; color: #94a3b8; line-height: 1.6; margin-bottom: 24px; }
        .countdown-text { font-size: 13px; color: #64748b; margin-bottom: 20px; }
        .btn-reload {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            border: none;
            padding: 13px 20px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-reload:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <h1>Sesi Halaman Kedaluwarsa</h1>
        <p>Halaman ini telah berada dalam status diam terlalu lama sehingga sesi keamanan (CSRF Token) kedaluwarsa.</p>
        <div class="countdown-text" id="countdownNotice">
            Mengalihkan ke halaman masuk dalam <b id="countdown" style="color: #6366f1;">2</b> detik...
        </div>
        <a href="{{ url('/login') }}" class="btn-reload" id="reloadBtn">
            <i class="fa-solid fa-arrow-rotate-right"></i> Masuk Kembali
        </a>
    </div>

    <script>
        // Segera ubah history browser ke /login via GET agar jika user menekan tombol Refresh (F5)
        // browser tidak mengirim ulang form POST (yang menyebabkan perulangan 419 lagi).
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', "{{ url('/login') }}");
        }

        let timeLeft = 2;
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            timeLeft--;
            if (countdownEl) countdownEl.innerText = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.replace("{{ url('/login') }}");
            }
        }, 1000);

        document.getElementById('reloadBtn').addEventListener('click', function(e) {
            e.preventDefault();
            clearInterval(timer);
            window.location.replace("{{ url('/login') }}");
        });
    </script>
</body>
</html>
