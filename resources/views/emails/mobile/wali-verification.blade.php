{{-- ============================================================
  Email: Permintaan verifikasi jadi wali
  Dikirim ke wali utama saat ada wali lain mau terhubung
  ============================================================ --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Wali Baru</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; margin: 0; padding: 20px; }
    .container { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #1e3a5f 0%, #2563a8 100%); padding: 32px 40px; color: #fff; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
    .header p { margin: 8px 0 0; opacity: 0.85; font-size: 14px; }
    .body { padding: 32px 40px; }
    .info-box { background: #f0f4ff; border-radius: 8px; padding: 20px 24px; margin-bottom: 24px; }
    .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #6b7280; font-size: 13px; }
    .info-value { font-weight: 600; color: #1e3a5f; font-size: 14px; }
    .action-buttons { display: flex; gap: 12px; margin: 28px 0; }
    .btn { flex: 1; padding: 14px 20px; border-radius: 8px; text-align: center; font-size: 15px; font-weight: 600; text-decoration: none; cursor: pointer; }
    .btn-approve { background: #16a34a; color: #fff; }
    .btn-reject { background: #ef4444; color: #fff; }
    .btn:hover { opacity: 0.9; }
    .warning { font-size: 12px; color: #9ca3af; text-align: center; margin-top: 24px; }
    .footer { background: #f9fafb; padding: 20px 40px; font-size: 12px; color: #9ca3af; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Permintaan-Verifikasi Wali Baru</h1>
      <p>ALIM Alim — Sistem Informasi Manajemen Pendidikan</p>
    </div>

    <div class="body">
      <p style="margin-top:0">Yth. <strong>{{ $waliName }}</strong>,</p>

      <p>Bapak/Ibu <strong>{{ $waliName }}</strong> mengajukan permintaan untuk menjadi
        <strong>{{ $roleLabel }}</strong> dari <strong>{{ $studentName }}</strong>.</p>

      <div class="info-box">
        <div class="info-row">
          <span class="info-label">Nama Santri</span>
          <span class="info-value">{{ $studentName }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Peran yang dimintа</span>
          <span class="info-value">{{ $roleLabel }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Batas waktu</span>
          <span class="info-value">{{ $expiresAt }}</span>
        </div>
      </div>

      <p>Jika Anda mengenal orang ini dan menyetujui permintaan tersebut, silakan klik:</p>

      <div class="action-buttons">
        <a href="{{ $approveUrl }}" class="btn btn-approve">Setujui</a>
        <a href="{{ $rejectUrl }}" class="btn btn-reject">Tolak</a>
      </div>

      <p style="font-size:14px; color:#6b7280;">Jika Anda tidak mengenali permintaan ini, abaikan email ini
        atau hubungi administrators sekolah untuk pelaporan.</p>
    </div>

    <div class="warning">
      Email ini berlaku selama 48 jam. Setelah habis, tautan tidak akan berfungsi.
    </div>

    <div class="footer">
      &copy; {{ date('Y') }} ALIM Alim —email ini dikirim otomatis, mohon nicht dibalas.
    </div>
  </div>
</body>
</html>