<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Thermal — {{ $dormitory->name }}</title>
    <style>
        @page { size: {{ $size }}mm auto; margin: 2mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            width: {{ $size }}mm;
            max-width: {{ $size }}mm;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
        }
        .actions { padding: 6px; text-align: center; }
        .actions button, .actions a {
            display: inline-block; margin: 2px; padding: 5px 9px; border: 1px solid #111;
            background: #fff; color: #111; font-size: 10px; text-decoration: none; cursor: pointer;
        }
        .label {
            page-break-after: always;
            break-after: page;
            text-align: center;
            padding: {{ $size === '80' ? '4mm' : '2.5mm' }} 2mm;
        }
        .label:last-child { page-break-after: auto; break-after: auto; }
        .school { font-size: {{ $size === '80' ? '11px' : '9px' }}; font-weight: 700; text-transform: uppercase; line-height: 1.25; }
        .dorm { font-size: {{ $size === '80' ? '9px' : '8px' }}; margin: 1px 0 2mm; }
        .qr {
            width: {{ $size === '80' ? '62mm' : '46mm' }};
            height: {{ $size === '80' ? '62mm' : '46mm' }};
            margin: 0 auto;
        }
        .qr svg { width: 100%; height: 100%; display: block; }
        .name { font-size: {{ $size === '80' ? '13px' : '11px' }}; font-weight: 700; margin-top: 2mm; line-height: 1.25; }
        .meta { font-size: {{ $size === '80' ? '9px' : '8px' }}; margin-top: 1px; }
        .token { font-family: monospace; font-size: 6px; word-break: break-all; margin-top: 1.5mm; color: #333; }
        .actions { display: block; }
        @media print { .actions { display: none !important; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Cetak Thermal</button>
        <a href="{{ route('user.asrama.gatepass.cards', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">Kembali</a>
    </div>

    @forelse($residents as $resident)
        <div class="label">
            <div class="school">{{ $dormitory->school?->name ?? 'ALIM' }}</div>
            <div class="dorm">{{ $dormitory->name }}</div>
            <div class="qr">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(320)->margin(0)->generate($tokens[$resident->id] ?? '') !!}
            </div>
            <div class="name">{{ $resident->student?->name ?? '-' }}</div>
            <div class="meta">{{ $resident->room?->name ?? '-' }} · Bed #{{ $resident->bed_number }}</div>
            <div class="token">{{ \Illuminate\Support\Str::limit($tokens[$resident->id] ?? '', 48) }}</div>
        </div>
    @empty
        <div class="label">Belum ada penghuni aktif.</div>
    @endforelse
</body>
</html>
