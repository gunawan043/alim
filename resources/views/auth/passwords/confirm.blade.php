<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Reset Password | ALIM</title>
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
        .container { position: relative; z-index: 10; text-align: center; max-width: 480px; padding: 2rem; animation: fadeInUp 0.6s ease-out; }
        .success-icon { display: inline-flex; align-items: center; justify-content: center; width: 88px; height: 88px; background: rgba(0,89,129,0.15); border: 1.5px solid rgba(0,89,129,0.4); border-radius: 24px; margin-bottom: 1.5rem; animation: float 4s ease-in-out infinite; }
        .success-icon i { font-size: 2.5rem; color: #ffae01; }
        .error-title { font-size: 1.5rem; font-weight: 700; color: #e0eaf2; margin-bottom: 0.5rem; }
        .error-desc { font-size: 0.9rem; color: #7a9ab5; line-height: 1.7; margin-bottom: 1.5rem; }
        .alert-box { background: rgba(0,89,129,0.1); border: 1px solid rgba(0,89,129,0.25); border-radius: 18px; padding: 1.25rem; margin-bottom: 1.5rem; text-align: left; font-size: 0.875rem; color: #8aaecf; line-height: 1.6; }
        .alert-box i { color: #ffae01; margin-right: 6px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 10px; font-size: 0.875rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s ease; font-family: inherit; }
        .btn-primary { background: linear-gradient(135deg, #005981, #004a67); color: #fff; border: 1px solid rgba(0,89,129,0.5); box-shadow: 0 4px 14px rgba(0,89,129,0.25); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,89,129,0.35); }
        .btn-accent { background: linear-gradient(135deg, #ffae01, #e69500); color: #001e2e; box-shadow: 0 4px 14px rgba(255,174,1,0.25); }
        .btn-accent:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(255,174,1,0.35); }
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
        <div class="success-icon"><i class="ri-lock-unlock-line"></i></div>
        <h1 class="error-title">Password Berhasil Diatur Ulang!</h1>
        <p class="error-desc">Password akun Anda telah berhasil diperbarui. Silakan login dengan password baru.</p>
        <div class="alert-box"><i class="ri-shield-check-line"></i> Jika Anda tidak merasa melakukan reset password, segera hubungi HRD.</div>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
            <a href="{{ route('login') }}" class="btn btn-primary"><i class="ri-home-4-line"></i> Login Sekarang</a>
        </div>
    </div>
    <div class="footer-note">&copy; {{ date('Y') }} ALIM &mdash; Ponpes Abu Hurairah Mataram</div>
</body>
</html>