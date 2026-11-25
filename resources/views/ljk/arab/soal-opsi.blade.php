@php
    use App\Services\Ljk\LjkArabicText;

    // Tata letak otomatis (naskah soal): semua kolom berlebar SAMA.
    // LJK/paket tidak mengirim `opsiLayout` sehingga memakai grid lama.
    $opsiLayout = $block['opsiLayout'] ?? null;

    // ── Ukuran kolom ──────────────────────────────────────────────────────
    // Setiap opsi memakai DUA kolom terpisah di dalam tabel:
    //
    //   | label | isi opsi | label | isi opsi | …
    //
    // Kolom label berlebar TETAP (LF) dan kolom isi berlebar TETAP (IF).
    // Karena lebar ditentukan dari angka (mm), bukan dari spasi, maka:
    //   - semua label berdiri pada satu garis vertikal;
    //   - awal teks setiap opsi mulai pada x yang sama;
    //   - jarak label→isi konsisten berapa pun panjang teksnya.
    //
    // Semua ukuran dipakai lewat variabel — lihat blok di bawah — dan
    // DITURUNKAN DARI UKURAN FONT, sehingga berubah otomatis bila guru
    // mengganti font_size. Lebar kosong dalam mm tidak dipakai untuk
    // menebak posisi; nilainya datang dari service (satu nilai untuk
    // seluruh naskah) sehingga label ا/ب/ج/د segaris di semua soal.
@endphp
@if(! empty($block['opsi']))
    @if($opsiLayout && ! empty($opsiLayout['rows']))
        @php
            $kolom = max(1, (int) $opsiLayout['columns']);
            $lebarMm = isset($opsiWidthMm) && $opsiWidthMm > 0
                ? (float) $opsiWidthMm
                : 157.58;

            // ── Tiap opsi = satu sel, label menempel langsung ─────────────
            //
            // Bentuk cetak: "طَوِيلٌ .ا". Sel opsi TIDAK diberi lebar mm,
            // karena `table-layout: auto` akan memberi sel sebesar teksnya
            // sendiri — celah antara kata pendek dan labelnya jadi nol.
            // Memberi lebar tetap membuat teks pendek berakhir jauh di kiri
            // labelnya (keluhan "jarak antar opsi masih jauh").
            //
            // Lebar yang dipakai sama untuk semua soal adalah LEBAR LABEL
            // (dari ukuran font), bukan lebar teks opsi — jadi posisi label
            // ا/ب/ج/د tetap segaris dari soal pertama hingga terakhir.
            $fontOpsi = (float) ($fontSize ?? 11);
            $ptMm = 25.4 / 72;                              // 1 pt dalam mm
            $labelMm = round($fontOpsi * $ptMm * 1.7, 2);   // huruf + titik
            $gapMm = round($fontOpsi * $ptMm * 0.35, 2);    // kata → label
            $antarMm = round($fontOpsi * $ptMm * 0.6, 2);   // label → opsi berikut

            $align = $rtl ? 'right' : 'left';
            $whiteSpace = ! empty($opsiLayout['wrap']) ? 'normal' : 'nowrap';
        @endphp
        {{-- RTL: teks lalu label (label di kanan teks), dirapatkan ke kanan.
             LTR: label lalu teks, dirapatkan ke kiri. --}}
        <table class="opts opts-grid {{ $rtl ? 'rtl-exam' : 'ltr' }}" cellspacing="0" cellpadding="0">
            <tr>
                @foreach($opsiLayout['rows'] as $row)
                    @foreach($row as $cell)
                        @php
                            $opsi = $block['opsi'][$cell['index']] ?? null;
                            $label = $opsi ? ($opsi['display_label'] ?? $opsi['label']) : '';
                            $teks = $opsi ? LjkArabicText::marksToHtml($opsi['teks']) : '&nbsp;';
                            $marker = $opsi ? LjkArabicText::optionMarkerRtl($label) : '&nbsp;';
                        @endphp
                        @if($rtl)
                            <td class="col-opsi" style="text-align: right; white-space: {{ $whiteSpace }}; padding: 0 0 0 {{ $antarMm }}mm;">
                                {!! $teks !!}<span class="letter letter-ar" style="margin-right: {{ $gapMm }}mm;">{!! $marker !!}</span>
                            </td>
                        @else
                            <td class="col-opsi" style="text-align: left; white-space: {{ $whiteSpace }}; padding: 0 {{ $antarMm }}mm 0 0;">
                                <span class="letter" style="margin-right: {{ $gapMm }}mm;">{!! LjkArabicText::marksToHtml($label) !!}.</span>{!! $teks !!}
                            </td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
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
                                {!! LjkArabicText::marksToHtml($opsi['teks']) !!}
                                <span class="letter letter-ar">{!! LjkArabicText::optionMarkerRtl($opsi['display_label'] ?? $opsi['label']) !!}</span>
                            @else
                                <span class="letter">{!! LjkArabicText::marksToHtml($opsi['display_label'] ?? $opsi['label']) !!}.</span>
                                {!! LjkArabicText::marksToHtml($opsi['teks']) !!}
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
