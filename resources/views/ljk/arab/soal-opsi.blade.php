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

            // Lebar tiap kolom dihitung sekali dari area opsi yang dipakai
            // SEMUA soal, bukan dari lebar tabel baris ini. Karena semua baris
            // memakai nilai yang sama, penanda A/B/C/D berada di satu garis
            // lurus dari soal nomor 1 sampai terakhir.
            $unitMm = round($lebarMm / $kolom, 2);

            // Jarak antar kolom = padding + lebar sel, sehingga N kolom
            // tepat memenuhi `$lebarMm` (tanpa meluber / tumpang tindih).
            $selMm = round(max(1.0, $unitMm - $jedaMm), 2);

            $align = $rtl ? 'right' : 'left';
            $padStyle = $rtl
                ? "0 0 0 {$jedaMm}mm"
                : "0 {$jedaMm}mm 0 0";

            // Grid tetap melipat teks yang lebih panjang dari kolomnya agar
            // tidak menimpa kolom sebelah. Opsi pendek tetap satu baris.
            $whiteSpace = ! empty($opsiLayout['wrap']) ? 'normal' : 'nowrap';
        @endphp
        {{-- Satu tabel per baris, SELALU N kolom. Dompdf membagi lebar tabel
             dari proporsi `width` sel, sehingga lebar berbeda / colspan
             membuat kolom baris lain bergeser. Semua sel di sini berlebar
             identik dan tanpa colspan → kolom lurus sejajar. --}}
        @foreach($opsiLayout['rows'] as $row)
            @php
                $sisa = max(0, $kolom - count($row));
                // RTL: service sudah membalik urutan sel (ا paling kanan).
                // Sisa sel kosong ditempatkan di KIRI agar ا tetap di tepi
                // kanan walau soal hanya punya 2–3 pilihan.
                $selIsi = $row;
            @endphp
            <table class="opts opts-auto {{ $rtl ? 'rtl' : '' }}" style="width: {{ $lebarMm }}mm;">
                <tr>
                    @if($rtl && $sisa > 0)
                        @for($i = 0; $i < $sisa; $i++)
                            <td style="width: {{ $selMm }}mm; padding: {{ $padStyle }};">&nbsp;</td>
                        @endfor
                    @endif

                    @foreach($selIsi as $cell)
                        @php $opsi = $block['opsi'][$cell['index']] ?? null; @endphp
                        <td style="width: {{ $selMm }}mm; padding: {{ $padStyle }}; text-align: {{ $align }}; white-space: {{ $whiteSpace }};">
                            @if($opsi)
                                @if($rtl)
                                    {{-- RTL: teks pilihan di kiri, marker ".ج" di
                                         KANAN. Marker memakai lebar TETAP (6 mm)
                                         agar ا/ب/ج/د sama lebar dan selalu berada
                                         di tepi kanan sel. --}}
                                    {!! LjkArabicText::toHtmlRtl($opsi['teks'], false) !!}
                                    <span class="letter letter-ar" style="display: inline-block; width: 6mm; margin-left: 1mm; text-align: right; white-space: nowrap;">.{!! LjkArabicText::toHtmlRtl($opsi['display_label'] ?? $opsi['label'], false) !!}</span>
                                @else
                                    <span class="letter">{!! LjkArabicText::toHtmlRtl($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                    {!! LjkArabicText::toHtmlRtl($opsi['teks'], false) !!}
                                @endif
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach

                    {{-- LTR: sel kosong di kanan. Lebar & padding SAMA dengan
                         sel berisi supaya posisi kolom tidak bergeser. --}}
                    @if(! $rtl && $sisa > 0)
                        @for($i = 0; $i < $sisa; $i++)
                            <td style="width: {{ $selMm }}mm; padding: {{ $padStyle }};">&nbsp;</td>
                        @endfor
                    @endif
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
                                {{-- RTL: teks di kiri, marker ".ج" di kanan.
                                     Sama dengan cabang opts-auto agar naskah & LJK
                                     identik. --}}
                                {!! LjkArabicText::toHtmlRtl($opsi['teks'], false) !!}
                                <span class="letter letter-ar" style="display: inline-block; width: 6mm; margin-left: 1mm; text-align: right; white-space: nowrap;">.{!! LjkArabicText::toHtmlRtl($opsi['display_label'] ?? $opsi['label'], false) !!}</span>
                            @else
                                <span class="letter">{!! LjkArabicText::toHtmlRtl($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                {!! LjkArabicText::toHtmlRtl($opsi['teks'], false) !!}
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
