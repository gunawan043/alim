<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>IP Diblokir</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#212529">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0">
<tr>
<td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08)">
<tr>
    <td style="background:#6c1010;padding:20px;text-align:center">
        <img src="https://raw.githubusercontent.com/gunawan043/alim/main/public/build/images/alim-light-name.png" alt="Alim" height="70" style="display:block;margin:auto">
        <h2 style="margin:10px 0 0;color:#ffffff;font-size:20px;font-weight:600">🔒 Alamat IP Diblokir</h2>
    </td>
</tr>
<tr>
    <td style="padding:30px">
        <p style="font-size:14px;line-height:1.6;margin:0 0 15px">
            Yth. Tim Super Admin,
        </p>
        <p style="font-size:14px;line-height:1.6;margin:0 0 15px">
            Sistem keamanan ALIM telah memblokir alamat IP berikut karena mendeteksi
            <strong>{{ $attempts }} percobaan login gagal</strong>:
        </p>
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;margin:15px 0">
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;width:35%"><strong>Alamat IP</strong></td>
                <td style="padding:8px;border:1px solid #dee2e6"><code>{{ $ipAddress }}</code></td>
            </tr>
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa"><strong>Waktu Dibuka Kembali</strong></td>
                <td style="padding:8px;border:1px solid #dee2e6">{{ $blockedUntil }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa"><strong>Percobaan Gagal</strong></td>
                <td style="padding:8px;border:1px solid #dee2e6">{{ $attempts }} kali</td>
            </tr>
        </table>
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
