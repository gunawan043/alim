<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Akun Dibuka Kembali</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#212529">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0">
<tr>
<td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08)">
<tr>
    <td style="background:#005981;padding:20px;text-align:center">
        <img src="{{ config('app.url') }}/build/images/alim-light-name.png" alt="Alim" height="70" style="display:block;margin:auto">
        <h2 style="margin:10px 0 0;color:#ffffff;font-size:20px;font-weight:600">✅ Akun Dibuka Kembali</h2>
    </td>
</tr>
<tr>
    <td style="padding:30px">
        <p style="font-size:14px;line-height:1.6;margin:0 0 15px">
            Yth. <strong>{{ $userName }}</strong>,
        </p>
        <div style="background:#d1e7dd;border:1px solid #a3cfbb;border-radius:6px;padding:15px;margin:20px 0;font-size:14px;color:#0f5132">
            <strong>✅好消息 — Selamat!</strong><br>
            Akun Anda telah dibuka kembali oleh Super Admin dan siap digunakan.
        </div>
        <p style="font-size:14px;line-height:1.6;margin:0 0 20px">
            Jika Anda belum meminta pembukaan akun ini, segera hubungi Super Admin.
        </p>
        <p style="font-size:13px;color:#6c757d;margin-top:20px">
            Email ini dikirim secara otomatis oleh sistem keamanan ALIM.<br>
            © {{ date('Y') }} ALIM – Security Monitoring System
        </p>
    </td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>