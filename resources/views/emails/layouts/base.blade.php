@php
    $logoUrl = trim((string) config('app.email_logo_url'));

    if ($logoUrl === '') {
        $logoUrl = 'https://cdn.jsdelivr.net/gh/gunawan043/alim@main/public/build/images/alim-light-name.png';
    }

    $primary = '#005981';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Notifikasi ALIM')</title>
    <style>
        body { margin: 0; padding: 0; background: #f1f5f9; font-family: Arial, Helvetica, sans-serif; color: #1f2937; }
        table { border-collapse: collapse; }
        .email-shell { width: 100%; background: #f1f5f9; padding: 24px 0; }
        .email-card { width: 600px; max-width: 100%; background: #ffffff; border-radius: 10px; overflow: hidden; margin: 0 auto; }
        .email-header { background: {{ $primary }}; padding: 24px 30px; text-align: center; }
        .email-header img { height: 64px; max-width: 220px; display: block; margin: 0 auto 10px; }
        .email-header h1 { color: #ffffff; font-size: 20px; line-height: 1.35; margin: 0; font-weight: 700; }
        .email-body { padding: 30px; font-size: 14px; line-height: 1.7; color: #334155; }
        .email-body p { margin: 0 0 14px; }
        .email-body h2 { color: {{ $primary }}; font-size: 20px; margin: 0 0 14px; }
        .email-body h3 { color: {{ $primary }}; font-size: 16px; margin: 20px 0 10px; }
        .email-button { display: inline-block; background: {{ $primary }}; color: #ffffff !important; text-decoration: none; padding: 11px 22px; border-radius: 6px; font-weight: bold; font-size: 14px; margin: 8px 0 4px; }
        .email-muted { color: #64748b; font-size: 13px; }
        .email-alert { background: #fff7e6; border: 1px solid #f5c26b; border-radius: 8px; padding: 14px 16px; margin: 18px 0; color: #7c4a03; font-size: 13px; }
        .email-danger { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px 16px; margin: 18px 0; color: #991b1b; font-size: 13px; }
        .email-success { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 16px; margin: 18px 0; color: #166534; font-size: 13px; }
        .email-code { display: block; background: #f1f5f9; color: {{ $primary }}; border-radius: 8px; padding: 16px; text-align: center; font-size: 30px; letter-spacing: 6px; font-weight: 700; margin: 18px 0; }
        .email-table { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin: 18px 0; font-size: 13px; }
        .email-table td { padding: 10px 13px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .email-table tr:last-child td { border-bottom: 0; }
        .email-table .label { width: 36%; background: #f8fafc; color: #64748b; font-weight: bold; }
        .email-footer { padding: 18px 30px; text-align: center; background: #f8fafc; color: #64748b; font-size: 11px; line-height: 1.6; }
        .email-footer strong { color: {{ $primary }}; }
        @media only screen and (max-width: 620px) {
            .email-card { width: 100% !important; border-radius: 0; }
            .email-header, .email-body, .email-footer { padding-left: 18px !important; padding-right: 18px !important; }
        }
    </style>
</head>
<body>
<table class="email-shell" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center">
            <table class="email-card" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td class="email-header">
                        <img src="{{ $logoUrl }}" alt="ALIM">
                        <h1>@yield('heading', 'Notifikasi ALIM')</h1>
                    </td>
                </tr>
                <tr>
                    <td class="email-body">
                        <p>Assalamu&rsquo;alaikum warahmatullahi wabarakatuh,</p>
                        @yield('salutation')
                        @yield('content')
                        <p style="margin-top:24px">Wassalamu&rsquo;alaikum warahmatullahi wabarakatuh.</p>
                    </td>
                </tr>
                <tr>
                    <td class="email-footer">
                        <strong>ALIM</strong> &mdash; Academic Learning & Information Management<br>
                        Email ini dikirim otomatis. Mohon tidak membalas email ini.<br>
                        &copy; {{ date('Y') }} ALIM. Seluruh hak dilindungi.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
