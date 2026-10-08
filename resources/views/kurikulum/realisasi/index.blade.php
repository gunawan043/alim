@extends('layouts.master')
@section('title', 'Realisasi Pembelajaran')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Realisasi Pembelajaran @endslot
    @endcomponent

    {{-- STATISTIK --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-book-2-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Buku Administrasi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['books']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Jurnal &amp; nilai guru mapel</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-article-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Jurnal Pertemuan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['meetings']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Pertemuan tercatat</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-check-double-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">TP Terealisasi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['realized_tp']) }}<small class="fw-normal text-muted ms-1 stat-label">/ {{ $stats['planned_tp'] }}</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-route-line me-1"></i>Realisasi TP/ATP</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title {{ $stats['progress'] >= 100 ? 'bg-success-subtle' : 'bg-warning-subtle' }} rounded fs-2">
                                <i class="ri-line-chart-line {{ $stats['progress'] >= 100 ? 'text-success' : 'text-warning' }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Progres Realisasi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['progress'] }}%</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Rata-rata seluruh buku</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="realisasiList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Realisasi per Buku Administrasi (Kelas &amp; Mapel)</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $rows->count() }} buku</span>
                                @if($isTeam)
                                    <span class="badge bg-info-subtle text-info ms-1"><i class="ri-team-line me-1"></i>Tim Kurikulum (semua kelas)</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" action="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                <select name="academic_year_id" class="form-select" style="width:150px" onchange="this.form.submit()">
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                <select name="semester" class="form-select" style="width:110px" onchange="this.form.submit()">
                                    <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                                <a href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                            </form>
                        </div>
                    </div>
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
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="cakupanAtpList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Cakupan ATP (lintas kelas)</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $atpRows->count() }} ATP</span>
                                <span class="text-muted small ms-2">Realisasi TP dari jurnal seluruh kelas pengguna ATP.</span>
                            </p>
                        </div>
                    </div>
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
        </div>
    </div>
@endsection
