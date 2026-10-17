<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline — ALIM</title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f8fafc; font-family:system-ui,sans-serif; color:#1e293b; padding:24px; }
        .card { background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(15,23,42,.08); padding:32px; max-width:420px; text-align:center; }
        .icon { width:64px; height:64px; margin:0 auto 16px; border-radius:16px; background:#eef2ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; font-size:28px; }
        h1 { font-size:20px; margin:0 0 8px; }
        p { color:#64748b; font-size:14px; line-height:1.6; margin:0 0 20px; }
        button { background:#4f46e5; color:#fff; border:0; border-radius:8px; padding:10px 18px; font-weight:600; cursor:pointer; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚡</div>
        <h1>Anda sedang offline</h1>
        <p>Halaman ini membutuhkan koneksi internet. Data presensi yang sudah tersimpan tetap aman; silakan sambungkan kembali perangkat Anda.</p>
        <button onclick="location.reload()">Coba Lagi</button>
    </div>
</body>
</html>
