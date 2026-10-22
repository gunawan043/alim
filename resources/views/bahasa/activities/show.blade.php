@extends('layouts.master')

@section('title', 'Detail Kegiatan Bahasa')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.activities.index', ['userId' => $userId]) }}">AM Nasyath Syahri</a> @endslot
        @slot('title') {{ $activity->title }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <h4 class="mb-1">{{ $activity->title }}</h4>
                    <div class="lang-muted">{{ $types[$activity->activity_type] ?? $activity->activity_type }} · {{ $activity->period_month?->format('m/Y') }}</div>
                    <p class="mt-2 mb-1">{{ $activity->theme ?: 'Tema belum ditentukan.' }}</p>
                    <div class="lang-muted">{{ $activity->activity_date?->format('d/m/Y') ?? 'Tanggal belum ditentukan' }} {{ $activity->time_start ? '· '.substr($activity->time_start, 0, 5) : '' }}{{ $activity->time_end ? ' - '.substr($activity->time_end, 0, 5) : '' }} · {{ $activity->place ?: 'Tempat belum ditentukan' }}</div>
                </div>
                <div class="d-flex gap-2 align-items-start">
                    <a href="{{ route('user.bahasa.activities.print', ['userId' => $userId, 'activity' => $activity->id]) }}" target="_blank" class="btn btn-light"><i class="ri-printer-line me-1"></i>Cetak</a>
                    @if($canManage)<a href="{{ route('user.bahasa.activities.edit', ['userId' => $userId, 'activity' => $activity->id]) }}" class="btn btn-soft-warning"><i class="ri-pencil-line me-1"></i>Edit</a>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-4"><div class="card h-100"><div class="card-body"><div class="lang-muted">Pelaksana</div><div class="fw-semibold">{{ ucfirst($activity->executor_type) }}{{ $activity->executor_name ? ' · '.$activity->executor_name : '' }}</div><div class="lang-muted mt-2">MC</div><div>{{ $activity->mc_name ?: '-' }}</div><div class="lang-muted mt-2">Peserta</div><div>{{ $activity->participants_count }}</div></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-body"><div class="lang-muted">Panitia</div><div>{{ $activity->committee ?: '-' }}</div></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-body"><div class="lang-muted">Status</div><div class="fw-semibold">{{ ucfirst($activity->status) }}</div><div class="lang-muted mt-2">Catatan Partisipasi</div><div>{{ $activity->participation_notes ?: '-' }}</div></div></div></div>
        <div class="col-12"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Laporan Kegiatan</h5></div><div class="card-body">{{ $activity->report ?: 'Laporan belum diisi.' }}</div></div></div>
    </div>
@endsection
