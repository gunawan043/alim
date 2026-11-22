{{--
    Kop laporan: cakupan unit, periode, catatan metode, garis emas di bawah.
    Parameter: $period (string), $note (string|null), $scope (ReportScope|null)
--}}
@php($scope = $scope ?? null)
@if (! empty($period) || ! empty($note) || $scope)
    <div class="lap-period">
        <div class="lap-period-main">
            @if ($scope)
                <span class="lap-scope">
                    <i class="ri-focus-3-line"></i>
                    {{ $scope->label }}
                </span>
            @endif
            @if (! empty($period))
                <span class="lap-period-label">Periode</span>{{ $period }}
            @endif
        </div>
        @if (! empty($note))
            <div class="lap-period-note">{{ $note }}</div>
        @endif
    </div>
@endif
