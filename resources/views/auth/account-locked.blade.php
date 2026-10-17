<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Dikunci | ALIM</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;900&display=swap" rel="stylesheet">
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
        }
        .bg-grid { position: fixed; inset: 0; background-image: linear-gradient(rgba(0,89,129,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(0,89,129,0.06) 1px, transparent 1px); background-size: 60px 60px; pointer-events: none; z-index: 0; }
        .bg-glow { position: fixed; border-radius: 50%; filter: blur(140px); pointer-events: none; z-index: 0; }
        .glow-1 { width: 700px; height: 700px; background: #005981; opacity: 0.09; top: -200px; left: -150px; }
        .glow-2 { width: 500px; height: 500px; background: #ffae01; opacity: 0.07; bottom: -150px; right: -100px; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .logo-mark { position: fixed; top: 1.75rem; left: 50%; transform: translateX(-50%); z-index: 20; display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-mark img { height: 30px; opacity: 0.75; }
        .logo-mark span { font-size: 0.8rem; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: rgba(0,89,129,0.5); }
        .container { position: relative; z-index: 10; text-align: center; max-width: 520px; padding: 2rem; animation: fadeInUp 0.6s ease-out; }
        .error-icon { display: inline-flex; align-items: center; justify-content: center; width: 88px; height: 88px; background: rgba(255,174,1,0.12); border: 1.5px solid rgba(255,174,1,0.35); border-radius: 24px; margin-bottom: 1.5rem; animation: float 4s ease-in-out infinite; }
        .error-icon i { font-size: 2.5rem; color: #ffae01; }
        .error-code { font-size: 5rem; font-weight: 900; line-height: 1; background: linear-gradient(135deg, #ffae01 0%, #e69500 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: -4px; margin-bottom: 0.5rem; }
        .error-title { font-size: 1.5rem; font-weight: 700; color: #e0eaf2; margin-bottom: 0.75rem; }
        .error-desc { font-size: 0.9rem; color: #7a9ab5; line-height: 1.7; margin-bottom: 1.5rem; }
        .alert-box { background: rgba(255,174,1,0.08); border: 1px solid rgba(255,174,1,0.25); border-radius: 18px; padding: 1.25rem; margin-bottom: 1.5rem; text-align: left; }
        .alert-header { display: flex; align-items: center; gap: 8px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #ffae01; margin-bottom: 0.75rem; }
        .alert-box ul { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .alert-box ul li { display: flex; align-items: flex-start; gap: 10px; font-size: 0.875rem; color: #8aaecf; line-height: 1.5; }
        .alert-box ul li i { color: #ffae01; margin-top: 2px; flex-shrink: 0; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 10px; font-size: 0.875rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s ease; font-family: inherit; }
        .btn-primary { background: linear-gradient(135deg, #005981, #004a67); color: #fff; border: 1px solid rgba(0,89,129,0.5); box-shadow: 0 4px 14px rgba(0,89,129,0.25); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,89,129,0.35); }
        .btn-outline { background: transparent; color: #7a9ab5; border: 1.5px solid rgba(0,89,129,0.3); }
        .btn-outline:hover { background: rgba(0,89,129,0.08); border-color: rgba(0,89,129,0.5); color: #e0eaf2; transform: translateY(-1px); }
        .footer-note { position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%); font-size: 0.73rem; color: #2a4a60; white-space: nowrap; z-index: 20; }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-glow glow-1"></div>
    <div class="bg-glow glow-2"></div>
    <a href="{{ url('/') }}" class="logo-mark">
        <img src="https://raw.githubusercontent.com/gunawan043/alim/main/public/build/images/alim-light-name.png" alt="ALIM">
        <span>ALIM</span>
    </a>
    <div class="container">
        <div class="error-icon"><i class="ri-shield-warning-line"></i></div>
        <div class="error-code">LOCKED</div>
        <h1 class="error-title">Akun Dikunci Otomatis</h1>
        <p class="error-desc">Akun Anda telah dikunci karena terlalu banyak percobaan login gagal. Hubungi HRD untuk membuka kembali.</p>
        <div class="alert-box">
            <div class="alert-header"><i class="ri-information-line"></i> Yang Perlu Dilakukan</div>
            <ul>
                <li><i class="ri-checkbox-circle-line"></i> Hubungi HRD untuk verifikasi dan buka akun</li>
                <li><i class="ri-checkbox-circle-line"></i> Pastikan Anda mengingat username &amp; password dengan benar</li>
                <li><i class="ri-checkbox-circle-line"></i> Jangan bagikan informasi login kepada siapapun</li>
            </ul>
        </div>
        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary"><i class="ri-home-4-line"></i> Beranda</a>
            <a href="{{ route('login') }}" class="btn btn-outline"><i class="ri-arrow-left-line"></i> Kembali ke Login</a>
        </div>
    </div>
    <div class="footer-note">&copy; {{ date('Y') }} ALIM &mdash; Ponpes Abu Hurairah Mataram</div>
</body>
</html>