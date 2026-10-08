@extends('layouts.master')
@section('title', 'Review Soal Serumpun')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Evaluasi @endslot
        @slot('title') Review Soal Serumpun @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line text-warning"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Menunggu Review</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['pending'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Perlu keputusan Anda</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-check-double-line text-success"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Sudah Disetujui</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['approved'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Keputusan Anda</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-error-warning-line text-danger"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium fw-medium text-muted mb-0 stat-label">Perlu Perbaikan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['revision'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Menunggu revisi penulis</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0">Inbox Review — Guru Serumpun Lintas Satuan Pendidikan</h5>
            <p class="text-muted mb-0 small">Reviewer ditetapkan berdasarkan kesamaan mapel/jenjang/tahun ajaran, bukan sekolah yang sama.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Jenis</th>
                        <th>Konteks</th>
                        <th>Ringkas</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                        @php
                            $reviewable = $assignment->reviewable;
                            $isSoal = $assignment->reviewable_type === \App\Models\Soal::class;
                            $meta = ['pending' => ['Menunggu', 'bg-warning-subtle text-warning'], 'approved' => ['Disetujui', 'bg-success-subtle text-success'], 'revision' => ['Perlu Perbaikan', 'bg-danger-subtle text-danger']][$assignment->status] ?? ['-', 'bg-secondary-subtle text-secondary'];
                        @endphp
                        <tr>
                            <td><span class="badge {{ $isSoal ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">{{ $isSoal ? 'Soal' : 'Paket Soal' }}</span></td>
                            <td class="small">
                                @if($isSoal)
                                    {{ $reviewable?->bankSoal?->subject?->name ?? '—' }}
                                    · {{ $reviewable?->bankSoal?->gradeLevel?->name ?? 'Semua Kelas' }}
                                @else
                                    {{ $reviewable?->kisiKisi?->subject?->name ?? '—' }}
                                    · {{ $reviewable?->kisiKisi?->gradeLevel?->name ?? 'Semua Kelas' }}
                                @endif
                            </td>
                            <td class="small">{{ \Illuminate\Support\Str::limit(strip_tags($isSoal ? ($reviewable?->pertanyaan ?? '') : ($reviewable?->judul ?? '')), 120) }}</td>
                            <td class="text-center"><span class="badge {{ $meta[1] }}">{{ $meta[0] }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('user.review-soal.show', ['userId' => $userId, 'assignmentId' => $assignment->id]) }}" class="btn btn-sm btn-soft-primary">
                                    <i class="ri-eye-line"></i> Tinjau
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="ri-inbox-line fs-1 d-block mb-2"></i>Belum ada tugas review.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
