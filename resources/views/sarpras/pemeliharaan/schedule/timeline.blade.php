@extends('layouts.master')
@section('title') Timeline Pemeliharaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}">Jadwal Pemeliharaan</a> @endslot
        @slot('title') Timeline {{ $year }}
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.pemeliharaan.schedule.timeline') }}" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Tahun</label><input type="number" name="year" class="form-control" value="{{ $year }}" min="2000" max="2100"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Satuan Kerja</label><select name="work_unit_id" class="form-select"><option value="">Semua</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" {{ request('work_unit_id') === $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Tampilkan</button></div>
                <div class="col-md-3 text-md-end">@if($canManage)<a href="{{ route('sarpras.pemeliharaan.schedule.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Tambah Jadwal</a>@endif</div>
            </form>
        </div>
    </div>

    @php
        $months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    @endphp

    <div class="row g-3">
        @foreach($months as $index => $month)
            @php $monthNumber = $index + 1; $items = $byMonth->get($monthNumber, collect()); @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2"><h6 class="mb-0">{{ $month }} {{ $year }}</h6><span class="badge bg-primary-subtle text-primary">{{ $items->count() }}</span></div>
                    <div class="card-body p-2">
                        @forelse($items as $schedule)
                            @php $tone = ['pending'=>'warning','approved'=>'success','reschedule_requested'=>'info','rejected'=>'danger'][$schedule->unit_approval_status] ?? 'secondary'; @endphp
                            <a href="{{ route('sarpras.pemeliharaan.schedule.show', ['id' => $schedule->id]) }}" class="d-block border rounded p-2 mb-2 text-decoration-none text-dark">
                                <div class="d-flex justify-content-between align-items-start gap-2"><div><div class="small fw-semibold">{{ $schedule->maintenance_type }}</div><div class="text-muted" style="font-size:.72rem">{{ $schedule->asset?->asset_name ?? $schedule->room?->room_name ?? $schedule->building?->building_name ?? '-' }}</div></div><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}" style="font-size:.65rem">{{ $schedule->approval_label }}</span></div>
                                <div class="d-flex justify-content-between mt-1" style="font-size:.7rem"><span class="text-muted">{{ $schedule->next_maintenance_date?->format('d/m/Y') }}</span><span class="text-muted">{{ $schedule->workUnit?->name }}</span></div>
                            </a>
                        @empty
                            <div class="text-center text-muted py-4 small"><i class="ri-calendar-line fs-3 d-block mb-1"></i>Belum ada jadwal</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
