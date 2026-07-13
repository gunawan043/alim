@extends('layouts.master')

@section('title', 'Kalender Kepulangan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Kalender Kepulangan Santri</h4>
                    <small class="text-muted">Pantau kepulangan berdasarkan izin pulang</small>
                </div>
                <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid ?? \App\Models\Dormitory::where('is_active', true)->first()?->id]) }}"
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
                    <p class="text-muted mb-1">Total Rencana</p>
                    <h4>{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Tepat Waktu</p>
                    <h4 class="text-success">{{ $stats['on_time'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Terlambat</p>
                    <h4 class="text-danger">{{ $stats['overdue'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Belum Kembali (Lewat)</p>
                    <h4 class="text-warning">{{ $stats['pending'] }}</h4>
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
                <h6 class="text-primary">
                    <i class="ri-calendar-event-line me-1"></i>
                    {{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM Y') }}
                    <span class="badge bg-light text-dark ms-2">{{ $items->count() }} rencana</span>
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Santri</th>
                                <th>Asrama / Kamar</th>
                                <th>Tgl Kembali</th>
                                <th>Aktual</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $p)
                            <tr>
                                <td>
                                    <strong>{{ $p->student->nama ?? '?' }}</strong>
                                    <br><small class="text-muted">{{ $p->student->nis ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ $p->dormitory->name ?? '-' }}
                                    <br><small class="text-muted">Kamar {{ $p->room->kode ?? $p->room->nomor ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ $p->expected_return_datetime->format('H:i') }}
                                </td>
                                <td>
                                    @if($p->actual_return_datetime)
                                        <span class="text-success">{{ $p->actual_return_datetime->format('d M H:i') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusBadge = match($p->status) {
                                            'returned' => 'success',
                                            'approved' => $p->expected_return_datetime->isPast() ? 'warning' : 'info',
                                            'overdue' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($p->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('user.calendar.return.show', ['userId' => $userId, 'id' => $p->id]) }}"
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
                <i class="ri-calendar-2-line" style="font-size:3rem"></i>
                <p class="mt-2">Tidak ada rencana kepulangan dalam rentang waktu ini.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection