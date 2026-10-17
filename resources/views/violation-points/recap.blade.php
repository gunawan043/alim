@extends('layouts.master')
@section('title') Rekap Poin Pelanggaran @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @php
        $severities = [
            ['min' => 25, 'subtle' => 'dark-subtle',    'text' => 'dark',    'icon' => 'ri-error-warning-fill', 'label' => 'Sangat Berat'],
            ['min' => 16, 'subtle' => 'danger-subtle',  'text' => 'danger',  'icon' => 'ri-close-circle-fill',  'label' => 'Berat'],
            ['min' => 6,  'subtle' => 'warning-subtle', 'text' => 'warning', 'icon' => 'ri-alert-fill',         'label' => 'Sedang'],
            ['min' => 1,  'subtle' => 'info-subtle',    'text' => 'info',    'icon' => 'ri-information-fill',   'label' => 'Ringan'],
        ];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Peserta Didik</a> @endslot
        @slot('li_2') <a href="{{ route('user.violation-points.dashboard', ['userId' => $userId]) }}">Dashboard Kedisiplinan</a> @endslot
        @slot('title') Rekap Poin per Santri @endslot
    @endcomponent

    {{-- Ringkasan --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-user-3-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Santri Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($summary['total_students']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh santri aktif</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-file-list-3-line text-danger"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Pelanggaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($summary['total_violations']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh catatan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-alert-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Poin</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($summary['total_points']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Akumulasi seluruh poin</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-group-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Santri Melanggar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($summary['students_with_violations']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>
                        {{ $summary['total_students'] > 0 ? round(($summary['students_with_violations'] / $summary['total_students']) * 100, 1) : 0 }}% dari total santri
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0">Rekap Poin Pelanggaran per Santri</h5>
                    <p class="text-muted mb-0 small">Daftar akumulasi poin pelanggaran setiap peserta didik.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('user.violation-points.dashboard', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-dashboard-2-line me-1"></i>Dashboard
                    </a>
                    <a href="{{ route('user.violation-points.export-pdf', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-outline-danger btn-sm">
                        <i class="ri-file-pdf-2-line me-1"></i>Ekspor PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label small text-muted mb-1">Cari Santri</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama atau NISN..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Rombel</label>
                    <select name="study_group_id" class="form-select form-select-sm">
                        <option value="">Semua Rombel</option>
                        @foreach($studyGroups as $sg)
                            <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small text-muted mb-1">Min. Poin</label>
                    <input type="number" name="min_points" class="form-control form-control-sm" placeholder="0" value="{{ request('min_points') }}" min="0">
                </div>
                <div class="col-lg-3 col-md-8">
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="ri-filter-3-line me-1"></i>Tampilkan</button>
                        <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}" class="btn btn-light btn-sm" title="Reset"><i class="ri-refresh-line"></i></a>
                    </div>
                </div>
            </form>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="text-muted small text-uppercase fw-medium">Tingkat:</span>
                <span class="badge bg-dark-subtle text-dark"><i class="ri-error-warning-fill me-1"></i>&ge; 25 Sangat Berat</span>
                <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-fill me-1"></i>16–24 Berat</span>
                <span class="badge bg-warning-subtle text-warning"><i class="ri-alert-fill me-1"></i>6–15 Sedang</span>
                <span class="badge bg-info-subtle text-info"><i class="ri-information-fill me-1"></i>1–5 Ringan</span>
                <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-fill me-1"></i>0 Tidak Ada</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px">#</th>
                            <th>Peserta Didik</th>
                            <th style="width:170px">Rombel</th>
                            <th class="text-center" style="width:100px">Jml. Pel.</th>
                            <th class="text-center" style="width:120px">Total Poin</th>
                            <th class="text-center" style="width:160px">Tingkat</th>
                            <th class="text-center" style="width:100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recaps as $i => $s)
                            @php
                                $pts = $s->violation_points_sum_points ?? 0;
                                $rombel = $s->studyGroups->first()?->studyGroup?->full_name ?? '-';
                                $words = preg_split('/\s+/', trim((string) $s->name)) ?: [];
                                $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1).(count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : ''));

                                $sev = ['subtle' => 'success-subtle', 'text' => 'success', 'icon' => 'ri-checkbox-circle-fill', 'label' => 'Tidak Ada'];
                                foreach ($severities as $t) {
                                    if ($pts >= $t['min']) { $sev = $t; break; }
                                }
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $recaps->firstItem() + $i }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle" style="font-size:.65rem;">{{ $initials }}</span>
                                        </div>
                                        <div>
                                            <span class="fw-semibold">{{ $s->name }}</span>
                                            @if($s->nisn)<small class="text-muted d-block">NISN: {{ $s->nisn }}</small>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="small">{{ $rombel }}</td>
                                <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">{{ $s->violation_points_count ?? 0 }}x</span></td>
                                <td class="text-center"><span class="badge bg-{{ $sev['subtle'] }} text-{{ $sev['text'] }} fw-semibold">{{ $pts }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $sev['subtle'] }} text-{{ $sev['text'] }}">
                                        <i class="{{ $sev['icon'] }} me-1"></i>{{ $sev['label'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('user.violation-points.recap.detail', ['userId' => $userId, 'studentUuid' => $s->id]) }}"
                                       class="btn btn-sm btn-soft-primary" title="Detail per santri">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7"
                                title="Tidak ada data ditemukan"
                                message="Ubah kata kunci pencarian atau filter yang digunakan." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('shared._pagination', ['paginator' => $recaps])
        </div>
    </div>
@endsection
