<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP | ALIM</title>
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
        .logo-mark img { height: 68px; }
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

        .otp-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .otp-digit {
            width: 48px;
            height: 54px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 700;
            background: rgba(0, 89, 129, 0.06);
            border: 1.5px solid rgba(0, 89, 129, 0.25);
            border-radius: 12px;
            color: #00416c;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .otp-digit::placeholder { color: #2a4a60; }
        .otp-digit:focus {
            border-color: #005981;
            box-shadow: 0 0 0 3px rgba(0, 89, 129, 0.12);
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
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(0, 89, 129, 0.25);
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0, 89, 129, 0.35); }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .resend-text {
            text-align: center;
            font-size: 0.82rem;
            color: #7a9ab5;
            margin-top: 1.25rem;
        }
        .resend-text a { color: #ffae01; text-decoration: none; font-weight: 600; }
        .resend-text a:hover { text-decoration: underline; }

        .alert-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 0.82rem;
            margin-bottom: 1rem;
            background: rgba(255, 174, 1, 0.08);
            border: 1px solid rgba(255, 174, 1, 0.25);
            color: #b87a00;
            text-align: left;
        }
        .alert-box i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }

        .timer-text {
            text-align: center;
            font-size: 0.8rem;
            color: #7a9ab5;
            margin-top: 0.75rem;
        }
        .timer-text span { font-weight: 700; color: #ffae01; }
        .timer-expired span { color: #ff6464; }

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

            <div class="auth-header">
                <div class="auth-icon">
                    <i class="ri-shield-check-line"></i>
                </div>
                <div class="auth-title-main">Verifikasi Email</div>
                <div class="auth-title-sub">Masukkan 6 digit kode OTP yang dikirim ke email Anda</div>
            </div>

            @error('otp')
                <div class="alert-box">
                    <i class="ri-error-warning-line"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <form method="POST" action="{{ route('password.otp.verify') }}" id="otp-form" autocomplete="off">
                @csrf
                <input type="hidden" name="otp" id="otp">

                <div class="otp-inputs">
                    @for ($i = 1; $i <= 6; $i++)
                        <input type="text"
                               class="otp-digit otp-input"
                               maxlength="1"
                               data-index="{{ $i }}"
                               inputmode="numeric"
                               pattern="[0-9]*"
                               required>
                    @endfor
                </div>

                <button class="btn-submit" type="submit">
                    <i class="ri-checkbox-circle-line"></i> Verifikasi OTP
                </button>
            </form>

            <p class="timer-text" id="timer-wrap">
                OTP berlaku <span id="timer">--:--</span>
            </p>
        </div>

        <div class="footer-note">&copy; {{ date('Y') }} ALIM &mdash; Ponpes Abu Hurairah Mataram</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var inputs = document.querySelectorAll('.otp-input');
            var otpHidden = document.getElementById('otp');

            inputs.forEach(function (input, index) {
                input.addEventListener('input', function (e) {
                    input.value = input.value.replace(/[^0-9]/g, '');
                    if (input.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                    updateOtp();
                });

                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                input.addEventListener('paste', function (e) {
                    e.preventDefault();
                    var data = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                    data.split('').forEach(function (num, i) {
                        if (inputs[i]) inputs[i].value = num;
                    });
                    updateOtp();
                    if (inputs[Math.min(data.length, 5)]) inputs[Math.min(data.length, 5)].focus();
                });
            });

            function updateOtp() {
                if (otpHidden) otpHidden.value = Array.from(inputs).map(function (i) { return i.value; }).join('');
            }

            // Timer
            var timerEl = document.getElementById('timer');
            var timerWrap = document.getElementById('timer-wrap');
            var expiresAt = {{ $expiresAt?->timestamp ?? 'null' }};
            var form = document.getElementById('otp-form');
            var btn = form ? form.querySelector('button[type=submit]') : null;

            if (!expiresAt) {
                if (timerEl) timerEl.innerText = '00:00';
            } else {
                function tick() {
                    var remaining = expiresAt - Math.floor(Date.now() / 1000);
                    if (remaining <= 0) {
                        if (timerEl) timerEl.innerText = '00:00';
                        if (timerEl) timerEl.style.color = '#ff6464';
                        if (timerWrap) timerWrap.classList.add('timer-expired');
                        if (btn) btn.disabled = true;
                        clearInterval(interval);
                        return;
                    }
                    var m = Math.floor(remaining / 60);
                    var s = remaining % 60;
                    if (timerEl) timerEl.innerText = m + ':' + (s < 10 ? '0' : '') + s;
                }
                tick();
                var interval = setInterval(tick, 1000);
            }
        });
    </script>
</body>
</html>