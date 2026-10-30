@extends('layouts.master')

@section('title', 'Nilai & Perkembangan Bahasa')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
    $modeTitles = [
        'nilai' => 'Nilai Bahasa',
        'progress' => 'Grafik Perkembangan',
        'at-risk' => 'Santri Tertinggal',
        'leger' => 'Leger Nilai Bahasa',
        'ujian' => 'Ujian Lisan Bahasa',
        'penilaian' => 'Penilaian Ujian Bahasa',
        'report' => 'Laporan Capaian Bahasa',
    ];
    $modeTitle = $modeTitles[$mode] ?? 'Nilai & Perkembangan Bahasa';
    $languageLabel = $language === 'inggris' ? 'Bahasa Inggris' : 'Bahasa Arab';
@endphp

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Nilai & Perkembangan @endslot
        @slot('title') {{ $modeTitle }} @endslot
    @endcomponent

    {{-- FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- HEADER HALAMAN --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">{{ $modeTitle }}</h5>
                    <p class="text-muted mb-0 small">
                        {{ $languageLabel }} · {{ $academicYear?->name ?? '—' }} · semester {{ ucfirst($semester) }}
                    </p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        <a href="{{ route('user.bahasa.academic.nilai', ['userId' => $userId, 'language' => $language]) }}"
                           class="btn {{ $mode === 'nilai' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Nilai
                        </a>
                        <a href="{{ route('user.bahasa.academic.progress', ['userId' => $userId, 'language' => $language]) }}"
                           class="btn {{ $mode === 'progress' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Grafik
                        </a>
                        <a href="{{ route('user.bahasa.academic.leger', ['userId' => $userId, 'language' => $language]) }}"
                           class="btn {{ $mode === 'leger' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Leger
                        </a>
                        <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="ri-printer-line me-1"></i> Cetak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Bahasa</label>
                        <select name="language" class="form-select">
                            <option value="arab" @selected($language === 'arab')>Bahasa Arab</option>
                            <option value="inggris" @selected($language === 'inggris')>Bahasa Inggris</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                            <option value="genap" @selected($semester === 'genap')>Genap</option>
                        </select>
                    </div>
                    <x-bahasa-filter-fields
                        :schools="$schools"
                        :grade-levels="$gradeLevels"
                        :study-groups="$studyGroups"
                        :show-school="$showSchool"
                        :school-id="$schoolId"
                        :grade-level-id="$gradeLevelId"
                        :study-group-id="$studyGroupId"
                    />
                    @if($mode === 'at-risk')
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Ambang Nilai</label>
                            <input type="number" name="threshold" value="{{ $threshold }}" min="0" max="100" class="form-control">
                        </div>
                    @endif
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="ri-filter-3-line me-1"></i> Terapkan
                        </button>
                        <a href="{{ url()->current() }}" class="btn btn-light flex-shrink-0" title="Reset filter">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-group-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Santri Bernilai</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['students'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Total santri dengan nilai</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-bar-chart-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Rata-rata</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['average'] ?? 0, 1, ',', '.') }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Nilai rata-rata keseluruhan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-trophy-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Nilai Tertinggi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['highest'] ?? 0, 1, ',', '.') }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Nilai tertinggi santri</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-alert-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Di Bawah Ambang</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['at_risk'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small">
                        <i class="ri-information-line me-1"></i>Ambang {{ number_format($threshold, 0, ',', '.') }} · {{ $stats['books'] ?? 0 }} buku nilai
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- CHART --}}
    @if(in_array($mode, ['progress', 'report', 'nilai'], true))
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed">
                <div class="row g-3 align-items-center">
                    <div class="col-sm">
                        <h5 class="card-title mb-0"><i class="ri-line-chart-line text-primary me-1"></i>Rata-rata Nilai per Kelas</h5>
                        <p class="text-muted mb-0 small">Perbandingan rata-rata nilai per kelas.</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="languageClassChart" style="min-height:280px"></div>
            </div>
        </div>
    @endif

    {{-- ALERT AT-RISK --}}
    @if($mode === 'at-risk')
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-2"></i>
            Menampilkan santri dengan rata-rata di bawah {{ number_format($threshold, 0, ',', '.') }}. Data diambil dari nilai resmi Satuan Pendidikan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- TABEL --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">{{ $mode === 'leger' ? 'Leger Nilai per Santri' : 'Daftar Nilai Santri' }}</h5>
                    <p class="text-muted mb-0 small">{{ $languageLabel }} · {{ $rows->count() }} santri</p>
                </div>
                <div class="col-sm-auto">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ $rows->count() }} santri</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th>Nama Santri</th>
                            <th>Kelas</th>
                            @foreach($subjects as $subject)
                                <th class="text-center">{{ $subject }}</th>
                            @endforeach
                            <th class="text-center">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $index => $row)
                            <tr>
                                <td class="text-center text-muted">{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $row['student']?->name ?? '—' }}</td>
                                <td>{{ $row['class'] }}</td>
                                @foreach($subjects as $subject)
                                    <td class="text-center">
                                        {{ isset($row['scores'][$subject]) ? number_format((float) $row['scores'][$subject], 1, ',', '.') : '—' }}
                                    </td>
                                @endforeach
                                <td class="text-center fw-bold {{ $row['average'] < $threshold ? 'text-danger' : 'text-success' }}">
                                    {{ number_format((float) $row['average'], 1, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + $subjects->count() }}" class="text-center py-5">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/msoeawqm.json"
                                        trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a"
                                        style="width:75px;height:75px">
                                    </lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Belum ada nilai</h6>
                                    <p class="text-muted mb-0 small">Belum ada nilai bahasa pada periode ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    const el = document.getElementById('languageClassChart');
    if (!el || !window.ApexCharts) return;

    const labels = @json($classAverages->keys()->values());
    const data = @json($classAverages->values());

    new ApexCharts(el, {
        chart: { type: 'bar', height: 280, toolbar: { show: false } },
        series: [{ name: 'Rata-rata', data: data }],
        xaxis: { categories: labels, labels: { style: { fontSize: '11px' } } },
        yaxis: { min: 0, max: 100, labels: { style: { fontSize: '11px' } } },
        colors: ['#0f5132'],
        dataLabels: { enabled: true, formatter: function (value) { return Number(value).toFixed(1); } },
        grid: { strokeDashArray: 4 },
        tooltip: { y: { formatter: function (value) { return Number(value).toFixed(1); } } },
    }).render();
})();
</script>
@endpush