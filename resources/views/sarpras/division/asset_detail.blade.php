@extends('layouts.master')
@section('title') Detail Aset @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.division.index') }}">Division Portal</a> @endslot
    @slot('title') {{ Str::limit($asset->asset_name, 40) }} @endslot
@endcomponent

<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ $asset->asset_name }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4">
                        <h6 class="text-muted">Kode</h6>
                        <p><code>{{ $asset->asset_code }}</code></p>
                    </div>
                    <div class="col-sm-4">
                        <h6 class="text-muted">Kategori</h6>
                        <p>{{ $asset->category->nama ?? '-' }}</p>
                    </div>
                    <div class="col-sm-4">
                        <h6 class="text-muted">Status</h6>
                        <p>
                            <span class="badge {{ $asset->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst(str_replace('_', ' ', $asset->status)) }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-4">
                        <h6 class="text-muted">Ruangan</h6>
                        <p>{{ $asset->room?->room_name ?? '-' }} (Lt. {{ $asset->room?->floor ?? '-' }})</p>
                    </div>
                    <div class="col-sm-4">
                        <h6 class="text-muted">PIC</h6>
                        <p>{{ $asset->pic }}</p>
                    </div>
                    <div class="col-sm-4">
                        <h6 class="text-muted">Konstruksi</h6>
                        <p>{{ $asset->year ?? '-' }}</p>
                    </div>
                </div>
                <p class="mt-3">{{ $asset->description ?? '-' }}</p>
            </div>
        </div>

        {{-- Passport Link --}}
        <div class="card">
            <div class="card-body text-center">
                <a href="{{ route('sarpass.assets.passport.show', $asset->uuid ?? $asset->id) }}" class="btn btn-primary btn-lg">
                    <i class="ri-passport-line me-2"></i>Buka Asset Passport Lengkap
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Quick Actions</h6></div>
            <div class="card-body">
                <a href="{{ route('sarpass.assets.passport.show', $asset->uuid ?? $asset->id) }}" class="btn btn-info w-100 mb-2">
                    <i class="ri-passport-line me-1"></i> Passport
                </a>
                <a href="{{ route('sarpass.scan.start', $asset->id) }}" class="btn btn-outline-info w-100 mb-2">
                    <i class="ri-qr-scan-line me-1"></i> Scan QR
                </a>
                <a href="{{ route('sarpras.repairs.create', $asset->id) }}" class="btn btn-warning w-100 mb-2">
                    <i class="ri-tools-line me-1"></i> Buat Laporan Kerusakan
                </a>
            </div>
        </div>
    </div>
</div>
@endsection