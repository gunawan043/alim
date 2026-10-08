@extends('layouts.master')
@section('title', 'Kurikulum Pembelajaran')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Kurikulum Pembelajaran @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Kurikulum Pembelajaran</h4>
            <p class="text-muted mb-0 small">
                Kalender Pendidikan → Pekan Efektif → JP Efektif → CP → TP → ATP → Perangkat.
                Seluruh angka dibaca dari sumber data yang sama.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">
                <i class="ri-flag-2-line align-bottom me-1"></i> CP
            </a>
            <a href="{{ route('user.kurikulum.tp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-soft-primary btn-sm">
                <i class="ri-list-check-2 align-bottom me-1"></i> TP
            </a>
            <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-soft-primary btn-sm">
                <i class="ri-route-line align-bottom me-1"></i> ATP
            </a>
            <a href="{{ route('user.kurikulum.prota.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-soft-primary btn-sm">
                <i class="ri-calendar-schedule-line align-bottom me-1"></i> PROTA
            </a>
            <a href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-soft-primary btn-sm">
                <i class="ri-calendar-2-line align-bottom me-1"></i> PROSEM
            </a>
            <a href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-soft-primary btn-sm">
                <i class="ri-line-chart-line align-bottom me-1"></i> Realisasi
            </a>
            <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-primary btn-sm">
                <i class="ri-booklet-line align-bottom me-1"></i> Perangkat
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" class="row g-3 align-items-end">
                <div class="col-xxl-3 col-sm-6">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-6">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <span class="badge {{ $ringkasan && ($ringkasan['is_persisted'] ?? false) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} p-2">
                        <i class="ri-calendar-check-line align-bottom me-1"></i>
                        Pekan Efektif: {{ $ringkasan && ($ringkasan['is_persisted'] ?? false) ? 'tersimpan dari Kalender' : 'hitung langsung dari Kalender' }}
                    </span>
                </div>
                <div class="col-xxl-2 col-sm-6">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line align-bottom me-1"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Minggu Efektif</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $mingguEfektif }}</h4>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="ri-calendar-check-line text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Hari Efektif</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $ringkasan['total_hari_efektif'] ?? 0 }}</h4>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                <i class="ri-calendar-2-line text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Mapel Terdaftar</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $rows->unique('subject_id')->count() }}</h4>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="ri-book-2-line text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 fs-13">ATP Tersusun</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $rows->whereNotNull('atp_id')->count() }}</h4>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                <i class="ri-route-line text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-map-2-line text-primary me-1"></i> Peta Kurikulum per Jenjang &amp; Mapel</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $rows->count() }} baris</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
@endsection
