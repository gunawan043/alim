@php
    $calendar = $dashboard['calendar'] ?? [];
    $kaldik = $calendar['kaldik'] ?? ['month_label' => now()->translatedFormat('F Y'), 'items' => [], 'total' => 0];
    $prayer = $calendar['prayer'] ?? ['available' => false, 'times' => []];
    $items = $kaldik['items'] ?? [];
    $nextPrayer = $prayer['next'] ?? null;
@endphp

<div class="row g-3 mb-3">
    {{-- KALDIK BULAN BERJALAN --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Kalender pendidikan · {{ $kaldik['month_label'] }}</h5>
                <span class="small text-muted">{{ $kaldik['total'] }} kegiatan</span>
            </div>
            <div class="card-body" style="max-height:340px;overflow:auto;">
                @forelse($items as $item)
                    <div class="kaldik-row">
                        <span class="kaldik-dot" style="background: {{ $item['color'] }}"></span>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="kaldik-name">{{ $item['name'] }}</span>
                                @if($item['is_ongoing'])
                                    <span class="badge" style="background: var(--dash-navy-soft); color: var(--dash-navy-2);">Berlangsung</span>
                                @elseif($item['is_past'])
                                    <span class="badge" style="background: var(--dash-surface-2); color: var(--dash-ink-3);">Selesai</span>
                                @elseif($item['days_left'] !== null && $item['days_left'] <= 7)
                                    <span class="badge" style="background: var(--dash-surface-2); color: var(--dash-gold-ink);">{{ $item['days_left'] }} hari lagi</span>
                                @endif
                            </div>
                            <div class="kaldik-meta">
                                {{ $item['date_label'] }}
                                @if($item['type'])
                                    · {{ ucfirst($item['type']) }}
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="dash-empty">
                        <i class="ri-calendar-2-line"></i>
                        <p>Tidak ada kegiatan pada {{ $kaldik['month_label'] }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- JADWAL SHALAT — garis waktu hari ini --}}
    <div class="col-xl-6">
        <div class="card" style="height: auto;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Jadwal shalat</h5>
                @if(! empty($prayer['city']))
                    <span class="small text-muted">{{ $prayer['city'] }}</span>
                @endif
            </div>
            <div class="card-body d-flex flex-column">
                @if(empty($prayer['available']))
                    <div class="dash-empty">
                        <i class="ri-moon-line"></i>
                        <p>{{ $prayer['reason'] ?? 'Jadwal shalat belum tersedia' }}</p>
                    </div>
                @else
                    <div class="d-flex justify-content-between align-items-baseline mb-3 gap-3">
                        <div class="small text-muted">
                            {{ $prayer['date_label'] }}
                            @if($prayer['hijri'])
                                <span class="d-block">{{ $prayer['hijri'] }}</span>
                            @endif
                        </div>
                        @if($nextPrayer)
                            <div class="text-end">
                                <span class="small text-muted">Berikutnya</span>
                                <span class="fw-semibold ms-1" style="color: var(--dash-navy-2);">
                                    {{ $nextPrayer['label'] }} {{ $nextPrayer['time'] }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="prayer-track">
                        @foreach($prayer['times'] as $time)
                            <div class="prayer-item {{ $time['is_next'] ? 'is-next' : ($time['is_past'] ? 'is-past' : '') }}">
                                <div class="prayer-label">{{ $time['label'] }}</div>
                                <div class="prayer-time">{{ $time['time'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="small text-muted mt-3">
                        Sumber {{ $prayer['source'] }} · diperbarui {{ now()->format('H:i') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
