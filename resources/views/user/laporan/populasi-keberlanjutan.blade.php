@extends('layouts.master')

@section('title') Laporan Keberlanjutan Pendidikan dan Retensi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi & Populasi @endslot
        @slot('title') Keberlanjutan & Retensi @endslot
    @endcomponent

    @include('user.laporan.partials.population-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">Santri yang melanjutkan ke jenjang/unit berikutnya, tidak tercatat melanjutkan, dan perjalanan pendidikan sejak masuk.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.populasi.keberlanjutan.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm"><i class="ri-file-excel-2-line me-1"></i>Excel</a>
            <a href="{{ route('user.laporan.populasi.keberlanjutan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm"><i class="ri-file-pdf-2-line me-1"></i>PDF</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        @include('user.laporan.partials.population-filters', ['filterRoute' => 'user.laporan.populasi.keberlanjutan'])
    </div></div>

    <div class="alert alert-warning border-0 small">
        <i class="ri-information-line me-1"></i>
        <strong>Tidak melanjutkan ke unit internal tidak otomatis berarti putus sekolah.</strong>
        Santri dapat melanjutkan ke lembaga lain, pindah domisili, atau belum tercatat karena data belum lengkap. Status “tidak tercatat” adalah status data, bukan vonis putus sekolah.
    </div>

    <div class="row g-3 mb-3">
        @php
            $cards = [
                ['label' => 'Santri Acuan', 'value' => $report['summary']['cohort'], 'note' => $academicYear->name, 'class' => 'text-primary'],
                ['label' => 'Melanjutkan', 'value' => $report['summary']['continued'], 'note' => 'punya riwayat setelahnya', 'class' => 'text-success'],
                ['label' => 'Tidak Tercatat', 'value' => $report['summary']['not_continued'], 'note' => 'belum ada riwayat lanjutan', 'class' => 'text-warning'],
                ['label' => 'Tercatat Lulus', 'value' => $report['summary']['graduated'], 'note' => 'dari kelompok tidak tercatat', 'class' => 'text-info'],
                ['label' => 'Keluar Tercatat', 'value' => $report['summary']['known_exit'], 'note' => 'mutasi/berhenti', 'class' => 'text-danger'],
                ['label' => 'Keberlanjutan', 'value' => $report['summary']['rate'] !== null ? number_format($report['summary']['rate'], 2).'%' : '—', 'note' => $report['next_year'] ? 'terhadap tahun berikutnya' : 'tahun berikutnya belum tersedia', 'class' => 'text-success'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="col-6 col-xl-2">
                <div class="card h-100"><div class="card-body py-3">
                    <div class="text-muted small text-uppercase fw-semibold">{{ $card['label'] }}</div>
                    <div class="fs-3 fw-bold {{ $card['class'] }}">{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</div>
                    <div class="small text-muted">{{ $card['note'] }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-bar-chart-2-line me-1 text-primary"></i>Keberlanjutan per Tahun Acuan</h5></div>
                <div class="card-body"><div id="continuityChart"></div></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap Angkatan / Tahun</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Tahun</th><th class="text-center">Santri</th><th class="text-center">Lanjut</th><th class="text-center">%</th></tr></thead>
                    <tbody>
                        @forelse($report['cohorts'] as $cohort)
                            <tr class="{{ $cohort['selected'] ? 'table-active' : '' }}"><td>{{ $cohort['name'] }}</td><td class="text-center">{{ number_format($cohort['cohort']) }}</td><td class="text-center">{{ number_format($cohort['continued']) }}</td><td class="text-center">{{ $cohort['rate'] !== null ? number_format($cohort['rate'], 2).'%' : '—' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-route-line me-1 text-primary"></i>Perjalanan Santri</h5>
            <span class="small text-muted">{{ number_format($report['journey']->count()) }} santri</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>No</th><th>Santri</th><th>Tahun Masuk</th><th>Kelas Awal</th><th>Tahun Terakhir</th><th>Kelas Akhir</th><th>Melanjutkan</th><th>Status</th><th>Lulus</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $rows->firstItem() + $loop->index }}</td>
                            <td><div class="fw-medium">{{ $row['student_name'] ?: '—' }}</div><div class="small text-muted">{{ $row['nisn'] ?: '—' }}</div></td>
                            <td>{{ $row['first_year'] ?: '—' }}</td><td>{{ $row['first_class'] ?: '—' }}</td>
                            <td>{{ $row['last_year'] ?: '—' }}</td><td>{{ $row['last_class'] ?: '—' }}</td>
                            <td>@if($row['continued'])<span class="badge bg-success-subtle text-success">Ya</span>@else<span class="badge bg-warning-subtle text-warning">Tidak tercatat</span>@endif</td>
                            <td>{{ $row['status'] ?: '—' }}</td><td>{{ $row['graduation_year'] ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">Belum ada data pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        @if($rows->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $rows])</div>@endif
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        const cohorts = @json($report['cohorts']);
        const el = document.querySelector('#continuityChart');
        if (!el) return;
        if (!cohorts.length) { el.innerHTML = '<div class="text-center text-muted py-5">Belum ada data.</div>'; return; }
        new ApexCharts(el, {
            chart: { type: 'bar', height: 300, toolbar: { show: false } },
            series: [{ name: 'Keberlanjutan (%)', data: cohorts.map(r => r.rate) }],
            xaxis: { categories: cohorts.map(r => r.name) },
            colors: ['#20c997'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '52%' } },
            dataLabels: { enabled: true, formatter: v => v !== null ? v + '%' : '—' },
            yaxis: { min: 0, max: 100, title: { text: '%' } },
            grid: { borderColor: '#e2e8f0' }
        }).render();
    })();
</script>
@endsection
