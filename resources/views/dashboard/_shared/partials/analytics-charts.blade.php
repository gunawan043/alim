@php
    $charts = $charts ?? [];
    $analytics = $analytics ?? [];
    $uid = substr(md5($user->id), 0, 6);

    $attendance = $analytics['attendance'] ?? [];
    $attendanceRows = $attendance['series'] ?? [];
    $attendanceRates = array_map(fn ($row) => $row['rate'], $attendanceRows);
    $attendanceLabels = array_map(fn ($row) => $row['label'], $attendanceRows);
    $validRates = array_values(array_filter($attendanceRates, fn ($v) => $v !== null));
    $hasAttendance = $validRates !== [];

    // Sumbu Y dimulai dekat nilai terendah, bukan dari 0: variasi 88%–95%
    // tidak terlihat kalau grafiknya membentang 0–100.
    $rateMin = $validRates ? min($validRates) : 0;
    $yMin = $rateMin >= 60 ? (int) (floor(($rateMin - 5) / 10) * 10) : 0;
    $yTicks = max(2, (int) ((100 - $yMin) / 10));

    $mutations = $analytics['mutations'] ?? [];
    $mutationRows = $mutations['series'] ?? [];
    $hasMutation = collect($mutationRows)->contains(fn ($row) => ($row['masuk'] ?? 0) > 0 || ($row['keluar'] ?? 0) > 0);

    $gender = $analytics['distribution']['gender'] ?? ['L' => 0, 'P' => 0];
    $genderTotal = (int) ($gender['L'] ?? 0) + (int) ($gender['P'] ?? 0);
    $genderShare = fn (int $value) => $genderTotal > 0 ? round(($value / $genderTotal) * 100, 1) : 0;

    $attendanceId = 'dash-attendance-'.$uid;
    $genderId = 'dash-gender-'.$uid;
    $mutationId = 'dash-mutation-'.$uid;
@endphp

