<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Portal — Login | {{ config('app.name', 'ALIM') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #005981 0%, #003d5a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .vendor-login-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .vendor-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .vendor-brand h2 { color: #005981; font-weight: 700; }
        .vendor-brand small { color: #7a9ab5; }
        .btn-vendor {
            background: #005981;
            border: none;
            color: white;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-vendor:hover { background: #004a67; color: white; }
        .text-muted { color: #7a9ab5 !important; }
    </style>
</head>
<body>
    <div class="vendor-login-card">
        <div class="vendor-brand">
            <i class="ri-building-4-line" style="font-size: 2.5rem; color: #005981;"></i>
            <h2>Vendor Portal</h2>
            <small>{{ config('app.name') }}</small>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.login.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label text-muted">Kode Vendor</label>
                <input type="text" name="vendor_code" class="form-control" placeholder="Masukkan kode vendor" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label text-muted" for="remember">Ingat saya</label>
            </div>
            <button type="submit" class="btn btn-vendor w-100">Masuk</button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">Butuh akses? Hubungi admin sekolah.</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
