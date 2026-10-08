@extends('layouts.master')
@section('title') Kurikulum @endsection

@section('css')
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
        .badge-soft-info    { background: #e0f2fe; color: #075985; }
        .badge-soft-secondary { background: #e2e8f0; color: #334155; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Kurikulum Pembelajaran @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Kurikulum Pembelajaran</h4>
                <p class="text-muted small mb-0">
                    Kalender Pendidikan → Pekan Efektif → JP Efektif → CP → TP → ATP → Perangkat.
                    Seluruh angka dibaca dari sumber data yang sama.
                </p>
            </div>
            <form method="GET" action="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" class="d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small text-muted mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body py-3">
                    <div class="text-muted small">Minggu Efektif (Kalender)</div>
                    <h4 class="mb-0 text-success">{{ $mingguEfektif }}</h4>
                    @if($ringkasan && ! ($ringkasan['is_persisted'] ?? false))
                        <span class="badge badge-soft-warning mt-1" style="font-size:0.6rem">Belum digenerate resmi</span>
                    @else
                        <span class="badge badge-soft-success mt-1" style="font-size:0.6rem">Tersimpan</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body py-3">
                    <div class="text-muted small">Hari Efektif</div>
                    <h4 class="mb-0 text-primary">{{ $ringkasan['total_hari_efektif'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body py-3">
                    <div class="text-muted small">Mapel Terdaftar</div>
                    <h4 class="mb-0">{{ $rows->unique('subject_id')->count() }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body py-3">
                    <div class="text-muted small">ATP Tersusun</div>
                    <h4 class="mb-0">{{ $rows->whereNotNull('atp_id')->count() }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ri-book-2-line me-1"></i> Peta Kurikulum per Jenjang &amp; Mapel</h6>
            <div class="d-flex gap-1">
                <a href="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary">CP</a>
                <a href="{{ route('user.kurikulum.tp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-sm btn-outline-primary">TP</a>
                <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-sm btn-outline-primary">ATP</a>
                <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="btn btn-sm btn-outline-primary">Perangkat</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Jenjang</th>
                            <th>Fase</th>
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
                                <td>{{ $row['grade'] }}</td>
                                <td>
                                    @if($row['fase'])
                                        <span class="badge badge-soft-info">{{ $row['fase'] }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="fw-medium">{{ $row['subject'] }}</td>
                                <td class="text-center">{{ $row['weekly_hours'] }}</td>
                                <td class="text-center fw-semibold">{{ $row['jp_efektif'] }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $row['cp_count'] > 0 ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $row['cp_count'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $row['tp_count'] > 0 ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $row['tp_count'] }}</span>
                                </td>
                                <td>
                                    @if($row['atp_id'])
                                        <a href="{{ route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $row['atp_id']]) }}"
                                           class="badge {{ $row['atp_status'] === 'published' ? 'badge-soft-success' : 'badge-soft-warning' }} text-decoration-none">
                                            {{ $row['atp_status'] === 'published' ? 'Terbit' : 'Draft' }} · {{ $row['atp_total_jp'] }} JP
                                        </a>
                                    @else
                                        <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester, 'subject_id' => $row['subject_id']]) }}"
                                           class="small text-decoration-none">
                                            <i class="ri-add-line"></i> Susun ATP
                                        </a>
                                    @endif
                                </td>
                                <td class="text-center">{{ $row['rombel'] }}</td>
                                <td class="text-center">{{ $row['guru'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Belum ada mapel per jenjang. Lengkapi di
                                    <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">Data Kelas</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
