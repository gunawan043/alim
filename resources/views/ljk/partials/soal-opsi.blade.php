@php
    use App\Services\Ljk\LjkArabicText;

    // Tata letak otomatis (naskah soal): kolom seragam & tiap pilihan 1 baris.
    // LJK/paket tidak mengirim `opsiLayout` sehingga memakai grid lama.
    $opsiLayout = $block['opsiLayout'] ?? null;
@endphp
@if(! empty($block['opsi']))
    @if($opsiLayout && ! empty($opsiLayout['rows']))
        {{-- SATU TABEL PER BARIS, tiap baris SELALU berisi tepat N kolom
             berlebar sama, tanpa colspan.

             Dompdf membagi lebar tabel dari proporsi `width` sel, bukan
             dari grid posisi. Akibatnya:
               • `colspan` membuat kolom baris berikutnya bergeser
                 (C mulai 113,2pt padahal seharusnya 111,7pt);
               • `<colgroup>` dan `table-layout: fixed` tidak dihormati.
             Satu-satunya bentuk yang menghasilkan kolom lurus sejajar
             adalah tabel terpisah per baris dengan N <td> berlebar SAMA
             PERSIS; jatah yang tak terpakai diisi sel kosong. --}}
        @php
            $kolom = max(1, (int) $opsiLayout['columns']);
            // Lebar dalam MM, BUKAN persen: Dompdf menyesuaikan lebar kolom
            // dengan isinya bila memakai %, sehingga B/C bergeser antar soal.
            $lebarMm = isset($opsiWidthMm) && $opsiWidthMm > 0
                ? (float) $opsiWidthMm
                : 157.58;
            $unitMm = round($lebarMm / $kolom, 2);
            $align = $rtl ? 'right' : 'left';
        @endphp
        @foreach($opsiLayout['rows'] as $row)
            @php $sisa = max(0, $kolom - array_sum(array_column($row, 'span'))); @endphp
            <table class="opts opts-auto" style="width: {{ $lebarMm }}mm;">
                <tr>
                    @foreach($row as $cell)
                        @php
                            $opsi = $block['opsi'][$cell['index']] ?? null;
                            $span = (int) $cell['span'];
                        @endphp
                        <td style="width: {{ $unitMm }}mm; text-align: {{ $align }};">
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

                        {{-- Opsi panjang merebut jatah kolom berikutnya: isi sel
                             sisa dengan kosong agar jumlah kolom tetap N. --}}
                        @for($i = 1; $i < $span; $i++)
                            <td style="width: {{ $unitMm }}mm;">&nbsp;</td>
                        @endfor
                    @endforeach

                    @for($i = 0; $i < $sisa; $i++)
                        <td style="width: {{ $unitMm }}mm;">&nbsp;</td>
                    @endfor
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
