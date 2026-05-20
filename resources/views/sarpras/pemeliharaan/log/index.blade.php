@extends('layouts.master')
@section('title') Riwayat Perawatan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Riwayat Perawatan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4">
                    <div class="col-sm"><h5 class="card-title mb-0">Riwayat Perawatan</h5></div>
                    <div class="col-sm-auto">
                        <a href="{{ route('sarpras.pemeliharaan.log.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i> Tambah Perawatan</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Cari jenis perawatan..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Jenis Perawatan</th><th>Target</th><th>Tanggal</th><th>Petugas</th><th>Vendor</th><th>Biaya</th><th>Kondisi Sebelum</th><th>Kondisi Sesudah</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $l)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $l->maintenance_type }}</td>
                                <td>
                                    @if($l->asset) <span class="badge bg-primary-subtle text-primary">{{ $l->asset->asset_name }}</span>
                                    @elseif($l->room) <span class="badge bg-info-subtle text-info">{{ $l->room->room_name }}</span>
                                    @elseif($l->building) <span class="badge bg-secondary-subtle">{{ $l->building->building_name }}</span>
                                    @endif
                                </td>
                                <td>{{ $l->maintenance_date?->format('d/m/Y') }}</td>
                                <td>{{ $l->performer?->name ?? '-' }}</td>
                                <td>{{ $l->vendor_name ?? '-' }}</td>
                                <td>{{ $l->actual_cost ? 'Rp '.number_format($l->actual_cost,0,',','.') : '-' }}</td>
                                <td>{{ ucfirst(str_replace('_',' ',$l->condition_before ?? '-')) }}</td>
                                <td>{{ ucfirst(str_replace('_',' ',$l->condition_after ?? '-')) }}</td>
                                <td><a href="{{ route('sarpras.pemeliharaan.log.show', ['id' => $l->id]) }}" class="btn btn-sm btn-soft-primary"><i class="ri-eye-line"></i></a></td>
                            </tr>
                            @empty
                            <tr><td colspan="10" class="text-center py-4"><p class="text-muted mb-0">Belum ada riwayat perawatan.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $logs])
            </div>
        </div>
    </div>
</div>
@endsection
