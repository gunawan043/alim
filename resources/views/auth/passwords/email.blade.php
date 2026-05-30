<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password | ALIM</title>
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
            overflow-x: hidden;
            position: relative;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 89, 129, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 89, 129, 0.06) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        .bg-glow {
            position: fixed;
            border-radius: 50%;
            filter: blur(140px);
            pointer-events: none;
            z-index: 0;
        }
        .glow-1 { width: 700px; height: 700px; background: #005981; opacity: 0.09; top: -200px; left: -150px; }
        .glow-2 { width: 500px; height: 500px; background: #ffae01; opacity: 0.07; bottom: -150px; right: -100px; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-mark {
            position: fixed;
            top: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .logo-mark img { height: 68px;}
        .logo-mark span { font-size: 0.8rem; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: rgba(0, 89, 129, 0.5); }

        .auth-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 2rem 1.5rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(0, 89, 129, 0.2);
            border-radius: 24px;
            padding: 2.25rem;
            backdrop-filter: blur(12px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: rgba(0, 89, 129, 0.12);
            border: 1.5px solid rgba(0, 89, 129, 0.3);
            border-radius: 20px;
            margin-bottom: 1rem;
        }
        .auth-icon i { font-size: 1.75rem; color: #ffae01; }

        .auth-title-main {
            font-size: 1.1rem;
            font-weight: 700;
            color: #4a6a80;
            margin-bottom: 0.3rem;
        }
        .auth-title-sub {
            font-size: 0.8rem;
            color: #7a9ab5;
        }

        .form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #4a6a80;
            margin-bottom: 0.4rem;
            letter-spacing: 0.02em;
        }
        .form-label span { color: #ffae01; }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            background: rgba(0, 89, 129, 0.06);
            border: 1.5px solid rgba(0, 89, 129, 0.2);
            border-radius: 12px;
            color: #00416c;
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            outline: none;
        }
        .form-control::placeholder { color: #4a6a80; }
        .form-control:focus {
            border-color: rgba(0, 89, 129, 0.6);
            box-shadow: 0 0 0 3px rgba(0, 89, 129, 0.1);
            background: rgba(0, 89, 129, 0.08);
        }
        .form-control.is-invalid { border-color: rgba(255, 174, 1, 0.5); }

        .invalid-feedback {
            display: block;
            font-size: 0.78rem;
            color: #ffae01;
            margin-top: 0.3rem;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #005981, #004a67);
            color: #fff;
            border: 1px solid rgba(0, 89, 129, 0.5);
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 1rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(0, 89, 129, 0.25);
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0, 89, 129, 0.35); }

        .alert-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            text-align: left;
        }
        .alert-box i { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
        .alert-box strong { display: block; margin-bottom: -20px; }
        .alert-success {
            background: rgba(0, 89, 129, 0.12);
            border: 1px solid rgba(0, 89, 129, 0.3);
            color: #7a9ab5;
        }
        .alert-warning {
            background: rgba(255, 174, 1, 0.08);
            border: 1px solid rgba(255, 174, 1, 0.25);
            color: #b80000;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: #7a9ab5;
            text-decoration: none;
            transition: color 0.2s;
            margin-top: 1.25rem;
        }
        .back-link:hover { color: #ffae01; }

        .footer-note {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.73rem;
            color: #2a4a60;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-glow glow-1"></div>
    <div class="bg-glow glow-2"></div>

    <a href="{{ url('/') }}" class="logo-mark">
        <img src="https://raw.githubusercontent.com/gunawan043/alim/main/public/build/images/logo-dark.png" alt="ALIM">
    </a>

    <div class="auth-wrapper">
        <div class="auth-card">

            @if (session('status'))
                <div class="alert-box alert-success">
                    <i class="ri-checkbox-circle-line"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            <div class="auth-header">
                <div class="auth-icon">
                    <i class="ri-key-line"></i>
                </div>
                <div class="auth-title-main">Lupa Password</div>
                <div class="auth-title-sub">Masukkan email terdaftar untuk reset password</div>
            </div>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email <span>*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email" placeholder="nama@email.com"
                           value="{{ old('email') }}" required>
                    @error('email')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <button class="btn-submit" type="submit">
                    <i class="ri-send-plane-line"></i> Kirim Link Reset
                </button>
            </form>

            <div class="text-center">
                <a href="{{ route('login') }}" class="back-link">
                    <i class="ri-arrow-left-line"></i> Kembali ke Login
                </a>
            </div>
        </div>

        <div class="footer-note">&copy; {{ date('Y') }} ALIM &mdash; Ponpes Abu Hurairah Mataram</div>
    </div>
</body>
</html>