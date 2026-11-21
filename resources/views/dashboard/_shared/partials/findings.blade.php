@php
    /**
     * @var array $findings  Daftar temuan:
     *   ['title' => string, 'description' => string, 'count' => int,
     *    'severity' => 'info'|'warning'|'danger', 'url' => ?string, 'rule' => string]
     */
@endphp

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title">Temuan &amp; perlu perhatian</h5>
        <span class="small text-muted">{{ count($findings) }} temuan</span>
    </div>
    <div class="card-body">
        @forelse($findings as $finding)
            @php
                $severity = $finding['severity'] ?? 'warning';
                $flagClass = match ($severity) {
                    'danger' => 'is-danger',
                    'info' => 'is-info',
                    default => 'is-warning',
                };
            @endphp
            <div class="finding-row">
                <div class="finding-main">
                    <span class="finding-flag {{ $flagClass }}" aria-hidden="true"></span>
                    <div>
                        @if(! empty($finding['url']))
                            <a href="{{ $finding['url'] }}" class="finding-title">{{ $finding['title'] }}</a>
                        @else
                            <div class="finding-title">{{ $finding['title'] }}</div>
                        @endif
                        <div class="finding-desc">{{ $finding['description'] }}</div>
                    </div>
                </div>
                <span class="finding-count">{{ number_format($finding['count'] ?? 0, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="dash-empty">
                <i class="ri-checkbox-circle-line"></i>
                <p>Tidak ada temuan pada periode ini</p>
                <small>Semua indikator berada di bawah ambang perhatian</small>
            </div>
        @endforelse
    </div>
</div>
