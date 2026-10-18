<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline — Koneksi Terputus | ALIM</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'IBM Plex Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #001e2e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            background: #f7fbfd;
        }
        .bg-grid {
            position: fixed; inset: 0;
            background-image: linear-gradient(rgba(0,89,129,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(0,89,129,.06) 1px, transparent 1px);
            background-size: 60px 60px; pointer-events: none; z-index: 0;
        }
        .bg-glow { position: fixed; border-radius: 50%; filter: blur(140px); pointer-events: none; z-index: 0; }
        .glow-1 { width: 700px; height: 700px; background: #005981; opacity: .09; top: -200px; left: -150px; }
        .glow-2 { width: 500px; height: 500px; background: #ffae01; opacity: .07; bottom: -150px; right: -100px; }
        .glow-3 { width: 350px; height: 350px; background: #005981; opacity: .05; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .container { position: relative; z-index: 10; text-align: center; max-width: 600px; padding: 2rem; animation: fadeInUp .6s ease-out; }
        .error-icon {
            display: inline-flex; align-items: center; justify-content: center; width: 88px; height: 88px;
            background: rgba(255,174,1,.12); border: 1.5px solid rgba(255,174,1,.35);
            border-radius: 24px; margin-bottom: 1.5rem; animation: float 4s ease-in-out infinite;
        }
        .error-icon svg { width: 42px; height: 42px; color: #ffae01; }
        .error-code {
            font-size: 4.6rem; font-weight: 900; line-height: 1; color: rgba(0,89,129,.25);
            letter-spacing: -2px; margin-bottom: .5rem; user-select: none; position: relative;
        }
        .error-code::after {
            content: 'OFFLINE'; position: absolute; inset: 0;
            background: linear-gradient(135deg, #ffae01 0%, #e69500 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .error-title { font-size: 1.6rem; font-weight: 700; color: #005981; margin-bottom: .75rem; }
        .error-desc { font-size: .95rem; color: #7a9ab5; line-height: 1.7; margin-bottom: 2rem; }
        .reasons {
            background: rgba(0,89,129,.1); border: 1px solid rgba(0,89,129,.25);
            border-radius: 18px; padding: 1.5rem; margin-bottom: 2rem; text-align: left;
        }
        .reasons-header {
            display: flex; align-items: center; gap: 8px; font-size: .72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .1em; color: #ffae01; margin-bottom: 1rem;
        }
        .reasons ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .reasons ul li { display: flex; align-items: flex-start; gap: 10px; font-size: .875rem; color: #005981; line-height: 1.5; }
        .check { color: #ffae01; font-weight: 900; margin-top: -1px; flex-shrink: 0; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 10px;
            font-size: .875rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer;
            transition: all .2s ease; font-family: inherit;
        }
        .btn-primary { background: linear-gradient(135deg,#005981,#004a67); color: #fff; border: 1px solid rgba(0,89,129,.5); box-shadow: 0 4px 14px rgba(0,89,129,.25); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,89,129,.35); }
        .btn-outline { background: transparent; color: #5289b6; border: 1.5px solid rgba(0,89,129,.3); }
        .btn-outline:hover { background: rgba(0,89,129,.08); border-color: rgba(0,89,129,.5); color: #5289b6; transform: translateY(-1px); }
        .footer-note { position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%); font-size: .73rem; color: #2a4a60; white-space: nowrap; z-index: 20; }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-glow glow-1"></div>
    <div class="bg-glow glow-2"></div>
    <div class="bg-glow glow-3"></div>

    <div class="container">
        <div class="error-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 2l20 20"></path>
                <path d="M8.5 8.5a6.5 6.5 0 0 1 9.2 0"></path>
                <path d="M5 5a11 11 0 0 1 14 0"></path>
                <path d="M12 20h.01"></path>
            </svg>
        </div>

        <div class="error-code">OFFLINE</div>
        <h1 class="error-title">Anda Sedang Offline</h1>
        <p class="error-desc">Halaman ini membutuhkan koneksi internet. Data presensi yang sudah tersimpan tetap aman; silakan sambungkan kembali perangkat Anda.</p>

        <div class="reasons">
            <div class="reasons-header"><span>Yang Bisa Anda Lakukan</span></div>
            <ul>
                <li><span class="check">✓</span> Data presensi yang sudah tersimpan tetap aman</li>
                <li><span class="check">✓</span> Sambungkan kembali perangkat ke jaringan internet</li>
                <li><span class="check">✓</span> Muat ulang halaman setelah koneksi tersedia</li>
            </ul>
        </div>

        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="location.reload()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-3-6.7"></path><path d="M21 3v6h-6"></path></svg>
                Coba Lagi
            </button>
            <a href="{{ url('/') }}" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 10.5L12 3l9 7.5"></path><path d="M5 9.5V21h14V9.5"></path></svg>
                Beranda
            </a>
        </div>
    </div>

    <div class="footer-note">&copy; {{ date('Y') }} ALIM &mdash; Ponpes Abu Hurairah Mataram</div>
    <script>
        window.addEventListener('online', function () { location.reload(); });
    </script>
</body>
</html>
