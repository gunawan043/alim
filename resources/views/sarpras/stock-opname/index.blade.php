@extends('layouts.master')
@section('title') Stock Opname Aset @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Stock Opname
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Sesi', $stats['total'] ?? 0, 'primary', 'ri-clipboard-line'],
            ['Direncanakan', $stats['planned'] ?? 0, 'warning', 'ri-calendar-todo-line'],
            ['Berjalan', $stats['running'] ?? 0, 'info', 'ri-scan-2-line'],
            ['Selesai', $stats['closed'] ?? 0, 'success', 'ri-check-double-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body py-3 d-flex align-items-center gap-3">
                        <span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span>
                        <div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.stock-opname.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Kode / judul sesi…"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Satuan Kerja</label><select name="work_unit_id" class="form-select"><option value="">Semua</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" {{ request('work_unit_id') === $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['planned'=>'Direncanakan','in_progress'=>'Berjalan','closed'=>'Selesai','cancelled'=>'Dibatalkan'] as $key=>$label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                <div class="col-md-2"><a href="{{ route('sarpras.stock-opname.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line me-1"></i>Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-clipboard-line me-2 text-primary"></i>Sesi Stock Opname</h5>
                <p class="text-muted small mb-0">Opname gabungan Unit Rumah Tangga dan satuan kerja, berbasis QR per unit aset.</p>
            </div>
            <a href="{{ route('sarpras.stock-opname.create') }}" class="btn btn-success btn-sm"><i class="ri-add-line me-1"></i>Buat Sesi</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>No</th><th>Kode / Judul</th><th>Satuan Kerja</th><th>Cakupan</th><th>Jadwal</th><th>Status</th><th>Progres</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                    @forelse($sessions as $i => $session)
                        @php
                            $tone = ['planned'=>'warning','in_progress'=>'info','closed'=>'success','cancelled'=>'secondary'][$session->status] ?? 'secondary';
                            $label = ['planned'=>'Direncanakan','in_progress'=>'Berjalan','closed'=>'Selesai','cancelled'=>'Dibatalkan'][$session->status] ?? ucfirst($session->status);
                            $percent = $session->items_count > 0 ? round($session->observed_count / $session->items_count * 100) : 0;
                        @endphp
                        <tr>
                            <td>{{ $sessions->firstItem() + $i }}</td>
                            <td><a href="{{ route('sarpras.stock-opname.show', $session->id) }}" class="fw-medium link-primary">{{ $session->title }}</a><div class="small text-muted"><code>{{ $session->session_code }}</code></div></td>
                            <td>{{ $session->workUnit?->name ?? '-' }}</td>
                            <td class="small">@if($session->room)<div><i class="ri-door-open-line me-1"></i>{{ $session->room->room_name }}</div>@else<span class="text-muted">Seluruh ruang unit</span>@endif @if($session->category)<div class="text-muted"><i class="ri-price-tag-3-line me-1"></i>{{ $session->category->name }}</div>@endif</td>
                            <td class="small">{{ $session->scheduled_date?->format('d/m/Y') }}</td>
                            <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $label }}</span></td>
                            <td style="min-width:130px"><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-{{ $tone }}" style="width:{{ $percent }}%"></div></div><small class="text-muted">{{ $percent }}%</small></div><small class="text-muted">{{ $session->observed_count }}/{{ $session->items_count }} aset</small></td>
                            <td class="text-end"><a href="{{ route('sarpras.stock-opname.show', $session->id) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td>
                        </tr>
                    @empty
                        <x-empty-state colspan="8" title="Belum ada sesi stock opname" message="Buat sesi untuk mencocokkan data aset dengan kondisi fisik di lapangan.">
                            <a href="{{ route('sarpras.stock-opname.create') }}" class="btn btn-success mt-3"><i class="ri-add-line me-1"></i>Buat Sesi</a>
                        </x-empty-state>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sessions->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $sessions])</div>@endif
    </div>
@endsection
