@php
    $analytics = $dashboard['analytics'] ?? [];
    $distribution = $analytics['distribution'] ?? [];
    $byGrade = $distribution['by_grade'] ?? [];
    $byClass = $distribution['by_class'] ?? [];
    $gradeChartId = 'dash-grade-'.substr(md5($user->id), 0, 6);
@endphp

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-bar-chart-2-line me-1 text-primary"></i>Distribusi Santri per Jenjang</h5>
                <a href="{{ route('user.laporan.populasi.profil', ['userId' => $user->id]) }}" class="btn btn-sm btn-light border">Rincian</a>
            </div>
            <div class="card-body">
                @if(empty($byGrade))
                    <div class="text-center text-muted py-5">
                        <i class="ri-bar-chart-box-line fs-2 d-block mb-1"></i>
                        Belum ada riwayat kelas pada periode ini.
                    </div>
                @else
                    <div id="{{ $gradeChartId }}" style="min-height:280px;"></div>
                    @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var el = document.getElementById(@json($gradeChartId));
                                if (!el || !window.ApexCharts) return;
                                new ApexCharts(el, {
                                    chart: { type: 'bar', height: 280, toolbar: { show: false } },
                                    series: [{ name: 'Santri', data: @json(array_column($byGrade, 'total')) }],
                                    xaxis: { categories: @json(array_column($byGrade, 'name')) },
                                    colors: ['#405189'],
                                    plotOptions: { bar: { borderRadius: 4, columnWidth: '48%' } },
                                    dataLabels: { enabled: false },
                                    grid: { borderColor: '#f1f1f1' }
                                }).render();
                            });
                        </script>
                    @endpush
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-building-2-line me-1 text-info"></i>Keterisian Rombel</h5>
                <span class="small text-muted">{{ count($byClass) }} rombel</span>
            </div>
            <div class="card-body p-0" style="max-height:340px;overflow:auto;">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Rombel</th>
                            <th class="text-center">Santri</th>
                            <th class="text-center">Kapasitas</th>
                            <th class="text-center">Keterisian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byClass as $row)
                            @php
                                $capacity = (int) ($row['capacity'] ?? 0);
                                $filled = (int) $row['total'];
                                $rate = $capacity > 0 ? round(($filled / $capacity) * 100, 1) : null;
                            @endphp
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-center">{{ number_format($filled) }}</td>
                                <td class="text-center">{{ $capacity > 0 ? number_format($capacity) : '—' }}</td>
                                <td class="text-center">
                                    @if($rate === null)
                                        <span class="text-muted">—</span>
                                    @else
                                        <span class="badge bg-{{ $rate > 100 ? 'danger' : ($rate >= 80 ? 'success' : 'warning') }}-subtle text-{{ $rate > 100 ? 'danger' : ($rate >= 80 ? 'success' : 'warning') }}">
                                            {{ number_format($rate, 1) }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada rombel pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
