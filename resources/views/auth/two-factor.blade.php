<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Login | ALIM</title>
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
        .bg-glow { position: fixed; border-radius: 50%; filter: blur(140px); pointer-events: none; z-index: 0; }
        .glow-1 { width: 700px; height: 700px; background: #005981; opacity: .09; top: -200px; left: -150px; }
        .glow-2 { width: 500px; height: 500px; background: #ffae01; opacity: .07; bottom: -150px; right: -100px; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .logo-mark { position: fixed; top: 1.5rem; left: 50%; transform: translateX(-50%); z-index: 20; text-decoration: none; }
        .logo-mark img { height: 68px; }
        .auth-wrapper { position: relative; z-index: 10; width: 100%; max-width: 440px; padding: 2rem 1.5rem; animation: fadeInUp .6s ease-out; }
        .auth-card { background: rgba(255,255,255,.04); border: 1px solid rgba(0,89,129,.2); border-radius: 24px; padding: 2.25rem; backdrop-filter: blur(12px); }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        .auth-icon { display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: rgba(0,89,129,.12); border: 1.5px solid rgba(0,89,129,.3); border-radius: 20px; margin-bottom: 1rem; }
        .auth-icon i { font-size: 1.75rem; color: #ffae01; }
        .auth-title-main { font-size: 1.1rem; font-weight: 700; color: #4a6a80; margin-bottom: .3rem; }
        .auth-title-sub { font-size: .8rem; color: #7a9ab5; }
        .otp-inputs { display: flex; gap: 8px; justify-content: center; margin-bottom: 1.5rem; }
        .otp-digit { width: 48px; height: 54px; text-align: center; font-size: 1.4rem; font-weight: 700; background: rgba(0,89,129,.06); border: 1.5px solid rgba(0,89,129,.25); border-radius: 12px; color: #00416c; font-family: inherit; transition: border-color .2s, box-shadow .2s; outline: none; }
        .otp-digit:focus { border-color: #005981; box-shadow: 0 0 0 3px rgba(0,89,129,.12); }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #005981, #004a67); color: #fff; border: 1px solid rgba(0,89,129,.5); border-radius: 12px; font-size: .9rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: all .2s ease; box-shadow: 0 4px 14px rgba(0,89,129,.25); }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,89,129,.35); }
        .btn-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; }
        .resend-text { text-align: center; font-size: .82rem; color: #7a9ab5; margin-top: 1.25rem; }
        .resend-text button { color: #ffae01; background: none; border: 0; font: inherit; font-weight: 600; cursor: pointer; padding: 0; }
        .resend-text button:hover { text-decoration: underline; }
        .resend-text form { display: inline; }
        .alert-box { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; border-radius: 12px; font-size: .82rem; margin-bottom: 1rem; background: rgba(255,174,1,.08); border: 1px solid rgba(255,174,1,.25); color: #b87a00; text-align: left; }
        .alert-box i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
        .timer-text { text-align: center; font-size: .8rem; color: #7a9ab5; margin-top: .75rem; }
        .timer-text span { font-weight: 700; color: #ffae01; }
        .timer-expired span { color: #ff6464; }
        .footer-note { text-align: center; margin-top: 1.5rem; font-size: .73rem; color: #2a4a60; }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-glow glow-1"></div>
    <div class="bg-glow glow-2"></div>

    <a href="{{ url('/') }}" class="logo-mark">
        <img src="https://raw.githubusercontent.com/gunawan043/alim/main/public/build/images/alim-dark-name.png" alt="ALIM">
    </a>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon"><i class="ri-shield-keyhole-line"></i></div>
                <div class="auth-title-main">Verifikasi Login</div>
                <div class="auth-title-sub">Masukkan 6 digit kode yang dikirim ke {{ $email }}</div>
            </div>

            @if(session('status'))
                <div class="alert-box" style="background:rgba(0,89,129,.08);border-color:rgba(0,89,129,.2);color:#4a6a80">
                    <i class="ri-information-line"></i><span>{{ session('status') }}</span>
                </div>
            @endif

            @error('otp')
                <div class="alert-box"><i class="ri-error-warning-line"></i><span>{{ $message }}</span></div>
            @enderror
            @error('recovery_code')
                <div class="alert-box"><i class="ri-error-warning-line"></i><span>{{ $message }}</span></div>
            @enderror
            @error('totp_code')
                <div class="alert-box"><i class="ri-error-warning-line"></i><span>{{ $message }}</span></div>
            @enderror

            @if($totpEnabled ?? false)
                <form method="POST" action="{{ route('two-factor.totp') }}" autocomplete="off" style="margin-bottom:1.25rem">
                    @csrf
                    <p style="font-size:.82rem;color:#4a6a80;text-align:center;margin-bottom:.6rem">
                        Gunakan kode 6 digit dari aplikasi authenticator
                    </p>
                    <input type="text" name="totp_code" class="otp-digit" maxlength="6" inputmode="numeric"
                           pattern="[0-9]*" placeholder="000000" autocomplete="one-time-code" required
                           style="width:100%;height:44px;font-size:1.2rem;letter-spacing:5px">
                    <button class="btn-submit" type="submit" style="margin-top:.75rem">
                        <i class="ri-smartphone-line"></i> Verifikasi Authenticator
                    </button>
                </form>
                <p style="text-align:center;font-size:.75rem;color:#7a9ab5;margin-bottom:1rem">atau gunakan OTP email di bawah</p>
            @endif

            <form method="POST" action="{{ route('two-factor.verify') }}" id="otp-form" autocomplete="off">
                @csrf
                <input type="hidden" name="otp" id="otp">

                <div class="otp-inputs">
                    @for ($i = 1; $i <= 6; $i++)
                        <input type="text" class="otp-digit otp-input" maxlength="1" data-index="{{ $i }}"
                               inputmode="numeric" pattern="[0-9]*" required @if($i === 1) autofocus @endif>
                    @endfor
                </div>

                <button class="btn-submit" type="submit"><i class="ri-checkbox-circle-line"></i> Verifikasi &amp; Masuk</button>
            </form>

            <p class="timer-text" id="timer-wrap">Kode berlaku <span id="timer">--:--</span></p>

            @if($recoveryCodesAvailable ?? false)
                <details style="margin-top:1rem;font-size:.82rem;color:#4a6a80">
                    <summary style="cursor:pointer;text-align:center;color:#7a9ab5">Tidak bisa menerima email? Gunakan kode pemulihan</summary>
                    <form method="POST" action="{{ route('two-factor.recovery') }}" style="margin-top:.75rem">
                        @csrf
                        <input type="text" name="recovery_code" class="otp-digit" style="width:100%;height:44px;font-size:.95rem;letter-spacing:1px"
                               placeholder="ABCDE-FGHIJ" autocomplete="one-time-code" required>
                        <button class="btn-submit" type="submit" style="margin-top:.75rem">
                            <i class="ri-key-2-line"></i> Verifikasi Kode Pemulihan
                        </button>
                    </form>
                </details>
            @endif

            <div class="resend-text">
                <form method="POST" action="{{ route('two-factor.resend') }}">
                    @csrf
                    <button type="submit">Kirim ulang kode</button>
                </form>
                <span style="margin:0 .5rem;color:#ccc">·</span>
                <form method="POST" action="{{ route('two-factor.cancel') }}">
                    @csrf
                    <button type="submit">Batalkan</button>
                </form>
            </div>

            <p class="footer-note">Jika Anda tidak merasa melakukan login, batalkan dan hubungi administrator.</p>
        </div>
    </div>

    <script>
        const inputs = [...document.querySelectorAll('.otp-input')];
        const hidden = document.getElementById('otp');

        function syncOtp() {
            hidden.value = inputs.map(input => input.value).join('');
        }

        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 1);
                if (input.value && index < inputs.length - 1) inputs[index + 1].focus();
                syncOtp();
            });
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && index > 0) inputs[index - 1].focus();
            });
            input.addEventListener('paste', (event) => {
                const digits = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                if (!digits) return;
                event.preventDefault();
                inputs.forEach((item, itemIndex) => item.value = digits[itemIndex] || '');
                syncOtp();
                inputs[Math.min(digits.length, 5)].focus();
            });
        });

        document.getElementById('otp-form').addEventListener('submit', syncOtp);

        const expiresAt = {{ $expiresAt ? $expiresAt->timestamp * 1000 : 0 }};
        const timer = document.getElementById('timer');
        const timerWrap = document.getElementById('timer-wrap');

        function tick() {
            if (!expiresAt) {
                timer.textContent = '--:--';
                return;
            }
            const remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
            const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
            const seconds = String(remaining % 60).padStart(2, '0');
            timer.textContent = `${minutes}:${seconds}`;
            timerWrap.classList.toggle('timer-expired', remaining <= 0);
        }

        tick();
        setInterval(tick, 1000);
    </script>
</body>
</html>
