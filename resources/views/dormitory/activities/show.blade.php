@extends('layouts.master')

@section('title') Detail Kegiatan Asrama @endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $activityUrl = ['userId' => $userId, 'asramaUuid' => $dormitory->id];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.kegiatan.index', $activityUrl) }}">Kegiatan</a> @endslot
        @slot('title') {{ $activity->name }} @endslot
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
            <h5 class="mb-1"><i class="ri-calendar-event-line me-2 text-primary"></i>{{ $activity->name }}</h5>
            <p class="text-muted small mb-0">
                {{ $activity->activity_date?->translatedFormat('l, d F Y') }}
                @if($activity->start_time)
                    · {{ substr($activity->start_time, 0, 5) }}@if($activity->end_time)–{{ substr($activity->end_time, 0, 5) }}@endif
                @endif
                @if($activity->location) · {{ $activity->location }}@endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.asrama.kegiatan.index', $activityUrl) }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-go-back-line me-1"></i>Kembali
            </a>
            @if($canRecord && $activity->isOpen())
                <a href="{{ route('user.asrama.kegiatan.attendance', $activityUrl + ['activityId' => $activity->id]) }}" class="btn btn-primary btn-sm">
                    <i class="ri-user-follow-line me-1"></i>Isi Presensi
                </a>
            @endif
            @if($canManage)
                @if($activity->isOpen())
                    <form method="POST" action="{{ route('user.asrama.kegiatan.close', $activityUrl + ['activityId' => $activity->id]) }}" class="d-inline"
                          data-confirm="Tutup kegiatan ini? Presensi akan dikunci.">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm"><i class="ri-lock-line me-1"></i>Tutup Kegiatan</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('user.asrama.kegiatan.reopen', $activityUrl + ['activityId' => $activity->id]) }}" class="d-inline"
                          data-confirm="Buka kembali kegiatan ini?">
                        @csrf
                        <button class="btn btn-outline-success btn-sm"><i class="ri-lock-unlock-line me-1"></i>Buka Kembali</button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            'hadir' => ['Hadir', 'success', 'ri-checkbox-circle-line'],
            'izin' => ['Izin', 'warning', 'ri-flight-takeoff-line'],
            'sakit' => ['Sakit', 'info', 'ri-sick-line'],
            'alpa' => ['Alpa', 'danger', 'ri-prohibited-line'],
            'total' => ['Total Tercatat', 'primary', 'ri-file-list-3-line'],
        ] as $key => [$label, $tone, $icon])
            <div class="col-6 col-xl">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body py-3 text-center">
                        <div class="avatar-sm mx-auto mb-2"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></div>
                        <h4 class="mb-0">{{ number_format($summary[$key]) }}</h4>
                        <p class="text-muted mb-0 small">{{ $label }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-information-line me-1 text-primary"></i>Informasi Kegiatan</h5>
            <span class="badge bg-{{ $activity->isOpen() ? 'success' : 'secondary' }}-subtle text-{{ $activity->isOpen() ? 'success' : 'secondary' }}">
                {{ $activity->isOpen() ? 'Dibuka — presensi dapat diisi' : 'Ditutup — presensi dikunci' }}
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-3"><span class="text-muted d-block">Dibuat oleh</span><span class="fw-medium">{{ $activity->creator?->name ?? '-' }}</span></div>
                <div class="col-md-3"><span class="text-muted d-block">Tanggal</span><span class="fw-medium">{{ $activity->activity_date?->translatedFormat('d/m/Y') }}</span></div>
                <div class="col-md-3"><span class="text-muted d-block">Waktu</span><span class="fw-medium">{{ $activity->start_time ? substr($activity->start_time, 0, 5) : '—' }}{{ $activity->end_time ? ' – '.substr($activity->end_time, 0, 5) : '' }}</span></div>
                <div class="col-md-3"><span class="text-muted d-block">Lokasi</span><span class="fw-medium">{{ $activity->location ?: '—' }}</span></div>
                @if($activity->description)
                    <div class="col-12"><span class="text-muted d-block">Deskripsi</span><span>{!! nl2br(e($activity->description)) !!}</span></div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-table-2 me-1 text-primary"></i>Presensi Kegiatan</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $summary['total'] }} santri tercatat</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>Santri</th>
                            <th>Kamar</th>
                            <th class="text-center">Status</th>
                            <th>Catatan</th>
                            <th>Dicatat oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $i => $row)
                            @php
                                $tone = match ($row->status) {
                                    'hadir' => 'success',
                                    'izin' => 'warning',
                                    'sakit' => 'info',
                                    'alpa' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-medium">{{ $row->student?->name ?? '-' }}</div>
                                    <div class="small text-muted">{{ $row->student?->nisn ?? '' }}</div>
                                </td>
                                <td class="small">{{ $row->room?->name ?? '-' }}</td>
                                <td class="text-center"><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst($row->status) }}</span></td>
                                <td class="small text-muted">{{ $row->notes ?: '—' }}</td>
                                <td class="small text-muted">{{ $row->recorder?->name ?? '-' }}<br>{{ $row->recorded_at?->format('d/m H:i') }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" title="Belum ada presensi"
                                message="Presensi belum diisi oleh staf asrama atau wali kamar.">
                                @if($canRecord && $activity->isOpen())
                                    <a href="{{ route('user.asrama.kegiatan.attendance', $activityUrl + ['activityId' => $activity->id]) }}" class="btn btn-primary mt-3">
                                        <i class="ri-user-follow-line me-1"></i>Isi Presensi Sekarang
                                    </a>
                                @endif
                            </x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
