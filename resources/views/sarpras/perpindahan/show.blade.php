@extends('layouts.master')
@section('title') Detail Perpindahan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.perpindahan.index') }}">Perpindahan</a> @endslot
    @slot('title') Detail @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detail Perpindahan Aset</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('sarpras.perpindahan.by-asset', ['id' => $history->asset_id]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-history-line me-1"></i> Riwayat Aset Ini
                    </a>
                    <a href="{{ route('sarpras.perpindahan.create') }}?asset_id={{ $history->asset_id }}" class="btn btn-success btn-sm">
                        <i class="ri-add-line me-1"></i> Catat Baru
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted fw-medium" style="width:200px">Aset</td>
                                <td>
                                    @if($history->asset)
                                        <a href="{{ route('sarpras.aset.show', ['id' => $history->asset_id]) }}" class="fw-medium">{{ $history->asset->asset_name }}</a>
                                        <br><small class="text-muted">{{ $history->asset->asset_code }}</small>
                                    @else — @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Dari Ruang</td>
                                <td>
                                    @if($history->fromRoom)
                                        {{ $history->fromRoom->room_name }}
                                        @if($history->fromRoom->building)
                                            <br><small class="text-muted">{{ $history->fromRoom->building->building_name }}</small>
                                        @endif
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Ke Ruang</td>
                                <td>
                                    @if($history->toRoom)
                                        <span class="badge bg-success-subtle text-success me-1">{{ $history->toRoom->room_name }}</span>
                                        @if($history->toRoom->building)
                                            <br><small class="text-muted">{{ $history->toRoom->building->building_name }}</small>
                                        @endif
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Tanggal Pindah</td>
                                <td>{{ $history->moved_date?->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Petugas</td>
                                <td>{{ $history->mover?->name ?? '-' }}</td>
                            </tr>
                            @if($history->reason)
                            <tr><td class="text-muted fw-medium">Alasan</td><td>{{ $history->reason }}</td></tr>
                            @endif
                            @if($history->asset?->room)
                            <tr>
                                <td class="text-muted fw-medium">Lokasi Saat Ini</td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">{{ $history->asset->room->room_name }}</span>
                                    @if($history->asset->room->building)
                                        <small class="text-muted"> — {{ $history->asset->room->building->building_name }}</small>
                                    @endif
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- FLOW VISUALIZATION --}}
        @if($history->fromRoom || $history->toRoom)
        <div class="card mt-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-route-line me-1"></i> Alur Perpindahan</h5></div>
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center gap-3">
                    <div class="text-center">
                        <div class="rounded-circle bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px">
                            <i class="ri-home-4-line fs-3"></i>
                        </div>
                        <p class="small fw-medium mb-0 mt-1">{{ $history->fromRoom?->room_name ?? 'Lokasi Awal' }}</p>
                        @if($history->fromRoom?->building)
                        <small class="text-muted">{{ $history->fromRoom->building->building_name }}</small>
                        @endif
                    </div>
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px">
                            <i class="ri-arrow-right-line fs-4"></i>
                        </div>
                        <small class="text-muted mt-1">{{ $history->moved_date?->format('d/m/Y') }}</small>
                    </div>
                    <div class="text-center">
                        <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px">
                            <i class="ri-map-pin-user-line fs-3"></i>
                        </div>
                        <p class="small fw-medium mb-0 mt-1">{{ $history->toRoom?->room_name ?? 'Lokasi Tujuan' }}</p>
                        @if($history->toRoom?->building)
                        <small class="text-muted">{{ $history->toRoom->building->building_name }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- SIDEBAR --}}
    <div class="col-lg-4">
        @if($history->asset)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Info Aset</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">Nama</td><td class="fw-medium">{{ $history->asset->asset_name }}</td></tr>
                    <tr><td class="text-muted small">Kode</td><td><code class="small">{{ $history->asset->asset_code ?? '-' }}</code></td></tr>
                    <tr><td class="text-muted small">Kategori</td><td>{{ $history->asset->category?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Kondisi</td>
                        <td>
                            @php $kc=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary']; @endphp
                            <span class="badge bg-{{ $kc[$history->asset->condition] ?? 'secondary' }}-subtle text-{{ $kc[$history->asset->condition] ?? 'secondary' }} small">
                                {{ ucfirst(str_replace('_',' ', $history->asset->condition)) }}
                            </span>
                        </td>
                    </tr>
                    <tr><td class="text-muted small">Petugas</td><td>{{ $history->mover?->name ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="card-footer bg-transparent border-top">
                <a href="{{ route('sarpras.aset.show', ['id' => $history->asset_id]) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="ri-eye-line me-1"></i> Lihat Detail Aset
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection