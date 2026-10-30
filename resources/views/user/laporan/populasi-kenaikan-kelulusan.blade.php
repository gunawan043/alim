@extends('layouts.master')

@section('title') Laporan Kenaikan Kelas dan Kelulusan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Mutasi & Populasi @endslot
        @slot('title') Kenaikan & Kelulusan @endslot
    @endcomponent

    @include('user.laporan.partials.population-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <p class="text-muted mb-0 small">Santri naik kelas, tinggal kelas, berpindah rombel/unit, rekap kelulusan, angkatan, dan lama masa studi.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.populasi.kenaikan-kelulusan.export', array_merge(['userId' => $userId], request()->query())) }}" class="btn btn-success btn-sm"><i class="ri-file-excel-2-line me-1"></i>Excel</a>
            <a href="{{ route('user.laporan.populasi.kenaikan-kelulusan.pdf', array_merge(['userId' => $userId], request()->query())) }}" target="_blank" class="btn btn-danger btn-sm"><i class="ri-file-pdf-2-line me-1"></i>PDF</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        @include('user.laporan.partials.population-filters', ['filterRoute' => 'user.laporan.populasi.kenaikan-kelulusan'])
    </div></div>

    <div class="alert alert-light border small">
        <strong>Definisi tindakan:</strong> naik kelas = jenjang berikutnya lebih tinggi; tinggal kelas = jenjang berikutnya sama;
        pindah unit = unit berikutnya berbeda; belum tercatat = tidak ada riwayat kelas berikutnya pada data.
        <strong>Angka kelulusan</strong> = lulusan tahun ajaran ini ÷ santri kelas akhir pada tahun ajaran yang sama. Definisi ini ditampilkan agar pembaca tidak membandingkan angka dengan basis berbeda.
    </div>

    <div class="row g-3 mb-3">
        @php
            $cards = [
                ['label' => 'Santri Acuan', 'value' => $report['summary']['total'], 'note' => $academicYear->name, 'class' => 'text-primary'],
                ['label' => 'Naik Kelas', 'value' => $report['summary']['promoted'], 'note' => $report['summary']['promotion_rate'] !== null ? number_format($report['summary']['promotion_rate'], 2).'%' : '—', 'class' => 'text-success'],
                ['label' => 'Tinggal Kelas', 'value' => $report['summary']['retained'], 'note' => 'jenjang sama', 'class' => 'text-warning'],
                ['label' => 'Pindah Unit', 'value' => $report['summary']['moved_unit'], 'note' => 'internal', 'class' => 'text-info'],
                ['label' => 'Pindah Rombel', 'value' => $report['summary']['rombel_moved'], 'note' => 'audit perpindahan', 'class' => 'text-info'],
                ['label' => 'Lulusan', 'value' => $report['graduation']['graduates'] ?? 0, 'note' => $report['graduation']['rate'] !== null ? number_format($report['graduation']['rate'], 2).'% dari kelas akhir' : 'angka belum tersedia', 'class' => 'text-success'],
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
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-bar-chart-grouped-line me-1 text-primary"></i>Rekap Tindakan per Unit</h5></div>
                <div class="card-body"><div id="promotionChart"></div></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap Kelulusan per Tahun</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Tahun</th><th class="text-center">Lulusan</th><th class="text-center">Kelas Akhir</th><th class="text-center">%</th><th class="text-center">Masa Studi</th></tr></thead>
                    <tbody>
                        @forelse($report['graduation_series'] as $row)
                            <tr class="{{ $row['id'] === $academicYear->id ? 'table-active' : '' }}"><td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['graduates']) }}</td><td class="text-center">{{ number_format($row['final_cohort']) }}</td><td class="text-center">{{ $row['rate'] !== null ? number_format($row['rate'], 2).'%' : '—' }}</td><td class="text-center">{{ $row['duration_average'] !== null ? number_format($row['duration_average'], 2).' th' : '—' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap Tindakan per Unit</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Unit</th><th class="text-center">Total</th><th class="text-center">Naik</th><th class="text-center">Tinggal</th><th class="text-center">Pindah Unit</th><th class="text-center">Belum Tercatat</th></tr></thead>
                    <tbody>
                        @forelse($report['by_unit'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['total']) }}</td><td class="text-center text-success">{{ number_format($row['promoted']) }}</td><td class="text-center text-warning">{{ number_format($row['retained']) }}</td><td class="text-center text-info">{{ number_format($row['moved_unit']) }}</td><td class="text-center text-muted">{{ number_format($row['not_recorded']) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap Tindakan per Jenjang</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Jenjang</th><th class="text-center">Total</th><th class="text-center">Naik</th><th class="text-center">Tinggal</th><th class="text-center">Pindah Unit</th><th class="text-center">Belum Tercatat</th></tr></thead>
                    <tbody>
                        @forelse($report['by_grade'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['total']) }}</td><td class="text-center text-success">{{ number_format($row['promoted']) }}</td><td class="text-center text-warning">{{ number_format($row['retained']) }}</td><td class="text-center text-info">{{ number_format($row['moved_unit']) }}</td><td class="text-center text-muted">{{ number_format($row['not_recorded']) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap Kelulusan per Unit ({{ $academicYear->name }})</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Unit</th><th class="text-center">Lulusan</th></tr></thead>
                    <tbody>
                        @forelse($report['graduation_by_unit'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['graduates']) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">Belum ada kelulusan pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Rekap Kelulusan per Jenjang ({{ $academicYear->name }})</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Jenjang</th><th class="text-center">Lulusan</th></tr></thead>
                    <tbody>
                        @forelse($report['graduation_by_grade'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center">{{ number_format($row['graduates']) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">Belum ada kelulusan pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Angkatan & Lama Masa Studi</h5></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Angkatan Masuk</th><th class="text-center">Lulusan</th><th class="text-center">Rata-rata Masa Studi</th></tr></thead>
                    <tbody>
                        @forelse($report['cohorts'] as $row)
                            <tr><td>{{ $row['entry_year'] }}</td><td class="text-center">{{ number_format($row['graduates']) }}</td><td class="text-center">{{ $row['duration_average'] !== null ? number_format($row['duration_average'], 2).' th' : '—' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Data angkatan belum tersedia.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Catatan Metode</h5></div>
                <div class="card-body small text-muted">
                    <p class="mb-2">{{ $report['source_note'] }}</p>
                    <p class="mb-2">Perbandingan naik/tinggal kelas memerlukan riwayat kelas tahun ajaran berikutnya. Bila belum tersedia, santri ditampilkan sebagai <strong>belum tercatat</strong>, bukan otomatis tinggal kelas.</p>
                    <p class="mb-0">Lama masa studi dihitung dari tahun ajaran pertama santri tercatat hingga tanggal kelulusan; data yang tidak lengkap tidak diisi dengan asumsi.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-table-line me-1 text-primary"></i>Detail Kenaikan / Kelulusan</h5>
            <span class="small text-muted">{{ number_format($report['details']->count()) }} santri</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>No</th><th>Santri</th><th>Unit</th><th>Jenjang</th><th>Kelas</th><th>Tindakan</th><th>Berikutnya</th><th class="text-center">Pindah Rombel</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $rows->firstItem() + $loop->index }}</td>
                            <td><div class="fw-medium">{{ $row['student_name'] ?: '—' }}</div><div class="small text-muted">{{ $row['nisn'] ?: '—' }}</div></td>
                            <td>{{ $row['school_name'] ?: '—' }}</td><td>{{ $row['grade_name'] ?: '—' }}</td><td>{{ $row['class_name'] ?: '—' }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $row['action_label'] }}</span></td>
                            <td>{{ $row['next_class_name'] ?: '—' }}</td>
                            <td class="text-center">{!! $row['rombel_moved'] ? '<i class="ri-check-line text-success"></i>' : '—' !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">Belum ada data pada filter ini.</td></tr>
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
        const rows = @json($report['by_unit']);
        const el = document.querySelector('#promotionChart');
        if (!el) return;
        if (!rows.length) { el.innerHTML = '<div class="text-center text-muted py-5">Belum ada data.</div>'; return; }
        new ApexCharts(el, {
            chart: { type: 'bar', height: 320, stacked: true, toolbar: { show: false } },
            series: [
                { name: 'Naik', data: rows.map(r => r.promoted) },
                { name: 'Tinggal', data: rows.map(r => r.retained) },
                { name: 'Pindah Unit', data: rows.map(r => r.moved_unit) },
                { name: 'Belum tercatat', data: rows.map(r => r.not_recorded) }
            ],
            xaxis: { categories: rows.map(r => r.name) },
            colors: ['#20c997', '#ffc107', '#0dcaf0', '#adb5bd'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            grid: { borderColor: '#e2e8f0' }
        }).render();
    })();
</script>
@endsection
