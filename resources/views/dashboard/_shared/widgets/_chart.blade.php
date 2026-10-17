@php
    $chartId = 'chart-' . \Illuminate\Support\Str::slug($data['key'] ?? 'widget') . '-' . substr(md5($user->id), 0, 6);
    $series = $data['series'] ?? ($data['options']['series'] ?? null);
    $hasData = ! empty($series) && collect($series)->flatten()->filter(fn ($v) => $v !== null && (float) $v > 0)->isNotEmpty();
@endphp

<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Statistik' }}</h4>
        @if (! empty($data['badge']))
            <span class="badge bg-primary-subtle text-primary">{{ $data['badge'] }}</span>
        @endif
    </div>
    <div class="card-body">
        @if (! $hasData)
            @include('dashboard._shared.widgets._empty', [
                'icon'  => $data['empty_icon'] ?? 'ri-bar-chart-box-line',
                'title' => $data['empty_title'] ?? 'Belum ada data untuk ditampilkan',
                'hint'  => $data['empty_hint'] ?? null,
            ])
        @else
            <div id="{{ $chartId }}" class="widget-chart-container"></div>

            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var el = document.getElementById(@json($chartId));
                        if (!el || !window.ApexCharts) return;

                        var options = @json($data['options'] ?? []);
                        options.chart = options.chart || {};
                        options.chart.type = options.chart.type || @json($data['type'] ?? 'line');
                        options.chart.height = options.chart.height || 250;
                        options.series = options.series || @json($series);
                        @if (! empty($data['labels']))
                            options.labels = @json($data['labels']);
                        @endif
                        @if (! empty($data['colors']))
                            options.colors = @json($data['colors']);
                        @endif

                        new ApexCharts(el, options).render();
                    });
                </script>
            @endpush
        @endif
    </div>
</div>
