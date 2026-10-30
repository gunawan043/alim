<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Verifikasi Dokumen — {{ config('app.name') }}</title>
    <style>
        :root { --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --brand:#405189; --ok:#0f9f6e; --bad:#dc3545; --warn:#b45309; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; background:#f6f8fb; color:var(--ink); }
        .wrap { max-width: 760px; margin: 0 auto; padding: 32px 18px 56px; }
        .brand { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
        .brand-mark { width:40px; height:40px; border-radius:12px; background:var(--brand); color:#fff; display:grid; place-items:center; font-weight:700; }
        .brand small { color:var(--muted); display:block; }
        .card { background:#fff; border:1px solid var(--line); border-radius:18px; box-shadow:0 8px 30px rgba(15,23,42,.06); overflow:hidden; }
        .hero { padding:26px 24px; border-bottom:1px solid var(--line); }
        .status { display:inline-flex; align-items:center; gap:8px; font-weight:700; font-size:13px; padding:7px 12px; border-radius:999px; }
        .status.ok { background:#e8f7f0; color:var(--ok); }
        .status.bad { background:#fdecee; color:var(--bad); }
        .status.warn { background:#fff5e6; color:var(--warn); }
        h1 { font-size:22px; margin:14px 0 4px; line-height:1.3; }
        p { line-height:1.65; }
        .muted { color:var(--muted); }
        .body { padding:22px 24px; }
        .grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:16px; }
        .item small { display:block; color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
        .item div { font-weight:600; word-break:break-word; }
        .signers { margin-top:22px; border-top:1px solid var(--line); padding-top:18px; }
        .signer { display:flex; justify-content:space-between; gap:14px; padding:10px 0; border-bottom:1px dashed var(--line); }
        .signer:last-child { border-bottom:0; }
        .badge { font-size:11px; padding:4px 8px; border-radius:999px; background:#eef2ff; color:#405189; }
        .hash { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px; background:#f8fafc; border:1px solid var(--line); padding:10px 12px; border-radius:10px; word-break:break-all; }
        .note { margin-top:18px; font-size:13px; color:var(--muted); }
        .footer { text-align:center; color:var(--muted); font-size:12px; margin-top:20px; }
        @media (max-width: 560px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <div class="brand-mark">AL</div>
        <div>
            <strong>{{ config('app.name', 'ALIM') }}</strong>
            <small>Verifikasi Dokumen Resmi</small>
        </div>
    </div>

    <div class="card">
        @if(! $document)
            <div class="hero">
                <span class="status bad">Tidak ditemukan</span>
                <h1>Dokumen tidak terdaftar</h1>
                <p class="muted">Token verifikasi tidak cocok dengan dokumen mana pun. Periksa kembali QR atau tautan yang Anda gunakan.</p>
            </div>
        @else
            @php
                $revoked = $document->status === 'revoked';
                $tampered = ! $revoked && ! $integrity;
                $valid = $document->status === 'issued' && $integrity;
                $statusClass = $valid ? 'ok' : ($revoked || $tampered ? 'bad' : 'warn');
                $statusLabel = $valid ? 'Dokumen Sah' : ($revoked ? 'Dokumen Dicabut' : ($tampered ? 'Integritas Tidak Cocok' : ucfirst($document->status)));
            @endphp
            <div class="hero">
                <span class="status {{ $statusClass }}">{{ $statusLabel }}</span>
                <h1>{{ $document->title }}</h1>
                <p class="muted" style="margin:0">
                    {{ ucwords(str_replace(['_', '-'], ' ', $document->document_type)) }}
                    @if(config('documents.public.show_document_number', true) && $document->document_number)
                        · {{ $document->document_number }}
                    @endif
                </p>
                @if($tampered)
                    <p style="margin:14px 0 0;color:var(--bad)">
                        Data registry tidak cocok dengan signature. Dokumen ini tidak dapat dinyatakan sah.
                    </p>
                @endif
                @if($revoked)
                    <p style="margin:14px 0 0;color:var(--bad)">
                        Dokumen ini dicabut{{ $document->revoked_at ? ' pada '.$document->revoked_at->translatedFormat('d F Y H:i') : '' }}.
                        @if($document->revocation_reason)
                            Alasan: {{ $document->revocation_reason }}
                        @endif
                    </p>
                @endif
            </div>

            <div class="body">
                <div class="grid">
                    <div class="item"><small>Unit</small><div>{{ $document->school?->name ?? '—' }}</div></div>
                    <div class="item"><small>Periode</small><div>{{ $document->academicYear?->name ?? '—' }}{{ $document->semester ? ' · '.ucfirst($document->semester) : '' }}</div></div>
                    <div class="item"><small>Diterbitkan</small><div>{{ $document->issued_at?->translatedFormat('d F Y H:i') ?? '—' }}</div></div>
                    <div class="item"><small>Metode Validasi</small><div>{{ $document->validation_mode === 'pades' ? 'PAdES + QR Registry' : 'QR Registry' }}</div></div>
                </div>

                @if($document->signers->isNotEmpty())
                    <div class="signers">
                        <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Penandatangan</div>
                        @foreach($document->signers as $signer)
                            <div class="signer">
                                <div>
                                    <strong>{{ config('documents.public.show_signer_names', true) ? $signer->name : 'Penandatangan' }}</strong>
                                    <div class="muted" style="font-size:13px">{{ $signer->role ?: 'Penandatangan' }}</div>
                                </div>
                                <span class="badge">
                                    {{ $signer->signature_status === 'signed' ? 'PAdES' : 'TTD Visual' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="signers">
                    <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Integritas Data</div>
                    <div class="hash">SHA-256 payload: {{ $document->payload_hash }}</div>
                    @if($document->file_hash)
                        <div class="hash" style="margin-top:8px;">SHA-256 berkas: {{ $document->file_hash }}</div>
                    @endif
                </div>

                @if($document->validation_mode === 'pades')
                    <p class="note">
                        Dokumen ini menggunakan tanda tangan elektronik PAdES. Untuk memeriksa signature kriptografis,
                        buka file PDF di Adobe Acrobat Reader atau pembaca PDF yang mendukung PAdES. Halaman ini
                        memverifikasi registry penerbitan tanpa perlu mengunggah dokumen.
                    </p>
                @else
                    <p class="note">
                        Dokumen ini terdaftar pada registry ALIM. Stempel dan scan tanda tangan tetap menjadi bagian
                        visual dokumen; keasliannya diverifikasi melalui token QR ini.
                    </p>
                @endif

                <p class="note">
                    Halaman ini tidak menampilkan data pribadi santri. Untuk keterangan lebih lanjut, hubungi unit
                    penerbit dokumen.
                </p>
            </div>
        @endif
    </div>

    <div class="footer">
        Dipindai {{ $document?->scan_count ?? 0 }} kali · {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</div>
</body>
</html>
