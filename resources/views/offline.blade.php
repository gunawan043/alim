<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#405189">
    <title>Offline — ALIM</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f3f6f9;
            color: #212529;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 14px;
            padding: 32px 28px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        }
        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            font-size: 30px;
        }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { margin: 0 0 20px; color: #6b7280; font-size: 14px; line-height: 1.6; }
        button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: #405189;
            cursor: pointer;
        }
        button:active { opacity: .85; }
        .hint { margin-top: 14px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📡</div>
        <h1>Tidak ada koneksi internet</h1>
        <p>
            Halaman ini tidak dapat dimuat karena perangkat sedang offline.
            Periksa koneksi Wi-Fi atau data seluler Anda, lalu coba lagi.
        </p>
        <button type="button" onclick="window.location.reload()">Coba Lagi</button>
        <div class="hint">ALIM — Sistem Informasi Terpadu</div>
    </div>
</body>
</html>
