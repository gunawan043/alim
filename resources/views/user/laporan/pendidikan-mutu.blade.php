@extends('layouts.master')
@section('title') Dashboard Mutu Pendidikan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Pendidikan @endslot
        @slot('title') Mutu Pendidikan @endslot
    @endcomponent

    @include('user.laporan.partials.academic-tabs')

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Keterhubungan Kurikulum & Kesiapan Akademik</h5>
                    <p class="text-muted mb-0 small">CP → TP → ATP → Prota → Prosem → Perangkat → Jurnal → Nilai → Rapor.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary">{{ $year?->name ?? 'Tahun ajaran belum tersedia' }} · {{ ucfirst($semester) }}</span>
            </div>

            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($years as $item)
                            <option value="{{ $item->id }}" @selected($year?->id === $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected($semester === 'genap')>Genap</option>
                    </select>
                </div>
                @if(! $schoolId)
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Unit</label>
                        <select name="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua unit</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" @selected(request('school_id') === $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Jenjang</label>
                    <select name="grade_level_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua jenjang</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" @selected(request('grade_level_id') === $grade->id)>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Kolom JP','counts.rows','kombinasi rombel-mapel','primary'],
            ['Konsisten','counts.ok','JP & jadwal cocok','success'],
            ['Perlu Perbaikan','counts.warning','ada selisih JP/jadwal','danger'],
            ['Kesiapan Rapor','readiness.rate','persen santri terdaftar rapor','info'],
        ] as [$label,$path,$note,$color])
            @php
                $value = data_get($report, $path);
            @endphp
            <div class="col-6 col-xl-3">
                <div class="card h-100"><div class="card-body py-3">
                    <div class="text-muted small text-uppercase fw-semibold">{{ $label }}</div>
                    <div class="fs-3 fw-bold text-{{ $color }}">
                        {{ $value === null ? '—' : (str_contains($path,'rate') ? number_format((float)$value,2).'%' : number_format((int)$value)) }}
                    </div>
                    <div class="small text-muted">{{ $note }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Keterhubungan Dokumen Kurikulum</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th>Tahap</th><th class="text-center">Jumlah</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach([
                                    'cp' => 'CP', 'tp' => 'TP', 'atp' => 'ATP', 'prota' => 'Prota',
                                    'prosem' => 'Prosem', 'perangkat' => 'Perangkat', 'jurnal' => 'Jurnal',
                                    'buku_nilai' => 'Buku Nilai',
                                ] as $key => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-center">{{ number_format((int)($report['coverage'][$key] ?? 0)) }}</td>
                                        <td>
                                            @if(($report['coverage'][$key] ?? 0) > 0)
                                                <span class="badge bg-success-subtle text-success">Tersedia</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Belum ada</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Kesiapan Rapor</h5></div>
                <div class="card-body">
                    @foreach([
                        'students' => 'Santri aktif', 'groups' => 'Rombel',
                        'groups_with_homeroom' => 'Rombel punya wali', 'books' => 'Buku nilai',
                        'rapor_registered' => 'Terdaftar rapor',
                    ] as $key => $label)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted small">{{ $label }}</span>
                            <strong>{{ number_format((int)($report['readiness'][$key] ?? 0)) }}</strong>
                        </div>
                    @endforeach
                    @if(($report['readiness']['students'] ?? 0) > 0 && ($report['readiness']['groups_with_homeroom'] ?? 0) < ($report['readiness']['groups'] ?? 0))
                        <div class="alert alert-warning small mt-3 mb-0">
                            Ada rombel tanpa wali kelas. Rapor tidak dapat dipublikasikan dengan aman.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Konsistensi JP: Rombel vs SK vs Jadwal vs Prota</h5>
            <span class="small text-muted">{{ number_format($report['counts']['warning']) }} perlu diperbaiki</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Rombel</th><th>Jenjang</th><th>Mapel</th><th class="text-center">JP Rombel</th><th class="text-center">JP SK</th><th class="text-center">Slot Jadwal</th><th class="text-center">JP Prota</th><th>Masalah</th></tr>
                    </thead>
                    <tbody>
                        @forelse($report['mismatches'] as $row)
                            <tr>
                                <td>{{ $row['group'] }}</td><td>{{ $row['grade'] }}</td><td>{{ $row['subject'] }}</td>
                                <td class="text-center">{{ $row['planned'] }}</td><td class="text-center">{{ $row['sk'] }}</td>
                                <td class="text-center">{{ $row['schedule'] }}</td><td class="text-center">{{ $row['prota'] ?? '—' }}</td>
                                <td><span class="small text-danger">{{ implode('; ', $row['issues']) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">Tidak ada selisih JP pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Beban Guru</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Guru</th><th class="text-center">Total JP</th><th class="text-center">Rombel</th></tr></thead>
                    <tbody>
                        @forelse($report['teacher_workload'] as $teacher)
                            <tr><td>{{ $teacher['teacher'] }}</td><td class="text-center">{{ $teacher['hours'] }}</td><td class="text-center">{{ $teacher['groups'] }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada penugasan mengajar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
