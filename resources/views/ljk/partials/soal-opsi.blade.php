@php
    use App\Services\Ljk\LjkArabicText;

    // Tata letak otomatis (naskah soal): kolom seragam & tiap pilihan 1 baris.
    // LJK/paket tidak mengirim `opsiLayout` sehingga memakai grid lama.
    $opsiLayout = $block['opsiLayout'] ?? null;
@endphp
@if(! empty($block['opsi']))
    @if($opsiLayout && ! empty($opsiLayout['rows']))
        <table class="opts opts-auto">
            @foreach($opsiLayout['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                        @php
                            $opsi = $block['opsi'][$cell['index']] ?? null;
                            $kolom = max(1, (int) $opsiLayout['columns']);
                        @endphp
                        <td style="width: {{ round(100 / $kolom, 4) }}%; text-align: {{ $rtl ? 'right' : 'left' }};">
                            @if($opsi)
                                @if($rtl)
                                    {{-- RTL: teks dulu, lalu penanda ".ج" di ujung kanan. --}}
                                    {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                    <span class="letter">.{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}</span>
                                @else
                                    <span class="letter">{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                    {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                @endif
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach

                    {{-- Sel kosong agar sisa kolom pada baris terakhir tetap
                         selebar kolom lain (lebar seragam, tidak melebar). --}}
                    @for($i = count($row); $i < (int) $opsiLayout['columns']; $i++)
                        <td style="width: {{ round(100 / max(1, (int) $opsiLayout['columns']), 4) }}%;">&nbsp;</td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @else
        <table class="opts">
            <tr>
                @for($i = 0; $i < $maxOptions; $i++)
                    @php
                        // RTL: opsi pertama (ا) di kolom paling kanan.
                        $idx = $rtl ? ($maxOptions - 1 - $i) : $i;
                        $opsi = $block['opsi'][$idx] ?? null;
                    @endphp
                    <td style="width: {{ $colWidth }}%; text-align: {{ $rtl ? 'right' : 'left' }};">
                        @if($opsi)
                            @if($rtl)
                                {{-- RTL: teks dulu, lalu penanda ".ج" di ujung kanan. --}}
                                {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                <span class="letter">.{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}</span>
                            @else
                                <span class="letter">{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                            @endif
                        @else
                            &nbsp;
                        @endif
                    </td>
                @endfor
            </tr>
        </table>
    @endif
@endif
