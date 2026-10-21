@extends('layouts.master')
@section('title', 'Ruang Kerja OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Ringkasan @endslot
        @endcomponent

        @include('osipp._header')

        @if(! $period)
            <div class="card">
                <div class="card-body osipp-empty">
                    <i class="ri-organization-chart d-block mb-2"></i>
                    <h5 class="mb-2">Belum ada periode kepengurusan OSIPP</h5>
                    <p class="text-muted mb-3">Buat periode terlebih dahulu untuk mulai mendata pengurus, program kerja, kegiatan, administrasi, pembinaan, dan evaluasi.</p>
                    @if($canManage)
                        <a href="{{ route('osipp.periode.index', ['userId' => $userId]) }}" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i>Buat Periode Kepengurusan
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="avatar-title bg-primary-subtle rounded fs-4"><i class="ri-group-line text-primary"></i></span>
                                <p class="text-uppercase fw-semibold text-muted mb-0 stat-label">Pengurus Aktif</p>
                            </div>
                            <h3 class="fw-bold mb-1">{{ number_format($stats['members']) }}</h3>
                            <p class="text-muted small mb-0">{{ number_format($stats['core_members']) }} pengurus inti · {{ number_format($stats['qisms'] ?? 0) }} Qism · {{ $period->name }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="avatar-title bg-info-subtle rounded fs-4"><i class="ri-list-check-2 text-info"></i></span>
                                <p class="text-uppercase fw-semibold text-muted mb-0 stat-label">Program Kerja</p>
                            </div>
                            <h3 class="fw-bold mb-1">{{ number_format($stats['programs']) }}</h3>
                            <p class="text-muted small mb-0">{{ number_format($stats['programs_running']) }} berjalan · {{ number_format($stats['programs_completed']) }} selesai</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="avatar-title bg-warning-subtle rounded fs-4"><i class="ri-flag-line text-warning"></i></span>
                                <p class="text-uppercase fw-semibold text-muted mb-0 stat-label">Kegiatan</p>
                            </div>
                            <h3 class="fw-bold mb-1">{{ number_format($stats['activities']) }}</h3>
                            <p class="text-muted small mb-0">
                                <span class="badge bg-info-subtle text-info">{{ number_format($stats['activities_pending']) }} menunggu verifikasi</span>
                                <span class="ms-1">{{ number_format($stats['activities_completed']) }} selesai</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="avatar-title bg-success-subtle rounded fs-4"><i class="ri-folder-shield-2-line text-success"></i></span>
                                <p class="text-uppercase fw-semibold text-muted mb-0 stat-label">Administrasi</p>
                            </div>
                            <h3 class="fw-bold mb-1">{{ number_format($stats['documents']) }}</h3>
                            <p class="text-muted small mb-0">{{ number_format($stats['documents_pending']) }} menunggu verifikasi · {{ number_format($stats['coachings']) }} pembinaan</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-7">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0"><i class="ri-todo-line me-1 text-info"></i>Menunggu Verifikasi</h5>
                            <a href="{{ route('osipp.kegiatan.index', ['userId' => $userId, 'period_id' => $period->id, 'status' => 'submitted']) }}" class="btn btn-sm btn-outline-info">Semua Kegiatan</a>
                        </div>
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small mb-2">Kegiatan</h6>
                            @forelse($pendingActivities as $activity)
                                <div class="osipp-soft-row d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <div>
                                        <div class="fw-semibold">{{ $activity->title }}</div>
                                        <div class="text-muted small">
                                            {{ $activity->activity_date?->translatedFormat('d M Y') }}
                                            @if($activity->program) · {{ $activity->program->name }} @endif
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('osipp.kegiatan.verify', ['userId' => $userId, 'activity' => $activity->id]) }}">
                                        @csrf
                                        <input type="hidden" name="period_id" value="{{ $period->id }}">
                                        <button class="btn btn-sm btn-success" type="submit"><i class="ri-check-line me-1"></i>Verifikasi</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-muted small mb-3">Tidak ada kegiatan yang menunggu verifikasi.</p>
                            @endforelse

                            <h6 class="text-muted text-uppercase small mb-2 mt-3">Administrasi</h6>
                            @forelse($pendingDocuments as $document)
                                <div class="osipp-soft-row d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <div>
                                        <div class="fw-semibold">{{ $document->title }}</div>
                                        <div class="text-muted small">{{ $document->type_label }} · {{ $document->created_at?->translatedFormat('d M Y') }}</div>
                                    </div>
                                    <a href="{{ route('osipp.administrasi.index', ['userId' => $userId, 'period_id' => $period->id, 'status' => 'submitted']) }}" class="btn btn-sm btn-outline-success">Tinjau</a>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Tidak ada administrasi yang menunggu verifikasi.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0"><i class="ri-list-check me-1 text-primary"></i>Progres Program</h5>
                            <a href="{{ route('osipp.program.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
                        </div>
                        <div class="card-body">
                            @forelse($programs as $program)
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                        <span class="fw-semibold text-truncate">{{ $program->name }}</span>
                                        <span class="text-muted small">{{ $program->progress }}%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-{{ $program->status_badge }}" role="progressbar" style="width: {{ $program->progress }}%" aria-valuenow="{{ $program->progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-muted small mt-1">{{ $program->status_label }} · {{ $program->activities_count }} kegiatan</div>
                                </div>
                            @empty
                                <div class="osipp-empty"><i class="ri-list-check-2 d-block mb-2"></i><p class="text-muted mb-0">Belum ada program kerja.</p></div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0"><i class="ri-calendar-event-line me-1 text-warning"></i>Agenda Mendatang</h5>
                            <a href="{{ route('osipp.kegiatan.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-outline-warning">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            @forelse($upcomingActivities as $activity)
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="text-center flex-shrink-0" style="width: 46px;">
                                        <div class="fw-bold fs-5">{{ $activity->activity_date?->format('d') }}</div>
                                        <div class="text-muted text-uppercase" style="font-size: 10px;">{{ $activity->activity_date?->translatedFormat('M') }}</div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $activity->title }}</div>
                                        <div class="text-muted small">
                                            {{ $activity->location ?: 'Lokasi belum ditentukan' }}
                                            @if($activity->program) · {{ $activity->program->name }} @endif
                                        </div>
                                    </div>
                                    <span class="badge bg-{{ $activity->status_badge }}">{{ $activity->status_label }}</span>
                                </div>
                            @empty
                                <div class="osipp-empty"><i class="ri-calendar-line d-block mb-2"></i><p class="text-muted mb-0">Belum ada agenda mendatang.</p></div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0"><i class="ri-award-line me-1 text-success"></i>Evaluasi Terakhir</h5>
                            <a href="{{ route('osipp.evaluasi.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-outline-success">Buka</a>
                        </div>
                        <div class="card-body">
                            @if($latestEvaluation)
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="avatar-lg">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">{{ $latestEvaluation->grade ?: '–' }}</span>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">{{ number_format((float) $latestEvaluation->total_score, 2) }}<small class="text-muted fs-12">/100</small></h4>
                                        <div class="text-muted small">{{ $latestEvaluation->type_label }} · {{ $latestEvaluation->status_label }}</div>
                                    </div>
                                </div>
                                <p class="text-muted small mb-1"><strong>Kekuatan:</strong> {{ \Illuminate\Support\Str::limit($latestEvaluation->strengths ?: 'Belum diisi', 130) }}</p>
                                <p class="text-muted small mb-0"><strong>Rekomendasi:</strong> {{ \Illuminate\Support\Str::limit($latestEvaluation->recommendations ?: 'Belum diisi', 130) }}</p>
                            @else
                                <div class="osipp-empty"><i class="ri-file-chart-line d-block mb-2"></i><p class="text-muted mb-0">Belum ada evaluasi kepengurusan.</p></div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0"><i class="ri-team-line me-1 text-primary"></i>Pengurus Inti</h5>
                            <a href="{{ route('osipp.anggota.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-outline-primary">Kelola Anggota</a>
                        </div>
                        <div class="card-body">
                            @forelse($coreMembers as $member)
                                <span class="badge bg-light text-dark border me-1 mb-1 py-2 px-3">
                                    <span class="osipp-avatar me-1">{{ strtoupper(substr($member->student?->name ?? '?', 0, 1)) }}</span>
                                    {{ $member->student?->name ?? 'Santri' }} · {{ $member->position }}
                                </span>
                            @empty
                                <span class="text-muted small">Belum ada pengurus inti yang didata.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
