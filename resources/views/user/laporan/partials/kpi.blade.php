{{--
    Baris KPI laporan — kartu Velzon dengan tile ikon berwarna sesuai konteks.
    Parameter: $items = [[
        'label' => 'Total santri',
        'value' => 369,             // angka atau string seperti '—'
        'unit'  => '%',             // opsional
        'note'  => 'per 21 Nov 2026',
        'tone'  => 'ok'|'warn'|'bad', // opsional, hanya untuk catatan
        'icon'  => 'user-3',        // opsional, nama ikon Remix tanpa awalan ri- / akhiran -line
        'color' => 'primary',       // opsional: primary|success|info|warning|danger|secondary
    ], ...]
--}}
@php
    $items = $items ?? [];

    // Ikon dan warna dipilih dari kata kunci label, supaya setiap halaman
    // konsisten tanpa harus mengulang konfigurasi di 17 view.
    // Urutan penting: frasa paling spesifik harus lebih dulu ("belum lengkap"
    // sebelum "lengkap", "presensi gtk" sebelum "gtk").
    $meta = function (string $label): array {
        $l = mb_strtolower($label);
        $map = [
            // Kehadiran
            ['presensi', ['calendar-check', 'success']],
            ['kehadiran', ['calendar-check', 'success']],
            ['terlambat', ['time', 'warning']],
            ['tidak hadir', ['close-circle', 'danger']],
            ['alpa', ['close-circle', 'danger']],
            ['sakit', ['capsule', 'warning']],
            ['izin', ['file-shield-2', 'info']],
            ['hadir', ['checkbox-circle', 'success']],

            // Santri & GTK
            ['santri baru', ['user-add', 'success']],
            ['santri acuan', ['user-3', 'primary']],
            ['santri aktif', ['user-3', 'primary']],
            ['total santri', ['user-3', 'primary']],
            ['data santri', ['user-3', 'primary']],
            ['santri', ['user-3', 'primary']],
            ['siswa', ['user-3', 'primary']],
            ['laki-laki', ['men', 'info']],
            ['perempuan', ['women', 'warning']],
            ['akun nonaktif', ['user-forbid', 'secondary']],
            ['akun aktif', ['user-follow', 'success']],
            ['gtk aktif', ['team', 'info']],
            ['total gtk', ['team', 'info']],
            ['data gtk', ['team', 'info']],
            ['gtk', ['team', 'info']],
            ['guru', ['team', 'info']],
            ['alumni bekerja', ['briefcase-2', 'info']],
            ['alumni', ['graduation-cap', 'warning']],
            ['tercatat lulus', ['award', 'success']],
            ['lulusan', ['award', 'success']],
            ['lulus', ['award', 'success']],

            // Nilai & ketuntasan
            ['nilai belum diisi', ['edit-2', 'warning']],
            ['nilai lengkap', ['file-check', 'success']],
            ['belum lengkap', ['file-warning', 'warning']],
            ['data nilai', ['bar-chart-box', 'warning']],
            ['nilai & ketuntasan', ['bar-chart-box', 'warning']],
            ['di bawah kktp', ['arrow-down-circle', 'danger']],
            ['ketuntasan', ['donut-chart', 'success']],
            ['perubahan kelengkapan', ['arrow-left-right', 'info']],
            ['rata-rata', ['bar-chart-2', 'primary']],
            ['meningkat', ['arrow-up-circle', 'success']],
            ['menurun', ['arrow-down-circle', 'danger']],
            ['konsisten', ['check-double', 'success']],
            ['perlu perbaikan', ['error-warning', 'warning']],
            ['kesiapan rapor', ['file-chart', 'info']],

            // Rombel & kelas
            ['naik kelas', ['arrow-up-circle', 'success']],
            ['tinggal kelas', ['repeat', 'warning']],
            ['rombel aktif', ['group', 'secondary']],
            ['pindah rombel', ['swap', 'info']],
            ['rombel', ['group', 'secondary']],
            ['kelas', ['group', 'secondary']],
            ['pindah unit', ['building-2', 'info']],

            // Mutasi & populasi
            ['total peristiwa', ['file-list-3', 'primary']],
            ['internal', ['swap-box', 'info']],
            ['masuk', ['login-box', 'success']],
            ['keluar tercatat', ['logout-box', 'warning']],
            ['keluar', ['logout-box', 'danger']],
            ['berhenti', ['user-unfollow', 'danger']],
            ['selisih', ['scales-3', 'secondary']],
            ['pertumbuhan bersih', ['line-chart', 'success']],
            ['pertumbuhan', ['line-chart', 'primary']],
            ['perubahan populasi', ['line-chart', 'primary']],
            ['populasi', ['group-2', 'primary']],
            ['awal tahun', ['calendar-event', 'secondary']],
            ['akhir tahun', ['calendar-todo', 'primary']],
            ['angka keberlanjutan', ['percent', 'info']],
            ['melanjutkan', ['arrow-right-up', 'success']],
            ['belum tercatat', ['question', 'secondary']],
            ['mutasi', ['swap-box', 'info']],

            // Asrama
            ['asrama', ['hotel', 'info']],
            ['sisa kapasitas', ['door-closed', 'warning']],
            ['kapasitas', ['door-open', 'secondary']],
            ['penghuni aktif', ['user-location', 'primary']],
            ['kunjungan', ['walk', 'info']],

            // Perangkat, jurnal, administrasi
            ['berjurnal', ['book-2', 'info']],
            ['jurnal', ['book-2', 'info']],
            ['berperangkat', ['file-text', 'secondary']],
            ['perangkat', ['file-text', 'secondary']],
            ['berpresensi', ['calendar-check', 'success']],
            ['buku lengkap', ['book-open', 'success']],
            ['buku administrasi', ['book-open', 'info']],
            ['buku', ['book-open', 'info']],
            ['kolom jp', ['checkbox-multiple', 'secondary']],
            ['kewajiban', ['task', 'warning']],
            ['terpenuhi', ['checkbox-circle', 'success']],
            ['belum terlaksana', ['time', 'warning']],
            ['terlaksana', ['check-double', 'success']],
            ['keterlaksanaan', ['check-double', 'success']],
            ['total catatan', ['sticky-note', 'secondary']],
            ['catatan', ['sticky-note', 'secondary']],
            ['administrasi', ['clipboard', 'info']],
            ['analisis akademik', ['line-chart', 'primary']],
            ['mutu pendidikan', ['award', 'warning']],

            // Lain-lain
            ['biaya', ['wallet-3', 'warning']],
            ['saldo', ['wallet-3', 'success']],
            ['gizi', ['restaurant', 'warning']],
            ['aktif', ['user-follow', 'success']],
        ];

        foreach ($map as [$needle, $value]) {
            if (str_contains($l, $needle)) {
                return $value;
            }
        }

        // Label yang tidak dikenali tetap dapat ikon berbeda-beda, supaya
        // deretan kartu tidak terlihat seragam.
        $fallback = [
            ['pie-chart', 'primary'],
            ['bar-chart-2', 'info'],
            ['line-chart', 'success'],
            ['donut-chart', 'warning'],
            ['radar', 'danger'],
            ['bubble-chart', 'secondary'],
        ];

        return $fallback[abs(crc32($l)) % count($fallback)];
    };
