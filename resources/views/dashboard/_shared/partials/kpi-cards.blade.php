@php
    /**
     * @var array $kpis  Daftar KPI:
     *   ['label' => string, 'value' => mixed, 'note' => ?string, 'compare' => ?float,
     *    'url' => ?string, 'empty' => ?string, 'unit' => ?string]
     *
     * Kartu hanya memuat tiga hal: label, angka, dan keterangan pembanding.
     * Ikon dan warna dekoratif dihapus — angka itu sendiri yang menjadi isi.
     */
    $format = function ($value) {
        if ($value === null || ! is_numeric($value)) {
            return $value;
        }

        $number = (float) $value;

        // Desimal hanya ditampilkan kalau memang ada (mis. 96,5%).
        return floor($number) === $number
            ? number_format($number, 0, ',', '.')
            : number_format($number, 1, ',', '.');
    };
@endphp

<div class="dash-kpi-grid">
    @foreach($kpis as $kpi)
        @php
            $raw = $kpi['value'] ?? null;
            $display = $format($raw);
            $compare = $kpi['compare'] ?? null;
            $unit = $kpi['unit'] ?? null;
            $isNumber = is_numeric($raw);
            $canCount = $isNumber && floor((float) $raw) === (float) $raw;
            $deltaClass = $compare === null ? 'is-flat' : ($compare > 0 ? 'is-up' : ($compare < 0 ? 'is-down' : 'is-flat'));
        @endphp

        <div class="dash-kpi-cell">
            @if(! empty($kpi['url']))
                <a href="{{ $kpi['url'] }}" class="card dash-kpi text-decoration-none {{ $display === null ? 'is-empty' : '' }}">
            @else
                <div class="card dash-kpi {{ $display === null ? 'is-empty' : '' }}">
            @endif
                    <p class="dash-kpi-label">{{ $kpi['label'] }}</p>

                    @if($display === null)
                        <p class="dash-kpi-value">—</p>
                        <p class="dash-kpi-note is-warning">{{ $kpi['empty'] ?? 'Data belum tersedia' }}</p>
                    @else
                        <p class="dash-kpi-value">
                            @if($canCount)
                                <span data-dash-count="{{ (float) $raw }}">0</span>
                            @else
                                {{ $display }}
                            @endif
                            @if($unit)
                                <span class="dash-kpi-unit">{{ $unit }}</span>
                            @endif
                        </p>

                        @if($compare !== null || ! empty($kpi['note']))
                            <p class="dash-kpi-note">
                                @if($compare !== null)
                                    <span class="dash-kpi-delta {{ $deltaClass }}">
                                        <i class="ri-arrow-{{ $compare > 0 ? 'up' : ($compare < 0 ? 'down' : 'right') }}-line"></i>{{ number_format(abs($compare), 1, ',', '.') }}%
                                    </span>
                                    @if(! empty($kpi['note']))
                                        <span aria-hidden="true">·</span>
                                    @endif
                                @endif
                                {{ $kpi['note'] }}
                            </p>
                        @endif
                    @endif
            @if(! empty($kpi['url']))
                </a>
            @else
                </div>
            @endif
        </div>
    @endforeach
</div>
