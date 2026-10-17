@extends('layouts.master')

@section('title', 'Dashboard Pengasuh')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Dashboard Pengasuh Asrama</h4>
                    <small class="text-muted">Ringkasan operasional harian — {{ $today->isoFormat('dddd, D MMMM Y') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-group-line"></i> Total Santri Aktif</p>
                    <h4>{{ $totalSantri }}</h4>
                    <small class="text-muted">di {{ $activeAsrama->count() }} asrama</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-time-line"></i> Izin Pending</p>
                    <h4 class="text-warning">{{ $permitPending }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-alarm-warning-line"></i> Overdue</p>
                    <h4 class="text-danger">{{ $overduePermits }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-error-warning-line"></i> Pelanggaran (7 hari)</p>
                    <h4 class="text-danger">{{ $violationsWeek }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-logout-box-line"></i> Izin Hari Ini</p>
                    <h4>{{ $permitsToday }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-footprint-line"></i> Kunjungan Hari Ini</p>
                    <h4>{{ $visitsToday }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-home-heart-line"></i> Kepulangan Hari Ini</p>
                    <h4>{{ $returnsToday }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-medal-line"></i> Poin ≥50 (Kritis)</p>
                    <h4 class="text-danger">{{ $criticalPoints }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1"></i> Izin Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Santri</th>
                                    <th>Asrama</th>
                                    <th>Berangkat</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPermits as $p)
                                <tr>
                                    <td><strong>{{ $p->student->nama ?? '?' }}</strong></td>
                                    <td>{{ $p->dormitory->name ?? '-' }}</td>
                                    <td>{{ $p->departure_date->format('d M') }}</td>
                                    <td><span class="badge bg-{{ $p->status === 'approved' ? 'success' : ($p->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($p->status) }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Tidak Ada Izin Aktif</h6>
                                    <p class="text-muted mb-3 small">Semua santri tercatat hadir sesuai jadwal.</p>
                                </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-error-warning-line me-1"></i> Pelanggaran Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Santri</th>
                                    <th>Jenis</th>
                                    <th>Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentViolations as $v)
                                <tr>
                                    <td>{{ $v->violation_date->format('d M') }}</td>
                                    <td>{{ $v->student->nama ?? '?' }}</td>
                                    <td><small>{{ $v->violation_type }}</small></td>
                                    <td><strong class="text-danger">{{ $v->points }}</strong></td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Tidak Ada Pelanggaran Terbaru</h6>
                                    <p class="text-muted mb-3 small">Semua santri mematuhi aturan asrama.</p>
                                </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($policies->count() > 0)
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ri-file-shield-2-line me-1"></i> Kebijakan Asrama Aktif</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Judul</th>
                            <th>Tipe</th>
                            <th>Berlaku</th>
                            <th>Kuota Izin</th>
                            <th>Kuota Kunjungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($policies as $p)
                        <tr>
                            <td><strong>{{ $p->title }}</strong></td>
                            <td><code>{{ $p->policy_type }}</code></td>
                            <td>{{ $p->effective_from?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $p->permit_quota ?? '∞' }}</td>
                            <td>{{ $p->visit_quota ?? '∞' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection