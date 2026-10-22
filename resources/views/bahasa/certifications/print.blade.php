<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sertifikat Bahasa — {{ $record->student?->name }}</title>
    @include('bahasa._styles')
    <style>
        .certificate { width: 900px; max-width: 100%; margin: 24px auto; padding: 55px 60px; border: 10px double #0f5132; background: #fffdf7; text-align: center; font-family: Georgia, serif; }
        .certificate .brand { color: #0f5132; letter-spacing: .18em; font-weight: 700; }
        .certificate h1 { font-size: 42px; margin: 10px 0 4px; color: #0f5132; }
        .certificate .name { font-size: 32px; font-weight: 700; border-bottom: 2px solid #d1a12b; display: inline-block; padding: 4px 22px 8px; margin: 22px 0 12px; }
        .certificate .meta { color: #555; margin-top: 16px; font-size: 14px; }
        .certificate .score { font-size: 24px; color: #0f5132; font-weight: 700; margin-top: 18px; }
        .certificate .footer { display: flex; justify-content: space-between; margin-top: 55px; font-size: 13px; }
    </style>
</head>
<body class="p-4">
    <div class="certificate">
        <div class="brand">DEPARTEMEN BAHASA · PAH MATARAM</div>
        <h1>SERTIFIKAT BAHASA</h1>
        <div class="meta">Diberikan kepada</div>
        <div class="name">{{ $record->student?->name ?? '-' }}</div>
        <div>atas keberhasilan menyelesaikan <strong>{{ $record->certification_name }}</strong></div>
        <div class="meta">{{ $record->language_label }} · Level {{ $record->level ?: '-' }}</div>
        @if($record->score !== null)<div class="score">Nilai {{ number_format((float) $record->score, 1, ',', '.') }}</div>@endif
        <div class="meta">Nomor: {{ $record->certificate_number ?: '-' }} · Tanggal: {{ $record->issued_date?->format('d/m/Y') ?? '-' }}</div>
        <div class="footer">
            <div>Kepala Departemen Bahasa<br><br><br>__________________________</div>
            <div>Mataram, {{ now()->format('d/m/Y') }}<br>Qismul Lughah<br><br>__________________________</div>
        </div>
    </div>
    <div class="text-center no-print"><button onclick="window.print()" class="btn btn-primary">Cetak Sertifikat</button></div>
</body>
</html>
