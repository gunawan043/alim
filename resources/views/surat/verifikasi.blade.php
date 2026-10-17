<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Verifikasi Surat — ALIM</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #f1f5f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1e293b; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(15,23,42,.08); max-width: 640px; width: 100%; overflow: hidden; }
        .head { padding: 22px 26px; background: linear-gradient(135deg, #eef2ff, #e0f2fe); border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 14px; }
        .head .mark { width: 46px; height: 46px; border-radius: 12px; background: #4f46e5; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; }
        .head h1 { margin: 0; font-size: 17px; }
        .head p { margin: 2px 0 0; font-size: 12px; color: #64748b; }
        .body { padding: 24px 26px; }
        .status { display: flex; align-items: flex-start; gap: 12px; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; }
        .status.valid { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .status.invalid { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .status .icon { font-size: 22px; line-height: 1; }
        .status h2 { margin: 0 0 3px; font-size: 15px; }
        .status p { margin: 0; font-size: 12.5px; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { text-align: left; padding: 9px 0; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        th { width: 150px; color: #64748b; font-weight: 500; }
        td strong { font-weight: 600; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #e2e8f0; color: #334155; }
        .badge.ok { background: #dcfce7; color: #166534; }
        .badge.bad { background: #fee2e2; color: #991b1b; }
        .foot { padding: 16px 26px 22px; font-size: 11.5px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
        @media (max-width: 480px) { th { width: 110px; } .body { padding: 18px; } }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <div class="mark">A</div>
            <div>
                <h1>Verifikasi Dokumen ALIM</h1>
                <p>Academic Learning &amp; Information Management</p>
            </div>
        </div>

        <div class="body">
            @if($valid && $surat)
                <div class="status valid">
                    <div class="icon">✓</div>
                    <div>
                        <h2>Dokumen Terverifikasi</h2>
                        <p>Surat ini tercatat pada arsip resmi {{ $surat->school->name ?? 'satuan pendidikan' }}.</p>
                    </div>
                </div>

                <table>
                    <tr><th>Jenis Surat</th><td>Surat {{ ucfirst($jenis) }}</td></tr>
                    <tr><th>Nomor Surat</th><td><strong>{{ $surat->nomor_surat }}</strong></td></tr>
                    <tr><th>Tanggal</th><td>{{ $surat->tanggal_surat?->translatedFormat('d F Y') ?? '-' }}</td></tr>
                    <tr><th>Perihal</th><td>{{ $surat->perihal }}</td></tr>
                    @if($jenis === 'masuk')
                        <tr><th>Pengirim</th><td>{{ $surat->pengirim }}</td></tr>
                    @else
                        <tr><th>Tujuan</th><td>{{ $surat->tujuan }}</td></tr>
                        <tr><th>Penandatangan</th><td>{{ $surat->penandatangan ?: '-' }}</td></tr>
                    @endif
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge ok">{{ ucfirst(str_replace('_', ' ', $surat->status)) }}</span>
                        </td>
                    </tr>
                    <tr><th>Satuan Pendidikan</th><td>{{ $surat->school->name ?? '-' }}</td></tr>
                </table>
            @else
                <div class="status invalid">
                    <div class="icon">!</div>
                    <div>
                        <h2>Dokumen Tidak Valid</h2>
                        <p>
                            @if($surat)
                                Surat ini berstatus <strong>dibatalkan</strong> dan tidak lagi berlaku.
                            @else
                                Kode verifikasi tidak ditemukan pada arsip surat ALIM.
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <div class="foot">
            Halaman ini dihasilkan otomatis dari arsip surat ALIM. Jika ada pertanyaan, hubungi Tata Usaha
            {{ $surat->school->name ?? 'satuan pendidikan' }}.
        </div>
    </div>
</body>
</html>
