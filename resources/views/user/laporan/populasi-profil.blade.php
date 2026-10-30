@extends('layouts.master')

@section('title') Laporan Profil dan Sebaran Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi & Populasi @endslot
        @slot('title') Profil & Sebaran Santri @endslot
    @endcomponent

    @include('user.laporan.partials.population-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">Jumlah santri aktif per unit, jenjang, kelas, dan rombel; sebaran usia dan jenis kelamin bila data tersedia.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.populasi.profil.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm"><i class="ri-file-excel-2-line me-1"></i>Excel</a>
            <a href="{{ route('user.laporan.populasi.profil.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm"><i class="ri-file-pdf-2-line me-1"></i>PDF</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        @include('user.laporan.partials.population-filters', ['filterRoute' => 'user.laporan.populasi.profil', 'showGender' => true])
    </div></div>

    <div class="row g-3 mb-3">
        @php
            $cards = [
                ['label' => 'Santri Aktif', 'value' => $report['summary']['total'], 'note' => $report['academic_year']->name, 'class' => 'text-primary'],
                ['label' => 'Laki-laki', 'value' => $report['summary']['male'], 'note' => 'jenis kelamin L', 'class' => 'text-info'],
                ['label' => 'Perempuan', 'value' => $report['summary']['female'], 'note' => 'jenis kelamin P', 'class' => 'text-danger'],
                ['label' => 'Rombel', 'value' => $report['summary']['rombel'], 'note' => 'kelas aktif', 'class' => 'text-secondary'],
                ['label' => 'Rata-rata / Rombel', 'value' => $report['summary']['average_per_rombel'] !== null ? number_format($report['summary']['average_per_rombel'], 2) : '—', 'note' => 'santri per rombel', 'class' => 'text-success'],
                ['label' => 'Perubahan Populasi', 'value' => $report['summary']['growth_rate'] !== null ? (($report['summary']['growth_rate'] > 0 ? '+' : '').number_format($report['summary']['growth_rate'], 2).'%') : '—', 'note' => $report['previous_year']?->name ?? 'belum ada pembanding', 'class' => ($report['summary']['growth_rate'] ?? 0) < 0 ? 'text-danger' : 'text-success'],
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
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-pie-chart-line me-1 text-primary"></i>Sebaran Jenis Kelamin</h5></div>
                <div class="card-body"><div id="genderChart"></div></div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-bar-chart-2-line me-1 text-info"></i>Sebaran Usia</h5></div>
                <div class="card-body"><div id="ageChart"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Per Unit</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Unit</th><th class="text-center">Santri</th><th class="text-center">L</th><th class="text-center">P</th><th class="text-center">Perubahan</th></tr></thead>
                    <tbody>
                        @forelse($report['by_unit'] as $row)
                            @php $composition = $report['composition']->firstWhere('id', $row['id']); @endphp
                            <tr><td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['total']) }}</td><td class="text-center">{{ number_format($row['male']) }}</td><td class="text-center">{{ number_format($row['female']) }}</td><td class="text-center {{ ($composition['delta'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ ($composition['delta'] ?? 0) > 0 ? '+' : '' }}{{ number_format($composition['delta'] ?? 0) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Per Jenjang</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Jenjang</th><th class="text-center">Santri</th><th class="text-center">L</th><th class="text-center">P</th></tr></thead>
                    <tbody>
                        @forelse($report['by_grade'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['total']) }}</td><td class="text-center">{{ number_format($row['male']) }}</td><td class="text-center">{{ number_format($row['female']) }}</td></tr>
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
            <h5 class="card-title mb-0"><i class="ri-building-2-line me-1 text-primary"></i>Sebaran per Kelas / Rombel</h5>
            <span class="small text-muted">{{ number_format($report['by_class']->count()) }} rombel</span>
        </div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Rombel</th><th>Unit</th><th>Jenjang</th><th class="text-center">Santri</th><th class="text-center">Kapasitas</th><th class="text-center">Keterisian</th></tr></thead>
            <tbody>
                @forelse($report['by_class'] as $row)
                    <tr><td>{{ $row['name'] }}</td><td>{{ $row['school_name'] }}</td><td>{{ $row['grade_name'] }}</td><td class="text-center">{{ number_format($row['total']) }}</td><td class="text-center">{{ $row['capacity'] ?: '—' }}</td><td class="text-center">{{ $row['fill_rate'] !== null ? number_format($row['fill_rate'], 2).'%' : '—' }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data rombel.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        const genderEl = document.querySelector('#genderChart');
        const ageEl = document.querySelector('#ageChart');
        const gender = [{{ $report['summary']['male'] }}, {{ $report['summary']['female'] }}];
        const ages = @json($report['age_groups']);

        if (genderEl && gender.some(v => v > 0)) {
            new ApexCharts(genderEl, {
                chart: { type: 'donut', height: 300 },
                series: gender,
                labels: ['Laki-laki', 'Perempuan'],
                colors: ['#116891', '#dc3545'],
                legend: { position: 'bottom' },
                dataLabels: { enabled: true }
            }).render();
        } else if (genderEl) {
            genderEl.innerHTML = '<div class="text-center text-muted py-5">Data jenis kelamin belum tersedia.</div>';
        }

        if (ageEl && ages.length) {
            new ApexCharts(ageEl, {
                chart: { type: 'bar', height: 300, toolbar: { show: false } },
                series: [{ name: 'Santri', data: ages.map(r => r.count) }],
                xaxis: { categories: ages.map(r => r.label) },
                colors: ['#20c997'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#e2e8f0' }
            }).render();
        } else if (ageEl) {
            ageEl.innerHTML = '<div class="text-center text-muted py-5">Data usia belum tersedia.</div>';
        }
    })();
</script>
@endsection
