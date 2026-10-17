@extends('layouts.master')
@section('title', 'Repair vs Replace')

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Repair vs Replace Engine @endslot
@endcomponent

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fs-4 mb-1">Rekomendasi Perbaikan atau Penggantian</h4>
        <p class="text-muted mb-0">Skor 0–100 — semakin tinggi, semakin sehat.</p>
    </div>
    <form method="POST" action="{{ route('sarpras.rvr.bulk') }}">
        @csrf
        <button class="btn btn-primary" type="submit">
            <i class="ri-refresh-line"></i> Evaluasi Ulang Semua Aset
        </button>
    </form>
</div>

<div class="row g-3 mb-4">
    @foreach(['GOOD' => 'success', 'MONITOR' => 'info', 'REPAIR' => 'warning', 'REPLACE' => 'danger', 'CRITICAL' => 'dark'] as $key => $color)
    <div class="col">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">{{ $key }}</h6>
                <h3 class="mb-0 text-{{ $color }}">{{ $summary[$key] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    @endforeach
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="recommendation" class="form-select">
            <option value="">Semua Rekomendasi</option>
            @foreach(['GOOD','MONITOR','REPAIR','REPLACE','CRITICAL'] as $rec)
            <option value="{{ $rec }}" @selected(($filters['recommendation'] ?? '') === $rec)>{{ $rec }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-primary">Filter</button>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Nama Aset</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th class="text-center">Skor</th>
                    <th class="text-center">Rekomendasi</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recommendations as $r)
                <tr>
                    <td><code>{{ $r->asset->asset_code }}</code></td>
                    <td>{{ $r->asset->asset_name }}</td>
                    <td>{{ $r->asset->category->name ?? '—' }}</td>
                    <td>
                        {{ $r->asset->room?->building?->building_name ?? '—' }}
                        <small class="text-muted d-block">{{ $r->asset->room?->room_name ?? '' }}</small>
                    </td>
                    <td class="text-center fw-bold">{{ $r->health_score }}</td>
                    <td class="text-center">
                        @php
                            $badge = match($r->recommendation) {
                                'GOOD' => 'success', 'MONITOR' => 'info',
                                'REPAIR' => 'warning', 'REPLACE' => 'danger',
                                default => 'dark'
                            };
                        @endphp
                        <span class="badge bg-{{ $badge }}">{{ $r->recommendation }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('sarpras.rvr.show', $r->asset_id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada rekomendasi. Jalankan evaluasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $recommendations->links() }}</div>
</div>
@endsection