@extends('layouts.master')
@section('title') Riwayat Perpindahan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Riwayat Perpindahan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4">
                    <div class="col-sm"><h5 class="card-title mb-0">Riwayat Perpindahan Aset</h5></div>
                    <div class="col-sm-auto">
                        <a href="{{ route('sarpras.perpindahan.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i> Catat Perpindahan</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <select name="asset_id" class="form-control">
                            <option value="">Semua Aset</option>
                            @foreach($assets as $a)
                                <option value="{{ $a->id }}" {{ request('asset_id')==$a->id?'selected':'' }}>{{ $a->asset_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-nowrap">
                        <thead class="table-light">
                            <tr><th>#</th><th>Aset</th><th>Dari Ruang</th><th>Ke Ruang</th><th>Tanggal</th><th>Alasan</th><th>Petugas</th></tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $h)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $h->asset?->asset_name ?? '-' }}</td>
                                <td>{{ $h->fromRoom?->room_name ?? '-' }}</td>
                                <td><span class="badge bg-success-subtle text-success">{{ $h->toRoom?->room_name ?? '-' }}</span></td>
                                <td>{{ $h->moved_date?->format('d/m/Y') }}</td>
                                <td>{{ $h->reason ?? '-' }}</td>
                                <td>{{ $h->mover?->name ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-4"><p class="text-muted mb-0">Belum ada data perpindahan.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $histories])
            </div>
        </div>
    </div>
</div>
@endsection