@endphp

@if (count($items))
    @php
        // Baris terakhir selalu penuh: kartu di baris itu dilebarkan
        // supaya tidak menyisakan kolom kosong (mis. 5 kartu → 3 + 2).
        $total = count($items);
        $perRow = $total <= 4 ? $total : (in_array($total, [5, 6], true) ? 3 : 4);
        $fullRows = intdiv($total, $perRow);
        $remainder = $total % $perRow;
    @endphp
    <div class="row g-3 lap-kpi-row">
        @foreach ($items as $index => $item)
            @php
                $raw = $item['value'] ?? '—';
                $isNumber = is_numeric($raw);
                $canCount = $isNumber && floor((float) $raw) === (float) $raw && abs((float) $raw) < 1000000;
                $tone = $item['tone'] ?? null;
                $printed = $isNumber ? number_format((float) $raw, 0, ',', '.') : $raw;
                [$autoIcon, $autoColor] = $meta((string) ($item['label'] ?? ''));
                $icon = $item['icon'] ?? $autoIcon;
                $color = $item['color'] ?? $autoColor;

                $onLastRow = $remainder > 0 && intdiv($index, $perRow) === $fullRows;
                $span = (int) (12 / ($onLastRow ? $remainder : $perRow));
            @endphp
            <div class="col-md-6 col-xxl-{{ $span }} col-xl-{{ $span }}">
                <div class="card card-animate h-100 lap-kpi-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="flex-grow-1 min-w-0">
                                <p class="lap-kpi-label">{{ $item['label'] }}</p>
                                <div class="lap-kpi-value">
                                    @if ($canCount)
                                        <span data-lap-count="{{ (int) $raw }}">{{ $printed }}</span>
                                    @else
                                        <span>{{ $printed }}</span>
                                    @endif
                                    @if (! empty($item['unit']))
                                        <span class="lap-kpi-unit">{{ $item['unit'] }}</span>
                                    @endif
                                </div>
                                @if (! empty($item['note']))
                                    <p class="lap-kpi-note {{ $tone ? 'is-'.$tone : '' }}">{{ $item['note'] }}</p>
                                @endif
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $color }}-subtle rounded fs-2">
                                    <i class="ri-{{ $icon }}-line text-{{ $color }}"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
