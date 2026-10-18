@extends('layouts.master')
@section('title') Jadwal Pemeliharaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Jadwal Pemeliharaan
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Jadwal', $stats['total'] ?? 0, 'primary', 'ri-calendar-line'],
            ['Menunggu Unit', $stats['pending'] ?? 0, 'warning', 'ri-time-line'],
            ['Terlambat', $stats['overdue'] ?? 0, 'danger', 'ri-alarm-warning-line'],
            ['Disetujui Unit', $stats['approved'] ?? 0, 'success', 'ri-check-double-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.pemeliharaan.schedule.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Satuan Kerja</label><select name="work_unit_id" class="form-select"><option value="">Semua</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" {{ request('work_unit_id') === $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Persetujuan</label><select name="unit_approval_status" class="form-select"><option value="">Semua</option>@foreach(['pending'=>'Menunggu Unit','approved'=>'Disetujui','reschedule_requested'=>'Minta Jadwal Ulang','rejected'=>'Ditolak'] as $key=>$label)<option value="{{ $key }}" {{ request('unit_approval_status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Frekuensi</label><select name="frequency" class="form-select"><option value="">Semua</option>@foreach(\App\Models\AssetMaintenanceSchedule::FREQUENCY_OPTIONS as $frequency)<option value="{{ $frequency }}" {{ request('frequency') === $frequency ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $frequency)) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Waktu</label><select name="status" class="form-select"><option value="">Semua</option><option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Terlambat</option><option value="upcoming" {{ request('status') === 'upcoming' ? 'selected' : '' }}>Akan datang</option></select></div>
                <div class="col-md-1"><button class="btn btn-primary w-100"><i class="ri-filter-3-line"></i></button></div>
                <div class="col-md-2"><a href="{{ route('sarpras.pemeliharaan.schedule.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line me-1"></i>Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-tools-line me-2 text-primary"></i>Jadwal Pemeliharaan</h5><p class="text-muted small mb-0">URT menyusun jadwal; satuan kerja hanya melihat dan menyetujui jadwal unitnya.</p></div>
            @if($canManage)<a href="{{ route('sarpras.pemeliharaan.schedule.create') }}" class="btn btn-success btn-sm"><i class="ri-add-line me-1"></i>Tambah Jadwal</a>@endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Jenis</th><th>Target</th><th>Satuan Kerja</th><th>Frekuensi</th><th>Jadwal Berikutnya</th><th>Penanggung Jawab</th><th>Persetujuan Unit</th><th>Waktu</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse($schedules as $i => $schedule)
                        @php
                            $approvalTone = ['pending'=>'warning','approved'=>'success','reschedule_requested'=>'info','rejected'=>'danger'][$schedule->unit_approval_status] ?? 'secondary';
                            $isOverdue = $schedule->next_maintenance_date?->isPast();
                        @endphp
                        <tr>
                            <td>{{ $schedules->firstItem() + $i }}</td>
                            <td class="fw-medium">{{ $schedule->maintenance_type }}</td>
                            <td class="small">@if($schedule->asset)<i class="ri-archive-line me-1"></i>{{ $schedule->asset->asset_name }}@elseif($schedule->room)<i class="ri-door-open-line me-1"></i>{{ $schedule->room->room_name }}@elseif($schedule->building)<i class="ri-building-line me-1"></i>{{ $schedule->building->building_name }}@endif</td>
                            <td class="small">{{ $schedule->workUnit?->name ?? '-' }}</td>
                            <td class="small">{{ ucfirst(str_replace('_',' ', $schedule->frequency)) }}</td>
                            <td class="small">{{ $schedule->next_maintenance_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="small">{{ $schedule->responsibleUser?->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $approvalTone }}-subtle text-{{ $approvalTone }}">{{ $schedule->approval_label }}</span>@if($schedule->unit_proposed_date)<div class="small text-muted mt-1">Usulan: {{ $schedule->unit_proposed_date->format('d/m/Y') }}</div>@endif</td>
                            <td>@if($isOverdue)<span class="badge bg-danger-subtle text-danger">Terlambat</span>@elseif($schedule->next_maintenance_date && $schedule->next_maintenance_date->diffInDays(now()) <= 7)<span class="badge bg-warning-subtle text-warning">Mendekati</span>@else<span class="badge bg-success-subtle text-success">Terjadwal</span>@endif</td>
                            <td class="text-end"><a href="{{ route('sarpras.pemeliharaan.schedule.show', $schedule->id) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a>@if($canManage)<a href="{{ route('sarpras.pemeliharaan.schedule.edit', $schedule->id) }}" class="btn btn-sm btn-outline-warning"><i class="ri-edit-line"></i></a>@endif</td>
                        </tr>
                    @empty
                        <x-empty-state colspan="10" title="Belum ada jadwal pemeliharaan" message="URT dapat menyusun jadwal perawatan gedung, ruang, atau unit aset.">
                            @if($canManage)<a href="{{ route('sarpras.pemeliharaan.schedule.create') }}" class="btn btn-success mt-3"><i class="ri-add-line me-1"></i>Tambah Jadwal</a>@endif
                        </x-empty-state>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($schedules->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $schedules])</div>@endif
    </div>
@endsection
