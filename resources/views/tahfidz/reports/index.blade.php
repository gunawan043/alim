@extends('layouts.master')

@section('title', 'Laporan & Grafik Tahfidz')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')

    @component('components.breadcrumb')
        @slot('li_1') Tahfidz @endslot
        @slot('li_2') Laporan @endslot
        @slot('title') Laporan & Grafik Tahfidz @endslot
    @endcomponent

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-end gap-2">
            <div>
                <h5 class="card-title mb-1"><i class="ri-bar-chart-box-line text-primary me-1"></i>Laporan & Grafik Tahfidz</h5>
                <p class="text-muted mb-0 small">Tren setoran, jenis setoran, kehadiran halaqah, dan capaian target.</p>
            </div>
            <a href="{{ route('user.tahfidz.gradebook.index', ['userId' => $userId, 'group_id' => $groupId]) }}" class="btn btn-soft-primary">
                <i class="ri-book-3-line me-1"></i>Buku Nilai
            </a>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('user.tahfidz.reports.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Halaqah</label>
                    <select name="group_id" class="form-select" onchange="this.form.submit()">
                        @foreach($groups as $availableGroup)
                            <option value="{{ $availableGroup->id }}" @selected((string) $groupId === (string) $availableGroup->id)>
                                {{ $availableGroup->name }}{{ $availableGroup->studyGroup ? ' — '.$availableGroup->studyGroup->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Bulan</label>
                    <input type="month" name="month" class="form-control" value="{{ $monthInput }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    @if(! $group)
        <div class="alert alert-info mt-3"><i class="ri-information-line me-1"></i>Belum ada halaqah yang dapat Anda akses.</div>
    @else
        <div class="row tahfidz-stat mt-1">
            @foreach([
                ['Santri', $stats['members'], 'ri-user-heart-line', 'primary'],
                ['Setoran', $stats['setoran'], 'ri-file-list-3-line', 'info'],
                ['Kehadiran', $stats['attendance_rate'] !== null ? number_format((float) $stats['attendance_rate'], 1).'%' : '—', 'ri-calendar-check-line', 'success'],
                ['Rata Target', $stats['target_avg'] !== null ? number_format((float) $stats['target_avg'], 1).'%' : '—', 'ri-focus-3-line', 'warning'],
            ] as [$label, $value, $icon, $color])
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate"><div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                            <div><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ $value }}</h3></div>
                        </div>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0">Tren Setoran Harian — {{ $monthLabel }}</h6></div>
                    <div class="card-body"><div id="dailySetoranChart"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0">Komposisi Jenis Setoran</h6></div>
                    <div class="card-body"><div id="setoranTypeChart"></div></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0">Kehadiran Halaqah</h6></div>
                    <div class="card-body"><div id="attendanceChart"></div></div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0">Capaian per Santri</h6></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Santri</th><th class="text-center">Setoran</th><th class="text-center">Kehadiran</th><th class="text-center">Target</th></tr></thead>
                            <tbody>
                                @forelse($studentRows as $row)
                                    <tr>
                                        <td>{{ $row['student']?->name }}<div class="text-muted small">{{ $row['student']?->nis }}</div></td>
                                        <td class="text-center">{{ $row['setoran'] }}</td>
                                        <td class="text-center">{{ $row['attendance_percent'] !== null ? number_format((float) $row['attendance_percent'], 1).'%' : '—' }}</td>
                                        <td class="text-center">{{ $row['target_percent'] !== null ? number_format((float) $row['target_percent'], 1).'%' : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada santri.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (! window.ApexCharts) return;

        const dailyLabels = @json(array_keys($daily));
        const dailyValues = @json(array_values($daily));
        const typeLabels = ['Ziyadah', 'Tikrar', 'Murajaah', 'Robet'];
        const typeValues = @json(array_values($typeCounts));
        const attendanceLabels = ['Hadir', 'Izin', 'Sakit', 'Alpa', 'Belum Setor', 'Belum Siap'];
        const attendanceValues = @json(array_values($attendanceCounts));

        const dailyEl = document.getElementById('dailySetoranChart');
        if (dailyEl) {
            new ApexCharts(dailyEl, {
                chart: { type: 'line', height: 300, toolbar: { show: false } },
                series: [{ name: 'Setoran', data: dailyValues }],
                xaxis: { categories: dailyLabels },
                stroke: { curve: 'smooth', width: 3 },
                colors: ['#405189'],
                dataLabels: { enabled: false },
                grid: { borderColor: '#e9ebec' },
            }).render();
        }

        const typeEl = document.getElementById('setoranTypeChart');
        if (typeEl) {
            new ApexCharts(typeEl, {
                chart: { type: 'donut', height: 300 },
                labels: typeLabels,
                series: typeValues,
                colors: ['#0ab39c', '#405189', '#f7b84b', '#f06548'],
                legend: { position: 'bottom' },
            }).render();
        }

        const attendanceEl = document.getElementById('attendanceChart');
        if (attendanceEl) {
            new ApexCharts(attendanceEl, {
                chart: { type: 'bar', height: 280, toolbar: { show: false } },
                series: [{ name: 'Jumlah', data: attendanceValues }],
                xaxis: { categories: attendanceLabels },
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                colors: ['#0ab39c', '#405189', '#f7b84b', '#f06548', '#6c757d', '#343a40'],
                dataLabels: { enabled: false },
                legend: { show: false },
            }).render();
        }
    });
</script>
@endpush
