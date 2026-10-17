@extends('layouts.master')

@section('title') Kegiatan Asrama @endsection

@section('css')
<style>
    .stat-card { border: 0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    .stat-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
</style>
@endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Kegiatan Asrama @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-1"><i class="ri-calendar-event-line me-2 text-primary"></i>Kegiatan Asrama</h5>
            <p class="text-muted small mb-0">Kegiatan dibuat Kepala/Wakil Asrama; presensi diisi Staf Asrama dan Wali Kamar.</p>
        </div>
        @if($canManage)
            <div class="d-flex gap-2">
                <a href="{{ route('user.asrama.kegiatan.schedules', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-outline-primary">
                    <i class="ri-repeat-line me-1"></i>Kegiatan Rutin
                </a>
                <a href="{{ route('user.asrama.kegiatan.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i>Tambah Kegiatan
                </a>
            </div>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body py-3">
                <div class="stat-label">Total Kegiatan</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['total']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body py-3">
                <div class="stat-label">Sedang Dibuka</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($stats['open']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body py-3">
                <div class="stat-label">Ditutup</div>
                <div class="fs-3 fw-bold text-secondary">{{ number_format($stats['closed']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body py-3">
                <div class="stat-label">Baris Presensi</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($stats['attendance']) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.asrama.kegiatan.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Cari Kegiatan</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama kegiatan…">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Dibuka</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Ditutup</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('user.asrama.kegiatan.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ri-list-check me-1 text-primary"></i>Daftar Kegiatan</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $activities->total() }} kegiatan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>Nama Kegiatan</th>
                            <th>Tanggal / Waktu</th>
                            <th>Lokasi</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Alpa</th>
                            <th>Status</th>
                            <th class="text-end" style="width:150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $i => $activity)
                            <tr>
                                <td>{{ $activities->firstItem() + $i }}</td>
                                <td>
                                    <div class="fw-medium">{{ $activity->name }}</div>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit((string) $activity->description, 60) }}</div>
                                </td>
                                <td class="small">
                                    {{ $activity->activity_date?->translatedFormat('d M Y') }}
                                    @if($activity->start_time)
                                        <div class="text-muted">{{ substr($activity->start_time, 0, 5) }}@if($activity->end_time)–{{ substr($activity->end_time, 0, 5) }}@endif</div>
                                    @endif
                                </td>
                                <td class="small">{{ $activity->location ?: '—' }}</td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $activity->hadir_count }}</span></td>
                                <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $activity->alpa_count }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $activity->isOpen() ? 'success' : 'secondary' }}-subtle text-{{ $activity->isOpen() ? 'success' : 'secondary' }}">
                                        {{ $activity->isOpen() ? 'Dibuka' : 'Ditutup' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('user.asrama.kegiatan.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'activityId' => $activity->id]) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="ri-eye-line"></i></a>
                                        @if($canRecord && $activity->isOpen())
                                            <a href="{{ route('user.asrama.kegiatan.attendance', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'activityId' => $activity->id]) }}" class="btn btn-sm btn-outline-primary" title="Isi Presensi"><i class="ri-user-follow-line"></i></a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="8" title="Belum ada kegiatan"
                                message="Kepala/Wakil Asrama dapat menambahkan kegiatan, lalu staf/wali kamar mengisi presensinya." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($activities->hasPages())
            <div class="card-footer">@include('shared._pagination', ['paginator' => $activities])</div>
        @endif
    </div>
@endsection
