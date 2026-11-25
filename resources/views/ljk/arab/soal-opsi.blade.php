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

            // ── Satu grup opsi = satu pasangan sel, dirapatkan ke KANAN ──
            //
            // Tiap grup adalah | isi opsi | .label | — label selalu menempel
            // ke kanan isi, dan seluruh baris dirapatkan ke tepi kanan area
            // opsi. Lebar sel isi TIDAK dihitung per soal: nilainya datang
            // dari service (`$opsiCellMm`) yang menurunkannya dari ukuran
            // font, sehingga setiap soal memakai lebar identik dan label
            // ا/ب/ج/د tidak bergeser antar soal.
            $fontOpsi = (float) ($fontSize ?? 11);

            // Satuan relatif: semua ukuran mengikuti ukuran font soal.
            // `table.opts td` memakai mm/em yang tetap untuk lebar, jadi
            // nilainya dihitung di sini (1 em ≈ 0,35 mm per pt pada 96 dpi).
            $ptMm = 25.4 / 72;                       // 1 pt dalam mm
            $labelMm = round($fontOpsi * $ptMm * 1.6, 2);  // kolom huruf + titik
            $gapMm = round($fontOpsi * $ptMm * 0.35, 2);   // jarak label→isi

            if (isset($opsiCellMm) && $opsiCellMm > 0) {
                $isiMm = round((float) $opsiCellMm, 2);
            } else {
                // LJK/paket: tidak ada lebar bersama, pakai rumus yang sama
                // dari ukuran font agar hasilnya tetap seragam per dokumen.
                $isiMm = round(min($fontOpsi * 1.22, $lebarMm / 4), 2);
            }

            // Kolom per baris: muat bila seluruh grup selebar $isiMm + label
            // masih masuk area opsi.
            $unitPerGrupMm = $isiMm + $labelMm + $gapMm;
            $muatPerBaris = max(1, (int) floor($lebarMm / max(1.0, $unitPerGrupMm)));
            $kolomBaris = max(1, min($kolom, $muatPerBaris));

            $align = $rtl ? 'right' : 'left';

            $whiteSpace = ! empty($opsiLayout['wrap']) ? 'normal' : 'nowrap';
        @endphp
        {{-- RTL: label di KANAN isi, seluruh baris dirapatkan ke tepi kanan.
             LTR: label di KIRI isi, baris dirapatkan ke kiri.
             Tidak ada spasi manual — posisi diatur lebar sel (mm). --}}
        <table class="opts opts-grid {{ $rtl ? 'rtl' : 'ltr' }}" style="width: {{ round($lebarMm, 2) }}mm;" cellspacing="0" cellpadding="0">
            <tr>
                @foreach($opsiLayout['rows'] as $row)
                    @foreach($row as $cell)
                        @php
                            $opsi = $block['opsi'][$cell['index']] ?? null;
                            $label = $opsi ? ($opsi['display_label'] ?? $opsi['label']) : '';
                        @endphp
                        @if($rtl)
                            <td class="col-isi" style="width: {{ $isiMm }}mm; text-align: right; white-space: {{ $whiteSpace }}; padding: 0;">
                                @if($opsi){!! LjkArabicText::marksToHtml($opsi['teks']) !!}@else&nbsp;@endif
                            </td>
                            <td class="col-label" style="width: {{ $labelMm }}mm; text-align: right; padding: 0 0 0 {{ $gapMm }}mm;">
                                @if($opsi)<span class="letter letter-ar">{!! LjkArabicText::optionMarkerRtl($label) !!}</span>@else&nbsp;@endif
                            </td>
                        @else
                            <td class="col-label" style="width: {{ $labelMm }}mm; text-align: left; padding: 0 {{ $gapMm }}mm 0 0;">
                                @if($opsi)<span class="letter">{!! LjkArabicText::marksToHtml($label) !!}.</span>@else&nbsp;@endif
                            </td>
                            <td class="col-isi" style="width: {{ $isiMm }}mm; text-align: left; white-space: {{ $whiteSpace }}; padding: 0;">
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
