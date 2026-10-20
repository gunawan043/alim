<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Surat — {{ $surat->nomor_surat }}</title>
    <style>
        @page { size: A4; margin: 18mm 20mm; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; margin: 0; padding: 20px; }
        .no-print { margin-bottom: 16px; display: flex; gap: 8px; }
        .btn { display: inline-block; padding: 7px 14px; border: 1px solid #333; border-radius: 4px; background: #fff; color: #000; text-decoration: none; font-family: Arial, sans-serif; font-size: 10pt; cursor: pointer; }
        .btn-primary { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .kop { display: flex; align-items: center; gap: 14px; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 18px; }
        .kop .logo { width: 64px; height: 64px; border: 1px solid #999; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif; font-size: 9pt; color: #666; flex-shrink: 0; }
        .kop .text { text-align: center; flex-grow: 1; }
        .kop h3 { margin: 0; font-size: 15pt; text-transform: uppercase; }
        .kop p { margin: 2px 0 0; font-size: 9.5pt; }
        .meta { margin: 14px 0 18px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; vertical-align: top; font-size: 11.5pt; }
        .meta td.label { width: 90px; }
        .meta td.sep { width: 10px; }
        .body { min-height: 220px; line-height: 1.75; white-space: pre-line; font-size: 12pt; }
        .signature { margin-top: 30px; width: 100%; }
        .signature td { width: 55%; vertical-align: top; }
        .signature .space { height: 70px; }
        .signature .name { font-weight: bold; text-decoration: underline; }
        .verify { margin-top: 26px; display: flex; align-items: center; gap: 10px; border-top: 1px dashed #999; padding-top: 10px; }
        .verify svg { width: 84px; height: 84px; }
        .verify .small { font-family: Arial, sans-serif; font-size: 8pt; color: #444; word-break: break-all; }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    @php
        $verifyUrl = $surat->verification_token
            ? route('surat.verify', ['token' => $surat->verification_token])
            : null;
        $qrSvg = $verifyUrl
            ? \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(110)->margin(0)->generate($verifyUrl)
            : null;
        $qrData = $qrSvg ? 'data:image/svg+xml;base64,'.base64_encode((string) $qrSvg) : null;
    @endphp

    <div class="no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('waka.surat-keluar.show', $surat->id) }}" class="btn">Kembali</a>
    </div>

    <div class="kop">
        <div class="logo">LOGO</div>
        <div class="text">
            <h3>{{ $surat->school->name ?? 'Satuan Pendidikan' }}</h3>
            <p>
                @if($surat->school?->address){{ $surat->school->address }}@endif
                @if($surat->school?->phone) · Telp. {{ $surat->school->phone }}@endif
                @if($surat->school?->email) · {{ $surat->school->email }}@endif
            </p>
        </div>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">Nomor</td><td class="sep">:</td><td>{{ $surat->nomor_surat }}</td>
                <td style="text-align:right">{{ $surat->tanggal_surat?->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Perihal</td><td class="sep">:</td><td colspan="2"><strong>{{ $surat->perihal }}</strong></td>
            </tr>
            <tr>
                <td colspan="4" style="padding-top:10px">Kepada Yth.<br><strong>{{ $surat->tujuan }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="body">{{ $surat->isi_surat ?: $surat->perihal }}</div>

    <table class="signature">
        <tr>
            <td></td>
            <td style="text-align:center">
                {{ $surat->tanggal_surat?->translatedFormat('d F Y') }}<br>
                {{ $surat->jabatan_penandatangan ?: 'Penandatangan' }}
                <div class="space"></div>
                <div class="name">{{ $surat->penandatangan ?: '................................' }}</div>
            </td>
        </tr>
    </table>

    @if($qrSvg)
        <div class="verify">
            @if($qrData)
                <img src="{{ $qrData }}" width="84" height="84" alt="QR Verifikasi">
            @endif
            <div class="small">
                Verifikasi keaslian dokumen:<br>
                {{ $verifyUrl }}
            </div>
        </div>
    @endif
</body>
</html>
