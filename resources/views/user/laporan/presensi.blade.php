@extends('layouts.master')

@section('title') Laporan Presensi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Semua Laporan</a> @endslot
        @slot('title') Laporan Presensi GTK @endslot
    @endcomponent

    @php
        $statusLabels = [
            'hadir' => 'Hadir',
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'alpa' => 'Alpa',
            'cuti' => 'Cuti',
            'dinas_luar' => 'Dinas Luar',
        ];
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase fw-semibold">Total Catatan</div>
                    <div class="fs-3 fw-bold">{{ number_format($summary['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase fw-semibold">Hadir</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($summary['hadir']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase fw-semibold">Terlambat</div>
                    <div class="fs-3 fw-bold text-warning">{{ number_format($summary['terlambat']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase fw-semibold">Tidak Hadir</div>
                    <div class="fs-3 fw-bold text-danger">{{ number_format($summary['sakit'] + $summary['izin'] + $summary['alpa']) }}</div>
                    <div class="small text-muted">Sakit {{ $summary['sakit'] }} · Izin {{ $summary['izin'] }} · Alpa {{ $summary['alpa'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tren harian --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-line-chart-line me-2 text-primary"></i>Tren Presensi Harian</h5>
            <span class="text-muted small">{{ \Illuminate\Support\Carbon::parse($dari)->format('d/m/Y') }} — {{ \Illuminate\Support\Carbon::parse($sampai)->format('d/m/Y') }}</span>
        </div>
        <div class="card-body">
            <div id="presensiTrend"></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.presensi', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Dari Tanggal</label>
                    <input type="date" name="dari" class="form-control" value="{{ $dari }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Sampai Tanggal</label>
                    <input type="date" name="sampai" class="form-control" value="{{ $sampai }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach($statusLabels as $val => $label)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Cari GTK</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama GTK…">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                    <a href="{{ route('user.laporan.export', array_merge(['userId' => $userId, 'type' => 'presensi'], request()->query())) }}"
                       class="btn btn-success w-100"><i class="ri-download-2-line me-1"></i>CSV</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-calendar-check-line me-2 text-primary"></i>Rekap Presensi GTK</h5>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">{{ \Illuminate\Support\Carbon::parse($dari)->format('d/m/Y') }} — {{ \Illuminate\Support\Carbon::parse($sampai)->format('d/m/Y') }}</span>
                <a href="{{ route('user.laporan.pdf', array_merge(['userId' => $userId, 'type' => 'presensi'], request()->query())) }}"
                   target="_blank" class="btn btn-sm btn-danger">
                    <i class="ri-file-pdf-2-line me-1"></i>PDF
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>Tanggal</th>
                            <th>GTK</th>
                            <th>Status</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Terlambat</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($absensi as $i => $row)
                            @php
                                $tone = match ($row->status) {
                                    'hadir' => 'success',
                                    'sakit', 'cuti' => 'warning',
                                    'izin', 'dinas_luar' => 'info',
                                    'alpa' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>{{ $absensi->firstItem() + $i }}</td>
                                <td>{{ $row->tanggal?->format('d/m/Y') }}</td>
                                <td>
                                    <div class="fw-medium">{{ $row->gtk?->user?->name ?? '—' }}</div>
                                    <div class="small text-muted">{{ $row->gtk?->user?->email }}</div>
                                </td>
                                <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $statusLabels[$row->status] ?? ucfirst($row->status) }}</span></td>
                                <td>{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '—' }}</td>
                                <td>{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '—' }}</td>
                                <td>
                                    @if($row->terlambat_menit > 0)
                                        <span class="text-warning fw-medium">{{ $row->terlambat_menit }} menit</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row->keterangan, 60) ?: '—' }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="8" title="Belum ada data presensi"
                                message="Tidak ada catatan presensi GTK pada periode ini." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($absensi->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $absensi])
            </div>
        @endif
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        var trendData = @json($trend ?? []);
        var el = document.querySelector('#presensiTrend');
        if (!el) return;

        if (!trendData.length) {
            el.innerHTML = '<div class="text-center text-muted py-4">Belum ada data untuk grafik pada periode ini.</div>';
            return;
        }

        new ApexCharts(el, {
            chart: { type: 'bar', height: 320, stacked: true, toolbar: { show: false } },
            series: [
                { name: 'Hadir', data: trendData.map(function (d) { return d.hadir; }) },
                { name: 'Terlambat', data: trendData.map(function (d) { return d.terlambat; }) },
                { name: 'Izin', data: trendData.map(function (d) { return d.izin; }) },
                { name: 'Sakit', data: trendData.map(function (d) { return d.sakit; }) },
                { name: 'Alpa', data: trendData.map(function (d) { return d.alpa; }) }
            ],
            xaxis: { categories: trendData.map(function (d) { return d.day; }) },
            colors: ['#22c55e', '#f59e0b', '#3b82f6', '#eab308', '#ef4444'],
            plotOptions: { bar: { borderRadius: 3, columnWidth: '45%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            grid: { borderColor: '#e2e8f0' }
        }).render();
    })();
</script>
@endsection
