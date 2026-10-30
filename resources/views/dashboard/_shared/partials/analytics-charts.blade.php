@php
    $charts = $charts ?? [];
    $chartId = fn (string $key) => 'dash-chart-'.\Illuminate\Support\Str::slug($key).'-'.substr(md5($user->id), 0, 6);
    $hasData = fn (array $chart) => collect($chart['series'] ?? [])
        ->flatten(1)
        ->filter(fn ($value) => $value !== null && (float) $value > 0)
        ->isNotEmpty();

    // Deduplikasi: satu jenis data hanya boleh muncul sekali di dashboard.
    $seenSignatures = [];
    $uniqueCharts = [];
    foreach ($charts as $key => $chart) {
        $signature = implode('|', array_column($chart['series'] ?? [], 'name'));
        if ($signature !== '' && isset($seenSignatures[$signature])) {
            continue;
        }
        $seenSignatures[$signature] = true;
        $uniqueCharts[$key] = $chart;
    }
@endphp

@if(! empty($uniqueCharts))
    <div class="row g-3 mb-3">
        @foreach($uniqueCharts as $key => $chart)
            <div class="col-12 col-xl-6">
                <div class="card h-100 dash-animate" data-dash-animate>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-{{ ($chart['type'] ?? 'line') === 'bar' ? 'bar-chart-grouped' : 'line-chart' }}-line me-1 text-primary"></i>
                            {{ $chart['title'] }}
                        </h5>
                        @if(! empty($chart['url']))
                            <a href="{{ $chart['url'] }}" class="btn btn-sm btn-light border">Rincian</a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if(! $hasData($chart))
                            <div class="text-center text-muted py-5">
                                <i class="ri-bar-chart-box-line fs-2 d-block mb-1"></i>
                                {{ $chart['empty'] ?? 'Belum ada data untuk ditampilkan.' }}
                            </div>
                        @else
                            <div id="{{ $chartId($key) }}" style="min-height:280px;"></div>
                            @php
                                $chartPayload = [
                                    'id' => $chartId($key),
                                    'type' => $chart['type'] ?? 'line',
                                    'series' => $chart['series'] ?? [],
                                    'categories' => $chart['categories'] ?? [],
                                    'colors' => $chart['colors'] ?? ['#405189', '#0ab39c', '#f7b84b', '#f06548'],
                                ];
                            @endphp
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    var cfg = @json($chartPayload);
                                    var el = document.getElementById(cfg.id);
                                    if (!el || !window.ApexCharts) return;
                                    new ApexCharts(el, {
                                        chart: {
                                            type: cfg.type,
                                            height: 280,
                                            toolbar: { show: false },
                                            animations: { enabled: true, easing: 'easeinout', speed: 700, animateGradually: { enabled: true, delay: 80 } }
                                        },
                                        series: cfg.series,
                                        xaxis: { categories: cfg.categories },
                                        colors: cfg.colors,
                                        stroke: { curve: 'smooth', width: 2.5 },
                                        dataLabels: { enabled: false },
                                        legend: { position: 'top' },
                                        plotOptions: { bar: { borderRadius: 5, columnWidth: '52%' } },
                                        grid: { borderColor: '#f1f1f1' },
                                        noData: { text: 'Belum ada data' }
                                    }).render();
                                });
                            </script>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
