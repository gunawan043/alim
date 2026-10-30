@extends('layouts.master')

@section('title') Laporan Pertumbuhan Populasi Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi & Populasi @endslot
        @slot('title') Pertumbuhan Populasi @endslot
    @endcomponent

    @include('user.laporan.partials.population-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">
            Rekonsiliasi populasi awal dan akhir tahun ajaran. Santri dihitung satu kali berdasarkan riwayat kelas unik.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.populasi.pertumbuhan.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm"><i class="ri-file-excel-2-line me-1"></i>Excel</a>
            <a href="{{ route('user.laporan.populasi.pertumbuhan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm"><i class="ri-file-pdf-2-line me-1"></i>PDF</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        @include('user.laporan.partials.population-filters', ['filterRoute' => 'user.laporan.populasi.pertumbuhan'])
    </div></div>

    <div class="alert alert-light border small">
        <strong>Definisi:</strong> awal tahun = populasi pada tahun sebelumnya (atau santri yang sudah terdaftar sebelum tahun ajaran pada tahun pertama data);
        santri baru = riwayat kelas pertama pada tahun ajaran ini dan bukan mutasi masuk; akhir = rumus awal + baru + mutasi masuk − mutasi keluar − berhenti − lulusan.
        Selisih rekonsiliasi dibandingkan dengan populasi terdaftar yang tersisa.
    </div>

    <div class="row g-3 mb-3">
        @php
            $cards = [
                ['label' => 'Awal Tahun', 'value' => $report['summary']['opening'], 'note' => $report['previous_year']?->name ?? 'awal data', 'class' => 'text-primary'],
                ['label' => 'Santri Baru', 'value' => $report['summary']['new'], 'note' => 'pendaftaran baru', 'class' => 'text-success'],
                ['label' => 'Mutasi Masuk', 'value' => $report['summary']['mutation_in'], 'note' => 'disetujui', 'class' => 'text-success'],
                ['label' => 'Mutasi Keluar', 'value' => $report['summary']['mutation_out'], 'note' => 'disetujui', 'class' => 'text-danger'],
                ['label' => 'Berhenti', 'value' => $report['summary']['dropout'], 'note' => 'drop out', 'class' => 'text-warning'],
                ['label' => 'Lulusan', 'value' => $report['summary']['graduates'], 'note' => 'keluar karena lulus', 'class' => 'text-info'],
                ['label' => 'Akhir Tahun', 'value' => $report['summary']['closing'], 'note' => 'hasil rekonsiliasi', 'class' => 'text-primary'],
                ['label' => 'Pertumbuhan', 'value' => ($report['summary']['net_growth'] > 0 ? '+' : '').$report['summary']['net_growth'], 'note' => $report['summary']['growth_rate'] !== null ? number_format($report['summary']['growth_rate'], 2).'%' : 'persentase belum tersedia', 'class' => $report['summary']['net_growth'] < 0 ? 'text-danger' : 'text-success'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="col-6 col-xl-3">
                <div class="card h-100"><div class="card-body py-3">
                    <div class="text-muted small text-uppercase fw-semibold">{{ $card['label'] }}</div>
                    <div class="fs-3 fw-bold {{ $card['class'] }}">{{ number_format($card['value']) }}</div>
                    <div class="small text-muted">{{ $card['note'] }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-line-chart-line me-1 text-primary"></i>Grafik Pertumbuhan per Tahun Ajaran</h5></div>
                <div class="card-body"><div id="growthChart"></div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-scales-3-line me-1 text-info"></i>Rekonsiliasi</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted small">Populasi terdaftar</span><strong>{{ number_format($report['summary']['current_population']) }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted small">Hasil rumus</span><strong>{{ number_format($report['summary']['closing']) }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted small">Tersisa setelah keluar</span><strong>{{ number_format($report['summary']['closing_direct']) }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted small">Selisih</span>
                        <strong class="{{ $report['summary']['reconciliation_difference'] != 0 ? 'text-warning' : 'text-success' }}">{{ number_format($report['summary']['reconciliation_difference']) }}</strong>
                    </div>
                    <div class="small text-muted mt-2">Selisih bukan otomatis kesalahan; dapat berasal dari riwayat kelas atau mutasi yang belum lengkap.</div>
                </div>
            </div>
        </div>
    </div>

    @if($report['findings']->isNotEmpty())
        <div class="card mb-3"><div class="card-body">
            @foreach($report['findings'] as $finding)
                <div class="d-flex justify-content-between border-bottom py-2"><div><strong>{{ $finding['title'] }}</strong><div class="small text-muted">{{ $finding['description'] }}</div></div><span class="badge bg-warning-subtle text-warning">{{ number_format($finding['count']) }}</span></div>
            @endforeach
        </div></div>
    @endif

    @foreach(['unit' => 'Unit / Satuan', 'grade' => 'Jenjang', 'level' => 'Kelompok Pendidikan'] as $key => $label)
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Pertumbuhan per {{ $label }}</h5></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>{{ $label }}</th><th class="text-center">Awal</th><th class="text-center">Akhir</th><th class="text-center">Net</th><th class="text-center">%</th></tr></thead>
                <tbody>
                    @forelse($report['breakdown'][$key] as $row)
                        <tr><td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['opening']) }}</td><td class="text-center">{{ number_format($row['closing']) }}</td><td class="text-center {{ $row['net'] < 0 ? 'text-danger' : 'text-success' }}">{{ $row['net'] > 0 ? '+' : '' }}{{ number_format($row['net']) }}</td><td class="text-center">{{ $row['rate'] !== null ? number_format($row['rate'], 2).'%' : '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    @endforeach

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Riwayat Pertumbuhan</h5></div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Tahun Ajaran</th><th class="text-center">Awal</th><th class="text-center">Baru</th><th class="text-center">Masuk</th><th class="text-center">Keluar</th><th class="text-center">Berhenti</th><th class="text-center">Lulus</th><th class="text-center">Akhir</th><th class="text-center">Net</th><th class="text-center">%</th></tr></thead>
            <tbody>
                @forelse($report['series'] as $row)
                    <tr class="{{ $row['id'] === $report['academic_year']->name ? 'table-active' : '' }}">
                        <td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['opening']) }}</td><td class="text-center">{{ number_format($row['new']) }}</td><td class="text-center">{{ number_format($row['mutation_in']) }}</td><td class="text-center">{{ number_format($row['mutation_out']) }}</td><td class="text-center">{{ number_format($row['dropout']) }}</td><td class="text-center">{{ number_format($row['graduates']) }}</td><td class="text-center">{{ number_format($row['closing']) }}</td><td class="text-center {{ $row['net_growth'] < 0 ? 'text-danger' : 'text-success' }}">{{ $row['net_growth'] > 0 ? '+' : '' }}{{ number_format($row['net_growth']) }}</td><td class="text-center">{{ $row['growth_rate'] !== null ? number_format($row['growth_rate'], 2).'%' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        const series = @json($report['series']);
        const el = document.querySelector('#growthChart');
        if (!el) return;
        if (!series.length) {
            el.innerHTML = '<div class="text-center text-muted py-5">Belum ada data.</div>';
            return;
        }
        new ApexCharts(el, {
            chart: { type: 'line', height: 320, toolbar: { show: false } },
            series: [
                { name: 'Awal', data: series.map(r => r.opening) },
                { name: 'Akhir', data: series.map(r => r.closing) },
                { name: 'Pertumbuhan bersih', data: series.map(r => r.net_growth) }
            ],
            xaxis: { categories: series.map(r => r.name) },
            colors: ['#116891', '#20c997', '#ffc107'],
            stroke: { curve: 'smooth', width: [2, 2, 2] },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            grid: { borderColor: '#e2e8f0' }
        }).render();
    })();
</script>
@endsection
