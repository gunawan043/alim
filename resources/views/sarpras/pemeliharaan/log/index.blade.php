@extends('layouts.master')
@section('title') Riwayat Perawatan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Riwayat Perawatan @endslot
@endcomponent

<x-sarpras-stats :items="[
    ['label'=>'Total Catatan','value'=>$stats['total'],'tone'=>'primary','icon'=>'ri-history-line'],
    ['label'=>'Preventif','value'=>$stats['preventif'],'tone'=>'success','icon'=>'ri-shield-check-line'],
    ['label'=>'Korektif / Perbaikan','value'=>$stats['korektif'],'tone'=>'warning','icon'=>'ri-tools-line'],
    ['label'=>'Total Biaya','value'=>'Rp '.number_format($stats['cost'],0,',','.'),'tone'=>'danger','icon'=>'ri-money-dollar-circle-line'],
]" />

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
                    <div class="col-md-3">
                        <select name="log_type" class="form-select">
                            <option value="">Semua Jenis Catatan</option>
                            @foreach(['preventif'=>'Preventif / Perawatan','korektif'=>'Korektif / Perbaikan','inspeksi'=>'Inspeksi'] as $value=>$label)
                                <option value="{{ $value }}" {{ request('log_type')===$value?'selected':'' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                    <div class="col-md-1"><a href="{{ route('sarpras.pemeliharaan.log.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a></div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
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
                            <x-empty-state colspan="10" title="Belum ada riwayat" message="Catatan perawatan dan perbaikan akan tampil di sini." />
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
