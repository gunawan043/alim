<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | ALIM</title>
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
        .container { position: relative; z-index: 10; text-align: center; max-width: 480px; width: 100%; padding: 1.5rem 2rem; animation: fadeInUp 0.6s ease-out; }
        .card { background: rgba(0,30,46,0.85); border: 1px solid rgba(0,89,129,0.3); border-radius: 24px; padding: 2rem; backdrop-filter: blur(20px); }
        .greeting { font-size: 1.75rem; font-weight: 700; color: #ffae01; margin-bottom: 0.25rem; }
        .subtitle { font-size: 0.875rem; color: #7a9ab5; margin-bottom: 1.75rem; }
        .form-group { margin-bottom: 1rem; text-align: left; }
        .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: #8aaecf; margin-bottom: 0.4rem; letter-spacing: 0.03em; }
        .form-label span { color: #ffae01; }
        .form-control { width: 100%; padding: 10px 14px; background: rgba(0,89,129,0.1); border: 1px solid rgba(0,89,129,0.25); border-radius: 10px; color: #e0eaf2; font-size: 0.875rem; font-family: inherit; transition: border-color 0.2s; outline: none; }
        .form-control:focus { border-color: #005981; background: rgba(0,89,129,0.15); }
        .form-control::placeholder { color: #4a6a80; }
        .invalid-feedback { color: #ffae01; font-size: 0.78rem; margin-top: 0.25rem; }
        .form-hint { font-size: 0.75rem; color: #4a6a80; margin-top: 0.3rem; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 10px; font-size: 0.875rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s ease; font-family: inherit; }
        .btn-primary { background: linear-gradient(135deg, #005981, #004a67); color: #fff; border: 1px solid rgba(0,89,129,0.5); box-shadow: 0 4px 14px rgba(0,89,129,0.25); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,89,129,0.35); }
        .btn-accent { background: linear-gradient(135deg, #ffae01, #e69500); color: #001e2e; box-shadow: 0 4px 14px rgba(255,174,1,0.25); }
        .btn-accent:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(255,174,1,0.35); }
        .footer-note { position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%); font-size: 0.73rem; color: #2a4a60; white-space: nowrap; z-index: 20; }
        .switch-link { font-size: 0.85rem; color: #7a9ab5; margin-top: 1.25rem; }
        .switch-link a { color: #ffae01; text-decoration: none; font-weight: 600; }
        .switch-link a:hover { text-decoration: underline; }
        .pass-group { position: relative; }
        .pass-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #7a9ab5; font-size: 1rem; padding: 0; }
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
        <div class="card">
            <div class="greeting">أهلا وسهلا</div>
            <div class="subtitle">Buat akun baru untuk mengakses ALIM</div>

            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email <span>*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="nama@email.com" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span>*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Masukkan nama lengkap" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Password <span>*</span></label>
                    <div class="pass-group">
                        <input type="password" name="password" id="reg-password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
                        <button type="button" class="pass-toggle" onclick="toggleRegPass(this)"><i class="ri-eye-off-line"></i></button>
                    </div>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password <span>*</span></label>
                    <div class="pass-group">
                        <input type="password" name="password_confirmation" id="reg-confirm" class="form-control" placeholder="Ulangi password" required>
                        <button type="button" class="pass-toggle" onclick="toggleRegPass(this)"><i class="ri-eye-off-line"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Avatar / Foto Profil <span>*</span></label>
                    <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*" required>
                    @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-hint">Format: JPG, PNG. Maks 2MB.</div>
                </div>
                <div class="actions" style="margin-top:1.5rem">
                    <button type="submit" class="btn btn-primary"><i class="ri-user-add-line"></i> Daftar Sekarang</button>
                </div>
            </form>
            <div class="switch-link">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></div>
        </div>
    </div>
    <div class="footer-note">&copy; {{ date('Y') }} ALIM &mdash; Ponpes Abu Hurairah Mataram</div>
    <script>
    function toggleRegPass(btn) {
        const input = btn.closest('.pass-group').querySelector('input');
        if (input.type === 'password') { input.type = 'text'; btn.querySelector('i').className = 'ri-eye-line'; }
        else { input.type = 'password'; btn.querySelector('i').className = 'ri-eye-off-line'; }
    }
    </script>
</body>
</html>