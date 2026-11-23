@php
    use App\Services\Ljk\LjkArabicText;

    // Tata letak otomatis (naskah soal): kolom seragam & tiap pilihan 1 baris.
    // LJK/paket tidak mengirim `opsiLayout` sehingga memakai grid lama.
    $opsiLayout = $block['opsiLayout'] ?? null;
@endphp
@if(! empty($block['opsi']))
    @if($opsiLayout && ! empty($opsiLayout['rows']))
        {{-- SATU tabel dengan <colgroup> berlebar TETAP 1/N.
             Dompdf mengabaikan `table-layout: fixed` pada <td>, tetapi
             MENGHORMATI <col style="width"> — sehingga lebar setiap kolom
             selalu identik (mis. 4 opsi → tiap kolom tepat 25%, tidak ada
             yang 100px lalu sisanya 150px).
             Opsi yang lebih panjang memakai colspan = kelipatan kolom itu. --}}
        @php
            $kolom = max(1, (int) $opsiLayout['columns']);
            // Lebar dalam MM, BUKAN persen: Dompdf menyesuaikan lebar kolom
            // dengan isinya bila memakai %, sehingga B/C bergeser antar soal.
            // Total lebar area opsi (mm) dikirim dari SoalNaskahPdfService.
            $lebarMm = isset($opsiWidthMm) && $opsiWidthMm > 0
                ? (float) $opsiWidthMm
                : 157.58;
            $unitMm = round($lebarMm / $kolom, 2);
        @endphp
        <table class="opts opts-auto" style="width: {{ $lebarMm }}mm;">
            @foreach($opsiLayout['rows'] as $row)
                @php $sisa = max(0, $kolom - array_sum(array_column($row, 'span'))); @endphp
                <tr>
                    @foreach($row as $cell)
                        @php
                            $opsi = $block['opsi'][$cell['index']] ?? null;
                            $span = (int) $cell['span'];
                            $wMm = round($unitMm * $span, 2);
                        @endphp
                        <td @if($span > 1) colspan="{{ $span }}" @endif
                            style="width: {{ $wMm }}mm; text-align: {{ $rtl ? 'right' : 'left' }};">
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
                        <td colspan="{{ $sisa }}" style="width: {{ round($unitMm * $sisa, 2) }}mm;">&nbsp;</td>
                    @endif
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
