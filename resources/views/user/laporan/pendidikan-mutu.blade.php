@extends('layouts.master')

@section('title') Dashboard Mutu Pendidikan @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Akademik @endslot
        @slot('title') Mutu pendidikan @endslot
    @endcomponent

    @php
        $coverage = [
            'cp' => 'CP',
            'tp' => 'TP',
            'atp' => 'ATP',
            'prota' => 'Prota',
            'prosem' => 'Prosem',
            'perangkat' => 'Perangkat',
            'jurnal' => 'Jurnal',
            'buku_nilai' => 'Buku nilai',
        ];
        $coverageMax = max(1, max(array_map(fn ($key) => (int) ($report['coverage'][$key] ?? 0), array_keys($coverage))));
        $readiness = $report['readiness'];
        $homeroomMissing = ($readiness['students'] ?? 0) > 0
            && ($readiness['groups_with_homeroom'] ?? 0) < ($readiness['groups'] ?? 0);
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Mutu pendidikan</h1>
                <p class="lap-head-desc">Keterhubungan dokumen kurikulum dan kesiapan akademik, dari CP sampai rapor.</p>
            </div>
        </div>

        @include('user.laporan.partials.academic-tabs')

        <div class="card">
            <div class="card-body">
                <form method="GET" class="lap-filters">
                    <div class="lap-field">
                        <label class="form-label" for="lap-ay">Tahun ajaran</label>
                        <select id="lap-ay" name="academic_year_id" class="form-select" onchange="this.form.submit()">
                            @foreach ($years as $item)
                                <option value="{{ $item->id }}" @selected($year?->id === $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-semester">Semester</label>
                        <select id="lap-semester" name="semester" class="form-select" onchange="this.form.submit()">
                            <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                            <option value="genap" @selected($semester === 'genap')>Genap</option>
                        </select>
                    </div>
                    @if (! $schoolId)
                        <div class="lap-field">
                            <label class="form-label" for="lap-school">Unit</label>
                            <select id="lap-school" name="school_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua unit</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}" @selected(request('school_id') === $school->id)>{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="lap-field">
                        <label class="form-label" for="lap-grade">Jenjang</label>
                        <select id="lap-grade" name="grade_level_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua jenjang</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->id }}" @selected(request('grade_level_id') === $grade->id)>{{ $grade->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        @include('user.laporan.partials.period', [
            'period' => ($year?->name ?? 'Tahun ajaran belum tersedia').' — '.ucfirst($semester),
            'note' => 'Rantai dokumen: CP → TP → ATP → Prota → Prosem → Perangkat → Jurnal → Buku nilai.',
        ])

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Kolom JP', 'value' => $report['counts']['rows'], 'note' => 'kombinasi rombel dan mapel'],
            ['label' => 'Konsisten', 'value' => $report['counts']['ok'], 'note' => 'JP dan jadwal cocok', 'tone' => 'ok'],
            ['label' => 'Perlu perbaikan', 'value' => $report['counts']['warning'], 'note' => 'ada selisih JP atau jadwal', 'tone' => $report['counts']['warning'] > 0 ? 'bad' : null],
            [
                'label' => 'Kesiapan rapor',
                'value' => $report['readiness']['rate'] !== null ? number_format($report['readiness']['rate'], 1, ',', '.').'%' : '—',
                'note' => 'santri terdaftar rapor',
            ],
        ]])

        <div class="lap-grid lap-grid-main">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Keterhubungan dokumen kurikulum</h2>
                    <span class="lap-meta">8 tahap</span>
                </div>
                <div class="card-body">
                    <div class="lap-bars">
                        @foreach ($coverage as $key => $label)
                            @php
                                $count = (int) ($report['coverage'][$key] ?? 0);
                                $ratio = $count / $coverageMax;
                            @endphp
                            <div class="lap-bar-row">
                                <span class="lap-bar-name">{{ $label }}</span>
                                <span class="lap-bar-track">
                                    <span class="lap-bar-fill {{ $count === 0 ? 'is-bad' : '' }}" data-lap-bar="{{ $count === 0 ? 0.02 : $ratio }}"></span>
                                </span>
                                <span class="lap-bar-value {{ $count === 0 ? 'muted' : '' }}">{{ number_format($count, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Kesiapan rapor</h2>
                </div>
                <div class="card-body">
                    <div class="lap-list">
                        @foreach ([
                            'students' => 'Santri aktif',
                            'groups' => 'Rombel',
                            'groups_with_homeroom' => 'Rombel punya wali',
                            'books' => 'Buku nilai',
                            'rapor_registered' => 'Terdaftar rapor',
                        ] as $key => $label)
                            <div class="lap-list-row">
                                <span class="lap-list-label">{{ $label }}</span>
                                <span class="lap-list-value">{{ number_format((int) ($readiness[$key] ?? 0), 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($homeroomMissing)
                        <div class="lap-callout mt-3 mb-0">
                            <strong>Ada rombel tanpa wali kelas.</strong>
                            Rapor tidak dapat dipublikasikan dengan aman sampai wali kelas ditetapkan.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Konsistensi JP: rombel, SK, jadwal, dan prota</h2>
                <span class="lap-meta">{{ number_format($report['counts']['warning'], 0, ',', '.') }} perlu diperbaiki</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rombel</th>
                                <th>Jenjang</th>
                                <th>Mata pelajaran</th>
                                <th class="text-end">JP rombel</th>
                                <th class="text-end">JP SK</th>
                                <th class="text-end">Slot jadwal</th>
                                <th class="text-end">JP prota</th>
                                <th>Masalah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['mismatches'] as $row)
                                <tr>
                                    <td>{{ $row['group'] }}</td>
                                    <td>{{ $row['grade'] }}</td>
                                    <td>{{ $row['subject'] }}</td>
                                    <td class="text-end">{{ $row['planned'] }}</td>
                                    <td class="text-end">{{ $row['sk'] }}</td>
                                    <td class="text-end">{{ $row['schedule'] }}</td>
                                    <td class="text-end muted">{{ $row['prota'] ?? '—' }}</td>
                                    <td class="text-warning">{{ implode('; ', $row['issues']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Tidak ada selisih JP',
                                            'desc' => 'Semua baris pada saringan ini konsisten.',
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Beban guru</h2>
                <span class="lap-meta">{{ count($report['teacher_workload']) }} guru</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Guru</th>
                                <th class="text-end">Total JP</th>
                                <th class="text-end">Rombel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['teacher_workload'] as $teacher)
                                <tr>
                                    <td>{{ $teacher['teacher'] }}</td>
                                    <td class="text-end">{{ $teacher['hours'] }}</td>
                                    <td class="text-end muted">{{ $teacher['groups'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Belum ada penugasan mengajar',
                                            'desc' => 'Beban guru muncul setelah pembagian pelajaran diisi.',
                                        ])
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

@push('scripts')
    @include('user.laporan.partials._script')
@endpush
