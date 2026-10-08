@extends('layouts.master')
@section('title', 'Kurikulum Pembelajaran')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $totalMapel = $rows->unique('subject_id')->count();
        $atpCount = $rows->whereNotNull('atp_id')->count();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Kurikulum Pembelajaran @endslot
    @endcomponent

    {{-- STATISTIK --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Minggu Efektif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($mingguEfektif) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>
                        {{ $ringkasan && ($ringkasan['is_persisted'] ?? false) ? 'Tersimpan dari Kalender' : 'Hitung langsung dari Kalender' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-calendar-2-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Hari Efektif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($ringkasan['total_hari_efektif'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Dari Pekan Efektif semester ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-book-2-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mapel Terdaftar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalMapel) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-stack-line me-1"></i>{{ $rows->count() }} baris jenjang × mapel</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-route-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">ATP Tersusun</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($atpCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Dari {{ $rows->count() }} kombinasi</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="kurikulumList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Peta Kurikulum per Jenjang &amp; Mapel</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $rows->count() }} baris</span>
                                <span class="text-muted small ms-2">Kalender → Pekan Efektif → JP Efektif → CP → TP → ATP.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                    <select name="academic_year_id" class="form-select" style="width:150px" onchange="this.form.submit()">
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="semester" class="form-select" style="width:110px" onchange="this.form.submit()">
                                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                                    </select>
                                    <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-apps-2-line align-bottom me-1"></i> Buka Modul
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}"><i class="ri-flag-2-line me-1"></i> CP</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.tp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-list-check-2 me-1"></i> TP</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-route-line me-1"></i> ATP</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.prota.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-calendar-schedule-line me-1"></i> PROTA</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-calendar-2-line me-1"></i> PROSEM</a></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-line-chart-line me-1"></i> Realisasi</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"><i class="ri-booklet-line me-1"></i> RPM / Perangkat</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'ganjil']) }}" class="filter-badge {{ $semester === 'ganjil' ? 'active' : '' }}">Ganjil</a>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'genap']) }}" class="filter-badge {{ $semester === 'genap' ? 'active' : '' }}">Genap</a>
                        <span class="text-muted small ms-2 me-2">·</span>
                        <span class="text-muted small">{{ $totalMapel }} mapel · {{ $atpCount }} ATP tersusun</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jenjang</th>
                                <th class="text-center">Fase</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-center">JP/Minggu</th>
                                <th class="text-center">JP Efektif</th>
                                <th class="text-center">CP</th>
                                <th class="text-center">TP</th>
                                <th>ATP</th>
                                <th class="text-center">Rombel</th>
                                <th class="text-center">Guru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td class="fw-medium">{{ $row['grade'] }}</td>
                                    <td class="text-center">
                                        @if($row['fase'])
                                            <span class="badge bg-info-subtle text-info">{{ $row['fase'] }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['subject'] }}</td>
                                    <td class="text-center">{{ $row['weekly_hours'] }}</td>
                                    <td class="text-center fw-semibold">{{ $row['jp_efektif'] }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $row['cp_count'] > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row['cp_count'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $row['tp_count'] > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row['tp_count'] }}</span>
                                    </td>
                                    <td>
                                        @if($row['atp_id'])
                                            <a href="{{ route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $row['atp_id']]) }}"
                                               class="badge {{ $row['atp_status'] === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} text-decoration-none">
                                                {{ \App\Models\AlurTujuanPembelajaran::STATUS_OPTIONS[$row['atp_status']] ?? $row['atp_status'] }} · {{ $row['atp_total_jp'] }} JP
                                            </a>
                                        @else
                                            <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester, 'subject_id' => $row['subject_id']]) }}"
                                               class="link-primary small text-decoration-underline">
                                                Susun ATP
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $row['rombel'] }}</td>
                                    <td class="text-center">{{ $row['guru'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-inbox-archive-line fs-1 d-block mb-2"></i>
                                            Belum ada mapel per jenjang. Lengkapi di
                                            <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="link-primary">Data Kelas</a>.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
