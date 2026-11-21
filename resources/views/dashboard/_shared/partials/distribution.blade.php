@php
    $analytics = $dashboard['analytics'] ?? [];
    $distribution = $analytics['distribution'] ?? [];
    $byGrade = $distribution['by_grade'] ?? [];
    $byClass = $distribution['by_class'] ?? [];
    $uid = substr(md5($user->id), 0, 6);
    $gradeChartId = 'dash-grade-'.$uid;

    // Grafik horizontal: kategori pertama digambar paling bawah, jadi urutannya
    // dibalik supaya jenjang terendah tetap di atas.
    $gradeNames = array_reverse(array_column($byGrade, 'name'));
    $gradeTotals = array_reverse(array_column($byGrade, 'total'));
    $gradeHeight = max(300, count($byGrade) * 44 + 40);
    $gradeMax = max(1, (int) ($gradeTotals ? max($gradeTotals) : 1));
@endphp

<div class="row g-3 mb-3">
    {{-- DISTRIBUSI PER JENJANG --}}
    <div class="col-12 col-xl-5">
        <div class="card" style="height: auto;">
            <div class="card-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="card-title">Distribusi santri per jenjang</h5>
                    <p class="dash-chart-caption">Jumlah santri aktif tiap jenjang</p>
                </div>
                <a href="{{ route('user.laporan.populasi.profil', ['userId' => $user->id]) }}" class="btn btn-sm btn-light border">Rincian</a>
            </div>
            <div class="card-body">
                @if(empty($byGrade))
                    <div class="dash-empty" style="min-height: {{ $gradeHeight }}px;">
                        <i class="ri-bar-chart-box-line"></i>
                        <p>Belum ada riwayat kelas pada periode ini</p>
                    </div>
                @else
                    <div id="{{ $gradeChartId }}" style="min-height: {{ $gradeHeight }}px;"></div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            window.dashChart(@json($gradeChartId), {
                                chart: {
                                    type: 'bar',
                                    height: {{ $gradeHeight }},
                                    toolbar: { show: false },
                                    fontFamily: 'inherit',
                                    animations: {
                                        enabled: true,
                                        easing: 'easeout',
                                        speed: 800,
                                        animateGradually: { enabled: true, delay: 90 }
                                    }
                                },
                                series: [{ name: 'Santri', data: @json($gradeTotals) }],
                                colors: ['#00416c'],
                                plotOptions: {
                                    bar: { horizontal: true, borderRadius: 3, barHeight: 30, borderRadiusApplication: 'end' }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'end',
                                    offsetX: -8,
                                    style: { fontSize: '11px', fontWeight: 600, colors: ['#ffffff'] },
                                    formatter: function (v) { return window.dashFormat(v); }
                                },
                                xaxis: {
                                    categories: @json($gradeNames),
                                    max: {{ (int) ceil($gradeMax * 1.1) }},
                                    axisBorder: { show: false },
                                    axisTicks: { show: false },
                                    labels: { show: false }
                                },
                                yaxis: {
                                    labels: { style: { fontSize: '12px', colors: ['#5a6472'] } }
                                },
                                grid: { show: false, padding: { left: 4, right: 34, top: 0, bottom: 0 } },
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

    {{-- KETERISIAN ROMBEL — bar tumbuh saat masuk layar --}}
    <div class="col-12 col-xl-7">
        <div class="card" style="height: auto;">
            <div class="card-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="card-title">Keterisian rombel</h5>
                    <p class="dash-chart-caption">Santri terisi dibanding kapasitas rombel</p>
                </div>
                <span class="dash-chart-figure">{{ count($byClass) }}<span class="dash-chart-unit"> rombel</span></span>
            </div>
            <div class="card-body">
                @if(empty($byClass))
                    <div class="dash-empty" style="min-height: 260px;">
                        <i class="ri-building-2-line"></i>
                        <p>Belum ada rombel pada filter ini</p>
                    </div>
                @else
                    <div class="fill-list" style="max-height: 300px; overflow-y: auto;">
                        @foreach($byClass as $row)
                            @php
                                $capacity = (int) ($row['capacity'] ?? 0);
                                $filled = (int) $row['total'];
                                $rate = $capacity > 0 ? ($filled / $capacity) * 100 : null;
                                $barClass = $rate === null ? '' : ($rate > 100 ? 'is-over' : ($rate < 75 ? 'is-low' : ''));
                            @endphp
                            <div class="fill-row" data-fill-row>
                                <span class="fill-name">{{ $row['name'] }}</span>
                                <span class="fill-meta">{{ number_format($filled, 0, ',', '.') }}/{{ $capacity > 0 ? number_format($capacity, 0, ',', '.') : '—' }}</span>
                                <span class="fill-track" aria-hidden="true">
                                    <span class="fill-bar {{ $barClass }}" data-dash-fill="{{ $rate === null ? 0 : min(100, $rate) }}"></span>
                                </span>
                                <span class="fill-pct">{{ $rate === null ? '—' : number_format($rate, 1, ',', '.').'%' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
