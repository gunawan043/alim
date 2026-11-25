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
            // Lebar dalam MM, BUKAN persen: Dompdf menyesuaikan lebar kolom
            // dengan isinya bila memakai %, sehingga B/C bergeser antar soal.
            $lebarMm = isset($opsiWidthMm) && $opsiWidthMm > 0
                ? (float) $opsiWidthMm
                : 157.58;

            // ── Lebar kolom isi mengikuti TEKS TERPANJANG, bukan dibagi rata ──
            //
            // Membagi rata area opsi (155 / 4 = 38.75 mm per unit) membuat
            // kolom isi 31.15 mm walau teksnya hanya "بَيْتٌ" (≈ 10 mm).
            // Akibatnya teks pendek terpisah jauh dari labelnya dan jarak
            // antar opsi terlihat renggang. Di sini lebar isi dihitung dari
            // teks terpanjang + ruang aman, lalu DIPAKU untuk semua kolom
            // supaya grid tetap lurus.
            $meter = app(\App\Services\Evaluasi\SoalOpsiLayout::class);
            $fontOpsi = (float) ($fontSize ?? 11);

            // Diukur DUA kali: bentuk logis (muat? tepat?) dan bentuk VISUAL
            // (seberapa lebar teks benar-benar tercetak?). Naskah Arab di-shape
            // lebih dulu sehingga bentuk visual memakai presentation form dan
            // jauh lebih pendek dari teks sumber ber-harakat.
            $terpanjangLogisMm = 0.0;
            $terpanjangVisualMm = 0.0;
            foreach ($block['opsi'] as $o) {
                $teks = (string) ($o['teks'] ?? '');
                $terpanjangLogisMm = max($terpanjangLogisMm, $meter->textWidthMm($teks, $fontOpsi));

                if ($rtl && $teks !== '') {
                    $visual = html_entity_decode(strip_tags(LjkArabicText::marksToHtml($teks)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $terpanjangVisualMm = max($terpanjangVisualMm, $meter->textWidthMm($visual, $fontOpsi));
                }
            }
            if ($terpanjangVisualMm <= 0.0) {
                $terpanjangVisualMm = $terpanjangLogisMm;
            }

            // Ruang aman: teks tidak boleh menyentuh label opsi berikutnya.
            $ruangAmanMm = 1.8;
            $butuhMm = $labelMm + $gapMm + $terpanjangLogisMm + $ruangAmanMm;

            // Bila teks terpanjang masih muat dalam satu unit area opsi,
            // pakai ukuran seperlunya. Bila tidak, kembali ke pembagian rata
            // (teks akan dilipat atau di-nowrap sesuai `wrap`).
            $unitMm = $lebarMm / $kolom;
            $isiMm = $butuhMm <= $unitMm
                ? round($terpanjangVisualMm + $ruangAmanMm, 2)
                : round(max(1.0, $unitMm - $labelMm - $gapMm), 2);

            // Lebar tabel = jumlah kolom yang benar-benar dipakai. Bila isi
            // lebih sempit dari pembagian rata, tabel TIDAK direntangkan ke
            // seluruh area — ia rapat ke tepi kanan dan sisa ruang dibiarkan
            // kosong di kiri, sehingga jarak antar opsi tetap wajar.
            $totalMm = round(($labelMm + $gapMm + $isiMm) * $kolom, 2);

            // Tabel mengikuti isinya. TIDAK dibatasi `lebarMm`: batas itu
            // memotong sisa pembulatan kolom sehingga teks terpanjang (yang
            // muat berdasarkan ukuran logis) bisa berdesakan dengan label.
            // Lebar maksimum sudah dijaga oleh `$unitMm` di atas.

            // Sisa pembulatan diserap kolom isi terakhir agar tidak menyusut.
            $terpakaiMm = ($labelMm + $gapMm + $isiMm) * $kolom;
            $isiTerakhirMm = round($isiMm + ($totalMm - $terpakaiMm), 2);

            $align = $rtl ? 'right' : 'left';
            $labelAlign = $rtl ? 'right' : 'left';

            // Label selalu menempel ke sisi isi (RTL: label di kanan isi),
            // sehingga jarak label→isi tetap konsisten.
            $labelPad = $rtl ? "0 0 0 {$gapMm}mm" : "0 {$gapMm}mm 0 0";
            $isiPad = '0';

            // Grid tetap melipat teks yang lebih panjang dari kolomnya agar
            // tidak menimpa kolom sebelah. Opsi pendek tetap satu baris.
            $whiteSpace = ! empty($opsiLayout['wrap']) ? 'normal' : 'nowrap';
        @endphp
        {{-- Satu tabel per baris, lebar tiap kolom DIEKSPLISITKAN dalam mm.
             Tidak ada spasi manual yang dipakai untuk memposisikan apa pun. --}}
        @foreach($opsiLayout['rows'] as $row)
            @php
                $sisa = max(0, $kolom - count($row));
                // RTL: service sudah membalik urutan sel (ا paling kanan).
                // Sisa sel kosong ditempatkan di KIRI agar ا tetap di tepi
                // kanan walau soal hanya punya 2–3 pilihan.
                $selIsi = $row;
            @endphp
            <table class="opts opts-grid {{ $rtl ? 'rtl' : '' }}" style="width: {{ $totalMm }}mm;" cellspacing="0" cellpadding="0">
                <tr>
                    @if($rtl && $sisa > 0)
                        @for($i = 0; $i < $sisa; $i++)
                            {{-- Slot kosong tetap memakai DUA kolom dengan lebar
                                 sama, supaya grid tidak bergeser. --}}
                            <td class="col-label" style="width: {{ $labelMm }}mm;">&nbsp;</td>
                            <td class="col-isi" style="width: {{ $isiMm }}mm;">&nbsp;</td>
                        @endfor
                    @endif

                    @foreach($selIsi as $cell)
                        @php
                            $opsi = $block['opsi'][$cell['index']] ?? null;
                            $label = $opsi ? ($opsi['display_label'] ?? $opsi['label']) : '';
                            // Kolom isi TERAKHIR menyerap sisa pembulatan.
                            $isiSelMm = $loop->last ? $isiTerakhirMm : $isiMm;
                        @endphp
                        @if($rtl)
                            {{-- RTL: kolom ISI di kiri, kolom LABEL di kanan.
                                 Tabel tetap dibangun kiri→kanan; sel isi ditulis
                                 lebih dulu agar berada di kiri label. --}}
                            <td class="col-isi" style="width: {{ $isiSelMm }}mm; text-align: {{ $align }}; white-space: {{ $whiteSpace }}; padding: {{ $isiPad }};">
                                @if($opsi){!! LjkArabicText::marksToHtml($opsi['teks']) !!}@else&nbsp;@endif
                            </td>
                            <td class="col-label" style="width: {{ $labelMm }}mm; text-align: {{ $labelAlign }}; padding: {{ $labelPad }};">
                                @if($opsi)<span class="letter letter-ar">{!! LjkArabicText::optionMarkerRtl($label) !!}</span>@else&nbsp;@endif
                            </td>
                        @else
                            <td class="col-label" style="width: {{ $labelMm }}mm; text-align: {{ $labelAlign }}; padding: {{ $labelPad }};">
                                @if($opsi)<span class="letter">{!! LjkArabicText::marksToHtml($label) !!}.</span>@else&nbsp;@endif
                            </td>
                            <td class="col-isi" style="width: {{ $isiSelMm }}mm; text-align: {{ $align }}; white-space: {{ $whiteSpace }}; padding: {{ $isiPad }};">
                                @if($opsi){!! LjkArabicText::marksToHtml($opsi['teks']) !!}@else&nbsp;@endif
                            </td>
                        @endif
                    @endforeach

                    {{-- LTR: sel kosong di kanan. Struktur & lebar SAMA dengan
                         sel berisi supaya posisi kolom tidak bergeser. --}}
                    @if(! $rtl && $sisa > 0)
                        @for($i = 0; $i < $sisa; $i++)
                            <td class="col-label" style="width: {{ $labelMm }}mm;">&nbsp;</td>
                            <td class="col-isi" style="width: {{ $isiMm }}mm;">&nbsp;</td>
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
