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

    <div class="naskah-question">
        <div class="naskah-question-row">
            <div class="naskah-number">{{ $row['nomor'] }}.</div>
            <div class="naskah-question-body">
                <div>{!! nl2br(e($row['pertanyaan'] ?: '—')) !!}</div>

                @if(! empty($row['gambar_path']))
                    <div class="mt-2">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($row['gambar_path']) }}" alt="Gambar soal" style="max-width:320px;max-height:240px;">
                        @if(! empty($row['gambar_keterangan']))
                            <div class="small text-muted">{{ $row['gambar_keterangan'] }}</div>
                        @endif
                    </div>
                @endif

                @if(! empty($row['opsi']))
                    @php $optionCount = max(4, count($row['opsi'])); @endphp
                    <table class="naskah-options">
                        <tr>
                            @for($index = 0; $index < $optionCount; $index++)
                                @php $opsi = $row['opsi'][$index] ?? null; @endphp
                                <td style="width: {{ 100 / $optionCount }}%;">
                                    @if($opsi)
                                        <span class="naskah-option-label">{{ $opsi['label'] ?? chr(65 + $index) }}.</span>
                                        {{ $opsi['teks'] ?? '' }}
                                    @else
                                        &nbsp;
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endforeach
