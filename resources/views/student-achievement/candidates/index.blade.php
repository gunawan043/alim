@extends('layouts.master')
@section('title') Kandidat Mumtaz Murtafi' @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kesiswaan @endslot
        @slot('li_2')
            <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'akademik']) }}">
                Prestasi Santri
            </a>
        @endslot
        @slot('title') Kandidat Mumtaz Murtafi' @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line text-warning"></i></span>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Menunggu</p>
                            <h3 class="stat-value">{{ number_format($counts['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Belum ditinjau</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-check-double-line text-success"></i></span>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Sudah Diposting</p>
                            <h3 class="stat-value">{{ number_format($counts['posted'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-checkbox-circle-line me-1"></i>Sudah menjadi prestasi</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-secondary-subtle rounded">
                                <span class="avatar-title bg-secondary-subtle rounded fs-2"><i class="ri-close-circle-line text-secondary"></i></span>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Diabaikan</p>
                            <h3 class="stat-value">{{ number_format($counts['dismissed'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-close-line me-1"></i>Tidak diposting</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-user-star-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Kandidat</p>
                            <h3 class="stat-value">{{ number_format(array_sum($counts ?? [])) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-group-line me-1"></i>Seluruh kandidat</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD UTAMA --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Kandidat {{ \App\Models\MumtazMurtafiCandidate::statuses()[$status] ?? '' }}</h5>
                            <p class="text-muted mb-0 small">Daftar santri berpredikat Mumtaz Murtafi' dari rapor terbit.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <form method="POST"
                                      action="{{ route('user.student-achievement.candidates.sync', ['userId' => $userId]) }}"
                                      onsubmit="return confirm('Sinkronkan kandidat dari rapor yang sudah terbit pada tahun ajaran aktif?');"
                                      class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-warning btn-sm">
                                        <i class="ri-refresh-line me-1"></i> Sinkronkan Rapor Terbit
                                    </button>
                                </form>
                                <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'akademik']) }}"
                                   class="btn btn-outline-secondary btn-sm" title="Kembali ke Prestasi">
                                    <i class="ri-arrow-left-line me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:520px;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cari nama santri / NISN..." value="{{ request('search') }}">
                            <input type="hidden" name="status" value="{{ $status }}">
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#candidateFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['search', 'sort']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.student-achievement.candidates.index', ['userId' => $userId, 'status' => $status]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['search', 'sort']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('sort'))
                                <span class="filter-badge active"><i class="ri-sort-desc"></i> {{ request('sort') }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $candidates->count() }}</strong> dari
                            <strong>{{ $candidates->total() }}</strong> kandidat
                        </small>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Kelas</th>
                                    <th>Semester</th>
                                    <th class="text-center">Nilai</th>
                                    <th class="text-center">Peringkat</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($candidates as $i => $candidate)
                                    <tr>
                                        <td class="text-center text-muted">{{ $candidates->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $candidate->student?->name ?? '—' }}</div>
                                            <small class="text-muted">{{ $candidate->student?->nisn ?: $candidate->student?->nis ?: '—' }}</small>
                                        </td>
                                        <td>{{ $candidate->studyGroup?->full_name ?? '—' }}</td>
                                        <td>
                                            {{ $candidate->semester_label }}
                                            <div><small class="text-muted">{{ $candidate->academicYear?->name ?? '—' }}</small></div>
                                        </td>
                                        <td class="text-center fw-semibold">
                                            {{ $candidate->average_score !== null ? number_format((float) $candidate->average_score, 1) : '—' }}
                                        </td>
                                        <td class="text-center">{{ $candidate->class_rank ? '#'.$candidate->class_rank : '—' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $candidate->status_badge }}-subtle text-{{ $candidate->status_badge }}">
                                                {{ $candidate->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                @if($candidate->status === \App\Models\MumtazMurtafiCandidate::STATUS_PENDING)
                                                    <a href="{{ route('user.student-achievement.candidates.show', ['userId' => $userId, 'uuid' => $candidate->id]) }}"
                                                       class="btn btn-sm btn-outline-primary" title="Tinjau & Posting">
                                                        <i class="ri-send-plane-line me-1"></i> Tinjau
                                                    </a>
                                                @elseif($candidate->achievement)
                                                    <a href="{{ route('user.student-achievement.show', ['userId' => $userId, 'id' => $candidate->achievement->id, 'type' => 'akademik']) }}"
                                                       class="btn btn-sm btn-outline-primary" title="Lihat Prestasi">
                                                        <i class="ri-eye-line me-1"></i> Prestasi
                                                    </a>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Tidak ada kandidat"
                                        message="Tidak ada kandidat pada status ini." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($candidates->hasPages())
                        @include('shared._pagination', ['paginator' => $candidates->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL FILTER --}}
    <div class="modal fade" id="candidateFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.student-achievement.candidates.index', ['userId' => $userId]) }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Kandidat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama santri atau NISN...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-sort-desc"></i>Urutkan</div>
                                    <select name="sort" class="form-select">
                                        <option value="">Default</option>
                                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama (A–Z)</option>
                                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama (Z–A)</option>
                                        <option value="score_desc" {{ request('sort') === 'score_desc' ? 'selected' : '' }}>Nilai Tertinggi</option>
                                        <option value="score_asc" {{ request('sort') === 'score_asc' ? 'selected' : '' }}>Nilai Terendah</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.student-achievement.candidates.index', ['userId' => $userId, 'status' => $status]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection