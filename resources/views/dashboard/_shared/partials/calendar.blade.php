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
        <div class="card h-100 dash-animate" data-dash-animate>
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-calendar-event-line me-1 text-primary"></i>
                    Kalender Pendidikan · {{ $kaldik['month_label'] }}
                </h5>
                <span class="badge bg-primary-subtle text-primary">{{ $kaldik['total'] }} kegiatan</span>
            </div>
            <div class="card-body" style="max-height:340px;overflow:auto;">
                @forelse($items as $item)
                    <div class="d-flex align-items-start gap-2 border-bottom py-2 kaldik-row">
                        <span class="kaldik-dot" style="background: {{ $item['color'] }}"></span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold d-flex flex-wrap align-items-center gap-2">
                                {{ $item['name'] }}
                                @if($item['is_ongoing'])
                                    <span class="badge bg-success-subtle text-success">Berlangsung</span>
                                @elseif($item['is_past'])
                                    <span class="badge bg-secondary-subtle text-secondary">Selesai</span>
                                @elseif($item['days_left'] !== null && $item['days_left'] <= 7)
                                    <span class="badge bg-warning-subtle text-warning">{{ $item['days_left'] }} hari lagi</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                <i class="ri-calendar-2-line me-1"></i>{{ $item['date_label'] }}
                                @if($item['type'])
                                    · <span class="text-capitalize">{{ $item['type'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="ri-calendar-2-line fs-2 d-block mb-1"></i>
                        Tidak ada kegiatan kaldik pada {{ $kaldik['month_label'] }}.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- JADWAL SHALAT --}}
    <div class="col-xl-6">
        <div class="card h-100 dash-animate" data-dash-animate>
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-moon-clear-line me-1 text-primary"></i>Jadwal Shalat
                </h5>
                @if(! empty($prayer['city']))
                    <span class="badge bg-info-subtle text-info"><i class="ri-map-pin-line me-1"></i>{{ $prayer['city'] }}</span>
                @endif
            </div>
            <div class="card-body">
                @if(empty($prayer['available']))
                    <div class="text-center text-muted py-5">
                        <i class="ri-moon-line fs-2 d-block mb-1"></i>
                        {{ $prayer['reason'] ?? 'Jadwal shalat belum tersedia.' }}
                    </div>
                @else
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="small text-muted">
                            <i class="ri-calendar-line me-1"></i>{{ $prayer['date_label'] }}
                            @if($prayer['hijri'])
                                <span class="d-block">{{ $prayer['hijri'] }}</span>
                            @endif
                        </div>
                        @if($nextPrayer)
                            <div class="text-end">
                                <div class="text-muted small">Shalat berikutnya</div>
                                <div class="fw-bold text-primary">{{ $nextPrayer['label'] }} · {{ $nextPrayer['time'] }}</div>
                            </div>
                        @endif
                    </div>
                    <div class="prayer-grid">
                        @foreach($prayer['times'] as $time)
                            <div class="prayer-item {{ $time['is_next'] ? 'is-next' : ($time['is_past'] ? 'is-past' : '') }}">
                                <div class="prayer-label">{{ $time['label'] }}</div>
                                <div class="prayer-time">{{ $time['time'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-3">
                        Sumber: {{ $prayer['source'] }} · diperbarui {{ now()->format('H:i') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
