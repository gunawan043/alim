@extends('layouts.master')
@section('title') Riwayat Perpindahan — {{ $asset->asset_name }} @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.aset.index') }}">Aset</a> @endslot
    @slot('li_3') <a href="{{ route('sarpras.aset.show', ['id' => $asset->id]) }}">{{ $asset->asset_name }}</a> @endslot
    @slot('title') Perpindahan @endslot
@endcomponent

<div class="row">
    {{-- INFO ASET --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Info Aset</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">Nama</td><td class="fw-medium">{{ $asset->asset_name }}</td></tr>
                    <tr><td class="text-muted small">Kode</td><td><code class="small">{{ $asset->asset_code ?? '-' }}</code></td></tr>
                    <tr><td class="text-muted small">Kategori</td><td>{{ $asset->category?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Kondisi</td>
                        <td>
                            @php $kc=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary']; @endphp
                            <span class="badge bg-{{ $kc[$asset->condition] ?? 'secondary' }}-subtle text-{{ $kc[$asset->condition] ?? 'secondary' }} small">
                                {{ ucfirst(str_replace('_',' ', $asset->condition)) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Lokasi Saat Ini</td>
                        <td>
                            @if($asset->room)
                                {{ $asset->room->room_name }}
                                @if($asset->room->building)
                                    <br><small class="text-muted">{{ $asset->room->building->building_name }}</small>
                                @endif
                            @else <span class="text-muted">-</span> @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="card-footer bg-transparent border-top">
                <a href="{{ route('sarpras.aset.show', ['id' => $asset->id]) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="ri-arrow-left-line me-1"></i> Kembali ke Detail Aset
                </a>
            </div>
        </div>

        {{-- PETA LOKASI PERPINDAHAN --}}
        @if($histories->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-route-line me-1"></i> Rute Perpindahan</h5></div>
            <div class="card-body p-0">
                <div class="p-3">
                    @php
                        $allRooms = collect();
                        if($asset->room) $allRooms->push($asset->room);
                        foreach($histories as $h) {
                            if($h->fromRoom && !$allRooms->contains('id', $h->fromRoom->id)) $allRooms->push($h->fromRoom);
                            if($h->toRoom && !$allRooms->contains('id', $h->toRoom->id)) $allRooms->push($h->toRoom);
                        }
                    @endphp
                    @foreach($histories->reverse()->values() as $i => $h)
                        <div class="d-flex align-items-start mb-2">
                            <div class="flex-shrink-0 me-2">
                                <span class="badge bg-primary rounded-circle" style="width:24px;height:24px;line-height:24px;font-size:11px;text-align:center">{{ $loop->iteration }}</span>
                            </div>
                            <div>
                                <small class="fw-medium">{{ $h->moved_date?->format('d/m/Y') }}</small><br>
                                <small class="text-muted">
                                    {{ $h->fromRoom?->room_name ?? 'Lokasi awal' }}
                                    &rarr;
                                    {{ $h->toRoom?->room_name ?? 'Lokasi akhir' }}
                                </small>
                            </div>
                        </div>
                    @endforeach
                    @if($asset->room)
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-2">
                                <span class="badge bg-success rounded-circle" style="width:24px;height:24px;line-height:24px;font-size:11px;text-align:center">&bull;</span>
                            </div>
                            <div>
                                <small class="fw-medium text-success">Sekarang</small><br>
                                <small class="text-muted">{{ $asset->room->room_name }}</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- TABEL RIWAYAT --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Riwayat Perpindahan Aset</h5>
                <a href="{{ route('sarpras.perpindahan.create') }}?asset_id={{ $asset->id }}" class="btn btn-success btn-sm">
                    <i class="ri-add-line me-1"></i> Catat Perpindahan
                </a>
            </div>
            <div class="card-body">
                @if($histories->isEmpty())
                    <div class="text-center py-5">
                        <i class="ri-git-branch-line fs-1 text-muted"></i>
                        <h5 class="text-muted mt-2">Belum ada riwayat perpindahan</h5>
                        <p class="text-muted small">Aset ini belum pernah dipindahkan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Dari</th>
                                    <th>Ke</th>
                                    <th>Alasan</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($histories as $h)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $h->moved_date?->format('d/m/Y') }}</td>
                                    <td>
                                        @if($h->fromRoom)
                                            {{ $h->fromRoom->room_name }}
                                            @if($h->fromRoom->building)
                                                <br><small class="text-muted">{{ $h->fromRoom->building->building_name }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($h->toRoom)
                                            <span class="badge bg-success-subtle text-success">{{ $h->toRoom->room_name }}</span>
                                            @if($h->toRoom->building)
                                                <br><small class="text-muted">{{ $h->toRoom->building->building_name }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $h->reason ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="ri-user-line text-muted"></i>
                                            {{ $h->mover?->name ?? '-' }}
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection