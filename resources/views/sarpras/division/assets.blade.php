@extends('layouts.master')
@section('title') Division Assets @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Aset Division @endslot
@endcomponent

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="card-title mb-0"><i class="ri-filter-3-line me-1"></i> Filter</h5>
            </div>
            <div class="col-auto">
                <a href="{{ route('sarpras.division.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari nama/kode..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="tersedia" {{ request('status')=='tersedia'?'selected':'' }}>Tersedia</option>
                    <option value="maintenance" {{ request('status')=='maintenance'?'selected':'' }}>Maintenance</option>
                    <option value="rusak" {{ request('status')=='rusak'?'selected':'' }}>Rusak</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kode</th>
                        <th>Ruang</th>
                        <th>PIC</th>
                        <th>Status</th>
                        <th>Passport</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $a)
                    <tr>
                        <td>{{ Str::limit($a->asset_name, 30) }}</td>
                        <td><code>{{ $a->asset_code }}</code></td>
                        <td>{{ $a->room?->room_name ?? '-' }}</td>
                        <td>{{ $a->pic }}</td>
                        <td>
                            <span class="badge {{ $a->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst(str_replace('_', ' ', $a->status)) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('sarpass.assets.passport.show', $a->uuid ?? $a->id) }}" class="btn btn-sm btn-link p-0">
                                <i class="ri-eye-line"></i> Passport
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">Tidak ada aset</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $assets->links() }}
    </div>
</div>
@endsection