@extends('layouts.master')
@section('title', 'Realisasi Pembelajaran')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Realisasi Pembelajaran @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Realisasi Pembelajaran</h4>
            <p class="text-muted mb-0 small">
                Jurnal pertemuan → realisasi TP/ATP → asesmen formatif/sumatif → Buku Administrasi (Leger &amp; Rapor).
            </p>
        </div>
        @if($isTeam)
            <span class="badge bg-primary-subtle text-primary p-2"><i class="ri-team-line me-1"></i> Tampilan Tim Kurikulum (semua kelas)</span>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId]) }}" class="row g-3 align-items-end">
                <div class="col-xxl-3 col-sm-6">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-6">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
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
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Buku Administrasi</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-primary">{{ $stats['books'] }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="ri-book-2-line text-primary"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Jurnal Pertemuan</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-info">{{ $stats['meetings'] }}</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="ri-article-line text-info"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">TP Terealisasi</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 text-success">{{ $stats['realized_tp'] }} <span class="fs-14 text-muted">/ {{ $stats['planned_tp'] }}</span></h4>
                        <div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="ri-check-double-line text-success"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0 fs-13">Progres Realisasi</p>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <h4 class="fs-22 fw-semibold mb-0 {{ $stats['progress'] >= 100 ? 'text-success' : 'text-warning' }}">{{ $stats['progress'] }}%</h4>
                        <div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="ri-line-chart-line text-warning"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-article-line text-primary me-1"></i> Realisasi per Buku Administrasi (Kelas &amp; Mapel)</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $rows->count() }} buku</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mapel</th>
                        <th>Kelas</th>
                        <th>Guru</th>
                        <th style="width:200px">Realisasi TP</th>
                        <th class="text-center">Jurnal</th>
                        <th class="text-center">Formatif</th>
                        <th class="text-center">Sumatif</th>
                        <th class="text-end" style="width:230px">Tindak Lanjut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $book = $row->book; @endphp
                        <tr>
                            <td class="fw-medium">{{ $book->subject?->name ?? '-' }}</td>
                            <td>
                                {{ $book->studyGroup?->name ?? '-' }}
                                @if($book->studyGroup?->gradeLevel?->fase)
                                    <span class="badge bg-info-subtle text-info ms-1">{{ $book->studyGroup->gradeLevel->fase }}</span>
                                @endif
                            </td>
                            <td class="small">{{ $book->teacher?->name ?? '—' }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px">
                                        <div class="progress-bar {{ $row->progress >= 100 ? 'bg-success' : 'bg-warning' }}" style="width: {{ min(100, $row->progress) }}%"></div>
                                    </div>
                                    <span class="small text-muted" style="white-space:nowrap">{{ $row->realized_tp }}/{{ $row->planned_tp }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $row->meetings > 0 ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary' }}">{{ $row->meetings }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $row->formatif > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row->formatif }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $row->sumatif > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row->sumatif }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $book->id]) }}" class="btn btn-sm btn-soft-primary" title="Jurnal">
                                    <i class="ri-article-line"></i> Jurnal
                                </a>
                                <a href="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $book->id]) }}" class="btn btn-sm btn-soft-success" title="Asesmen Formatif">
                                    <i class="ri-draft-line"></i>
                                </a>
                                <a href="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $book->id]) }}" class="btn btn-sm btn-soft-warning" title="Nilai Sumatif">
                                    <i class="ri-file-list-3-line"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-article-line fs-1 d-block mb-2"></i>
                                    Belum ada Buku Administrasi (buku nilai) pada tahun ajaran/semester ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-route-line text-primary me-1"></i> Cakupan ATP (lintas kelas)</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $atpRows->count() }} ATP</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mapel</th>
                        <th>Jenjang / Fase</th>
                        <th class="text-center">Kelas Memakai</th>
                        <th style="width:220px">TP Terealisasi</th>
                        <th class="text-center">Jurnal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($atpRows as $row)
                        <tr>
                            <td class="fw-medium">{{ $row->atp->subject?->name ?? '-' }}</td>
                            <td>
                                {{ $row->atp->gradeLevel?->name ?? 'Semua Jenjang' }}
                                @if($row->atp->fase) <span class="badge bg-info-subtle text-info ms-1">{{ $row->atp->fase }}</span> @endif
                            </td>
                            <td class="text-center">{{ $row->classes }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px">
                                        <div class="progress-bar {{ $row->progress >= 100 ? 'bg-success' : 'bg-warning' }}" style="width: {{ min(100, $row->progress) }}%"></div>
                                    </div>
                                    <span class="small text-muted" style="white-space:nowrap">{{ $row->realized_tp }}/{{ $row->planned_tp }}</span>
                                </div>
                            </td>
                            <td class="text-center">{{ $row->meetings }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-route-line fs-1 d-block mb-2"></i>
                                    Belum ada ATP dengan kelas yang memakainya pada periode ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
