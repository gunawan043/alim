@extends('layouts.master')

@section('title', 'Timeline Santri')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Timeline Lengkap Santri</h4>
                    <small class="text-muted">Riwayat aktivitas asrama {{ $student->nama }}</small>
                </div>
                <div>
                    @if($asramaUuid ?? false)
                    <a href="{{ route('user.asrama.residents.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'id' => $student->id]) }}"
                       class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-1">
                            {{ strtoupper(substr($student->nama, 0, 1)) }}
                        </span>
                    </div>
                    <h5>{{ $student->nama }}</h5>
                    <p class="text-muted mb-1">NIS: {{ $student->nis ?? '-' }}</p>
                    <p class="text-muted mb-1">{{ $student->dormitory->name ?? '-' }}</p>
                    <p class="text-muted">Kamar {{ $student->room->kode ?? $student->room->nomor ?? '-' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Ringkasan Periode</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td>Total Event</td><td class="text-end"><strong>{{ $counts['total'] }}</strong></td></tr>
                        <tr><td>Izin</td><td class="text-end">{{ $counts['permits'] }}</td></tr>
                        <tr><td>Kunjungan</td><td class="text-end">{{ $counts['visits'] }}</td></tr>
                        <tr><td>Pelanggaran</td><td class="text-end">{{ $counts['violations'] }}</td></tr>
                        <tr><td>Mutasi Kamar</td><td class="text-end">{{ $counts['room_moves'] }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Dari</label>
                            <input type="date" name="start" class="form-control" value="{{ $start->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Sampai</label>
                            <input type="date" name="end" class="form-control" value="{{ $end->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100"><i class="ri-filter-line"></i></button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    @php
                        $iconMap = [
                            'check_in' => 'ri-login-box-line text-success',
                            'room_transfer' => 'ri-arrow-left-right-line text-info',
                            'leave_approved' => 'ri-pass-valid-line text-primary',
                            'leave_started' => 'ri-logout-box-line text-warning',
                            'returned' => 'ri-home-heart-line text-success',
                            'leave_overdue' => 'ri-alarm-warning-line text-danger',
                            'special_permission' => 'ri-vip-crown-line text-warning',
                            'permit_rejected' => 'ri-close-circle-line text-danger',
                            'visit_approved' => 'ri-footprint-line text-info',
                            'violation' => 'ri-error-warning-line text-danger',
                            'reward' => 'ri-medal-line text-success',
                            'hospitalized' => 'ri-heart-pulse-line text-danger',
                            'recovered' => 'ri-heart-line text-success',
                            'holiday' => 'ri-calendar-line text-secondary',
                            'expelled' => 'ri-user-unfollow-line text-danger',
                            'transfer' => 'ri-exchange-line text-secondary',
                        ];
                    @endphp

                    <div class="timeline-2">
                        @forelse($grouped as $date => $items)
                        <div class="time-label">
                            <span class="bg-light text-dark">{{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMM Y') }}</span>
                        </div>
                        @foreach($items as $e)
                        <div>
                            <i class="timeline-icon {{ $iconMap[$e->event_type] ?? 'ri-checkbox-circle-line text-secondary' }}"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="ri-time-line"></i> {{ $e->event_at->format('H:i') }}
                                </span>
                                <h3 class="timeline-header">
                                    {{ ucwords(str_replace('_',' ', $e->event_type)) }}
                                    @if($e->is_special_permission)
                                        <span class="badge bg-warning ms-1">Special</span>
                                    @endif
                                </h3>
                                <div class="timeline-body">
                                    @if($e->payload)
                                        <small class="text-muted">
                                            @foreach($e->payload as $k => $v)
                                                @if(is_string($v) || is_numeric($v))
                                                <strong>{{ ucwords(str_replace('_',' ', $k)) }}:</strong> {{ $v }} &nbsp;
                                                @endif
                                            @endforeach
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @empty
                        <div class="text-center text-muted py-5">
                            <i class="ri-time-line" style="font-size:3rem"></i>
                            <p class="mt-2">Tidak ada event dalam rentang waktu ini.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection