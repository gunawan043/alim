@extends('layouts.master')

@section('title') Laporan Kelengkapan Administrasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">Analisis Akademik</a> @endslot
        @slot('title') Kelengkapan Administrasi Akademik @endslot
    @endcomponent

    @include('user.laporan.partials.academic-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">
            Checklist empat kewajiban per buku: perangkat/RPM, jurnal, presensi/realisasi, dan input nilai.
            Persentase menunjukkan komponen yang sudah ada, bukan penilaian kualitas dokumen.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.akademik.administrasi.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm">
                <i class="ri-file-excel-2-line me-1"></i>Excel
            </a>
            <a href="{{ route('user.laporan.akademik.administrasi.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="ri-file-pdf-2-line me-1"></i>PDF
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.akademik.administrasi', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $filters['academic_year_id'] === $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="ganjil" {{ $filters['semester'] === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $filters['semester'] === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Guru</label>
                    <select name="teacher_id" class="form-select">
                        <option value="">Semua guru</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $filters['teacher_id'] === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Kelas</label>
                    <select name="study_group_id" class="form-select">
                        <option value="">Semua kelas</option>
                        @foreach($studyGroups as $group)
                            <option value="{{ $group->id }}" {{ $filters['study_group_id'] === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Mapel</label>
                    <select name="subject_id" class="form-select">
                        <option value="">Semua mapel</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $filters['subject_id'] === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Buku</div><div class="fs-3 fw-bold">{{ number_format($report['summary']['total_books']) }}</div><div class="small text-muted">{{ number_format($report['summary']['total_teachers']) }} guru</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Kewajiban</div><div class="fs-3 fw-bold">{{ number_format($report['summary']['obligations']) }}</div><div class="small text-muted">4 komponen/buku</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Terpenuhi</div><div class="fs-3 fw-bold text-success">{{ number_format($report['summary']['completed']) }}</div><div class="small text-muted">{{ $report['summary']['percentage'] !== null ? number_format($report['summary']['percentage'], 2).'%' : '—' }}</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Buku Lengkap</div><div class="fs-3 fw-bold text-success">{{ number_format($report['summary']['complete_books']) }}</div><div class="small text-muted">4/4 komponen</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Belum Lengkap</div><div class="fs-3 fw-bold text-danger">{{ number_format($report['summary']['incomplete_books']) }}</div><div class="small text-muted">perlu tindak lanjut</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold">Nilai Belum</div><div class="fs-3 fw-bold text-warning">{{ number_format($report['summary']['missing_nilai']) }}</div><div class="small text-muted">buku</div></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-user-settings-line me-1 text-primary"></i>Rekap per Guru</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Guru</th><th class="text-center">Buku</th><th class="text-center">Perangkat</th><th class="text-center">Jurnal</th><th class="text-center">Presensi</th><th class="text-center">Lengkap</th><th class="text-center">Skor</th></tr></thead>
                        <tbody>
                            @forelse($report['teacher_stats'] as $teacher)
                                <tr>
                                    <td>{{ $teacher['teacher_name'] }}</td>
                                    <td class="text-center">{{ $teacher['books'] }}</td>
                                    <td class="text-center">{{ $teacher['planned'] }}</td>
                                    <td class="text-center">{{ $teacher['journal'] }}</td>
                                    <td class="text-center">{{ $teacher['presensi'] }}</td>
                                    <td class="text-center">{{ $teacher['complete'] }}</td>
                                    <td class="text-center {{ $teacher['average_score'] < 100 ? 'text-warning' : 'text-success' }}">{{ number_format($teacher['average_score'], 2) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data guru.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-list-check-3 me-1 text-warning"></i>Komponen yang Belum Ada</h5></div>
                <div class="card-body">
                    <div class="row g-2 text-center">
                        <div class="col-6"><div class="border rounded p-2"><div class="small text-muted">Perangkat</div><div class="fs-4 fw-bold text-danger">{{ number_format($report['summary']['missing_plan']) }}</div></div></div>
                        <div class="col-6"><div class="border rounded p-2"><div class="small text-muted">Jurnal</div><div class="fs-4 fw-bold text-danger">{{ number_format($report['summary']['missing_journal']) }}</div></div></div>
                        <div class="col-6"><div class="border rounded p-2"><div class="small text-muted">Presensi</div><div class="fs-4 fw-bold text-danger">{{ number_format($report['summary']['missing_presensi']) }}</div></div></div>
                        <div class="col-6"><div class="border rounded p-2"><div class="small text-muted">Nilai</div><div class="fs-4 fw-bold text-danger">{{ number_format($report['summary']['missing_nilai']) }}</div></div></div>
                    </div>
                    <p class="small text-muted mt-3 mb-0">Tindak lanjut disesuaikan kewenangan: guru pemilik buku, koordinator kurikulum, atau pimpinan satuan pendidikan.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-table-line me-1 text-primary"></i>Checklist Administrasi per Buku</h5>
            <span class="small text-muted">{{ number_format($report['summary']['total_books']) }} buku</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Guru</th><th>Mapel / Kelas</th><th class="text-center">Perangkat</th><th class="text-center">Jurnal</th><th class="text-center">Presensi</th><th class="text-center">Nilai</th><th class="text-center">Skor</th><th>Tindak Lanjut</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row['teacher_name'] }}</td>
                                <td><div>{{ $row['subject_name'] }}</div><div class="small text-muted">{{ $row['class_name'] }}</div></td>
                                @foreach(['perangkat', 'jurnal', 'realisasi', 'nilai'] as $component)
                                    <td class="text-center">
                                        @if($row['components'][$component]['done'])
                                            <i class="ri-checkbox-circle-fill text-success"></i>
                                        @else
                                            <i class="ri-close-circle-fill text-danger"></i>
                                        @endif
                                        <div class="small text-muted">{{ $row['components'][$component]['detail'] }}</div>
                                    </td>
                                @endforeach
                                <td class="text-center {{ $row['administration_score'] < 100 ? 'text-warning' : 'text-success' }}">{{ number_format($row['administration_score'], 2) }}%</td>
                                <td>
                                    @if($row['administration_complete'])
                                        <span class="badge bg-success-subtle text-success">Lengkap</span>
                                    @else
                                        <span class="small text-danger">{{ $row['action'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">Belum ada buku administrasi pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rows->hasPages())
            <div class="card-footer">@include('shared._pagination', ['paginator' => $rows])</div>
        @endif
    </div>
@endsection
