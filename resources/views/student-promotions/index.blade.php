@extends('layouts.master')
@section('title') Promosi Santri @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Peserta Didik @endslot
        @slot('title') Promosi Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $total = $statistics['total'] ?? 0;
        $draft = $statistics['draft'] ?? 0;
        $completed = $statistics['completed'] ?? 0;
        $cancelled = $statistics['cancelled'] ?? 0;
        $pctCompleted = $total > 0 ? round($completed / $total * 100) : 0;
    @endphp

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-list-3-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Rencana</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($total) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Semua rencana promosi</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-draft-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Draft</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($draft) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Belum dieksekusi</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Selesai</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($completed) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>{{ $pctCompleted }}% dari total rencana</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-close-circle-line text-danger"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Dibatalkan/Gagal</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($cancelled) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tidak dieksekusi</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="promotionList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Promosi Santri</h5>
                            <p class="text-muted mb-0">Kelola kenaikan kelas, tinggal kelas, dan kelulusan massal.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.student-promotions.index', ['userId' => $userId]) }}"
                                      class="d-flex flex-wrap align-items-center gap-2">
                                    @if($statusFilter)
                                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                                    @endif
                                    <select name="academic_year" class="form-select" style="width:180px">
                                        <option value="">Semua Tahun Ajaran</option>
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ request('academic_year') == $ay->id ? 'selected' : '' }}>
                                                {{ $ay->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-filter-line align-bottom me-1"></i>Filter
                                    </button>
                                    <a href="{{ route('user.student-promotions.index', ['userId' => $userId]) }}"
                                       class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <a href="{{ route('user.student-promotions.create', ['userId' => $userId]) }}"
                                   class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Promosi Baru
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}"
                           class="filter-badge {{ !$statusFilter ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'draft', 'page' => null]) }}"
                           class="filter-badge {{ $statusFilter === 'draft' ? 'active' : '' }}">Draft</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'completed', 'page' => null]) }}"
                           class="filter-badge {{ $statusFilter === 'completed' ? 'active' : '' }}">Selesai</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'cancelled', 'page' => null]) }}"
                           class="filter-badge {{ $statusFilter === 'cancelled' ? 'active' : '' }}">Dibatalkan</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Rombel Asal</th>
                                    <th>Rombel Tujuan</th>
                                    <th>Tanggal Efektif</th>
                                    <th>Siswa</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($promotions as $promo)
                                    <tr>
                                        <td>{{ $loop->iteration + ($promotions->currentPage() - 1) * $promotions->perPage() }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $promo->fromAcademicYear?->name }}</span>
                                            <br>
                                            <small class="text-muted">→ {{ $promo->toAcademicYear?->name }}</small>
                                        </td>
                                        <td>{{ $promo->fromStudyGroup?->full_name ?? '-' }}</td>
                                        <td>{{ $promo->toStudyGroup?->full_name ?? '-' }}</td>
                                        <td>{{ $promo->promotion_date?->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $promo->total_students }}</span>
                                            @if($promo->success_count > 0)
                                                <span class="badge bg-success-subtle text-success">{{ $promo->success_count }} ✓</span>
                                            @endif
                                            @if($promo->failed_count > 0)
                                                <span class="badge bg-danger-subtle text-danger">{{ $promo->failed_count }} ✗</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $promo->status_badge_color }}-subtle text-{{ $promo->status_badge_color }}">
                                                {{ $promo->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('user.student-promotions.show', ['userId' => $userId, 'id' => $promo->id]) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="ri-arrow-up-line fs-1"></i>
                                            <p class="mb-0">Belum ada data promosi.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @include('shared._pagination', ['paginator' => $promotions])
                </div>
            </div>
        </div>
    </div>
@endsection
