@extends('layouts.master')
@section('title') Sarana Prasarana @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Dashboard @endslot
@endcomponent

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="avatar-sm mx-auto mb-2"><span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-4"><i class="ri-archive-line"></i></span></div>
                <h4 class="mb-0">{{ $totalAset }}</h4>
                <small class="text-muted">Total Aset</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="avatar-sm mx-auto mb-2"><span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-4"><i class="ri-error-warning-line"></i></span></div>
                <h4 class="mb-0">{{ $asetRusak }}</h4>
                <small class="text-muted">Aset Rusak</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="avatar-sm mx-auto mb-2"><span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-4"><i class="ri-door-open-line"></i></span></div>
                <h4 class="mb-0">{{ $totalRuang }}</h4>
                <small class="text-muted">Total Ruang</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="avatar-sm mx-auto mb-2"><span class="avatar-title bg-info-subtle text-info rounded-circle fs-4"><i class="ri-money-dollar-circle-line"></i></span></div>
                <h4 class="mb-0">{{ $totalNilai ? 'Rp ' . number_format($totalNilai/1000000,1) . 'jt' : '-' }}</h4>
                <small class="text-muted">Nilai Total</small>
            </div>
        </div>
    </div>
</div>

{{-- Main Content: Daftar Aset --}}
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0">Daftar Aset Saya</h6>
                <a href="{{ route('sarpras.user.aset.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">
                    <i class="ri-arrow-right-s-line me-1"></i>Lihat Semua
                </a>
            </div>
            <div class="card-body p-0">
                @if($recentAssets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Nama Aset</th>
                                <th>Kategori</th>
                                <th>Ruang</th>
                                <th>Kondisi</th>
                                <th>Nilai (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAssets as $a)
                            <tr onclick="window.location='{{ route('sarpras.user.aset.show', ['userId' => $userId, 'id' => $a->id]) }}'" style="cursor:pointer">
                                <td>
                                    <div class="fw-semibold">{{ $a->asset_name }}</div>
                                    <small class="text-muted">{{ $a->asset_code ? '#'.$a->asset_code : '' }} {{ $a->brand ? '— '.$a->brand : '' }}</small>
                                </td>
                                <td><span class="badge bg-info-subtle text-info" style="font-size:11px;">{{ $a->category?->name ?? '-' }}</span></td>
                                <td>
                                    <div class="small">{{ $a->room?->room_name ?? '-' }}</div>
                                    @if($a->room?->building)
                                        <small class="text-muted">{{ $a->room->building->building_name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php $c = ['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary'][$a->condition] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $c }}-subtle text-{{ $c }}" style="font-size:10px;">
                                        {{ ucfirst(str_replace('_',' ',$a->condition)) }}
                                    </span>
                                </td>
                                <td class="text-end text-muted small">{{ $a->acquisition_price ? number_format($a->acquisition_price,0,',','.') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-light text-muted rounded-circle fs-1"><i class="ri-archive-line"></i></span></div>
                    <h6 class="text-muted">Belum ada aset</h6>
                    <a href="{{ route('sarpras.user.aset.create', ['userId' => $userId]) }}" class="btn btn-sm btn-primary mt-2">
                        <i class="ri-add-line me-1"></i>Tambah Aset
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Kondisi Aset --}}
        <div class="card mb-3">
            <div class="card-header bg-white py-2"><h6 class="mb-0"><i class="ri-bar-chart-box-line text-primary me-2"></i>Kondisi Aset</h6></div>
            <div class="card-body p-2">
                @php $kondisi = $recentAssets->groupBy('condition')->map->count(); @endphp
                @foreach(['baik','rusak_ringan','rusak_sedang','rusak_berat'] as $k)
                    @php $count = $recentAssets->where('condition', $k)->count(); @endphp
                    @if($count > 0)
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="small text-muted">{{ ucfirst(str_replace('_',' ',$k)) }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                    @endif
                @endforeach
                <div class="d-flex justify-content-between py-1">
                    <span class="small fw-semibold">Total</span>
                    <strong>{{ $totalAset }}</strong>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header bg-white py-2"><h6 class="mb-0"><i class="ri-links-line text-primary me-2"></i>Akses Cepat</h6></div>
            <div class="card-body p-2">
                <div class="d-grid gap-2">
                    <a href="{{ route('sarpras.user.aset.create', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary text-start">
                        <i class="ri-add-line me-2"></i>Tambah Aset Baru
                    </a>
                    <a href="{{ route('sarpras.user.aset.import', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary text-start">
                        <i class="ri-upload-cloud-2-line me-2"></i>Import Aset dari Excel
                    </a>
                    <a href="{{ route('sarpras.user.ruang.index', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary text-start">
                        <i class="ri-door-open-line me-2"></i>Kelola Ruang
                    </a>
                    <a href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-danger text-start">
                        <i class="ri-error-warning-line me-2"></i>Laporkan Kerusakan
                    </a>
                    <a href="{{ route('sarpras.user.pengadaan.index', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-success text-start">
                        <i class="ri-shopping-cart-line me-2"></i>Ajukan Pengadaan
                    </a>
                </div>
            </div>
        </div>

        {{-- Pending Summary --}}
        @if($pendingDamage + $pendingProcurement > 0)
        <div class="card border-warning mt-3">
            <div class="card-body p-2">
                <h6 class="text-warning mb-2"><i class="ri-time-line me-1"></i>Menunggu Tindakan</h6>
                <div class="small">
                    @if($pendingDamage > 0)
                    <div class="mb-1">&#8226; {{ $pendingDamage }} laporan kerusakan pending</div>
                    @endif
                    @if($pendingProcurement > 0)
                    <div>&#8226; {{ $pendingProcurement }} permintaan pengadaan pending</div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection