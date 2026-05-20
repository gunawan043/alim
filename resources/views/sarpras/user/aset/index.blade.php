@extends('layouts.master')
@section('title') Daftar Aset @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('title') Daftar Aset @endslot
@endcomponent

<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0">Daftar Aset</h6>
                <div class="hstack gap-2">
                    <a href="{{ route('sarpras.user.aset.import', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-upload-cloud-2-line me-1"></i>Import
                    </a>
                    <a href="{{ route('sarpras.user.aset.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success">
                        <i class="ri-add-line me-1"></i>Tambah Aset
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($allAssets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Nama Aset</th>
                                <th>Kode</th>
                                <th>Kategori</th>
                                <th>Ruang</th>
                                <th>Kondisi</th>
                                <th>Nilai (Rp)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allAssets as $a)
                            <tr>
                                <td>
                                    <a href="{{ route('sarpras.user.aset.show', ['userId' => $userId, 'id' => $a->id]) }}" class="fw-semibold link-primary">
                                        {{ $a->asset_name }}
                                    </a>
                                    @if($a->brand)
                                        <div class="small text-muted">{{ $a->brand }}{{ $a->model ? ' — ' . $a->model : '' }}</div>
                                    @endif
                                </td>
                                <td><code class="small">{{ $a->asset_code ?? '-' }}</code></td>
                                <td><span class="badge bg-info-subtle text-info" style="font-size:11px;">{{ $a->category?->name ?? '-' }}</span></td>
                                <td>
                                    <div class="small">{{ $a->room?->room_name ?? '-' }}</div>
                                    @if($a->room?->building)
                                        <small class="text-muted">{{ $a->room->building->building_name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $c = ['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary'][$a->condition] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $c }}-subtle text-{{ $c }}" style="font-size:10px;">
                                        {{ ucfirst(str_replace('_',' ',$a->condition)) }}
                                    </span>
                                </td>
                                <td class="text-end text-muted small">{{ $a->acquisition_price ? number_format($a->acquisition_price,0,',','.') : '-' }}</td>
                                <td>
                                    <a href="{{ route('sarpras.user.aset.show', ['userId' => $userId, 'id' => $a->id]) }}" class="btn btn-sm btn-soft-primary py-0 px-1">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('sarpras.user.aset.edit', ['userId' => $userId, 'id' => $a->id]) }}" class="btn btn-sm btn-soft-warning py-0 px-1">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                    <form action="{{ route('sarpras.user.aset.destroy', ['userId' => $userId, 'id' => $a->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus aset ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger py-0 px-1"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-light text-muted rounded-circle fs-1"><i class="ri-archive-line"></i></span></div>
                    <h6 class="text-muted">Belum ada aset</h6>
                    <a href="{{ route('sarpras.user.aset.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success mt-2">
                        <i class="ri-add-line me-1"></i>Tambah Aset
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="ri-bar-chart-box-line text-primary me-2"></i>Ringkasan</h6></div>
            <div class="card-body p-2">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small text-muted">Total Aset</span>
                    <strong>{{ $allAssets->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small text-muted">Baik</span>
                    <strong>{{ $allAssets->where('condition','baik')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small text-muted">Rusak Ringan</span>
                    <strong>{{ $allAssets->where('condition','rusak_ringan')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small text-muted">Rusak Sedang</span>
                    <strong>{{ $allAssets->where('condition','rusak_sedang')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="small text-muted">Rusak Berat</span>
                    <strong>{{ $allAssets->where('condition','rusak_berat')->count() }}</strong>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="{{ route('sarpras.user.aset.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
                <i class="ri-add-line me-1"></i>Tambah Aset
            </a>
            <a href="{{ route('sarpras.user.aset.import', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
                <i class="ri-upload-cloud-2-line me-1"></i>Import dari Excel
            </a>
        </div>
    </div>
</div>
@endsection