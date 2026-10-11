@extends('layouts.master')
@section('title', 'Master Rumpun Mata Pelajaran')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">Kurikulum</a> @endslot
        @slot('title') Master Rumpun Mata Pelajaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="alert alert-info small">
        <i class="ri-information-line me-1"></i>
        Rumpun adalah <strong>entitas resmi lembaga</strong>. Pemetaan mapel → rumpun bersumber dari halaman ini
        (master data), bukan dari pola nama. Keanggotaan guru mengikuti tugas mengajar aktif; koordinator ditetapkan
        melalui <strong>tugas tambahan per satuan pendidikan</strong> dan tidak otomatis memiliki hak approval final.
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-layout-grid-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Rumpun Resmi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $groups->count() }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Umum · Agama · Bahasa · Tahfidz</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-price-tag-3-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mapel Belum Dipetakan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($unmappedCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-error-warning-line me-1"></i>Petakan melalui halaman rumpun</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-user-star-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Koordinator Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $groups->sum(fn ($g) => $g['coordinators']->count()) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Lewat tugas tambahan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-share-forward-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Kelompok Mapel Lintas Satuan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $crossSubjectCount }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <a href="{{ route('user.kurikulum.rumpun.cross-school', ['userId' => $userId]) }}" class="link-primary">Lihat kolaborasi mapel</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach($groups as $row)
            @php $group = $row['group']; @endphp
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">
                                <i class="ri-folder-2-line text-primary me-1"></i>{{ $group->name }}
                                <span class="badge bg-dark-subtle text-dark ms-1">{{ $group->code }}</span>
                            </h5>
                            <p class="text-muted mb-0 small">Tugas koordinator: {{ $group->coordinator_task_name ?: '—' }}</p>
                        </div>
                        <a href="{{ route('user.kurikulum.rumpun.show', ['userId' => $userId, 'groupId' => $group->id]) }}" class="btn btn-sm btn-soft-primary">
                            Kelola
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <span class="badge bg-primary-subtle text-primary">{{ $row['subject_count'] }} mapel</span>
                            <span class="badge bg-info-subtle text-info">{{ $row['teacher_count'] }} guru pengampu</span>
                            <span class="badge bg-success-subtle text-success">{{ $row['coordinators']->count() }} koordinator</span>
                        </div>

                        @if($row['coordinators']->isEmpty())
                            <p class="text-muted small mb-0"><i class="ri-information-line me-1"></i>Belum ada koordinator aktif.</p>
                        @else
                            <ul class="list-unstyled mb-0 small">
                                @foreach($row['coordinators'] as $coordinator)
                                    <li class="d-flex justify-content-between border-bottom py-1">
                                        <span><i class="ri-user-star-line text-success me-1"></i>{{ $coordinator['name'] ?? '—' }}</span>
                                        <span class="text-muted">{{ $coordinator['school'] ?? '—' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
