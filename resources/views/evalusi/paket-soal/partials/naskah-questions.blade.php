@foreach($rows as $row)
    @if(($row['type'] ?? 'question') === 'wacana')
        @php $wacana = $row['wacana'] ?? []; @endphp
        <div class="naskah-wacana">
            @if(! empty($wacana['judul']))
                <div class="fw-semibold">{{ $wacana['judul'] }}</div>
            @endif
            @php $wacanaText = $wacana['teks'] ?? ''; @endphp
            {!! str_contains($wacanaText, '<') ? $wacanaText : nl2br(e($wacanaText)) !!}
            @if(! empty($wacana['sumber']))
                <div class="small text-muted">Sumber: {{ $wacana['sumber'] }}</div>
            @endif
            @if(! empty($wacana['rentang_nomor']))
                <div class="small text-muted">Untuk soal nomor: {{ $wacana['rentang_nomor'] }}</div>
            @endif
        </div>
        @continue
    @endif

    @php
        $rtl = (bool) ($row['rtl'] ?? false);
        $maxOptions = max(4, count($row['opsi'] ?? []));
        $colWidth = round(100 / $maxOptions, 4);
        $block = ['opsi' => $row['opsi'] ?? []];
    @endphp

    <div class="naskah-question q">
        <table class="qwrap">
            <tr>
                @if($rtl)
                    <td class="qbody" style="text-align:right;">
                        <div>{!! nl2br(e($row['pertanyaan'] ?: '—')) !!}</div>
                        @if(! empty($row['gambar_path']))
                            <div class="mt-2">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($row['gambar_path']) }}" alt="Gambar soal" style="max-width:320px;max-height:240px;">
                                @if(! empty($row['gambar_keterangan']))
                                    <div class="small text-muted">{{ $row['gambar_keterangan'] }}</div>
                                @endif
                            </div>
                        @endif
                        @include('ljk.partials.soal-opsi', ['block' => $block, 'rtl' => true, 'maxOptions' => $maxOptions, 'colWidth' => $colWidth])
                    </td>
                    <td class="qno" style="text-align:right;">{{ $row['nomor'] }}.</td>
                @else
                    <td class="qno">{{ $row['nomor'] }}.</td>
                    <td class="qbody">
                        <div>{!! nl2br(e($row['pertanyaan'] ?: '—')) !!}</div>
                        @if(! empty($row['gambar_path']))
                            <div class="mt-2">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($row['gambar_path']) }}" alt="Gambar soal" style="max-width:320px;max-height:240px;">
                                @if(! empty($row['gambar_keterangan']))
                                    <div class="small text-muted">{{ $row['gambar_keterangan'] }}</div>
                                @endif
                            </div>
                        @endif
                        @include('ljk.partials.soal-opsi', ['block' => $block, 'rtl' => false, 'maxOptions' => $maxOptions, 'colWidth' => $colWidth])
                    </td>
                @endif
            </tr>
        </table>
    </div>
@endforeach
