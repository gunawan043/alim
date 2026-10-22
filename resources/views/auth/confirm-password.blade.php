<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Password | ALIM</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: #f4f8fa; color: #001e2e; font-family: 'IBM Plex Sans', system-ui, sans-serif; }
        .card { width: 100%; max-width: 420px; padding: 32px; border: 1px solid #dbe4ea; border-radius: 20px; background: #fff; box-shadow: 0 18px 50px rgba(0, 30, 46, .08); }
        .icon { width: 56px; height: 56px; display: grid; place-items: center; border-radius: 16px; background: rgba(0, 89, 129, .1); color: #005981; font-size: 26px; margin: 0 auto 16px; }
        h1 { font-size: 19px; text-align: center; margin-bottom: 6px; }
        p { color: #6b7d89; font-size: 13px; text-align: center; line-height: 1.5; margin-bottom: 22px; }
        label { display: block; font-size: 12px; font-weight: 600; color: #425b6b; margin-bottom: 6px; }
        input { width: 100%; padding: 11px 13px; border: 1px solid #cbd9e2; border-radius: 10px; font: inherit; font-size: 14px; outline: none; }
        input:focus { border-color: #0a7aa8; box-shadow: 0 0 0 3px rgba(10, 122, 168, .1); }
        .error { color: #b02a37; font-size: 12px; margin-top: 6px; }
        button { width: 100%; margin-top: 18px; padding: 12px; border: 0; border-radius: 10px; background: linear-gradient(145deg, #005981, #0a7aa8); color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
        button:hover { filter: brightness(1.06); }
        .back { display: block; margin-top: 18px; text-align: center; color: #6b7d89; font-size: 12px; text-decoration: none; }
        .back:hover { color: #005981; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="ri-lock-password-line"></i></div>
        <h1>Konfirmasi Password</h1>
        <p>Area ini memerlukan konfirmasi password sebelum melanjutkan.</p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" autofocus>
            @error('password')<div class="error">{{ $message }}</div>@enderror
            <button type="submit"><i class="ri-shield-check-line me-1"></i> Konfirmasi</button>
        </form>

        <a href="{{ route('root') }}" class="back"><i class="ri-arrow-left-line me-1"></i>Kembali ke Beranda</a>
    </div>
</body>
</html>
