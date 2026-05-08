<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Reset Password OTP</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif">

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<table width="600" cellpadding="0" cellspacing="0"
       style="background:#ffffff;margin:30px auto;border-radius:8px;overflow:hidden">

    <!-- HEADER -->
    <tr>
        <td style="background:#005981;padding:20px;text-align:center">
            <img src="{{ asset('images/logo-email.png') }}"
                 alt="ALIM"
                 height="70"
                 style="display:block;margin:auto">
        </td>
    </tr>

    <!-- BODY -->
    <tr>
        <td style="padding:30px;color:#333">

            <h2 style="margin-top:0;color:#005981">
                Reset Password Akun
            </h2>

            <p>Yth. <strong>{{ $name }}</strong>,</p>

            <p>
                Kami menerima permintaan <strong>reset password</strong>
                untuk akun Anda.
            </p>

            <p>
                Gunakan kode OTP berikut untuk melanjutkan proses reset password:
            </p>

            <div style="
                margin:25px 0;
                padding:15px;
                text-align:center;
                font-size:28px;
                letter-spacing:6px;
                font-weight:bold;
                background:#f1f5f9;
                border-radius:6px;
                color:#005981;">
                {{ $otp }}
            </div>

            <p style="font-size:14px;color:#555">
                ⏱️ Kode ini berlaku selama <strong>10 menit</strong>.
            </p>

            <p style="font-size:14px;color:#dc3545">
                ⚠️ Jika Anda tidak merasa melakukan permintaan ini,
                abaikan email ini dan segera hubungi administrator.
            </p>

            <hr style="margin:30px 0;border:none;border-top:1px solid #eee">

            <p style="font-size:12px;color:#777">
                Email ini dikirim otomatis oleh sistem keamanan ALIM.
                Mohon tidak membalas email ini.
            </p>

        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background:#f1f1f1;padding:15px;text-align:center;
                   font-size:12px;color:#666">
            © {{ date('Y') }} ALIM • Confidential & Secure System
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
