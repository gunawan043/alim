@php
    $agenda = $data['agenda'] ?? [];
    $priorities = $data['priorities'] ?? [];
    $toneMap = [
        'danger' => ['bg' => 'danger', 'text' => 'danger'],
        'warning' => ['bg' => 'warning', 'text' => 'warning'],
        'info' => ['bg' => 'info', 'text' => 'info'],
        'primary' => ['bg' => 'primary', 'text' => 'primary'],
        'success' => ['bg' => 'success', 'text' => 'success'],
    ];
@endphp

<div class="card h-100">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-0"><i class="ri-calendar-check-line me-2 text-primary"></i>Agenda Mendatang &amp; Prioritas</h5>
            <p class="text-muted small mb-0">30 hari ke depan dari jadwal pemeliharaan, work order, pertamanan, peminjaman, dan pengadaan.</p>
        </div>
        <a href="{{ route('sarpras.workflow.index') }}" class="btn btn-sm btn-outline-primary">Buka Alur Kerja</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-xl-7">
                <h6 class="fw-semibold mb-2"><i class="ri-time-line me-1 text-primary"></i>Agenda Terdekat</h6>
                @forelse($agenda as $item)
                    <div class="d-flex gap-2 align-items-start border-bottom py-2">
                        <span class="avatar-xs flex-shrink-0">
                            <span class="avatar-title rounded-circle bg-{{ $item['tone'] }}-subtle text-{{ $item['tone'] }} fs-14">
                                <i class="{{ $item['icon'] }}"></i>
                            </span>
                        </span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap justify-content-between gap-1">
                                <span class="fw-medium text-truncate">{{ $item['title'] }}</span>
                                <span class="badge bg-light text-dark border">{{ $item['date']->format('d/m/Y') }}</span>
                            </div>
                            <div class="text-muted small text-truncate">
                                {{ $item['category'] }} · {{ $item['target'] }}
                                @if(! empty($item['meta'])) · {{ $item['meta'] }} @endif
                            </div>
                        </div>
                        <a href="{{ $item['url'] }}" class="btn btn-sm btn-light border flex-shrink-0"><i class="ri-arrow-right-line"></i></a>
                    </div>
                @empty
                    <div class="alert alert-light border mb-0 small"><i class="ri-information-line me-1"></i>Belum ada agenda dalam 30 hari ke depan.</div>
                @endforelse
            </div>

            <div class="col-12 col-xl-5">
                <h6 class="fw-semibold mb-2"><i class="ri-alert-line me-1 text-danger"></i>Prioritas</h6>
                @forelse($priorities as $priority)
                    @php $tone = $toneMap[$priority['tone']] ?? $toneMap['info']; @endphp
                    <a href="{{ $priority['url'] }}" class="d-flex align-items-center gap-2 border rounded p-2 mb-2 text-decoration-none text-reset">
                        <span class="avatar-sm flex-shrink-0">
                            <span class="avatar-title rounded-circle bg-{{ $tone['bg'] }}-subtle text-{{ $tone['text'] }} fs-16">
                                <i class="{{ $priority['icon'] }}"></i>
                            </span>
                        </span>
                        <span class="flex-grow-1 min-w-0">
                            <span class="d-block fw-medium text-truncate">{{ $priority['label'] }}</span>
                            <span class="d-block text-muted small text-truncate">{{ $priority['hint'] }}</span>
                        </span>
                        <span class="badge bg-{{ $tone['bg'] }} fs-13">{{ $priority['count'] }}</span>
                    </a>
                @empty
                    <div class="alert alert-success-subtle border-0 mb-0 small"><i class="ri-checkbox-circle-line me-1"></i>Tidak ada prioritas mendesak saat ini.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
