@extends('layouts.master')
@section('title') Grafik Antropometri Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}">Antropometri</a> @endslot
        @slot('title') Grafik {{ $student->name }} @endslot
    @endcomponent

    <?php
    $latest  = $metrics->sortByDesc('record_date')->first();
    $first   = $metrics->sortBy('record_date')->first();
    $count   = $metrics->count();
    $latestBmi = $latest->bmi ?? null;
    $firstBmi  = $first->bmi ?? null;
    $bmiDiff   = ($latestBmi && $firstBmi) ? round($latestBmi - $firstBmi, 2) : null;
    $bmiLabel  = ['sangat_kurang'=>'Sangat Kurang','kurang'=>'Kurang','normal'=>'Normal','lebih'=>'Berlebih','gemuk'=>'Gemuk'];
    $badgeColor = ['sangat_kurang'=>'warning','kurang'=>'info','normal'=>'success','lebih'=>'primary','gemuk'=>'danger'];
    ?>

    @if($metrics->isEmpty())
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="ri-line-chart-line text-muted" style="font-size:4rem;"></i>
                        <h5 class="mt-3 text-muted">Belum Ada Data Pengukuran</h5>
                        <p class="text-muted">Tambahkan data antropometri untuk Santri ini terlebih dahulu.</p>
                        <a href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}" class="btn btn-secondary mt-2">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else

    {{-- Summary Cards --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card border-start border-1 border-primary">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <span class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                <i class="ri-bar-chart-line fs-5"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase">Total Pengukuran</p>
                            <h4 class="mb-0">{{ $count }}<span class="fs-6 text-muted ms-1">kali</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-success">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <span class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                <i class="ri-body-scan-line fs-5"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase">BMI Terakhir</p>
                            <h4 class="mb-0">{{ $latestBmi ? number_format($latestBmi, 2) : '-' }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-warning">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <span class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                <i class="ri-user-search-line fs-5"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase">Kategori</p>
                            <h6 class="mb-0">
                                @if($latest->bmi_category)
                                    <span class="badge bg-{{ $badgeColor[$latest->bmi_category] ?? 'secondary' }}">
                                        {{ $bmiLabel[$latest->bmi_category] ?? $latest->bmi_category }}
                                    </span>
                                @else
                                    -
                                @endif
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-info">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <span class="bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                <i class="bx bx-line-chart-down fs-5"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase">Perubahan BMI</p>
                            <h4 class="mb-0">
                                @if($bmiDiff !== null)
                                    <span class="{{ $bmiDiff > 0 ? 'text-success' : ($bmiDiff < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ $bmiDiff > 0 ? '+' : '' }}{{ $bmiDiff }}
                                    </span>
                                @else
                                    -
                                @endif
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BMI Chart --}}
    <?php
    $chartData = $metrics->sortBy('record_date')->map(fn($m) => [
        'label' => $m->record_date ? $m->record_date->format('d/m/Y') : '',
        'bmi' => $m->bmi ? (float) $m->bmi : null,
        'category' => $m->bmi_category ?? '',
    ])->values();
    ?>
    <div class="row mb-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0"><i class="ri-line-chart me-1"></i> Grafik Perkembangan BMI</h6>
                        <span class="badge bg-light text-dark">{{ $student->name }}</span>
                    </div>
                </div>
                <div class="card-body p-2">
                    <canvas id="bmiChart" style="max-height:220px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="ri-information-line me-1"></i> Referensi Kategori BMI</h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive table-sm">
                        <table class="table table-bordered mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori</th>
                                    <th>BMI</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-warning">Kurus</span></td>
                                    <td>&lt; 17.0</td>
                                    <td>@if($latest->bmi_category == 'sangat_kurang')<i class="ri-checkbox-circle-fill text-success"></i>@endif</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info">Kurang</span></td>
                                    <td>17.0 – 18.5</td>
                                    <td>@if($latest->bmi_category == 'kurang')<i class="ri-checkbox-circle-fill text-success"></i>@endif</td>
                                </tr>
                                <tr class="table-success">
                                    <td><span class="badge bg-success">Normal</span></td>
                                    <td>18.5 – 25.0</td>
                                    <td>@if($latest->bmi_category == 'normal')<i class="ri-checkbox-circle-fill text-success"></i>@endif</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">Berlebih</span></td>
                                    <td>25.0 – 27.0</td>
                                    <td>@if($latest->bmi_category == 'lebih')<i class="ri-checkbox-circle-fill text-success"></i>@endif</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-danger">Gemuk</span></td>
                                    <td>&gt; 27.0</td>
                                    <td>@if($latest->bmi_category == 'gemuk')<i class="ri-checkbox-circle-fill text-success"></i>@endif</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mt-2 mb-0">* Berdasarkan standar BMI dewasa (WHO)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- BMI Gauge --}}
    <?php
    $gaugeColor = $latest->bmi_category
        ? ($badgeColor[$latest->bmi_category] ?? 'secondary')
        : 'secondary';
    ?>
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <p class="text-muted small mb-1">BMI Terakhir</p>
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <div>
                            <div class="display-4 fw-bold text-{{ $gaugeColor }}">{{ $latestBmi ? number_format($latestBmi, 1) : '-' }}</div>
                            <span class="badge bg-{{ $gaugeColor }}">{{ $bmiLabel[$latest->bmi_category] ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="ri-list-check me-1"></i> Riwayat Pengukuran</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="text-center" style="width:40px">#</th>
                                    <th>Tanggal Ukur</th>
                                    <th class="text-center">Tinggi<br><small class="fw-normal">(cm)</small></th>
                                    <th class="text-center">Berat<br><small class="fw-normal">(kg)</small></th>
                                    <th class="text-center">BMI</th>
                                    <th class="text-center">Kategori</th>
                                    <th class="text-center">Sesi</th>
                                    <th class="text-center">Perubahan</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php
                                $sorted = $metrics->sortByDesc('record_date')->values();
                                $prevBmi = null;
                                foreach ($sorted as $i => $m):
                                    $diff = null;
                                    if ($prevBmi !== null && $m->bmi) {
                                        $diff = round($m->bmi - $prevBmi, 2);
                                    }
                                    $prevBmi = $m->bmi;
                                    $bc = $badgeColor[$m->bmi_category ?? ''] ?? 'secondary';
                                    $bl = $bmiLabel[$m->bmi_category ?? ''] ?? '-';
                                    $sessionLabel = ['awal_tahun'=>'Awal','tengah_tahun'=>'Tengah','akhir_tahun'=>'Akhir','lainnya'=>'Lain'][$m->measurement_session ?? ''] ?? '-';
                                ?>
                                <tr>
                                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $m->record_date ? $m->record_date->format('d M Y') : '-' }}</td>
                                    <td class="text-center">{{ $m->height_cm ?: '-' }}</td>
                                    <td class="text-center">{{ $m->weight_kg ?: '-' }}</td>
                                    <td class="text-center fw-bold">{{ $m->bmi ? number_format($m->bmi, 2) : '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $bc }}">{{ $bl }}</span>
                                    </td>
                                    <td class="text-center text-muted">{{ $sessionLabel }}</td>
                                    <td class="text-center">
                                        @if($diff !== null)
                                            <span class="badge bg-light text-dark {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted') }}">
                                                {{ $diff > 0 ? '▲ +' : '▼ ' }}{{ $diff }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endif

    <div class="mb-4">
        <a href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
        </a>
    </div>

    @if(!$metrics->isEmpty())
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const raw = @json($chartData);
        const labels = raw.map(r => r.label);
        const bmis   = raw.map(r => r.bmi);
        const ctx = document.getElementById('bmiChart').getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'BMI',
                    data: bmis,
                    borderColor: '#0f62ff',
                    backgroundColor: 'rgba(15,98,255,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#0f62ff',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => 'BMI: ' + ctx.parsed.y.toFixed(2)
                        }
                    }
                },
                scales: {
                    y: {
                        min: 10,
                        max: 35,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { stepSize: 5 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 45 }
                    }
                }
            }
        });
    });
    </script>
    @endpush
    @endif
@endsection