{{-- ============================================================
  Email: Notifikasi wali bahwa Santi berhasil terhubung
  ============================================================ --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Berhasil Terhubung dengan Santi</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; margin: 0; padding: 20px; }
    .container { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #1e3a5f 0%, #16a34a 100%); padding: 32px 40px; color: #fff; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
    .body { padding: 32px 40px; }
    .success-badge { display: inline-block; background: #dcfce7; color: #16a34a; border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
    .student-card { background: #f0f4ff; border-radius: 10px; padding: 20px 24px; margin: 20px 0; border-left: 4px solid #2563a8; }
    .student-name { font-size: 20px; font-weight: 700; color: #1e3a5f; margin-bottom: 8px; }
    .detail-row { display: flex; gap: 12px; margin: 6px 0; font-size: 14px; color: #475569; }
    .detail-label { color: #94a3b8; min-width: 100px; }
    .btn-primary { display: block; width: fit-content; background: #2563a8; color: #fff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px; margin: 28px 0; }
    .footer { background: #f9fafb; padding: 20px 40px; font-size: 12px; color: #9ca3af; text-align: center; }
    .role-badge { display: inline-block; background: #dbeafe; color: #2563a8; border-radius: 6px; padding: 2px 10px; font-size: 12px; font-weight: 600; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Terhubung dengan {{ $studentName }}</h1>
      <p>ALIM PUSTIK — Portal Wali Santri</p>
    </div>

    <div class="body">
      <span class="success-badge">&#10003; Berhasil Terhubung</span>

      <p>Selamat, <strong>{{ $waliName }}</strong>! Anda berhasil terhubung dengan:</p>

      <div class="student-card">
        <div class="student-name">{{ $studentName }}</div>
        <div class="detail-row">
          <span class="detail-label">Peran Anda:</span>
          <span><span class="role-badge">{{ $roleLabel }}</span></span>
        </div>
      </div>

      <p style="font-size:14px; color:#475569;">Sekarang Anda bisa:</p>
      <ul style="font-size:14px; color:#475569; padding-left:20px;">
        <li>Melihat absensi harian {{ $studentName }}</li>
        <li>Menerima notifikasi jika {{ $studentName }} sakit atau alfa</li>
        <li>Mengunduh daftar hadir dan raport</li>
      </ul>

      <a href="{{ $dashboardUrl }}" class="btn-primary">Buka Dashboard &#8594;</a>
    </div>

    <div class="footer">
      &copy; {{ date('Y') }} ALIM PUSTIK — email ini dikirim otomatis, mohon nicht dibalas.
    </div>
  </div>
</body>
</html>