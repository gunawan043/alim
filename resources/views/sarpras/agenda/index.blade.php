@extends('layouts.master')
@section('title') Agenda Kerja Sarpras @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Agenda Kerja
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Tugas', $stats['total'], 'primary', 'ri-calendar-task-line'],
            ['Terlambat', $stats['overdue'], 'danger', 'ri-alarm-warning-line'],
            ['Hari Ini', $stats['today'], 'warning', 'ri-sun-line'],
            ['Akan Datang', $stats['upcoming'], 'success', 'ri-calendar-check-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.agenda.index') }}" class="row g-2 align-items-end">
                @if($teamView)
                    <div class="col-md-4"><label class="form-label small fw-semibold">Pegawai Sarpras</label><select name="user_id" class="form-select">@foreach($users as $user)<option value="{{ $user->id }}" {{ $selectedUserId === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></div>
                @endif
                <div class="col-md-3"><label class="form-label small fw-semibold">Jenis Tugas</label><select name="type" class="form-select"><option value="">Semua</option>@foreach($types as $type)<option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Tampilkan</button></div>
                <div class="col-md-2"><a href="{{ route('sarpras.agenda.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line me-1"></i>Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-calendar-task-line me-2 text-primary"></i>Agenda {{ $selectedUser->name }}</h5>
                <p class="text-muted small mb-0">Work order, jadwal perawatan, checklist, dan stock opname yang menjadi tanggung jawab pegawai.</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Tugas</th><th>Lokasi / Unit</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse($tasks as $task)
                        @php $overdue = $task['date'] && $task['date']->lt(today()); @endphp
                        <tr class="{{ $overdue ? 'table-danger' : '' }}">
                            <td class="text-nowrap"><div class="fw-medium {{ $overdue ? 'text-danger' : '' }}">{{ $task['date']?->format('d/m/Y') ?? '-' }}</div><small class="text-muted">{{ $task['date']?->translatedFormat('l') ?? '-' }}</small></td>
                            <td><span class="badge bg-light text-dark border"><i class="{{ $task['icon'] }} me-1"></i>{{ $task['type'] }}</span></td>
                            <td><div class="fw-medium">{{ $task['title'] }}</div><small class="text-muted">{{ $task['subtitle'] }}</small></td>
                            <td class="small">{{ $task['location'] }}</td>
                            <td><span class="badge bg-{{ $task['tone'] }}-subtle text-{{ $task['tone'] }}">{{ $task['status'] }}</span></td>
                            <td class="text-end"><a href="{{ $task['url'] }}" class="btn btn-sm btn-outline-primary"><i class="ri-arrow-right-line"></i> Buka</a></td>
                        </tr>
                    @empty
                        <x-empty-state colspan="6" title="Tidak ada tugas" message="Belum ada work order, jadwal, checklist, atau stock opname untuk pegawai ini." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
