@php
    $hasError = ! empty($data['error']);
    $value = $data['value'] ?? null;

    if ($hasError || $value === null) {
        $display = '—';
    } elseif (is_numeric($value)) {
        $display = number_format((float) $value, 0, ',', '.');
    } else {
        $display = $value;
    }

    if (! $hasError && $value !== null && ! empty($data['suffix'])) {
        $display .= $data['suffix'];
    }

    $color = $data['color'] ?? 'primary';
@endphp

<div class="card card-animate">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="flex-grow-1" style="min-width: 0;">
                <p class="dash-label">{{ $data['label'] ?? '—' }}</p>
                <h4 class="widget-stat-value mb-1">
                    @if ($hasError)
                        <span class="text-danger">—</span>
                    @else
                        {{ $display }}
                        @if (! empty($data['trend']))
                            <small class="fs-13 {{ $data['trend'] > 0 ? 'text-success' : 'text-danger' }}">
                                <i class="ri-arrow-{{ $data['trend'] > 0 ? 'up' : 'down' }}-line"></i>
                                {{ abs($data['trend']) }}%
                            </small>
                        @endif
                    @endif
                </h4>
                @if (! empty($data['sub']))
                    <p class="mb-0 text-muted fs-13">{{ $data['sub'] }}</p>
                @endif
            </div>
            <span class="avatar-title bg-{{ $color }}-subtle flex-shrink-0">
                <i class="{{ $data['icon'] ?? 'ri-bar-chart-line' }} text-{{ $color }}"></i>
            </span>
        </div>
    </div>
</div>