<div class="row g-3 mb-3">
    {{-- TREN KEHADIRAN — grafik utama halaman --}}
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="card-title">Tren kehadiran santri</h5>
                    <p class="dash-chart-caption">14 hari terakhir · persentase santri hadir</p>
                </div>
                @if($hasAttendance && ($attendance['today_rate'] ?? null) !== null)
                    <span class="dash-chart-figure">{{ number_format((float) $attendance['today_rate'], 1, ',', '.') }}%</span>
                @endif
            </div>
            <div class="card-body">
                @if(! $hasAttendance)
                    <div class="dash-empty" style="min-height: 280px;">
                        <i class="ri-line-chart-line"></i>
                        <p>Presensi harian belum tersedia pada periode ini</p>
                        <small>Grafik muncul setelah presensi harian diisi</small>
                    </div>
                @else
                    <div id="{{ $attendanceId }}" style="min-height:280px;"></div>
                    <p class="dash-chart-note">Titik kosong menandakan presensi hari itu belum diisi.</p>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var detail = @json($attendanceRows);

                            window.dashChart(@json($attendanceId), {
                                chart: {
                                    type: 'area',
                                    height: 280,
                                    toolbar: { show: false },
                                    fontFamily: 'inherit',
                                    animations: {
                                        enabled: true,
                                        easing: 'easeout',
                                        speed: 900,
                                        animateGradually: { enabled: true, delay: 60 },
                                        dynamicAnimation: { enabled: true, speed: 350 }
                                    }
                                },
                                series: [{ name: 'Kehadiran', data: @json($attendanceRates) }],
                                stroke: { curve: 'smooth', width: 2.5, lineCap: 'round' },
                                fill: {
                                    type: 'gradient',
                                    gradient: { shadeIntensity: 1, opacityFrom: 0.26, opacityTo: 0.02, stops: [0, 90, 100] }
                                },
                                colors: ['#00416c'],
                                markers: { size: 0, hover: { size: 5 }, strokeColors: '#ffffff', strokeWidth: 2 },
                                xaxis: {
                                    categories: @json($attendanceLabels),
                                    axisBorder: { show: false },
                                    axisTicks: { show: false },
                                    labels: { rotate: 0, hideOverlappingLabels: true, style: { fontSize: '11px' } }
                                },
                                yaxis: {
                                    min: {{ $yMin }},
                                    max: 100,
                                    tickAmount: {{ $yTicks }},
                                    labels: { formatter: function (v) { return Math.round(v) + '%'; } }
                                },
                                grid: { borderColor: 'rgba(23, 33, 43, .08)', strokeDashArray: 4, padding: { left: 6, right: 6 } },
                                tooltip: {
                                    custom: function (ctx) {
                                        var row = detail[ctx.dataPointIndex];
                                        if (!row || row.rate === null) {
                                            return '<div class="dash-tt"><span class="dash-tt-title">' + (row ? row.label : '') + '</span>Presensi belum diisi</div>';
                                        }
                                        return '<div class="dash-tt">'
                                            + '<span class="dash-tt-title">' + row.label + '</span>'
                                            + '<span class="dash-tt-value">' + String(row.rate).replace('.', ',') + '% hadir</span>'
                                            + '<span class="dash-tt-meta">' + window.dashFormat(row.hadir) + ' dari ' + window.dashFormat(row.total) + ' santri</span>'
                                            + '</div>';
                                    }
                                },
                                noData: { text: 'Belum ada data presensi' }
                            });
                        });
                    </script>
                @endif
            </div>
        </div>
    </div>

    {{-- KOMPOSISI SANTRI --}}
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h5 class="card-title">Komposisi santri</h5>
                    <p class="dash-chart-caption">Santri aktif pada periode ini</p>
                </div>
            </div>
            <div class="card-body">
                @if($genderTotal === 0)
                    <div class="dash-empty" style="min-height: 220px;">
                        <i class="ri-pie-chart-line"></i>
                        <p>Belum ada data santri</p>
                    </div>
                @else
                    <div id="{{ $genderId }}" style="min-height:220px;"></div>

                    <div class="dash-legend">
                        <div class="dash-legend-row">
                            <span class="dash-legend-dot" style="background: #00416c"></span>
                            <span class="dash-legend-label">Laki-laki</span>
                            <span class="dash-legend-value">{{ number_format((int) $gender['L'], 0, ',', '.') }}</span>
                            <span class="dash-legend-share">{{ number_format($genderShare((int) $gender['L']), 1, ',', '.') }}%</span>
                        </div>
                        <div class="dash-legend-row">
                            <span class="dash-legend-dot" style="background: #ffae01"></span>
                            <span class="dash-legend-label">Perempuan</span>
                            <span class="dash-legend-value">{{ number_format((int) $gender['P'], 0, ',', '.') }}</span>
                            <span class="dash-legend-share">{{ number_format($genderShare((int) $gender['P']), 1, ',', '.') }}%</span>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            window.dashChart(@json($genderId), {
                                chart: {
                                    type: 'donut',
                                    height: 220,
                                    fontFamily: 'inherit',
                                    animations: { enabled: true, easing: 'easeout', speed: 800 }
                                },
                                series: [{{ (int) $gender['L'] }}, {{ (int) $gender['P'] }}],
                                labels: ['Laki-laki', 'Perempuan'],
                                colors: ['#00416c', '#ffae01'],
                                stroke: { width: 2, colors: ['#ffffff'] },
                                legend: { show: false },
                                dataLabels: { enabled: false },
                                plotOptions: {
                                    pie: {
                                        expandOnClick: false,
                                        donut: {
                                            size: '74%',
                                            labels: {
                                                show: true,
                                                name: { show: true, fontSize: '12px', color: window.dashMutedInk(), offsetY: 18 },
                                                value: { show: true, fontSize: '26px', fontWeight: 600, color: window.dashInk(), offsetY: -10 },
                                                total: {
                                                    show: true,
                                                    label: 'Santri aktif',
                                                    fontSize: '12px',
                                                    color: window.dashMutedInk(),
                                                    formatter: function (w) {
                                                        return window.dashFormat(w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0));
                                                    }
                                                }
                                            }
                                        }
                                    }
                                },
                                tooltip: {
                                    y: { formatter: function (v) { return window.dashFormat(v) + ' santri'; } }
                                }
                            });
                        });
                    </script>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- MUTASI MASUK & KELUAR --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-start gap-3">
        <div>
            <h5 class="card-title">Mutasi masuk &amp; keluar</h5>
            <p class="dash-chart-caption">12 bulan terakhir · mutasi resmi yang disetujui</p>
        </div>
        <div class="dash-chart-totals">
            <span><b>{{ number_format((int) ($mutations['total_in'] ?? 0), 0, ',', '.') }}</b> masuk</span>
            <span><b>{{ number_format((int) ($mutations['total_out'] ?? 0), 0, ',', '.') }}</b> keluar</span>
        </div>
    </div>
    <div class="card-body">
        @if(! $hasMutation)
            <div class="dash-empty" style="min-height: 260px;">
                <i class="ri-bar-chart-grouped-line"></i>
                <p>Belum ada mutasi resmi pada 12 bulan terakhir</p>
            </div>
        @else
            <div id="{{ $mutationId }}" style="min-height:260px;"></div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    window.dashChart(@json($mutationId), {
                        chart: {
                            type: 'bar',
                            height: 260,
                            toolbar: { show: false },
                            fontFamily: 'inherit',
                            animations: {
                                enabled: true,
                                easing: 'easeout',
                                speed: 800,
                                animateGradually: { enabled: true, delay: 80 }
                            }
                        },
                        series: [
                            { name: 'Masuk', data: @json(array_column($mutationRows, 'masuk')) },
                            { name: 'Keluar', data: @json(array_column($mutationRows, 'keluar')) }
                        ],
                        colors: ['#00416c', '#c3c9d1'],
                        plotOptions: {
                            bar: { borderRadius: 3, columnWidth: '68%', borderRadiusApplication: 'end' }
                        },
                        xaxis: {
                            categories: @json(array_column($mutationRows, 'label')),
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                            labels: { rotate: 0, hideOverlappingLabels: true, style: { fontSize: '11px' } }
                        },
                        yaxis: {
                            labels: { formatter: function (v) { return window.dashFormat(v); } }
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'left',
                            fontSize: '12px',
                            markers: { size: 5, shape: 'circle' },
                            itemMargin: { horizontal: 12, vertical: 0 }
                        },
                        grid: { borderColor: 'rgba(23, 33, 43, .08)', padding: { left: 6, right: 6 } },
                        tooltip: { shared: true, intersect: false }
                    });
                });
            </script>
        @endif
    </div>
</div>
