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
            background: #ffffff center/100% 100% no-repeat;
            page-break-inside: avoid;
        }
        .front-bg { background-image: url('{{ $frontBg }}'); }
        .back-bg { background-image: url('{{ $backBg }}'); }
        .card-el { position: absolute; overflow: hidden; line-height: 1.15; font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif; }
        .photo { width: 100%; height: 100%; object-fit: cover; border-radius: 1.2mm; background: #dbeafe; }
        .photo-empty { width: 100%; height: 100%; border: 0.2mm dashed #94a3b8; border-radius: 1.2mm; background: #eef2f7; }
        .signature-img { width: 100%; height: 100%; object-fit: contain; }
        .signature-empty { width: 100%; height: 100%; border-bottom: 0.25mm solid #94a3b8; }
    </style>
</head>
<body>
@foreach(collect($cards)->chunk(10) as $batch)
    <div class="sheet">
        <table class="card-grid">
            @foreach($batch->chunk(2) as $row)
                <tr>
                    @foreach($row as $card)
                        @php $student = $card['student']; @endphp
                        <td>
                            <div class="id-card {{ $frontBg ? 'front-bg' : '' }}">
                                @foreach($layout['front'] as $field => $cfg)
                                    @php $style = 'left:'.$cfg['x'].'mm;top:'.$cfg['y'].'mm;width:'.$cfg['w'].'mm;height:'.$cfg['h'].'mm;'; @endphp
                                    @if($field === 'photo')
                                        <div class="card-el" style="{{ $style }}">
                                            @if($card['photo'])
                                                <img class="photo" src="{{ $card['photo'] }}" alt="Foto">
                                            @else
                                                <div class="photo-empty"></div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="card-el" style="{{ $style }}font-size:{{ $cfg['size'] }}pt;text-align:{{ $cfg['align'] }};font-weight:{{ ! empty($cfg['bold']) ? '700' : '400' }};">
                                            @if($field === 'school'){{ $card['school'] }}
                                            @elseif($field === 'name'){{ $student->name }}
                                            @elseif($field === 'nis')NIS: {{ $student->nis ?: '-' }}
                                            @elseif($field === 'nisn')NISN: {{ $student->nisn ?: '-' }}
                                            @elseif($field === 'class')Kelas: {{ $card['class'] }}
                                            @elseif($field === 'validity'){{ $card['validity_text'] }}
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    @endforeach
                    @if($row->count() === 1)
                        <td></td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>

    <div class="sheet">
        <table class="card-grid">
            @foreach($batch->chunk(2) as $row)
                <tr>
                    @foreach($row as $card)
                        @php $student = $card['student']; @endphp
                        <td>
                            <div class="id-card {{ $backBg ? 'back-bg' : '' }}">
                                @foreach($layout['back'] as $field => $cfg)
                                    @php $style = 'left:'.$cfg['x'].'mm;top:'.$cfg['y'].'mm;width:'.$cfg['w'].'mm;height:'.$cfg['h'].'mm;'; @endphp
                                    @if($field === 'signature')
                                        <div class="card-el" style="{{ $style }}">
                                            @if($card['signature'])
                                                <img class="signature-img" src="{{ $card['signature'] }}" alt="TTD">
                                            @else
                                                <div class="signature-empty"></div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="card-el" style="{{ $style }}font-size:{{ $cfg['size'] }}pt;text-align:{{ $cfg['align'] }};font-weight:{{ ! empty($cfg['bold']) ? '700' : '400' }};">
                                            @if($field === 'note')Kartu ini merupakan identitas resmi santri. Simpan dan gunakan dengan baik.
                                            @elseif($field === 'signature_name'){{ $card['signature_name'] }}
                                            @elseif($field === 'signature_title'){{ $card['signature_title'] }}
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    @endforeach
                    @if($row->count() === 1)
                        <td></td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
