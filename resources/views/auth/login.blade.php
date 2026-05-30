<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ALIM</title>
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

        .auth-title-arabic {
            font-size: 1.5rem;
            font-weight: 700;
            color: #005981;
            margin-bottom: 0.25rem;
        }
        .auth-title-sub {
            font-size: 0.8rem;
            color: #7a9ab5;
        }

        .auth-logo-img {
            height: 76px;
            margin-bottom: 0.75rem;
        }

        .form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #7a9ab5;
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

        .input-group-wrap { position: relative; }
        .input-group-wrap .form-control { padding-right: 44px; }
        .input-group-wrap .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #4a6a80;
            font-size: 1.1rem;
            transition: color 0.2s;
            padding: 0;
        }
        .input-group-wrap .password-toggle:hover { color: #ffae01; }

        .invalid-feedback {
            display: block;
            font-size: 0.78rem;
            color: #ffae01;
            margin-top: 0.3rem;
        }

        .forgot-link {
            font-size: 0.8rem;
            color: #7a9ab5;
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: #ffae01; }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #005981, #004a67);
            color: #fff;
            border: 1px solid rgba(0, 89, 129, nil);
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(0, 89, 129, nil);
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0, 89, 129, nil); }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .form-check { display: flex; align-items: center; gap: 8px; margin-top: 0.5rem; }
        .form-check-input {
            width: 16px;
            height: 16px;
            accent-color: #005981;
            cursor: pointer;
            margin: 0;
        }
        .form-check-label {
            font-size: 0.82rem;
            color: #7a9ab5;
            cursor: pointer;
        }

        .alert-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            text-align: left;
        }
        .alert-box i { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
        .alert-box strong { display: block; margin-bottom: -20px; }
        .alert-danger {
            background: rgba(255, 174, 1, nil);
            border: 1px solid rgba(255, 174, 1, nil);
            color: #e69500;
        }
        .alert-warning {
            background: rgba(255, 174, 1, nil);
            border: 1px solid rgba(255, 174, 1, nil);
            color: #b80000;
        }
        .alert-success {
            background: rgba(0, 89, 129, nil);
            border: 1px solid rgba(0, 89, 129, nil);
            color: #7a9ab5;
        }

        .footer-note {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.73rem;
            color: #2a4a60;
        }

        @keyframes countdown-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .countdown-num {
            display: inline-block;
            font-weight: 700;
            min-width: 1.5ch;
            animation: countdown-pulse 1s ease-in-out infinite;
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

            {{-- Akun terkunci --}}
            @if ($errors->has('account_locked'))
                <div class="alert-box alert-danger">
                    <i class="ri-error-warning-line"></i>
                    <div>
                        <strong>Akun Terkunci</strong><br>
                        {{ $errors->first('account_locked') }}
                    </div>
                </div>
            @endif

            {{-- Login gagal umum --}}
            @if ($errors->has('login_failed'))
                <div class="alert-box alert-danger">
                    <i class="ri-error-warning-line"></i>
                    <div>
                        <strong>Login Gagal</strong><br>
                        {{ $errors->first('login_failed') }}
                    </div>
                </div>
            @endif

            {{-- Countdown lockout --}}
            @if (session('lockout') && session('seconds', 0) > 0)
                <div class="alert-box alert-warning">
                    <i class="ri-time-line"></i>
                    <div>
                        <strong>Terlalu Banyak Percobaan</strong><br>
                        Silakan tunggu <b><span class="countdown-num" id="countdown" data-seconds="{{ session('seconds') }}">{{ session('seconds') }}</span></b> detik sebelum mencoba lagi.
                    </div>
                </div>
            @endif

            <div class="auth-header">
                <div class="auth-icon">
                    <i class="ri-shield-user-line"></i>
                </div>
                <div class="auth-title-arabic">أهلا وسهلا</div>
                <div class="auth-title-sub">Academic Learning & Information Management</div>
            </div>

            <form action="{{ route('login.process') }}" method="POST" id="login-form">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Username <span>*</span></label>
                    <input type="text" class="form-control @error('email') is-invalid @enderror"
                           id="username" name="email"
                           placeholder="Masukkan username"
                           value="{{ old('email', '') }}"
                           required autofocus>
                    @error('email')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="mb-3">
                    <div style="display:flex;justify-content:flex-end">
                        <a href="{{ route('password.request') }}" class="forgot-link">Lupa password?</a>
                    </div>
                    <label for="password-input" class="form-label">Password <span>*</span></label>
                    <div class="input-group-wrap">
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               name="password" placeholder="Masukkan password"
                               id="password-input" required>
                        <button class="password-toggle" type="button" id="password-addon">
                            <i class="ri-eye-off-line"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="auth-remember-check" name="remember">
                    <label class="form-check-label" for="auth-remember-check">Ingat saya</label>
                </div>

                <button class="btn-submit" type="submit" id="login-btn">
                    <i class="ri-login-box-line"></i> Masuk
                </button>
            </form>
        </div>

        <div class="footer-note">&copy; {{ date('Y') }} ALIM &mdash; Ponpes Abu Hurairah Mataram</div>
    </div>

    <script>
        // Password toggle
        document.getElementById('password-addon')?.addEventListener('click', function () {
            var input = document.getElementById('password-input');
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ri-eye-line';
            } else {
                input.type = 'password';
                icon.className = 'ri-eye-off-line';
            }
        });

        // Countdown
        (function () {
            var el = document.getElementById('countdown');
            if (!el) return;
            var seconds = parseInt(el.dataset.seconds, 10);
            var btn = document.getElementById('login-btn');
            var form = document.getElementById('login-form');

            if (btn) btn.disabled = true;
            if (form) form.addEventListener('submit', function (e) { e.preventDefault(); });

            var timer = setInterval(function () {
                seconds--;
                if (el) el.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(timer);
                    if (btn) btn.disabled = false;
                    if (form) form.removeEventListener('submit', arguments.callee);
                }
            }, 1000);
        })();
    </script>
    <script src="{{ URL::asset('build/js/pages/password-addon.init.js') }}"></script>
</body>
</html>