<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Tidak Valid</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f6f8fa; padding: 40px; }
        .card { max-width: 480px; margin: 80px auto; background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #c0392b; }
        .icon { font-size: 48px; }
        p { color: #555; line-height: 1.6; }
        .btn { display: inline-block; margin-top: 16px; padding: 10px 20px; background: #2c3e50; color: #fff; border-radius: 4px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚠️</div>
        <h1>Link Tidak Valid</h1>
        <p>Token akses Anda sudah kadaluarsa atau tidak dikenali.</p>
        <p>Silakan minta admin untuk membuat ulang link undangan wali.</p>
        <a href="{{ url('/') }}" class="btn">Kembali ke Beranda</a>
    </div>
</body>
</html>
