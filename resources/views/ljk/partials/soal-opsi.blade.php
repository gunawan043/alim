@php
    use App\Services\Ljk\LjkArabicText;

    // Tata letak otomatis (naskah soal): kolom seragam & tiap pilihan 1 baris.
    // LJK/paket tidak mengirim `opsiLayout` sehingga memakai grid lama.
    $opsiLayout = $block['opsiLayout'] ?? null;
@endphp
@if(! empty($block['opsi']))
    @if($opsiLayout && ! empty($opsiLayout['rows']))
        {{-- Satu tabel PER BARIS tanpa colspan: Dompdf kurang tepat
             menghitung lebar bila colspan dipakai, sehingga lebar sel
             diberikan langsung dalam persen dari total jatah baris. --}}
        @foreach($opsiLayout['rows'] as $row)
            @php
                $totalSpan = array_sum(array_column($row, 'span')) ?: 1;
                // Sel kosong penutup agar total baris selalu 100%.
                $sisa = max(0, (int) $opsiLayout['columns'] - $totalSpan);
            @endphp
            <table class="opts opts-auto">
                <tr>
                    @foreach($row as $cell)
                        @php $opsi = $block['opsi'][$cell['index']] ?? null; @endphp
                        <td style="width: {{ round(100 * (int) $cell['span'] / ($totalSpan + $sisa), 4) }}%; text-align: {{ $rtl ? 'right' : 'left' }};">
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

                    @if($sisa > 0)
                        <td style="width: {{ round(100 * $sisa / ($totalSpan + $sisa), 4) }}%;">&nbsp;</td>
                    @endif
                </tr>
            </table>
        @endforeach
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
