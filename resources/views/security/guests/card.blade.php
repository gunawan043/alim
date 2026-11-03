<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Tamu — {{ $guest->visitor_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #eef2f7;
            margin: 0;
            padding: 24px;
            display: flex;
            justify-content: center;
        }
        .card {
            width: 340px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(0,0,0,.12);
            overflow: hidden;
        }
        .card-head {
            background: #0a5f9e;
            color: #fff;
            padding: 14px 18px;
            text-align: center;
        }
        .card-head .title { font-size: 12px; letter-spacing: .16em; text-transform: uppercase; opacity: .85; }
        .card-head .org { font-size: 15px; font-weight: 700; margin-top: 2px; }
        .card-body { padding: 18px; text-align: center; }
        .name { font-size: 20px; font-weight: 700; color: #14213d; }
        .code { font-family: monospace; font-size: 12px; color: #64748b; margin-top: 2px; }
        .qr { margin: 14px auto 8px; width: 150px; height: 150px; }
        .qr svg { width: 100%; height: 100%; }
        .meta { font-size: 12px; color: #475569; line-height: 1.6; }
        .meta strong { color: #14213d; }
        .badge {
            display: inline-block; margin-top: 10px; padding: 4px 12px; border-radius: 999px;
            background: #dcfce7; color: #166534; font-size: 11px; font-weight: 600;
        }
        .badge.out { background: #e2e8f0; color: #475569; }
        .foot {
            border-top: 1px dashed #cbd5e1; padding: 10px 18px; text-align: center;
            font-size: 10px; color: #94a3b8;
        }
        .actions { text-align: center; margin-top: 18px; }
        .btn {
            border: 0; background: #0a5f9e; color: #fff; padding: 9px 18px;
            border-radius: 8px; font-size: 13px; cursor: pointer;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .card { box-shadow: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div>
        <div class="card">
            <div class="card-head">
                <div class="title">Kartu Identitas Tamu</div>
                <div class="org">ALIM · Satuan Keamanan</div>
            </div>
            <div class="card-body">
                <div class="name">{{ $guest->visitor_name }}</div>
                <div class="code">{{ $guest->code }}</div>
                <div class="qr">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(300)->margin(0)->generate($guest->code) !!}
                </div>
                <div class="meta">
                    <div><strong>Keperluan:</strong> {{ $guest->purpose }}</div>
                    <div><strong>Bertemu:</strong> {{ $guest->person_to_meet ?: '—' }}</div>
                    <div><strong>Pos:</strong> {{ $guest->post->name ?? '—' }}</div>
                    <div><strong>Masuk:</strong> {{ optional($guest->check_in_at)->format('d/m/Y H:i') }}</div>
                </div>
                @if($guest->isInside())
                    <span class="badge">BERADA DI DALAM</span>
                @else
                    <span class="badge out">SUDAH KELUAR</span>
                @endif
            </div>
            <div class="foot">Kartu ini wajib dikembalikan ke pos gerbang saat keluar.</div>
        </div>
        <div class="actions">
            <button class="btn" onclick="window.print()">Cetak</button>
        </div>
    </div>
</body>
</html>
