@extends('layouts.master')
@section('title') Detail Aset — {{ $aset->asset_name }} @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('li_2') <a href="{{ route('sarpras.user.aset.index', ['userId' => $userId]) }}">Aset</a> @endslot
    @slot('title') {{ $aset->asset_name }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-3 align-items-center">
                    <div class="col-sm">
                        <h5 class="mb-0">Detail Aset</h5>
                    </div>
                    <div class="col-sm-auto">
                        <div class="hstack gap-2 justify-content-end">
                            <a href="{{ route('sarpras.user.aset.edit', ['userId' => $userId, 'id' => $aset->id]) }}" class="btn btn-sm btn-warning">
                                <i class="ri-pencil-line me-1"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted fw-medium" style="width:160px">Nama Aset</td>
                                <td>{{ $aset->asset_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Kode Aset</td>
                                <td><code>{{ $aset->asset_code ?? '-' }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Kategori</td>
                                <td>{{ $aset->category?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Ruang</td>
                                <td>
                                    @if($aset->room)
                                        <a href="{{ route('sarpras.user.ruang.show', ['userId' => $userId, 'id' => $aset->room->id]) }}">
                                            {{ $aset->room->room_name }}
                                        </a>
                                        @if($aset->room->building)
                                            <span class="text-muted"> — {{ $aset->room->building->building_name }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Merk</td>
                                <td>{{ $aset->brand ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Model</td>
                                <td>{{ $aset->model ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Kondisi</td>
                                <td>
                                    @php
                                        $c = ['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary'][$aset->condition] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">
                                        {{ ucfirst(str_replace('_',' ',$aset->condition)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Status</td>
                                <td>
                                    @php $sc = ['tersedia'=>'success','dipinjam'=>'info','dalam_perbaikan'=>'warning','dihapus'=>'secondary'][$aset->status] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">
                                        {{ ucfirst(str_replace('_',' ',$aset->status)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Tanggal Perolehan</td>
                                <td>{{ $aset->acquisition_date ? \Carbon\Carbon::parse($aset->acquisition_date)->format('d M Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Harga Perolehan</td>
                                <td>{{ $aset->acquisition_price ? 'Rp ' . number_format($aset->acquisition_price,0,',','.') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Nilai Saat Ini</td>
                                <td>{{ $aset->current_value ? 'Rp ' . number_format($aset->current_value,0,',','.') : '-' }}</td>
                            </tr>
                            @if($aset->notes)
                            <tr>
                                <td class="text-muted fw-medium">Catatan</td>
                                <td>{{ $aset->notes }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection