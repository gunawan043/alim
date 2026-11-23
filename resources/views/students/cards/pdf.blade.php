<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Pelajar</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif; color: #0f172a; }
        .sheet { width: 200mm; margin: 0 auto; page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .card-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .card-grid td { width: 50%; padding: 1mm 2mm; vertical-align: top; text-align: center; }
        .id-card {
            position: relative;
            width: 85.6mm;
            height: 53.98mm;
            margin: 0 auto;
            overflow: hidden;
            border: 0.25mm solid #cbd5e1;
            border-radius: 2.5mm;
            background: #f8fafc center/100% 100% no-repeat;
            page-break-inside: avoid;
        }
        .front-bg { background-image: url('{{ $frontBg }}'); }
        .back-bg { background-image: url('{{ $backBg }}'); }
        .photo-box { position: absolute; left: var(--photo-left); top: var(--photo-top); width: var(--photo-w); height: var(--photo-h); }
        .photo-box img, .photo-box .photo-empty { width: 100%; height: 100%; object-fit: cover; border-radius: 1mm; background: #dbeafe; border: 0.2mm solid #cbd5e1; display: block; }
        .front-qr { position: absolute; left: var(--photo-left); top: calc(var(--photo-top) + var(--photo-h) + 1mm); width: var(--qr-size); height: var(--qr-size); }
        .front-qr img { width: 100%; height: 100%; display: block; }
        .info-table { position: absolute; left: calc(var(--photo-left) + var(--photo-w) + 3mm); right: 4mm; top: 8mm; border-collapse: collapse; font-size: var(--info-size); line-height: var(--line-height); }
        .id-card.no-photo .info-table { left: 4mm; }
        .info-table td { padding: 0; vertical-align: top; }
        .info-table .label { width: 21mm; white-space: nowrap; }
        .info-table .colon { width: 3mm; text-align: center; }
        .signature { position: absolute; right: 4mm; bottom: 3mm; width: 35mm; text-align: center; }
        .signature img { width: var(--sig-w); height: var(--sig-h); object-fit: contain; display: block; margin: 0 auto; }
        .signature .sig-empty { width: var(--sig-w); height: var(--sig-h); border-bottom: 0.25mm solid #94a3b8; margin: 0 auto; }
        .signature .sig-name { font-size: calc(var(--info-size) - .5pt); font-weight: 700; margin-top: .5mm; }
        .signature .sig-title { font-size: calc(var(--info-size) - 1pt); }
        .back-note { position: absolute; left: 5mm; right: 5mm; top: 5mm; font-size: var(--back-size); line-height: var(--back-line-height); }
        .back-qr { position: absolute; left: var(--back-qr-left); top: var(--back-qr-top); width: var(--back-qr-size); height: var(--back-qr-size); }
        .back-qr img { width: 100%; height: 100%; display: block; }
    </style>
</head>
<body>
@foreach(collect($cards)->chunk(10) as $batch)
    <div class="sheet">
        <table class="card-grid">
            @foreach($batch->chunk(2) as $row)
                <tr>
                    @foreach($row as $card)
                        @php $student = $card['student']; $front = $layout['front']; @endphp
                        <td>
                            <div class="id-card {{ $frontBg ? 'front-bg' : '' }} {{ $front['show_photo'] ? '' : 'no-photo' }}"
                                 style="--info-size:{{ $front['font_size'] }}pt;--line-height:{{ $front['line_height'] }};--photo-w:{{ $front['photo_width'] }}mm;--photo-h:{{ $front['photo_height'] }}mm;--photo-left:{{ $front['photo_left'] }}mm;--photo-top:{{ $front['photo_top'] }}mm;--qr-size:{{ $front['qr_size'] }}mm;--sig-w:{{ $front['signature_width'] }}mm;--sig-h:{{ $front['signature_height'] }}mm;">
                                @if($front['show_photo'])
                                    <div class="photo-box">
                                        @if($card['photo'])
                                            <img src="{{ $card['photo'] }}" alt="Foto">
                                        @else
                                            <div class="photo-empty"></div>
                                        @endif
                                    </div>
                                @endif
                                @if($front['show_front_qr'] && $card['front_qr'])
                                    <div class="front-qr"><img src="{{ $card['front_qr'] }}" alt="QR NIS"></div>
                                @endif
                                <table class="info-table">
                                    @if($front['show_name'])
                                        <tr><td class="label">Nama</td><td class="colon">:</td><td>{{ $student->name }}</td></tr>
                                    @endif
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
                                    @if($front['show_ttl'])
                                        <tr><td class="label">TTL</td><td class="colon">:</td><td>{{ $card['ttl'] }}</td></tr>
                                    @endif
                                    @if($front['show_address'])
                                        <tr><td class="label">Alamat</td><td class="colon">:</td><td>{{ $card['address'] }}</td></tr>
                                    @endif
                                    @if($front['show_class'])
                                        <tr><td class="label">Kelas</td><td class="colon">:</td><td>{{ $card['class'] }}</td></tr>
                                    @endif
                                    @if($front['show_validity'])
                                        <tr><td class="label">Masa Berlaku</td><td class="colon">:</td><td>{{ $card['validity_text'] }}</td></tr>
                                    @endif
                                </table>
                                @if($front['show_signature'])
                                    <div class="signature">
                                        @if($card['signature'])
                                            <img src="{{ $card['signature'] }}" alt="TTD">
                                        @else
                                            <div class="sig-empty"></div>
                                        @endif
                                        @if($front['show_signature_name'])<div class="sig-name">{{ $card['signature_name'] }}</div>@endif
                                        @if($front['show_signature_title'])<div class="sig-title">{{ $card['signature_title'] }}</div>@endif
                                    </div>
                                @endif
                            </div>
                        </td>
                    @endforeach
                    @if($row->count() === 1)<td></td>@endif
                </tr>
            @endforeach
        </table>
    </div>

    <div class="sheet">
        <table class="card-grid">
            @foreach($batch->chunk(2) as $row)
                <tr>
                    @foreach($row as $card)
                        @php $back = $layout['back']; @endphp
                        <td>
                            <div class="id-card {{ $backBg ? 'back-bg' : '' }}"
                                 style="--back-size:{{ $back['font_size'] }}pt;--back-line-height:{{ $back['line_height'] }};--back-qr-left:{{ $back['back_qr_left'] }}mm;--back-qr-top:{{ $back['back_qr_top'] }}mm;--back-qr-size:{{ $back['back_qr_size'] }}mm;">
                                @if($back['show_note'])
                                    <div class="back-note">Kartu ini merupakan identitas resmi santri. Simpan dan gunakan dengan baik.</div>
                                @endif
                                @if($back['show_back_qr'] && $card['back_qr'])
                                    <div class="back-qr"><img src="{{ $card['back_qr'] }}" alt="QR Belakang"></div>
                                @endif
                            </div>
                        </td>
                    @endforeach
                    @if($row->count() === 1)<td></td>@endif
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
