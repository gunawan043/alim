@extends('layouts.master')

@section('title', 'Dashboard Koordinator Kurikulum')

@section('css')
<style>
.stat-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.quick-action-btn { transition: all 0.2s ease; border: 1px solid #e2e5e8; }
.quick-action-btn:hover { transform: translateY(-2px); border-color: #0d6efd; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') Koordinator Kurikulum @endslot
@endcomponent

<div class="row g-3 mb-3">
    <x-dashboards.stat-card label="Total Mapel" :value="$totalMapel" icon="ri-book-line" color="primary" />
    <x-dashboards.stat-card label="Progress Silabus" :value="$progressSilabus . '%'" icon="ri-percent-line" color="success" />
    <x-dashboards.stat-card label="Paket Soal Pending" :value="$paketSoalPending" icon="ri-file-list-3-line" color="warning" />
    <x-dashboards.stat-card label="Ujian Mendatang (14 Hari)" :value="$ujianMendatang" icon="ri-calendar-schedule-line" color="info" />
</div>

<div class="row g-3 mb-3">
    {{-- TRACKER SILABUS --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Tracker Realisasi Silabus per Mapel</h5>
            </div>
            <div class="card-body">
                @foreach(\App\Models\Subject::take(6)->get() as $mapel)
                @php
                    $score = \App\Models\SubjectKktp::where('subject_id', $mapel->id)
                        ->where('academic_year_id', $academicYear?->id)
                        ->avg('kktp_score') ?? 0;
                    $score = round($score);
                    $barColor = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
                @endphp
                <div class="d-flex align-items-center mb-2">
                    <span class="text-muted me-3" style="width:160px; font-size:13px;">{{ $mapel->name }}</span>
                    <div class="progress flex-grow-1" style="height:10px;">
                        <div class="progress-bar bg-{{ $barColor }}" style="width: {{ $score }}%"></div>
                    </div>
                    <span class="ms-2 fw-bold small" style="width:40px;">{{ $score }}%</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- AKSI CEPAT --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('user.kktp.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-primary">
                        <i class="ri-file-text-line me-1"></i>Manage KKTP / Silabus
                    </a>
                    <a href="{{ route('user.evalusi-bank-soal.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-warning">
                        <i class="ri-question-line me-1"></i>Review Bank Soal
                    </a>
                    <a href="{{ route('user.jadwal-kbm.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-success">
                        <i class="ri-calendar-line me-1"></i>Setup Jadwal Ujian
                    </a>
                    <a href="{{ route('user.kaldik.index', ['userId' => $user->id]) }}" class="btn quick-action-btn btn-outline-info">
                        <i class="ri-calendar-event-line me-1"></i>Kalender Akademik
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- SOAL PENDING REVIEW --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Soal Pending Review</h5>
                <span class="badge bg-warning">{{ $paketSoalPending }}</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($soalReview as $soal)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ Str::limit($soal->soal ?? 'Soal tanpa teks', 50) }}</strong>
                            <br><small class="text-muted">{{ $soal->paketSoal?->nama ?? 'Tanpa Paket' }}</small>
                        </div>
                        <a href="{{ route('user.evalusi-soal.show', ['userId' => $user->id, 'soal' => $soal->id]) }}" class="btn btn-sm btn-outline-primary">Review</a>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Tidak ada soal pending review</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- KALENDER AKADEMIK --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="card-title mb-0">Agenda Akademik Mendatang</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($calendarEvents as $event)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $event->name }}</strong>
                            <br><small class="text-muted">{{ $event->start_date?->format('d M Y') }} · {{ $event->category ?? 'General' }}</small>
                        </div>
                        <span class="badge bg-primary">{{ $event->start_date?->diffForHumans() }}</span>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Belum ada agenda akademik</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
