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
@endphp

<div class="card card-animate">
    <div class="card-body">
        <p class="dash-label">{{ $data['label'] ?? '—' }}</p>

        <p class="widget-stat-value mb-0">
            {{ $display }}
            @if (! empty($data['trend']))
                <small class="dash-kpi-delta {{ $data['trend'] > 0 ? 'is-up' : 'is-down' }}">
                    <i class="ri-arrow-{{ $data['trend'] > 0 ? 'up' : 'down' }}-line"></i>{{ abs($data['trend']) }}%
                </small>
            @endif
        </p>

        @if (! empty($data['sub']))
            <p class="mb-0 dash-kpi-note">{{ $data['sub'] }}</p>
        @endif
    </div>
</div>
