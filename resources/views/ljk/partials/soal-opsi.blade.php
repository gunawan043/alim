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
            $lebarMm = isset($opsiWidthMm) && $opsiWidthMm > 0
                ? (float) $opsiWidthMm
                : 157.58;

            // Lebar satu sel = area opsi / jumlah kolom (dari service).
            // Semua sel memakai angka yang SAMA, jadi A/B/C/D dan baris
            // berikutnya (mode 2 kolom) selalu lurus sejajar.
            $cellMm = isset($opsiLayout['cellMm']) && $opsiLayout['cellMm'] > 0
                ? round((float) $opsiLayout['cellMm'], 2)
                : round($lebarMm / $kolom, 2);

            // DomPDF menaruh padding DI LUAR `width`, jadi padding kanan
            // ikut dikurangkan agar N sel tetap muat dalam lebar tabel.
            $jedaMm = max(0.0, (float) ($opsiIndentMm ?? 2));
            $selMm = round(max(1.0, $cellMm - $jedaMm), 2);

            // Lebar tabel = jumlah kolom x lebar sel.
            $totalMm = round($cellMm * $kolom, 2);
            if ($totalMm > $lebarMm) {
                $totalMm = round($lebarMm, 2);
            }

            $align = $rtl ? 'right' : 'left';
            $padStyle = $rtl ? "0 0 0 {$jedaMm}mm" : "0 {$jedaMm}mm 0 0";

            // Mode 1 kolom boleh turun baris; 4/2 kolom sudah dijamin muat.
            $whiteSpace = ! empty($opsiLayout['wrap']) ? 'normal' : 'nowrap';

            // Baris dipadatkan: sel kosong mengisi sisa kolom agar label
            // opsi berikutnya tetap pada garis yang sama.
            $sel = [];
            foreach ($opsiLayout['rows'] as $row) {
                foreach ($row as $cell) {
                    $sel[] = $cell['index'];
                }
            }
        @endphp
        {{-- SATU tabel untuk semua baris (bukan satu tabel per baris):
             dengan begitu sel pada baris ke-2 jatuh tepat di bawah sel
             baris ke-1 saat layout memakai 2 kolom. --}}
        <table class="opts opts-grid {{ $rtl ? 'rtl' : 'ltr' }}" style="width: {{ $totalMm }}mm;" cellspacing="0" cellpadding="0">
            @foreach(array_chunk($sel, $kolom) as $baris)
                @php
                    $sisa = max(0, $kolom - count($baris));
                @endphp
                <tr>
                    {{-- RTL: urutan sel sudah dibalik service (ا paling kanan),
                         sel kosong diletakkan di KIRI. --}}
                    @if($rtl && $sisa > 0)
                        @for($i = 0; $i < $sisa; $i++)
                            <td style="width: {{ $selMm }}mm; padding: {{ $padStyle }};">&nbsp;</td>
                        @endfor
                    @endif

                    @foreach($baris as $idx)
                        @php $opsi = $block['opsi'][$idx] ?? null; @endphp
                        <td style="width: {{ $selMm }}mm; padding: {{ $padStyle }}; text-align: {{ $align }}; white-space: {{ $whiteSpace }};">
                            @if($opsi)
                                @if($rtl)
                                    {{-- RTL: teks pilihan di kiri, marker ".ج" di KANAN. --}}
                                    {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                    <span class="letter letter-ar">{!! LjkArabicText::optionMarkerRtl($opsi["display_label"] ?? $opsi["label"]) !!}</span>
                                @else
                                    <span class="letter">{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                    {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                @endif
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach

                    @if(! $rtl && $sisa > 0)
                        @for($i = 0; $i < $sisa; $i++)
                            <td style="width: {{ $selMm }}mm; padding: {{ $padStyle }};">&nbsp;</td>
                        @endfor
                    @endif
                </tr>
            @endforeach
        </table>
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
                                {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                <span class="letter letter-ar">{!! LjkArabicText::optionMarkerRtl($opsi["display_label"] ?? $opsi["label"]) !!}</span>
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
