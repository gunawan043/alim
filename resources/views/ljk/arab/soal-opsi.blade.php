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
    // Dompdf menambahkan padding DI LUAR `width`, jadi LF + IF harus dihitung
    // agar nol (N opsi tepat memenuhi lebar area opsi).
    $labelMm = 5.0;   // lebar kolom label (huruf + titik)
    $gapMm = 1.2;     // jarak tetap antara label dan isi
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
            // Model sebelumnya memakai grid: lebar kolom isi = teks TERPANJANG,
            // lalu dipaku untuk semua kolom. Akibatnya opsi pendek ("سُوقٌ")
            // duduk jauh di kiri labelnya sendiri dan antar grup renggang.
            //
            // Model sekarang: tiap grup adalah
            //     | isi opsi  relatif  | .label  |
            // dengan lebar HANYA sebesar isinya, dan label selalu menempel
            // tepat di kanan isi. Semua grup lalu dirapatkan ke tepi kanan
            // (`text-align: right` pada baris). Tidak ada kolom kosong yang
            // ditebak — tiap grup berakhir persis di labelnya.
            $meter = app(\App\Services\Evaluasi\SoalOpsiLayout::class);
            $fontOpsi = (float) ($fontSize ?? 11);

            // Lebar isi per opsi = lebar VISUAL teks setelah di-shape (bentuk
            // yang benar-benar tercetak), bukan teks sumber ber-harakat yang
            // jauh lebih panjang diukur.
            $isiPerOpsi = [];
            $terpanjangIsiMm = 0.0;
            foreach ($block['opsi'] as $idxOpsi => $o) {
                $teks = (string) ($o['teks'] ?? '');
                $w = 0.0;
                if ($rtl && $teks !== '') {
                    $visual = html_entity_decode(strip_tags(LjkArabicText::marksToHtml($teks)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $w = $meter->textWidthMm($visual, $fontOpsi);
                } else {
                    $w = $meter->textWidthMm($teks, $fontOpsi);
                }
                $isiPerOpsi[$idxOpsi] = $w;
                $terpanjangIsiMm = max($terpanjangIsiMm, $w);
            }

            // ── Lebar kolom isi mengikuti TEKS TERPANJANG, bukan dibagi rata ──
            //
            // Tabel membentang selebar area opsi dan dirapatkan ke KANAN
            // (`direction: rtl` + `text-align: right`), tetapi sel isi diberi
            // lebar TETAP agar label ا/ب/ج/د berdiri di posisi yang sama pada
            // setiap soal dan sejajar di bawah awal pertanyaan. Lebar sel isi
            // = teks terpanjang + ruang aman, jadi tidak ada kolom kosong
            // yang membuat jarak opsi renggang.
            $ruangAmanMm = 2.0;
            $isiMm = round($terpanjangIsiMm + $ruangAmanMm, 2);

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
