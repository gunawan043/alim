<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Pelajar</title>
    <style>
        @page { size: {{ $setting->card_width_mm }}mm {{ $setting->card_height_mm }}mm; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; width: {{ $setting->card_width_mm }}mm; height: {{ $setting->card_height_mm }}mm; }
        body { font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif; color: #0f172a; }
        .id-card {
            position: relative;
            width: {{ $setting->card_width_mm }}mm;
            height: {{ $setting->card_height_mm }}mm;
            overflow: hidden;
            background: #f8fafc center/100% 100% no-repeat;
            page-break-after: always;
        }
        .id-card.last { page-break-after: auto; }
        .card-bg { position: absolute; left: 0; top: 0; width: 100%; height: 100%; display: block; }
        .photo-box { position: absolute; left: var(--photo-left); top: var(--photo-top); width: var(--photo-w); height: var(--photo-h); }
        .photo-box img, .photo-box .photo-empty { width: 100%; height: 100%; object-fit: cover; border-radius: 1mm; background: #dbeafe; border: 0.2mm solid #cbd5e1; display: block; }
        .info-table { position: absolute; left: var(--info-left); right: 4mm; top: var(--info-top); border-collapse: collapse; font-size: var(--info-size); line-height: var(--line-height); }
        .info-table td { padding: 0; vertical-align: top; }
        .info-table .label { width: 21mm; white-space: nowrap; }
        .info-table .colon { width: 3mm; text-align: center; }
        .signature { position: absolute; right: 4mm; bottom: 3mm; width: 40mm; text-align: center; }
        .signature img { width: var(--sig-w); height: var(--sig-h); object-fit: contain; display: block; margin: var(--sig-gap) auto; }
        .signature .sig-empty { width: var(--sig-w); height: var(--sig-h); border-bottom: 0.25mm solid #94a3b8; margin: var(--sig-gap) auto; }
        .signature .sig-title { font-size: var(--sig-title-size); line-height: 1.2; margin-bottom: var(--sig-gap); }
        .signature .sig-name { font-size: var(--sig-name-size); font-weight: 700; margin-top: var(--sig-gap); }
        .back-note { position: absolute; left: 5mm; right: 5mm; top: 5mm; font-size: var(--back-size); line-height: var(--back-line-height); }
        .back-qr { position: absolute; left: var(--back-qr-left); top: var(--back-qr-top); width: var(--back-qr-size); height: var(--back-qr-size); }
        .back-qr img { width: 100%; height: 100%; display: block; }
    </style>
</head>
<body>
@foreach($cards as $card)
    @php $student = $card['student']; $front = $layout['front']; $back = $layout['back']; @endphp

    <div class="id-card"
         style="--info-size:{{ $front['font_size'] }}pt;--line-height:{{ $front['line_height'] }};--photo-w:{{ $front['photo_width'] }}mm;--photo-h:{{ $front['photo_height'] }}mm;--photo-left:{{ $front['photo_left'] }}mm;--photo-top:{{ $front['photo_top'] }}mm;--info-left:{{ $front['info_left'] }}mm;--info-top:{{ $front['info_top'] }}mm;--sig-w:{{ $front['signature_width'] }}mm;--sig-h:{{ $front['signature_height'] }}mm;--sig-title-size:{{ $front['signature_title_font_size'] }}pt;--sig-name-size:{{ $front['signature_name_font_size'] }}pt;--sig-gap:{{ $front['signature_gap'] }}mm;">
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
         style="--back-size:{{ $back['font_size'] }}pt;--back-line-height:{{ $back['line_height'] }};--back-qr-left:{{ $back['back_qr_left'] }}mm;--back-qr-top:{{ $back['back_qr_top'] }}mm;--back-qr-size:{{ $back['back_qr_size'] }}mm;">
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
