@extends('layouts.master')

@section('title', 'Dashboard Satuan Keamanan')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('title') Dashboard @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Tamu di Dalam', 'value' => number_format($guestInside), 'icon' => 'ri-user-received-line', 'color' => 'primary'],
            ['label' => 'Santri di Luar', 'value' => number_format($studentsOutside), 'icon' => 'ri-walk-line', 'color' => 'warning'],
            ['label' => 'Insiden Terbuka', 'value' => number_format($openIncidents), 'icon' => 'ri-alert-line', 'color' => 'danger'],
            ['label' => 'Patroli Hari Ini', 'value' => $patrolDone.' / '.$patrolToday, 'icon' => 'ri-shield-star-line', 'color' => 'success'],
        ],
    ])
    @endcomponent

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <i class="ri-line-chart-line me-1 text-primary"></i> Kunjungan Tamu 7 Hari Terakhir
                    </h5>
                    <a href="{{ route('user.keamanan.reports.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">
                        Laporan <i class="ri-arrow-right-line ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-end justify-content-between gap-2" style="height: 200px;">
                        @foreach($guestTrend as $point)
                            @php $height = max(6, (int) round($point['value'] / $maxTrend * 100)); @endphp
                            <div class="d-flex flex-column align-items-center flex-grow-1">
                                <span class="small fw-semibold text-muted mb-1">{{ $point['value'] }}</span>
                                <div class="w-100 rounded-top bg-primary-subtle" style="height: {{ $height }}%; min-height: 6px; position: relative;">
                                    <div class="w-100 rounded-top bg-primary" style="position:absolute; bottom:0; height: {{ $height }}%; min-height: 4px;"></div>
                                </div>
                                <span class="small text-muted mt-2">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-shield-star-line me-1 text-success"></i> Patroli Hari Ini</h5>
                </div>
                <div class="card-body p-0">
                    @forelse($todayPatrols as $schedule)
                        <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $schedule->team->name ?? $schedule->code }}</div>
                                <div class="small text-muted">
                                    {{ $schedule->shift->label() ?? 'Tanpa shift' }}
                                    · {{ $schedule->logs->count() }} checkpoint
                                </div>
                            </div>
                            <span class="badge bg-{{ $schedule->statusColor() }}-subtle text-{{ $schedule->statusColor() }}">
                                {{ $schedule->statusLabel() }}
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="ri-calendar-todo-line fs-1 text-muted"></i>
                            <p class="text-muted mb-0 mt-2">Belum ada jadwal patroli hari ini.</p>
                            <a href="{{ route('user.keamanan.patrol.index', ['userId' => $userId]) }}" class="btn btn-sm btn-primary mt-2">
                                Buat Jadwal
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1"><i class="ri-alert-line me-1 text-danger"></i> Insiden Terbaru</h5>
                    <a href="{{ route('user.keamanan.incidents.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-secondary">Semua</a>
                </div>
                <div class="card-body p-0">
                    @forelse($recentIncidents as $incident)
                        <a href="{{ route('user.keamanan.incidents.show', ['userId' => $userId, 'incident' => $incident->id]) }}"
                           class="d-flex align-items-start gap-2 px-3 py-3 border-bottom text-body text-decoration-none">
                            <span class="avatar-xs flex-shrink-0">
                                <span class="avatar-title rounded-circle bg-{{ $incident->severityColor() }}-subtle text-{{ $incident->severityColor() }}">
                                    <i class="ri-alert-fill"></i>
                                </span>
                            </span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $incident->title }}</div>
                                <div class="small text-muted">
                                    {{ $incident->code }} · {{ $incident->type->name ?? 'Tanpa jenis' }}
                                    · {{ optional($incident->occurred_at)->translatedFormat('d M Y H:i') }}
                                </div>
                            </div>
                            <span class="badge bg-{{ $incident->statusColor() }}-subtle text-{{ $incident->statusColor() }}">
                                {{ $incident->statusLabel() }}
                            </span>
                        </a>
                    @empty
                        <x-empty-state title="Belum ada insiden" message="Insiden keamanan yang dilaporkan akan muncul di sini." />
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1"><i class="ri-user-received-line me-1 text-primary"></i> Tamu Sedang di Dalam</h5>
                    <a href="{{ route('user.keamanan.guests.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-secondary">Semua</a>
                </div>
                <div class="card-body p-0">
                    @forelse($guestInsideList as $guest)
                        <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $guest->visitor_name }}</div>
                                <div class="small text-muted">
                                    {{ $guest->purpose }} · {{ optional($guest->check_in_at)->format('H:i') }}
                                    · {{ $guest->post->name ?? 'Tanpa pos' }}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('user.keamanan.guests.checkout', ['userId' => $userId, 'guest' => $guest->id]) }}"
                                  data-confirm="Catat tamu {{ $guest->visitor_name }} sudah keluar?">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-soft-danger">
                                    <i class="ri-logout-box-line me-1"></i>Keluar
                                </button>
                            </form>
                        </div>
                    @empty
                        <x-empty-state title="Tidak ada tamu di dalam" message="Tamu yang tercatat masuk akan tampil di sini." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:11px;">Tamu Hari Ini</p>
                    <h3 class="fw-bold mb-1">{{ number_format($guestToday) }}</h3>
                    <p class="small text-muted mb-0"><i class="ri-logout-box-r-line me-1"></i>{{ $guestOutToday }} sudah keluar</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:11px;">Kendaraan di Dalam</p>
                    <h3 class="fw-bold mb-1">{{ number_format($vehicleInside) }}</h3>
                    <p class="small text-muted mb-0"><i class="ri-roadster-line me-1"></i>{{ $vehicleToday }} masuk hari ini</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:11px;">Santri Terlambat</p>
                    <h3 class="fw-bold mb-1 {{ $studentsLate > 0 ? 'text-danger' : '' }}">{{ number_format($studentsLate) }}</h3>
                    <a href="{{ route('user.keamanan.gate.index', ['userId' => $userId, 'tab' => 'late']) }}" class="small text-decoration-none">
                        Lihat daftar <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1" style="font-size:11px;">Insiden Berat/Kritis</p>
                    <h3 class="fw-bold mb-1 {{ $criticalIncidents > 0 ? 'text-danger' : '' }}">{{ number_format($criticalIncidents) }}</h3>
                    <p class="small text-muted mb-0"><i class="ri-team-line me-1"></i>{{ $teamCount }} regu aktif</p>
                </div>
            </div>
        </div>
    </div>
@endsection
