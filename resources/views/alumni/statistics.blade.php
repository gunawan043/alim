@extends('layouts.master')
@section('title') Statistik Alumni @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.alumni.index', ['userId' => $userId]) }}">Data Alumni</a> @endslot
        @slot('title') Statistik @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        {{-- Summary Cards --}}
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-user-follow-line text-primary"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Alumni</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalTracer) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Tracer Terisi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $tracerFilledPct }}%</h3>
                        </div>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar bg-success" style="width:{{ $tracerFilledPct }}%"></div>
                        <div class="progress-bar bg-secondary" style="width:{{ 100 - $tracerFilledPct }}%"></div>
                    </div>
                    <small class="text-muted">{{ $tracerStats->get('filled', 0) + $tracerStats->get('verified', 0) }} dari {{ $totalTracer }} data</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-book-open-line text-info"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Melanjutkan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $studyStats->get('sudah', 0) }}</h3>
                        </div>
                    </div>
                    <small class="text-muted">{{ $studyStats->get('sedang', 0) }} masih studi</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-briefcase-line text-warning"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bekerja</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $workingStats->get('sudah', 0) }}</h3>
                        </div>
                    </div>
                    <small class="text-muted">{{ $workingStats->get('sedang', 0) }} sedang bekerja</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Per Tahun --}}
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-bar-chart-line me-2"></i>Jumlah Alumni per Tahun</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tahun Lulus</th>
                                    <th class="text-center">Jumlah Alumni</th>
                                    <th class="text-center">Grafik</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byYear as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item['year'] }}</td>
                                        <td class="text-center">{{ number_format($item['total']) }}</td>
                                        <td class="text-center" style="width:200px">
                                            <div class="progress" style="height:10px;">
                                                <div class="progress-bar bg-primary" style="width:{{ $totalTracer > 0 ? round($item['total'] / $totalTracer * 100) : 0 }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Per Satuan Pendidikan --}}
        @if($bySchool->count())
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-government-line me-2"></i>Per Satuan Pendidikan</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Satuan Pendidikan</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Proporsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bySchool as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item->school_name }}</td>
                                        <td class="text-center">{{ number_format($item->total) }}</td>
                                        <td class="text-center" style="width:200px">
                                            <div class="progress" style="height:10px;">
                                                <div class="progress-bar bg-success" style="width:{{ $totalTracer > 0 ? round($item->total / $totalTracer * 100) : 0 }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Status Tracer --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-checkbox-circle-line me-2"></i>Status Tracer Study</h6></div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        @php
                            $total = $tracerStats->sum();
                            $statuses = [
                                'pending'  => ['label' => 'Belum Diisi', 'color' => 'secondary', 'count' => $tracerStats->get('pending', 0)],
                                'filled'   => ['label' => 'Sudah Diisi', 'color' => 'info', 'count' => $tracerStats->get('filled', 0)],
                                'verified' => ['label' => 'Diverifikasi', 'color' => 'success', 'count' => $tracerStats->get('verified', 0)],
                            ];
                        @endphp
                        @foreach($statuses as $key => $s)
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $s['label'] }}</span>
                                    <span class="fw-semibold">{{ $s['count'] }}</span>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-{{ $s['color'] }}"
                                         style="width:{{ $total > 0 ? round($s['count'] / $total * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Studi Continuation --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-book-open-line me-2"></i>Melanjutkan Studi</h6></div>
                <div class="card-body">
                    @php
                        $studyTotal = $studyStats->sum();
                        $studyData = [
                            'belum'  => ['label' => 'Belum', 'color' => 'secondary', 'count' => $studyStats->get('belum', 0)],
                            'sedang' => ['label' => 'Sedang', 'color' => 'warning', 'count' => $studyStats->get('sedang', 0)],
                            'sudah'  => ['label' => 'Sudah', 'color' => 'success', 'count' => $studyStats->get('sudah', 0)],
                        ];
                    @endphp
                    <div class="d-flex flex-column gap-3">
                        @foreach($studyData as $key => $s)
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $s['label'] }}</span>
                                    <span class="fw-semibold">{{ $s['count'] }}</span>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-{{ $s['color'] }}"
                                         style="width:{{ $studyTotal > 0 ? round($s['count'] / $studyTotal * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Working Status --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-briefcase-line me-2"></i>Status Bekerja</h6></div>
                <div class="card-body">
                    @php
                        $workTotal = $workingStats->sum();
                        $workData = [
                            'belum'  => ['label' => 'Belum', 'color' => 'secondary', 'count' => $workingStats->get('belum', 0)],
                            'sedang' => ['label' => 'Sedang', 'color' => 'warning', 'count' => $workingStats->get('sedang', 0)],
                            'sudah'  => ['label' => 'Sudah', 'color' => 'success', 'count' => $workingStats->get('sudah', 0)],
                        ];
                    @endphp
                    <div class="d-flex flex-column gap-3">
                        @foreach($workData as $key => $s)
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $s['label'] }}</span>
                                    <span class="fw-semibold">{{ $s['count'] }}</span>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-{{ $s['color'] }}"
                                         style="width:{{ $workTotal > 0 ? round($s['count'] / $workTotal * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('user.alumni.index', ['userId' => $userId]) }}" class="btn btn-light">
            <i class="ri-arrow-left-line me-1"></i>Kembali ke Daftar Alumni
        </a>
    </div>
@endsection
