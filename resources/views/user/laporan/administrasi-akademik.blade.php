@extends('layouts.master')

@section('title') Laporan Kelengkapan Administrasi @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">Akademik</a> @endslot
        @slot('title') Administrasi akademik @endslot
    @endcomponent

    @php
        $summary = $report['summary'];
        $teacherStats = collect($report['teacher_stats'] ?? []);
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Administrasi akademik</h1>
                <p class="lap-head-desc">Checklist empat kewajiban per buku: perangkat atau RPM, jurnal, presensi, dan input nilai. Persentase menunjukkan komponen yang sudah ada, bukan penilaian kualitas dokumen.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.akademik.administrasi.export', array_merge(['userId' => $userId], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh Excel
                </a>
                <a href="{{ route('user.laporan.akademik.administrasi.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.academic-tabs')

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.akademik.administrasi', ['userId' => $userId]) }}" class="lap-filters">
                    <div class="lap-field">
                        <label class="form-label" for="lap-ay">Tahun ajaran</label>
                        <select id="lap-ay" name="academic_year_id" class="form-select">
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}" {{ $filters['academic_year_id'] === $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-semester">Semester</label>
                        <select id="lap-semester" name="semester" class="form-select">
                            <option value="ganjil" {{ $filters['semester'] === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ $filters['semester'] === 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-teacher">Guru</label>
                        <select id="lap-teacher" name="teacher_id" class="form-select">
                            <option value="">Semua guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ $filters['teacher_id'] === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-group">Kelas</label>
                        <select id="lap-group" name="study_group_id" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach ($studyGroups as $group)
                                <option value="{{ $group->id }}" {{ $filters['study_group_id'] === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-subject">Mata pelajaran</label>
                        <select id="lap-subject" name="subject_id" class="form-select">
                            <option value="">Semua mapel</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $filters['subject_id'] === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-actions">
                        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
                        <a href="{{ route('user.laporan.akademik.administrasi', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => $academicYear->name.' — '.ucfirst($filters['semester']),
            'note' => 'Satu buku mewakili satu pasangan guru, mata pelajaran, dan kelas.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Buku administrasi', 'value' => $summary['total_books'], 'note' => number_format($summary['total_teachers'], 0, ',', '.').' guru'],
            ['label' => 'Kewajiban', 'value' => $summary['obligations'], 'note' => '4 komponen per buku'],
            [
                'label' => 'Terpenuhi',
                'value' => $summary['completed'],
                'note' => $summary['percentage'] !== null ? number_format($summary['percentage'], 1, ',', '.').'% dari kewajiban' : 'persentase belum tersedia',
            ],
            ['label' => 'Buku lengkap', 'value' => $summary['complete_books'], 'note' => '4 dari 4 komponen', 'tone' => 'ok'],
            ['label' => 'Belum lengkap', 'value' => $summary['incomplete_books'], 'note' => 'perlu tindak lanjut', 'tone' => $summary['incomplete_books'] > 0 ? 'bad' : null],
            ['label' => 'Nilai belum diisi', 'value' => $summary['missing_nilai'], 'note' => 'buku', 'tone' => $summary['missing_nilai'] > 0 ? 'warn' : null],
        ]])

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Rekap per guru</h2>
                    <span class="lap-meta">{{ $teacherStats->count() }} guru</span>
                </div>
                <div class="card-body p-0">
                    @if ($teacherStats->isEmpty())
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada data guru', 'desc' => 'Tidak ada buku administrasi pada saringan ini.', 'height' => 200])
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Guru</th>
                                        <th class="text-end">Buku</th>
                                        <th class="text-end">Perangkat</th>
                                        <th class="text-end">Jurnal</th>
                                        <th class="text-end">Presensi</th>
                                        <th class="text-end">Lengkap</th>
                                        <th class="text-end">Skor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($teacherStats as $teacher)
                                        <tr>
                                            <td>{{ $teacher['teacher_name'] }}</td>
                                            <td class="text-end">{{ number_format($teacher['books'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ number_format($teacher['planned'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ number_format($teacher['journal'], 0, ',', '.') }}</td>
                                            <td class="text-end muted">{{ number_format($teacher['presensi'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($teacher['complete'], 0, ',', '.') }}</td>
                                            <td class="text-end {{ $teacher['average_score'] > 0 ? '' : 'muted' }}">{{ number_format($teacher['average_score'], 1, ',', '.') }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Komponen yang belum ada</h2>
                </div>
                <div class="card-body">
                    <div class="lap-list">
                        <div class="lap-list-row">
                            <span class="lap-list-label">Perangkat atau RPM</span>
                            <span class="lap-list-value {{ $summary['missing_plan'] > 0 ? 'is-bad' : '' }}">{{ number_format($summary['missing_plan'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Jurnal</span>
                            <span class="lap-list-value {{ $summary['missing_journal'] > 0 ? 'is-bad' : '' }}">{{ number_format($summary['missing_journal'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Presensi</span>
                            <span class="lap-list-value {{ $summary['missing_presensi'] > 0 ? 'is-bad' : '' }}">{{ number_format($summary['missing_presensi'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lap-list-row">
                            <span class="lap-list-label">Input nilai</span>
                            <span class="lap-list-value {{ $summary['missing_nilai'] > 0 ? 'is-bad' : '' }}">{{ number_format($summary['missing_nilai'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <p class="lap-note mt-3">Tindak lanjut disesuaikan kewenangan: guru pemilik buku, koordinator kurikulum, atau pimpinan satuan pendidikan.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Checklist per buku</h2>
                <span class="lap-meta">{{ number_format($summary['total_books'], 0, ',', '.') }} buku</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Guru</th>
                                <th>Mapel dan kelas</th>
                                <th style="width:120px">Perangkat</th>
                                <th style="width:120px">Jurnal</th>
                                <th style="width:120px">Presensi</th>
                                <th style="width:120px">Nilai</th>
                                <th class="text-end">Skor</th>
                                <th style="width:180px">Tindak lanjut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="lap-table-name">{{ $row['teacher_name'] }}</td>
                                    <td>
                                        <div>{{ $row['subject_name'] }}</div>
                                        <div class="lap-table-sub">{{ $row['class_name'] }}</div>
                                    </td>
                                    @foreach (['perangkat', 'jurnal', 'realisasi', 'nilai'] as $component)
                                        @php $done = $row['components'][$component]['done']; @endphp
                                        <td>
                                            <span class="lap-flag {{ $done ? 'is-done' : 'is-missing' }}">{{ $done ? 'Ada' : 'Belum' }}</span>
                                            <div class="lap-table-sub">{{ $row['components'][$component]['detail'] }}</div>
                                        </td>
                                    @endforeach
                                    <td class="text-end {{ $row['administration_score'] > 0 ? '' : 'muted' }}">{{ number_format($row['administration_score'], 1, ',', '.') }}%</td>
                                    <td>
                                        @if ($row['administration_complete'])
                                            <span class="badge bg-success-subtle">Lengkap</span>
                                        @else
                                            <span class="lap-table-sub">{{ $row['action'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada buku administrasi',
                                            'desc' => 'Tidak ada buku pada saringan ini.',
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($rows->hasPages())
                <div class="card-footer">
                    @include('shared._pagination', ['paginator' => $rows])
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @include('user.laporan.partials._script')
@endpush
