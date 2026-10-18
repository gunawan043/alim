@extends('layouts.master')
@section('title') Kartu Inventaris Ruangan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
        @slot('title') Kartu Inventaris Ruangan (KIR)
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.laporan.kir') }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold">Satuan Kerja</label><select name="work_unit_id" class="form-select"><option value="">Semua</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" {{ request('work_unit_id') === $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Ruang</label><select name="room_id" class="form-select"><option value="">Semua ruang</option>@foreach($allRooms as $room)<option value="{{ $room->id }}" {{ request('room_id') === $room->id ? 'selected' : '' }}>{{ $room->building?->building_name ? $room->building->building_name.' — ' : '' }}{{ $room->room_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                <div class="col-md-2"><a href="{{ route('sarpras.laporan.kir.pdf', request()->query()) }}" class="btn btn-danger w-100"><i class="ri-file-pdf-line me-1"></i>PDF</a></div>
            </form>
        </div>
    </div>

    @forelse($rooms as $room)
        <div class="card mb-3">
            <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h6 class="mb-0"><i class="ri-door-open-line me-1 text-primary"></i>{{ $room->room_name }}</h6><small class="text-muted">{{ $room->building?->building_name ?? '-' }} · {{ $room->workUnit?->name ?? '-' }} · PIC: {{ $room->responsibleUser?->name ?? $room->workUnit?->primarySarprasOfficer?->user?->name ?? '-' }}</small></div>
                <span class="badge bg-primary-subtle text-primary">{{ $room->assets->count() }} unit aset</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light"><tr><th>No Urut</th><th>Jenis Barang / Nama Barang</th><th>Merk</th><th>Tahun Pembelian</th><th>No Kode Barang</th><th class="text-end">Jumlah/Register</th><th class="text-center">Baik (B)</th><th class="text-center">Kurang Baik (KB)</th><th class="text-center">Rusak Berat (RB)</th><th>Keterangan</th></tr></thead>
                    <tbody>
                    @forelse($room->groupedAssets as $index => $group)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><div class="fw-medium">{{ $group['asset']->asset_name }}</div><small class="text-muted">{{ $group['asset']->category?->inventory_group ? $group['asset']->category->inventory_group.' · ' : '' }}{{ $group['asset']->category?->name }}</small></td>
                            <td>{{ $group['asset']->brand ?? '-' }}</td>
                            <td>{{ $group['asset']->acquisition_year ?? $group['asset']->acquisition_date?->format('Y') ?? '-' }}</td>
                            <td><code>{{ $group['asset']->asset_code }}</code></td>
                            <td class="text-end">{{ number_format($group['quantity']) }} {{ $group['asset']->unit }}</td>
                            <td class="text-center">{{ number_format($group['baik']) }}</td>
                            <td class="text-center">{{ number_format($group['kurang_baik']) }}</td>
                            <td class="text-center">{{ number_format($group['rusak_berat']) }}</td>
                            <td class="small text-muted">{{ $group['notes'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <x-empty-state colspan="10" title="Ruang belum berisi aset" message="Tambahkan aset ke ruang ini melalui menu Aset." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body"><x-empty-state title="Belum ada ruang" message="Tambahkan gedung dan ruang terlebih dahulu." /></div></div>
    @endforelse
@endsection
