@php
    /**
     * @var array $kpis  Daftar KPI:
     *   ['label' => string, 'value' => mixed, 'note' => ?string, 'compare' => ?float,
     *    'color' => string, 'icon' => string, 'url' => ?string, 'empty' => ?string]
     */
    $format = function ($value) {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return number_format((float) $value, 0, ',', '.');
        }

        return $value;
    };
@endphp

<div class="row g-3 mb-3">
    @foreach($kpis as $kpi)
        @php
            $display = $format($kpi['value'] ?? null);
            $color = $kpi['color'] ?? 'primary';
            $compare = $kpi['compare'] ?? null;
        @endphp
        <div class="col-6 col-lg-3">
            @if(! empty($kpi['url']))
                <a href="{{ $kpi['url'] }}" class="card card-animate h-100 text-decoration-none dash-kpi">
            @else
                <div class="card card-animate h-100 dash-kpi">
            @endif
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div style="min-width: 0;">
                            <p class="text-muted text-uppercase fw-semibold small mb-1">{{ $kpi['label'] }}</p>
                            @if($display === null)
                                <h4 class="mb-1 text-muted">—</h4>
                                <p class="mb-0 small text-warning">
                                    {{ $kpi['empty'] ?? 'Data belum tersedia' }}
                                </p>
                            @else
                                <h4 class="mb-1">
                                    {{ $display }}
                                    @if($compare !== null)
                                        <small class="fs-13 {{ $compare > 0 ? 'text-success' : ($compare < 0 ? 'text-danger' : 'text-muted') }}">
                                            <i class="ri-arrow-{{ $compare > 0 ? 'up' : ($compare < 0 ? 'down' : 'right') }}-line"></i>
                                            {{ abs($compare) }}%
                                        </small>
                                    @endif
                                </h4>
                                @if(! empty($kpi['note']))
                                    <p class="mb-0 small text-muted">{{ $kpi['note'] }}</p>
                                @endif
                            @endif
                        </div>
                        <span class="avatar-title bg-{{ $color }}-subtle rounded flex-shrink-0">
                            <i class="{{ $kpi['icon'] ?? 'ri-bar-chart-line' }} text-{{ $color }}"></i>
                        </span>
                    </div>
                </div>
            @if(! empty($kpi['url']))
                </a>
            @else
                </div>
            @endif
        </div>
    @endforeach
</div>
