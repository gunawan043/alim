<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Rujukan {{ $referral->referral_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 24px; font-size: 13px; }
        .sheet { max-width: 800px; margin: 0 auto; }
        .kop { display: flex; align-items: center; gap: 16px; border-bottom: 3px double #111; padding-bottom: 12px; }
        .logo { width: 64px; height: 64px; border: 2px solid #111; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; }
        .kop h1 { margin: 0; font-size: 18px; letter-spacing: .5px; }
        .kop p { margin: 2px 0 0; font-size: 12px; color: #444; }
        h2 { text-align: center; margin: 26px 0 4px; font-size: 16px; text-decoration: underline; letter-spacing: 1px; }
        .nomor { text-align: center; margin: 0 0 22px; font-size: 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        .identity td { padding: 4px 0; vertical-align: top; }
        .identity td:first-child { width: 150px; color: #555; }
        .box { border: 1px solid #999; padding: 12px; margin: 14px 0; min-height: 60px; }
        .box h3 { margin: 0 0 6px; font-size: 12px; text-transform: uppercase; color: #555; }
        .signature { margin-top: 40px; width: 260px; margin-left: auto; text-align: center; }
        .signature .space { height: 64px; }
        .print-btn { position: fixed; top: 16px; right: 16px; padding: 10px 16px; border: 0; background: #0d6efd; color: #fff; border-radius: 6px; cursor: pointer; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Cetak</button>

    <div class="sheet">
        <div class="kop">
            <div class="logo">UKS</div>
            <div>
                <h1>UNIT KESEHATAN SEKOLAH (UKS)</h1>
                <p>{{ $referral->school?->name ?? $referral->patient?->school?->name ?? 'Sekolah' }}</p>
                <p>Layanan Kesehatan Santri</p>
            </div>
        </div>

        <h2>SURAT RUJUKAN</h2>
        <p class="nomor">Nomor: {{ $referral->referral_no ?? '-' }}</p>

        <table class="identity">
            <tr><td>Nama Pasien</td><td>: <strong>{{ $referral->patient?->student?->name ?? '-' }}</strong></td></tr>
            <tr><td>No. Rekam Medis</td><td>: {{ $referral->patient?->medical_record_no ?? '-' }}</td></tr>
            <tr><td>NIS / NISN</td><td>: {{ $referral->patient?->student?->nis ?? '-' }} / {{ $referral->patient?->student?->nisn ?? '-' }}</td></tr>
            <tr><td>Jenis Kelamin</td><td>: {{ $referral->patient?->student?->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</td></tr>
            <tr><td>Asrama</td><td>: {{ $referral->patient?->student?->activeDormitoryResident?->dormitory?->name ?? '-' }}</td></tr>
            <tr><td>Tanggal Rujukan</td><td>: {{ $referral->referred_at?->translatedFormat('d F Y H:i') ?? '-' }}</td></tr>
            <tr><td>Fasilitas Tujuan</td><td>: <strong>{{ $referral->facility_name }}</strong> ({{ ucfirst(str_replace('_', ' ', $referral->facility_type ?? '-')) }})</td></tr>
            <tr><td>Urgensi</td><td>: {{ $referral->urgency_label }}</td></tr>
        </table>

        <div class="box">
            <h3>Diagnosis / Assessment</h3>
            {{ $referral->diagnosis ?: '-' }}
        </div>

        <div class="box">
            <h3>Alasan Rujukan</h3>
            {{ $referral->reason }}
        </div>

        <div class="box">
            <h3>Tindakan / Terapi Sebelum Rujukan</h3>
            {{ $referral->patient?->treatment ?: '-' }}
        </div>

        <table class="identity">
            <tr><td>Transportasi</td><td>: {{ $referral->transport ?: '-' }}</td></tr>
            <tr><td>Pendamping</td><td>: {{ $referral->accompanied_by ?: '-' }}</td></tr>
            <tr><td>Catatan</td><td>: {{ $referral->outcome ?: '-' }}</td></tr>
        </table>

        <div class="signature">
            <div>Petugas UKS,</div>
            <div class="space"></div>
            <div><strong>{{ $referral->referredBy?->name ?? '________________' }}</strong></div>
            <div style="font-size: 11px; color: #555;">{{ $referral->referred_at?->translatedFormat('d F Y') }}</div>
        </div>
    </div>
</body>
</html>
