@extends('layouts.master')

@section('title', 'Departemen Bahasa')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') Departemen Bahasa @endslot
        @slot('li_2') Dashboard @endslot
        @slot('title') Departemen Bahasa @endslot
    @endcomponent

    <div class="lang-hero mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative">
            <div>
                <h3 class="mb-1">Qismul Lughah</h3>
                <p>Pengawasan program bahasa: mufradat pekanan, pembinaan pelanggaran, dan AM Nasyath Syahri.</p>
                @if($activeYear)
                    <small class="text-white-50">Tahun ajaran {{ $activeYear->name }} · {{ $activeYear->semester }}</small>
                @endif
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.bahasa.mufradat.create', ['userId' => $userId]) }}" class="btn btn-light">
                    <i class="ri-add-line me-1"></i> Periode Mufradat
                </a>
                <a href="{{ route('user.bahasa.violations.create', ['userId' => $userId]) }}" class="btn btn-outline-light">
                    <i class="ri-error-warning-line me-1"></i> Catat Pelanggaran
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="lang-stat">
                <div class="label">Periode Mufradat</div>
                <div class="value">{{ $stats['periods'] }}</div>
                <div class="hint">{{ $stats['vocabularies'] }} kosakata pada periode aktif</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="lang-stat">
                <div class="label">Setoran Bulan Ini</div>
                <div class="value">{{ number_format($stats['setoran_month']) }}</div>
                <div class="hint">Catatan setoran hafalan mufradat</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="lang-stat">
                <div class="label">Pelanggaran Bulan Ini</div>
                <div class="value">{{ number_format($stats['violations_month']) }}</div>
                <div class="hint">{{ number_format($stats['points_month']) }} poin tercatat</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="lang-stat">
                <div class="label">Kegiatan Bahasa</div>
                <div class="value">{{ number_format($stats['activities_month']) }}</div>
                <div class="hint">AM Nasyath Syahri bulan ini</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Periode Mufradat</h5>
                        <div class="lang-muted">Daftar kosakata dan jadwal setoran pekanan</div>
                    </div>
                    <a href="{{ route('user.bahasa.mufradat.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Periode</th>
                                <th>Rentang</th>
                                <th class="text-center">Kosakata</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periods->take(6) as $period)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.bahasa.mufradat.show', ['userId' => $userId, 'period' => $period->id]) }}" class="fw-semibold text-reset">
                                            {{ $period->title }}
                                        </a>
                                        <div class="lang-muted">{{ $period->location_dorm ?: $period->location_school ?: 'Lokasi belum diisi' }}</div>
                                    </td>
                                    <td class="lang-muted">
                                        {{ $period->period_start?->format('d/m/Y') ?? '-' }} — {{ $period->period_end?->format('d/m/Y') ?? '-' }}
                                    </td>
                                    <td class="text-center">{{ $period->vocabularies_count }}</td>
                                    <td class="text-center">
                                        <span class="badge lang-badge {{ $period->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $period->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada periode mufradat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Pelanggaran Terbaru</h5>
                        <div class="lang-muted">Catatan bahasa dan pembinaan</div>
                    </div>
                    <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">Semua</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($recentViolations as $violation)
                        <a class="list-group-item list-group-item-action" href="{{ route('user.bahasa.violations.show', ['userId' => $userId, 'violation' => $violation->id]) }}">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $violation->student?->name ?? 'Santri' }}</div>
                                    <div class="lang-muted">{{ $violation->violation_type }} · {{ $violation->violation_date?->format('d/m/Y') }}</div>
                                </div>
                                <span class="badge lang-badge bg-warning-subtle text-warning align-self-start">{{ $violation->points }} poin</span>
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Belum ada pelanggaran.</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">AM Nasyath Syahri</h5>
                        <div class="lang-muted">Kegiatan bahasa bulanan</div>
                    </div>
                    <a href="{{ route('user.bahasa.activities.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">Semua</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($recentActivities as $activity)
                        <a class="list-group-item list-group-item-action" href="{{ route('user.bahasa.activities.show', ['userId' => $userId, 'activity' => $activity->id]) }}">
                            <div class="fw-semibold">{{ $activity->title }}</div>
                            <div class="lang-muted">{{ $activity->activity_type_label }} · {{ $activity->activity_date?->format('d/m/Y') ?? 'Belum dijadwalkan' }}</div>
                        </a>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Belum ada kegiatan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
