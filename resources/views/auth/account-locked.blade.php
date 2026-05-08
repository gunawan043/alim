<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Security Alert</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#212529">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0">
<tr>
<td align="center">

<!-- CONTAINER -->

<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08)">

<!-- HEADER -->
<tr>
    <td style="background:#005981;padding:20px;text-align:center">
        <img src="{{ config('app.url') }}/build/images/logo-light.png"
            alt="PUSTIK"
            height="70"
            style="display:block;margin:auto">

        <h2 style="margin:10px 0 0;color:#ffffff;font-size:20px;font-weight:600">
            ALIM Security System
        </h2>
    </td>
</tr>

<!-- BODY -->
<tr>
    <td style="padding:30px">

        <!-- ALERT -->
        <div style="
            background:#f8d7da;
            color:#842029;
            padding:15px;
            border-radius:6px;
            border:1px solid #f5c2c7;
            margin-bottom:20px;
            font-size:14px
        ">
            <strong>⚠️ Security Alert</strong><br>
            Akun pengguna telah <strong>dikunci otomatis</strong> oleh sistem.
        </div>

        <p style="font-size:14px;line-height:1.6;margin:0 0 15px">
            Sistem keamanan mendeteksi <strong>percobaan login gagal berulang</strong>.
            Untuk mencegah akses tidak sah, akun berikut telah dikunci:
        </p>

        <!-- USER INFO TABLE -->
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px">
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;width:35%">
                    <strong>Nama Pengguna</strong>
                </td>
                <td style="padding:8px;border:1px solid #dee2e6">
                    {{ $userName }}
                </td>
            </tr>
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa">
                    <strong>Email</strong>
                </td>
                <td style="padding:8px;border:1px solid #dee2e6">
                    {{ $email }}
                </td>
            </tr>
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa">
                    <strong>Jumlah Percobaan</strong>
                </td>
                <td style="padding:8px;border:1px solid #dee2e6">
                    {{ $attempts }} kali gagal
                </td>
            </tr>
        </table>

        <!-- ACTION -->
        <p style="margin-top:20px;font-size:14px">
            Silakan <strong>Super Admin / Personalia</strong> melakukan verifikasi
            dan membuka akun jika aktivitas dinilai valid.
        </p>

        <p style="margin-top:15px;color:#dc3545;font-size:13px">
            ⚠️ Email ini bersifat <strong>penting & rahasia</strong>.
            Jangan diabaikan.
        </p>

    </td>
</tr>

<!-- FOOTER -->
<tr>
    <td style="background:#f1f3f5;padding:15px;text-align:center;font-size:12px;color:#6c757d">
        © {{ date('Y') }} ALIM – Security Monitoring System<br>
        <span style="font-size:11px">Confidential • Authorized Personnel Only</span>
    </td>
</tr>

</table>
<!-- END CONTAINER -->

</td>
</tr>
</table>

</body>
</html>
