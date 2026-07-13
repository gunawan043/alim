@extends('layouts.master')

@section('title', 'Kalender Kunjungan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Kalender Kunjungan</h4>
                    <small class="text-muted">Jadwal kunjungan wali & keluarga ke asrama</small>
                </div>
                <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid ?? \App\Models\Dormitory::where('is_active', true)->first()?->id]) }}"
                   class="btn btn-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Jadwal</p>
                    <h4>{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Akan Datang</p>
                    <h4 class="text-info">{{ $stats['upcoming'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Sudah Check-in</p>
                    <h4 class="text-success">{{ $stats['arrived'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">No-Show</p>
                    <h4 class="text-danger">{{ $stats['no_show'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Dari</label>
                    <input type="date" name="start" class="form-control" value="{{ $start->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="end" class="form-control" value="{{ $end->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Asrama</label>
                    <select name="dormitory_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($dormitories as $d)
                            <option value="{{ $d->id }}" {{ $selectedDorm == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i> Filter</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            @forelse($grouped as $date => $items)
            <div class="mb-4">
                <h6 class="text-info">
                    <i class="ri-calendar-event-line me-1"></i>
                    {{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM Y') }}
                    <span class="badge bg-light text-dark ms-2">{{ $items->count() }} kunjungan</span>
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Pengunjung</th>
                                <th>Santri</th>
                                <th>Asrama / Kamar</th>
                                <th>Keperluan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $v)
                            <tr>
                                <td><strong>{{ $v->expected_arrival_datetime->format('H:i') }}</strong></td>
                                <td>
                                    <strong>{{ $v->visitor_name }}</strong>
                                    <br><small class="text-muted">{{ $v->visitor_relationship }}</small>
                                </td>
                                <td>
                                    {{ $v->student->nama ?? '?' }}
                                    <br><small class="text-muted">{{ $v->student->nis ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ $v->dormitory->name ?? '-' }}
                                    <br><small class="text-muted">Kamar {{ $v->room->kode ?? $v->room->nomor ?? '-' }}</small>
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $v->purpose }}</span></td>
                                <td>
                                    @php
                                        $badge = match($v->status) {
                                            'arrived' => 'success',
                                            'checked_out' => 'secondary',
                                            'approved' => 'info',
                                            'pending' => 'warning',
                                            'rejected', 'cancelled' => 'danger',
                                            'no_show' => 'dark',
                                            default => 'light',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ str_replace('_',' ', ucfirst($v->status)) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('user.calendar.visit.show', ['userId' => $userId, 'id' => $v->id]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-5">
                <i class="ri-footprint-line" style="font-size:3rem"></i>
                <p class="mt-2">Tidak ada jadwal kunjungan dalam rentang waktu ini.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection