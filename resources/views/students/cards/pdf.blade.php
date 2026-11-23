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
        .card-content { position: absolute; left: 4mm; right: 4mm; top: 3mm; bottom: 3mm; }
        .card-school { text-align: center; font-size: var(--title-size); font-weight: 700; line-height: 1.15; margin-bottom: 2mm; }
        .card-body-layout { width: 100%; }
        .photo-cell { width: var(--photo-w); vertical-align: top; }
        .card-photo, .photo-empty {
            width: var(--photo-w);
            height: var(--photo-h);
            border-radius: 1mm;
            object-fit: cover;
            display: block;
            background: #dbeafe;
            border: 0.2mm solid #cbd5e1;
        }
        .info-cell { vertical-align: top; padding-left: 2.5mm; }
        .card-info-table { width: 100%; border-collapse: collapse; font-size: var(--info-size); line-height: var(--line-height); }
        .card-info-table td { padding: 0; vertical-align: top; }
        .card-info-table .label { width: 22mm; white-space: nowrap; }
        .card-info-table .colon { width: 3mm; text-align: center; }
        .card-back-content { position: absolute; left: 5mm; right: 5mm; top: 4mm; bottom: 4mm; font-size: var(--back-size); line-height: var(--back-line-height); }
        .card-note { max-width: 68mm; }
        .card-signature { position: absolute; right: 0; bottom: 0; width: 40mm; text-align: center; }
        .card-signature img { width: var(--sig-w); height: var(--sig-h); object-fit: contain; display: block; margin: 0 auto; }
        .card-signature .sig-empty { width: var(--sig-w); height: var(--sig-h); border-bottom: 0.25mm solid #94a3b8; margin: 0 auto; }
        .card-signature .sig-name { font-size: var(--back-size); font-weight: 700; margin-top: 1mm; }
        .card-signature .sig-title { font-size: calc(var(--back-size) - 1pt); }
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
                            <div class="id-card {{ $frontBg ? 'front-bg' : '' }}"
                                 style="--title-size:{{ $front['title_size'] }}pt;--info-size:{{ $front['font_size'] }}pt;--line-height:{{ $front['line_height'] }};--photo-w:{{ $front['photo_width'] }}mm;--photo-h:{{ $front['photo_height'] }}mm;">
                                <div class="card-content">
                                    @if($front['show_school'])
                                        <div class="card-school">{{ $card['school'] }}</div>
                                    @endif
                                    <table class="card-body-layout"><tr>
                                        @if($front['show_photo'])
                                            <td class="photo-cell">
                                                @if($card['photo'])
                                                    <img class="card-photo" src="{{ $card['photo'] }}" alt="Foto">
                                                @else
                                                    <div class="photo-empty"></div>
                                                @endif
                                            </td>
                                        @endif
                                        <td class="info-cell">
                                            <table class="card-info-table">
                                                @if($front['show_name'])
                                                    <tr><td class="label">Nama</td><td class="colon">:</td><td>{{ $student->name }}</td></tr>
                                                @endif
                                                @if($front['show_nis'])
                                                    <tr><td class="label">NIS</td><td class="colon">:</td><td>{{ $student->nis ?: '-' }}</td></tr>
                                                @endif
                                                @if($front['show_nisn'])
                                                    <tr><td class="label">NISN</td><td class="colon">:</td><td>{{ $student->nisn ?: '-' }}</td></tr>
                                                @endif
                                                @if($front['show_class'])
                                                    <tr><td class="label">Kelas</td><td class="colon">:</td><td>{{ $card['class'] }}</td></tr>
                                                @endif
                                                @if($front['show_validity'])
                                                    <tr><td class="label">Masa Berlaku</td><td class="colon">:</td><td>{{ $card['validity_text'] }}</td></tr>
                                                @endif
                                            </table>
                                        </td>
                                    </tr></table>
                                </div>
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
                                 style="--back-size:{{ $back['font_size'] }}pt;--back-line-height:{{ $back['line_height'] }};--sig-w:{{ $back['signature_width'] }}mm;--sig-h:{{ $back['signature_height'] }}mm;">
                                <div class="card-back-content">
                                    @if($back['show_note'])
                                        <div class="card-note">Kartu ini merupakan identitas resmi santri. Simpan dan gunakan dengan baik.</div>
                                    @endif
                                    @if($back['show_signature'])
                                        <div class="card-signature">
                                            @if($card['signature'])
                                                <img src="{{ $card['signature'] }}" alt="TTD">
                                            @else
                                                <div class="sig-empty"></div>
                                            @endif
                                            @if($back['show_signature_name'])
                                                <div class="sig-name">{{ $card['signature_name'] }}</div>
                                            @endif
                                            @if($back['show_signature_title'])
                                                <div class="sig-title">{{ $card['signature_title'] }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
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
