@extends('layouts.master')

@section('title', 'Dashboard Kepala Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Dashboard Kepala Asrama</h4>
                    <small class="text-muted">Approval center & overview agregat — {{ $today->isoFormat('dddd, D MMMM Y') }}</small>
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
                    <p class="text-muted mb-1"><i class="ri-time-line"></i> Pending Approval</p>
                    <h4 class="text-warning">{{ $pendingTotal }}</h4>
                    <small class="text-muted">{{ $pendingApprovals['permits'] }} izin, {{ $pendingApprovals['visits'] }} kunjungan, {{ $pendingApprovals['room_moves'] }} mutasi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-alarm-warning-line"></i> Overdue Pulang</p>
                    <h4 class="text-danger">{{ $overduePermits }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-error-warning-line"></i> Pelanggaran (30 hari)</p>
                    <h4 class="text-danger">{{ $violationsMonth }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Approval Center — Izin Pending</h4>
                    <p class="card-title-desc">Izin yang menunggu persetujuan Anda</p>
                </div>
                <div class="card-body">
                    @if($recentPermits->isEmpty())
                        <p class="text-muted">Tidak ada izin pending saat ini.</p>
                    @else
                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Santri</th>
                                    <th>Asrama</th>
                                    <th>Keperluan</th>
                                    <th>Tgl Berangkat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPermits as $p)
                                <tr>
                                    <td>{{ $p->student->nama_santri ?? '-' }}</td>
                                    <td>{{ $p->dormitory->name ?? '-' }}</td>
                                    <td>{{ Str::limit($p->reason ?? '-', 40) }}</td>
                                    <td>{{ optional($p->departure_date)->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('user.asrama.permits.show', ['userId' => auth()->id(), 'asramaUuid' => $p->dormitory_id, 'permitUuid' => $p->id]) }}"
                                           class="btn btn-sm btn-primary">Detail</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection