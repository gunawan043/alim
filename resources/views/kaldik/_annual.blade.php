{{-- Tampilan KALENDER TAHUNAN: 12 bulan penuh dengan penanda kegiatan. --}}
@php
    $weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Ahd'];
    $todayStr = now()->toDateString();
@endphp

<div class="row g-3">
    @foreach($annualMonths as $month)
        @php
            $start = $month['start'];
            $leadingBlanks = $start->dayOfWeekIso - 1; // Senin = 1 → 0 blank
            $daysInMonth = $start->daysInMonth;
        @endphp
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card card-animate h-100 mb-0">
                <div class="card-header border-bottom-dashed py-2">
                    <button type="button" class="btn btn-link p-0 fw-semibold text-decoration-none"
                            onclick="kaldikOpenMonth('{{ $month['ym'] }}')">
                        <i class="ri-calendar-line me-1"></i>{{ $month['label'] }}
                    </button>
                </div>
                <div class="card-body p-2">
                    <div class="d-grid" style="grid-template-columns: repeat(7, 1fr); gap: 2px; font-size: .68rem; text-align: center;">
                        @foreach($weekdayLabels as $wd)
                            <div class="text-muted fw-semibold">{{ $wd }}</div>
                        @endforeach

                        @for($i = 0; $i < $leadingBlanks; $i++)
                            <div></div>
                        @endfor

                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php
                                $dateStr = $start->copy()->day($d)->toDateString();
                                $dayEvents = $eventsByDate[$dateStr] ?? [];
                                $isToday = $dateStr === $todayStr;
                                $titles = implode(' • ', array_slice(array_column($dayEvents, 'title'), 0, 4));
                            @endphp
                            <div class="rounded d-flex flex-column align-items-center justify-content-center py-1 {{ $isToday ? 'bg-primary-subtle fw-bold' : '' }}"
                                 title="{{ $titles }}"
                                 style="min-height: 22px; {{ $dayEvents ? 'cursor: help;' : '' }}">
                                <span class="{{ ! $dayEvents ? 'text-muted' : '' }}">{{ $d }}</span>
                                @if($dayEvents)
                                    <span class="d-flex gap-1 mt-1 align-items-center">
                                        @foreach(array_slice($dayEvents, 0, 3) as $ev)
                                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $ev['color'] ?: '#64748b' }};display:inline-block;"></span>
                                        @endforeach
                                        @if(count($dayEvents) > 3)
                                            <span class="text-muted" style="font-size:.6rem;">+{{ count($dayEvents) - 3 }}</span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
