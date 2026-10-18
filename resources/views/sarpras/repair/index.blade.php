@extends('layouts.master')
@section('title') Antrean Perbaikan Sarpras @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Antrean Perbaikan
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Permintaan', $stats['total'] ?? 0, 'primary', 'ri-tools-line'],
            ['Menunggu Dijadwalkan', $stats['waiting'] ?? 0, 'warning', 'ri-time-line'],
            ['Sudah Dijadwalkan', $stats['scheduled'] ?? 0, 'info', 'ri-calendar-check-line'],
            ['Selesai', $stats['done'] ?? 0, 'success', 'ri-check-double-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.repair.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="No permintaan / aset / judul…"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['submitted'=>'Diajukan','verification_pending'=>'Menunggu Verifikasi','approval_pending'=>'Menunggu Persetujuan','execution_pending'=>'Menunggu Pelaksanaan','started'=>'Dikerjakan','completed'=>'Selesai','closed'=>'Ditutup','verification_rejected'=>'Ditolak'] as $key=>$label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Prioritas</label><select name="priority" class="form-select"><option value="">Semua</option>@foreach(['high'=>'Tinggi','normal'=>'Normal','low'=>'Rendah'] as $key=>$label)<option value="{{ $key }}" {{ request('priority') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                <div class="col-md-1"><a href="{{ route('sarpras.repair.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-tools-line me-2 text-primary"></i>Permintaan Perbaikan</h5><p class="text-muted small mb-0">Laporan kerusakan unit otomatis masuk antrean. URT menentukan petugas dan jadwal.</p></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>No Permintaan</th><th>Aset / Lokasi</th><th>Pelapor</th><th>Prioritas</th><th>Status</th><th>Work Order</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse($repairs as $i => $repair)
                        @php
                            $tone = ['high'=>'danger','normal'=>'warning','low'=>'secondary'][$repair->priority] ?? 'secondary';
                            $statusTone = ['completed'=>'success','closed'=>'success','started'=>'info','execution_pending'=>'info','approval_pending'=>'warning','verification_pending'=>'warning','submitted'=>'warning','verification_rejected'=>'danger','approval_rejected'=>'danger'][$repair->status] ?? 'secondary';
                            $statusLabel = ['submitted'=>'Diajukan','verification_pending'=>'Menunggu Verifikasi','verification_in_progress'=>'Verifikasi','additional_info'=>'Perlu Info','approval_pending'=>'Menunggu Persetujuan','execution_pending'=>'Menunggu Pelaksanaan','started'=>'Dikerjakan','completed'=>'Selesai','closed'=>'Ditutup','stopped'=>'Dihentikan','verification_rejected'=>'Ditolak','approval_rejected'=>'Ditolak'][$repair->status] ?? ucfirst(str_replace('_',' ', $repair->status));
                        @endphp
                        <tr>
                            <td>{{ $repairs->firstItem() + $i }}</td>
                            <td><a href="{{ route('sarpras.repair.show', $repair->id) }}" class="fw-medium link-primary"><code>{{ $repair->request_number }}</code></a><div class="small text-muted">{{ $repair->title }}</div></td>
                            <td><div class="fw-medium">{{ $repair->asset?->asset_name ?? '-' }}</div><small class="text-muted">{{ $repair->asset?->room?->building?->building_name ? $repair->asset->room->building->building_name.' — ' : '' }}{{ $repair->asset?->room?->room_name ?? '-' }}</small></td>
                            <td class="small">{{ $repair->reportedBy?->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst($repair->priority) }}</span></td>
                            <td><span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}">{{ $statusLabel }}</span></td>
                            <td class="text-center">{{ $repair->work_orders_count }}</td>
                            <td class="small">{{ $repair->created_at?->format('d/m/Y') }}</td>
                            <td class="text-end"><a href="{{ route('sarpras.repair.show', $repair->id) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td>
                        </tr>
                    @empty
                        <x-empty-state colspan="9" title="Belum ada permintaan perbaikan" message="Laporan kerusakan dari satuan kerja akan muncul di sini." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($repairs->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $repairs])</div>@endif
    </div>
@endsection
