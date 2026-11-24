<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Pelajar</title>
    @php
        $s = (float) ($printScale ?? 2);
        $pageW = (float) ($setting->card_width_mm ?: 85.60) * $s;
        $pageH = (float) ($setting->card_height_mm ?: 53.98) * $s;
        $mm = fn ($value) => round((float) $value * $s, 2);
        $pt = fn ($value) => round((float) $value * $s, 2);
    @endphp
    <style>
        @page { size: {{ $pageW }}mm {{ $pageH }}mm; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; width: {{ $pageW }}mm; height: {{ $pageH }}mm; }
        body { font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif; color: #0f172a; }
        .id-card {
            position: relative;
            width: {{ $pageW }}mm;
            height: {{ $pageH }}mm;
            overflow: hidden;
            background: #f8fafc;
            page-break-after: always;
        }
        .id-card.last { page-break-after: auto; }
        .card-bg { position: absolute; left: 0; top: 0; width: 100%; height: 100%; display: block; }
        .photo-box { position: absolute; left: var(--photo-left); top: var(--photo-top); width: var(--photo-w); height: var(--photo-h); }
        .photo-box img, .photo-box .photo-empty { width: 100%; height: 100%; object-fit: cover; border-radius: {{ $mm(1) }}mm; background: #dbeafe; border: {{ $mm(.2) }}mm solid #cbd5e1; display: block; }
        .info-table { position: absolute; left: var(--info-left); right: {{ $mm(4) }}mm; top: var(--info-top); border-collapse: collapse; font-size: var(--info-size); line-height: var(--line-height); }
        .info-table td { padding: 0; vertical-align: top; }
        .info-table .label { width: {{ $mm(21) }}mm; white-space: nowrap; }
        .info-table .colon { width: {{ $mm(3) }}mm; text-align: center; }
        .signature { position: absolute; right: {{ $mm(4) }}mm; bottom: {{ $mm(3) }}mm; width: {{ $mm(40) }}mm; text-align: center; }
        .signature img { width: var(--sig-w); height: var(--sig-h); object-fit: contain; display: block; margin: var(--sig-gap) auto; }
        .signature .sig-empty { width: var(--sig-w); height: var(--sig-h); border-bottom: {{ $mm(.25) }}mm solid #94a3b8; margin: var(--sig-gap) auto; }
        .signature .sig-title { font-size: var(--sig-title-size); line-height: 1.2; margin-bottom: var(--sig-gap); }
        .signature .sig-name { font-size: var(--sig-name-size); font-weight: 700; margin-top: var(--sig-gap); }
        .back-note { position: absolute; left: {{ $mm(5) }}mm; right: {{ $mm(5) }}mm; top: {{ $mm(5) }}mm; font-size: var(--back-size); line-height: var(--back-line-height); }
        .back-qr { position: absolute; left: var(--back-qr-left); top: var(--back-qr-top); width: var(--back-qr-size); height: var(--back-qr-size); }
        .back-qr img { width: 100%; height: 100%; display: block; }
    </style>
</head>
<body>
@foreach($cards as $card)
    @php $student = $card['student']; $front = $layout['front']; $back = $layout['back']; @endphp

    <div class="id-card"
         style="--info-size:{{ $pt($front['font_size']) }}pt;--line-height:{{ $front['line_height'] }};--photo-w:{{ $mm($front['photo_width']) }}mm;--photo-h:{{ $mm($front['photo_height']) }}mm;--photo-left:{{ $mm($front['photo_left']) }}mm;--photo-top:{{ $mm($front['photo_top']) }}mm;--info-left:{{ $mm($front['info_left']) }}mm;--info-top:{{ $mm($front['info_top']) }}mm;--sig-w:{{ $mm($front['signature_width']) }}mm;--sig-h:{{ $mm($front['signature_height']) }}mm;--sig-title-size:{{ $pt($front['signature_title_font_size']) }}pt;--sig-name-size:{{ $pt($front['signature_name_font_size']) }}pt;--sig-gap:{{ $mm($front['signature_gap']) }}mm;">
        @if($frontBg)<img class="card-bg" src="{{ $frontBg }}" alt="">@endif
        @if($front['show_photo'])
            <div class="photo-box">
                @if($card['photo'])
                    <img src="{{ $card['photo'] }}" alt="Foto">
                @else
                    <div class="photo-empty"></div>
                @endif
            </div>
        @endif
        <table class="info-table">
            @if($front['show_name'])<tr><td class="label">Nama</td><td class="colon">:</td><td>{{ $student->name }}</td></tr>@endif
            @if($front['show_nis'] || $front['show_nisn'])
                <tr>
                    <td class="label">NIS/NISN</td><td class="colon">:</td>
                    <td>
                        @if($front['show_nis'] && $front['show_nisn'])
                            {{ $student->nis ?: '-' }} / {{ $student->nisn ?: '-' }}
                        @elseif($front['show_nis'])
                            {{ $student->nis ?: '-' }}
                        @else
                            {{ $student->nisn ?: '-' }}
                        @endif
                    </td>
                </tr>
            @endif
            @if($front['show_ttl'])<tr><td class="label">TTL</td><td class="colon">:</td><td>{{ $card['ttl'] }}</td></tr>@endif
            @if($front['show_address'])<tr><td class="label">Alamat</td><td class="colon">:</td><td>{{ $card['address'] }}</td></tr>@endif
            @if($front['show_class'])<tr><td class="label">Kelas</td><td class="colon">:</td><td>{{ $card['class'] }}</td></tr>@endif
        </table>
        @if($front['show_signature'])
            <div class="signature">
                @if($front['show_signature_title'])<div class="sig-title">{{ $card['signature_title'] }}</div>@endif
                @if($card['signature'])
                    <img src="{{ $card['signature'] }}" alt="TTD">
                @else
                    <div class="sig-empty"></div>
                @endif
                @if($front['show_signature_name'])<div class="sig-name">{{ $card['signature_name'] }}</div>@endif
            </div>
        @endif
    </div>

    <div class="id-card {{ $loop->last ? 'last' : '' }}"
         style="--back-size:{{ $pt($back['font_size']) }}pt;--back-line-height:{{ $back['line_height'] }};--back-qr-left:{{ $mm($back['back_qr_left']) }}mm;--back-qr-top:{{ $mm($back['back_qr_top']) }}mm;--back-qr-size:{{ $mm($back['back_qr_size']) }}mm;">
        @if($backBg)<img class="card-bg" src="{{ $backBg }}" alt="">@endif
        @if($back['show_note'])
            <div class="back-note">Kartu ini merupakan identitas resmi santri. Simpan dan gunakan dengan baik.</div>
        @endif
        @if($back['show_back_qr'] && $card['back_qr'])
            <div class="back-qr"><img src="{{ $card['back_qr'] }}" alt="QR Belakang"></div>
        @endif
    </div>
@endforeach
</body>
</html>
