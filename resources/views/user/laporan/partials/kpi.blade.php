{{--
    Baris KPI laporan.
    Parameter: $items = [[
        'label' => 'Total santri',
        'value' => 369,             // angka atau string seperti '—'
        'unit'  => '%',             // opsional
        'note'  => 'per 21 Nov 2026',
        'tone'  => 'ok'|'warn'|'bad' // opsional, hanya untuk catatan
    ], ...]
--}}
@php
    $items = $items ?? [];
@endphp

@if (count($items))
    <div class="lap-kpi">
        @foreach ($items as $item)
            @php
                $raw = $item['value'] ?? '—';
                $isNumber = is_numeric($raw);
                $canCount = $isNumber && floor((float) $raw) === (float) $raw && abs((float) $raw) < 1000000;
                $tone = $item['tone'] ?? null;
                $printed = $isNumber ? number_format((float) $raw, 0, ',', '.') : $raw;
            @endphp
            <div class="lap-kpi-cell">
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
        @endforeach
    </div>
@endif
