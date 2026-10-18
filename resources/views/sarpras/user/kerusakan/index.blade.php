@extends('layouts.master')
@section('title') Laporan Kerusakan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('title') Laporan Kerusakan
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Laporan', $stats['total'] ?? 0, 'primary', 'ri-file-list-3-line'],
            ['Menunggu', $stats['pending'] ?? 0, 'warning', 'ri-time-line'],
            ['Diproses', $stats['progress'] ?? 0, 'info', 'ri-tools-line'],
            ['Selesai', $stats['completed'] ?? 0, 'success', 'ri-check-double-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="No. laporan / aset / deskripsi…"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['pending'=>'Menunggu','reviewed'=>'Ditinjau','scheduled'=>'Dijadwalkan','in_progress'=>'Dikerjakan','completed'=>'Selesai','rejected'=>'Ditolak'] as $key=>$label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Tingkat</label><select name="damage_level" class="form-select"><option value="">Semua</option>@foreach(['ringan'=>'Ringan','sedang'=>'Sedang','berat'=>'Berat'] as $key=>$label)<option value="{{ $key }}" {{ request('damage_level') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                <div class="col-md-1"><a href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-error-warning-line me-2 text-danger"></i>Riwayat Laporan Kerusakan</h5><p class="text-muted small mb-0">Laporan otomatis masuk ke antrean perbaikan URT.</p></div>
            <a href="{{ route('sarpras.user.kerusakan.create', ['userId' => $userId]) }}" class="btn btn-danger btn-sm"><i class="ri-add-line me-1"></i>Buat Laporan</a>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>No. Laporan</th><th>Aset</th><th>Tingkat</th><th>Deskripsi</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($reports as $i => $report)
                @php
                    $lvl = ['ringan'=>'success','sedang'=>'warning','berat'=>'danger'][$report->damage_level] ?? 'secondary';
                    $st = ['pending'=>'warning','reviewed'=>'info','scheduled'=>'info','in_progress'=>'info','completed'=>'success','rejected'=>'secondary'][$report->status] ?? 'secondary';
                @endphp
                <tr>
                    <td>{{ $reports->firstItem() + $i }}</td>
                    <td><code class="small">{{ $report->report_number }}</code></td>
                    <td><div class="fw-medium">{{ $report->asset?->asset_name ?? '-' }}</div><small class="text-muted">{{ $report->asset?->room?->building?->building_name ? $report->asset->room->building->building_name.' — ' : '' }}{{ $report->asset?->room?->room_name ?? '-' }}</small></td>
                    <td><span class="badge bg-{{ $lvl }}-subtle text-{{ $lvl }}">{{ ucfirst($report->damage_level) }}</span></td>
                    <td class="small">{{ \Illuminate\Support\Str::limit($report->description, 60) }}</td>
                    <td><span class="badge bg-{{ $st }}-subtle text-{{ $st }}">{{ ucfirst(str_replace('_',' ', $report->status)) }}</span></td>
                    <td class="small">{{ $report->created_at?->format('d/m/Y') }}</td>
                    <td class="text-end">@if($report->asset)<a href="{{ route('sarpras.user.aset.show', ['userId' => $userId, 'id' => $report->asset_id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a>@endif</td>
                </tr>
            @empty
                <x-empty-state colspan="8" title="Belum ada laporan kerusakan" message="Laporkan kerusakan aset agar URT dapat menjadwalkan perbaikan.">
                    <a href="{{ route('sarpras.user.kerusakan.create', ['userId' => $userId]) }}" class="btn btn-danger mt-3"><i class="ri-add-line me-1"></i>Buat Laporan</a>
                </x-empty-state>
            @endforelse
        </tbody></table></div></div>
        @if($reports->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $reports])</div>@endif
    </div>
@endsection
