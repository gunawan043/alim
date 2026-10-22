@extends('layouts.master')

@section('title', 'Nilai & Perkembangan Bahasa')

@section('content')
    @include('bahasa._styles')

    @php
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

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Nilai & Perkembangan @endslot
        @slot('title') {{ $modeTitle }} @endslot
    @endcomponent

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $modeTitle }}</h4>
            <p class="text-muted mb-0">{{ $languageLabel }} · {{ $academicYear?->name ?? '-' }} · semester {{ ucfirst($semester) }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('user.bahasa.academic.nilai', ['userId' => $userId, 'language' => $language]) }}" class="btn btn-sm {{ $mode === 'nilai' ? 'btn-primary' : 'btn-light' }}">Nilai</a>
            <a href="{{ route('user.bahasa.academic.progress', ['userId' => $userId, 'language' => $language]) }}" class="btn btn-sm {{ $mode === 'progress' ? 'btn-primary' : 'btn-light' }}">Grafik</a>
            <a href="{{ route('user.bahasa.academic.leger', ['userId' => $userId, 'language' => $language]) }}" class="btn btn-sm {{ $mode === 'leger' ? 'btn-primary' : 'btn-light' }}">Leger</a>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-light"><i class="ri-printer-line me-1"></i>Cetak</button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label small mb-1">Bahasa</label><select name="language" class="form-select"><option value="arab" @selected($language === 'arab')>Bahasa Arab</option><option value="inggris" @selected($language === 'inggris')>Bahasa Inggris</option></select></div>
                <div class="col-md-2"><label class="form-label small mb-1">Semester</label><select name="semester" class="form-select"><option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option><option value="genap" @selected($semester === 'genap')>Genap</option></select></div>
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
                    <div class="col-md-2"><label class="form-label small mb-1">Ambang Nilai</label><input type="number" name="threshold" value="{{ $threshold }}" min="0" max="100" class="form-control"></div>
                @endif
                <div class="col-md-2"><button class="btn btn-soft-primary w-100"><i class="ri-filter-3-line me-1"></i>Terapkan</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Santri Bernilai</p><h3 class="fw-bold mb-0">{{ number_format($stats['students']) }}</h3></div></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-bar-chart-line text-info"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Rata-rata</p><h3 class="fw-bold mb-0">{{ number_format($stats['average'], 1, ',', '.') }}</h3></div></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-trophy-line text-success"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Nilai Tertinggi</p><h3 class="fw-bold mb-0">{{ number_format($stats['highest'], 1, ',', '.') }}</h3></div></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-alert-line text-warning"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Di Bawah Ambang</p><h3 class="fw-bold mb-0">{{ number_format($stats['at_risk']) }}</h3></div></div><div class="mt-2 text-muted" style="font-size:11px;">Ambang {{ number_format($threshold, 0, ',', '.') }} · {{ $stats['books'] }} buku nilai</div></div></div></div>
    </div>

    @if(in_array($mode, ['progress', 'report', 'nilai'], true))
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-line-chart-line text-primary me-1"></i>Rata-rata Nilai per Kelas</h5></div>
            <div class="card-body"><div id="languageClassChart" style="min-height:280px"></div></div>
        </div>
    @endif

    @if($mode === 'at-risk')
        <div class="alert alert-warning"><i class="ri-alert-line me-1"></i>Menampilkan santri dengan rata-rata di bawah {{ number_format($threshold, 0, ',', '.') }}. Data diambil dari nilai resmi Satuan Pendidikan.</div>
    @endif

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ $mode === 'leger' ? 'Leger Nilai per Santri' : 'Daftar Nilai Santri' }}</h5><div class="text-muted small">{{ $languageLabel }} · {{ $rows->count() }} santri</div></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:45px">No</th>
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
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $row['student']?->name ?? '-' }}</td>
                            <td>{{ $row['class'] }}</td>
                            @foreach($subjects as $subject)
                                <td class="text-center">{{ isset($row['scores'][$subject]) ? number_format((float) $row['scores'][$subject], 1, ',', '.') : '-' }}</td>
                            @endforeach
                            <td class="text-center fw-bold {{ $row['average'] < $threshold ? 'text-danger' : 'text-success' }}">{{ number_format((float) $row['average'], 1, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 4 + $subjects->count() }}" class="text-center text-muted py-5">Belum ada nilai bahasa pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('script')
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
@endsection
