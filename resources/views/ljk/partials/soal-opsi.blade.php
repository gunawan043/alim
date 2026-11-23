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

            // Dompdf menambahkan padding DI LUAR `width` sel, jadi lebar
            // sel harus dikurangi padding agar N sel tetap muat dalam
            // `$lebarMm`. Tanpa ini total lebar = N x (unit + padding) dan
            // kolom meluber hingga teks A menimpa B/C/D.
            $jedaMm = max(0.0, (float) ($opsiIndentMm ?? 2.0));
            $unitMm = round($lebarMm / $kolom, 2);
            $selMm = round(max(1.0, $unitMm - $jedaMm), 2);

            // Jarak antar kolom = padding + lebar sel, sehingga N kolom
            // tepat memenuhi `$lebarMm` (tanpa meluber / tumpang tindih).
            $padStyle = $rtl
                ? "0 0 0 {$jedaMm}mm"
                : "0 {$jedaMm}mm 0 0";

            $align = $rtl ? 'right' : 'left';
        @endphp
        {{-- Satu tabel per baris. Dompdf membagi lebar tabel dari proporsi
             `width` sel, sehingga `colspan` / lebar berbeda membuat kolom
             baris lain bergeser. Semua sel di sini berlebar identik dan
             tanpa colspan → kolom lurus sejajar dari atas ke bawah. --}}
        @foreach($opsiLayout['rows'] as $row)
            @php $sisa = max(0, $kolom - count($row)); @endphp
            <table class="opts opts-auto {{ $rtl ? 'rtl' : '' }}" style="width: {{ $lebarMm }}mm;">
                <tr>
                    @foreach($row as $cell)
                        @php $opsi = $block['opsi'][$cell['index']] ?? null; @endphp
                        <td style="width: {{ $selMm }}mm; padding: {{ $padStyle }}; text-align: {{ $align }};">
                            @if($opsi)
                                @if($rtl)
                                    {{-- RTL: penanda huruf memakai lebar TETAP agar
                                         tepi kanannya sejajar walau bentuk huruf
                                         ا/ب/ج/د berbeda lebar setelah di-shape
                                         (ﺍ lebih sempit dari ﺏ). Teks menyusul di
                                         kiri penanda. --}}
                                    <span class="letter letter-ar">{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                    {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                @else
                                    <span class="letter">{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                    {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                @endif
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach

                    {{-- Sel kosong agar jumlah kolom baris ini tetap N.
                         Lebar & padding SAMA dengan sel berisi supaya
                         posisi kolom tidak bergeser antar baris. --}}
                    @for($i = 0; $i < $sisa; $i++)
                        <td style="width: {{ $selMm }}mm; padding: {{ $padStyle }};">&nbsp;</td>
                    @endfor
                </tr>
            </table>
        @endforeach
    @else
        <table class="opts {{ $rtl ? 'rtl' : '' }}">
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
                                {{-- RTL: penanda huruf lebih dulu (berlebar tetap agar
                                     sejajar), teks menyusul di kirinya. Sama dengan
                                     cabang opts-auto supaya naskah & LJK identik. --}}
                                <span class="letter letter-ar">{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
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
