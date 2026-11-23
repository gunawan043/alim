@php
    use App\Services\Ljk\LjkArabicText;

    // Tata letak otomatis (naskah soal): semua kolom berlebar SAMA.
    // LJK/paket tidak mengirim `opsiLayout` sehingga memakai grid lama.
    $opsiLayout = $block['opsiLayout'] ?? null;
@endphp
@if(! empty($block['opsi']))
    @if($opsiLayout && ! empty($opsiLayout['rows']))
        @php
            $kolom = max(1, (int) $opsiLayout['columns']);
            // Lebar dalam MM, BUKAN persen: Dompdf menyesuaikan lebar kolom
            // dengan isinya bila memakai %, sehingga B/C bergeser antar soal.
            $lebarMm = isset($opsiWidthMm) && $opsiWidthMm > 0
                ? (float) $opsiWidthMm
                : 157.58;
            // Semua kolom SAMA PERSIS — tidak ada pilihan yang dilebarkan.
            $unitMm = round($lebarMm / $kolom, 2);
            $align = $rtl ? 'right' : 'left';
        @endphp
        {{-- Satu tabel per baris. Dompdf membagi lebar tabel dari proporsi
             `width` sel, sehingga `colspan` / lebar berbeda membuat kolom
             baris lain bergeser. Semua sel di sini berlebar identik dan
             tanpa colspan → kolom lurus sejajar dari atas ke bawah. --}}
        @foreach($opsiLayout['rows'] as $row)
            @php $sisa = max(0, $kolom - count($row)); @endphp
            <table class="opts opts-auto" style="width: {{ $lebarMm }}mm;">
                <tr>
                    @foreach($row as $cell)
                        @php $opsi = $block['opsi'][$cell['index']] ?? null; @endphp
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
                    @endforeach

                    {{-- Sel kosong agar jumlah kolom baris ini tetap N. --}}
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
