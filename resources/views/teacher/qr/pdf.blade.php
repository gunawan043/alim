<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR Kelas — {{ $studyGroup->full_name ?? $studyGroup->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; text-align: center; }
        .card { border: 2px solid #111; border-radius: 10px; padding: 22px 26px; }
        .school { font-size: 17pt; font-weight: bold; text-transform: uppercase; margin: 0; }
        .sub { font-size: 10pt; color: #444; margin: 3px 0 0; }
        .class-name { font-size: 20pt; font-weight: bold; margin: 22px 0 4px; }
        .meta { font-size: 10.5pt; color: #333; margin: 0 0 18px; }
        .qr { width: 300px; height: 300px; margin: 0 auto 16px; }
        .qr img { width: 300px; height: 300px; }
        .instruction { font-size: 10.5pt; line-height: 1.5; margin: 0 auto; max-width: 430px; }
        .footer { margin-top: 24px; font-size: 9pt; color: #555; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="school">{{ $studyGroup->school?->name ?? config('app.name') }}</h1>
        <p class="sub">
            {{ $studyGroup->school?->address ?? '' }}
            @if($studyGroup->school?->phone) · Telp. {{ $studyGroup->school->phone }} @endif
        </p>

        <div class="class-name">{{ $studyGroup->full_name ?? $studyGroup->name }}</div>
        <p class="meta">
            Wali Kelas: <strong>{{ $studyGroup->homeroomTeacher?->name ?? '—' }}</strong>
            &nbsp;|&nbsp; Tahun Ajaran: <strong>{{ $token->academicYear?->name ?? '—' }}</strong>
        </p>

        <div class="qr">
            <img src="{{ $qrData }}" alt="QR Code {{ $studyGroup->full_name ?? $studyGroup->name }}">
        </div>

        <p class="instruction">
            Pindai QR ini melalui menu <strong>Scan QR Kehadiran</strong> untuk mencatat
            absensi masuk dan keluar kelas sesuai jadwal mengajar.
        </p>

        <div class="footer">
            Dicetak {{ now()->translatedFormat('d F Y H:i') }}
        </div>
    </div>
</body>
</html>
