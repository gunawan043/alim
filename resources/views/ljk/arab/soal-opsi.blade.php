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

            // ── Grid kolom label + isi ──────────────────────────────────────
            //
            // Tiap opsi tetap memakai DUA sel: | isi opsi | .label |.
            // Kolom isi berlebar SAMA untuk semua opsi pada soal ini dan
            // kolom label berlebar sama pula, sehingga:
            //   - label ا/ب/ج/د berdiri di satu garis vertikal;
            //   - awal teks setiap opsi mulai pada x yang sama;
            //   - jarak label→isi konsisten.
            //
            // Lebar satu sel datang dari service (`cellMm` = area opsi /
            // jumlah kolom). Jumlah kolomnya sudah dihitung otomatis dari
            // panjang opsi soal ini, jadi opsi pendek tidak menyisakan ruang
            // kosong dan opsi panjang tidak pernah terpotong.
            $fontOpsi = (float) ($fontSize ?? 11);
            $ptMm = 25.4 / 72;                              // 1 pt dalam mm
            // Label memakai lebar HURUF saja (≈0,3 em): titik diletakkan di
            // sebelah kiri huruf oleh bidi, tidak menambah lebar. Jarak ke
            // isi tipis. Keduanya relatif terhadap font, jadi ikut berubah
            // saat ukuran font diganti.
            $labelMm = round($fontOpsi * $ptMm * 0.3, 2);   // huruf label
            $gapMm = round($fontOpsi * $ptMm * 0.25, 2);    // label → isi
            $padMm = max(0.0, (float) ($opsiIndentMm ?? 2));

            $cellMm = isset($opsiLayout['cellMm']) && $opsiLayout['cellMm'] > 0
                ? round((float) $opsiLayout['cellMm'], 2)
                : round($lebarMm / $kolom, 2);

            // Isi = sisa sel setelah label + jarak + padding kanan. Padding
            // ikut dikurangkan karena DomPDF menaruhnya DI LUAR `width`.
            $isiMm = round(max(1.0, $cellMm - $labelMm - $gapMm - $padMm), 2);

            // Lebar tabel = jumlah kolom × lebar sel, sehingga tabel tepat
            // sebesar isinya dan menempel ke tepi KANAN (sisa ruang di kiri).
            $totalMm = round($cellMm * $kolom, 2);
            if ($totalMm > $lebarMm) {
                $totalMm = round($lebarMm, 2);
            }

            $align = $rtl ? 'right' : 'left';

            // Mode 1 kolom: teks boleh turun baris di selnya sendiri agar
            // tidak terpotong. Mode 4/2 kolom sudah dijamin muat.
            $whiteSpace = ! empty($opsiLayout['wrap']) ? 'normal' : 'nowrap';

            // Padding kanan sel isi: jarak ke label opsi berikutnya. Dipasang
            // di sel isi (bukan sel label) supaya `cellMm` tetap utuh.
            $padIsi = $rtl ? "0 0 0 {$padMm}mm" : "0 {$padMm}mm 0 0";
            $padLabel = $rtl ? "0 0 0 {$gapMm}mm" : "0 {$gapMm}mm 0 0";
        @endphp
        {{-- RTL: label di KANAN isi, seluruh baris dirapatkan ke tepi kanan.
             LTR: label di KIRI isi, baris dirapatkan ke kiri.
             Tidak ada spasi manual — posisi diatur lebar sel (mm). --}}
        <table class="opts opts-grid {{ $rtl ? 'rtl' : 'ltr' }}" style="width: {{ $totalMm }}mm;" cellspacing="0" cellpadding="0">
            <tr>
                @foreach($opsiLayout['rows'] as $row)
                    @foreach($row as $cell)
                        @php
                            $opsi = $block['opsi'][$cell['index']] ?? null;
                            $label = $opsi ? ($opsi['display_label'] ?? $opsi['label']) : '';
                        @endphp
                        @if($rtl)
                            <td class="col-isi" style="width: {{ $isiMm }}mm; text-align: right; white-space: {{ $whiteSpace }}; padding: {{ $padIsi }};">
                                @if($opsi){!! LjkArabicText::marksToHtml($opsi['teks']) !!}@else&nbsp;@endif
                            </td>
                            <td class="col-label" style="width: {{ $labelMm }}mm; text-align: right; padding: {{ $padLabel }};">
                                @if($opsi)<span class="letter letter-ar">{!! LjkArabicText::optionMarkerRtl($label) !!}</span>@else&nbsp;@endif
                            </td>
                        @else
                            <td class="col-label" style="width: {{ $labelMm }}mm; text-align: left; padding: {{ $padLabel }};">
                                @if($opsi)<span class="letter">{!! LjkArabicText::marksToHtml($label) !!}.</span>@else&nbsp;@endif
                            </td>
                            <td class="col-isi" style="width: {{ $isiMm }}mm; text-align: left; white-space: {{ $whiteSpace }}; padding: {{ $padIsi }};">
                                @if($opsi){!! LjkArabicText::marksToHtml($opsi['teks']) !!}@else&nbsp;@endif
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
