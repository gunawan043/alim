@extends('layouts.master')

@section('title', 'Dashboard Admin Pendidikan Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Dashboard Admin Pendidikan</h4>
                    <small class="text-muted">Kalender, izin, kepulangan — {{ $today->isoFormat('dddd, D MMMM Y') }}</small>
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
                    <p class="text-muted mb-1"><i class="ri-flight-takeoff-line"></i> Berangkat Hari Ini</p>
                    <h4 class="text-info">{{ $permitsToday }}</h4>
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
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-arrow-down-line"></i> Kepulangan Hari Ini</p>
                    <h4 class="text-success">{{ $returnsToday }}</h4>
                    <small class="text-muted">{{ $returnPending }} kepulangan pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Akses Cepat</h4>
                </div>
                <div class="card-body">
                    <a href="{{ route('user.calendar.return.index', ['userId' => auth()->id()]) }}" class="btn btn-primary mb-2">
                        <i class="ri-calendar-event-line"></i> Kalender Kepulangan
                    </a>
                    <a href="{{ route('user.calendar.visit.index', ['userId' => auth()->id()]) }}" class="btn btn-info mb-2">
                        <i class="ri-footprint-line"></i> Kalender Kunjungan
                    </a>
                    <a href="{{ route('user.boarding-policies.index', ['userId' => auth()->id()]) }}" class="btn btn-secondary mb-2">
                        <i class="ri-file-shield-2-line"></i> Kebijakan Asrama
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection