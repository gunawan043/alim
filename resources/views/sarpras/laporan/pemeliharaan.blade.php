@extends('layouts.master')
@section('title') Laporan Pemeliharaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
    @slot('title') Pemeliharaan @endslot
@endcomponent

{{-- SUMMARY --}}
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card card-body text-center">
            <p class="text-muted small mb-1">Total Perawatan</p>
            <h3 class="mb-0">{{ $logs->count() }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <p class="text-muted small mb-1">Total Biaya</p>
            <h3 class="mb-0 text-danger">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <p class="text-muted small mb-1">Rata-rata Biaya</p>
            <h3 class="mb-0">Rp {{ number_format($logs->count() ? $totalBiaya / $logs->count() : 0, 0, ',', '.') }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <p class="text-muted small mb-1">Perawatan Terakhir</p>
            <h3 class="mb-0">{{ $logs->first()?->maintenance_date?->format('d/m/Y') ?? '-' }}</h3>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Riwayat Pemeliharaan</h5>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="ri-printer-line me-1"></i> Cetak</button>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    @if($schools->isNotEmpty())
                    <div class="col-md-3">
                        <select name="school_id" class="form-select">
                            <option value="">Semua Satuan Pendidikan</option>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i> Filter</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('sarpras.laporan.pemeliharaan') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Jenis Perawatan</th>
                                <th>Target</th>
                                <th>Tanggal</th>
                                <th>Petugas</th>
                                <th>Vendor</th>
                                <th>Biaya</th>
                                <th>Kondisi Sebelum</th>
                                <th>Kondisi Sesudah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $l)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $l->maintenance_type }}</td>
                                <td>
                                    @if($l->asset)
                                        <a href="{{ route('sarpras.aset.show', ['id' => $l->asset_id]) }}">{{ $l->asset->asset_name }}</a>
                                    @elseif($l->room)
                                        {{ $l->room->room_name }}
                                    @elseif($l->building)
                                        {{ $l->building->building_name }}
                                    @else - @endif
                                </td>
                                <td>{{ $l->maintenance_date?->format('d/m/Y') }}</td>
                                <td>{{ $l->performer?->name ?? '-' }}</td>
                                <td>{{ $l->vendor_name ?? '-' }}</td>
                                <td class="text-end">{{ $l->actual_cost ? 'Rp ' . number_format($l->actual_cost, 0, ',', '.') : '-' }}</td>
                                <td>{{ ucfirst(str_replace('_',' ', $l->condition_before ?? '-')) }}</td>
                                <td>
                                    @if($l->condition_after)
                                        @php $colors=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary']; @endphp
                                        <span class="badge bg-{{ $colors[$l->condition_after] ?? 'secondary' }}-subtle text-{{ $colors[$l->condition_after] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_',' ', $l->condition_after)) }}
                                        </span>
                                    @else - @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center py-4">Tidak ada data pemeliharaan.</td></tr>
                            @endforelse
                        </tbody>
                        @if($logs->isNotEmpty())
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end">Total Biaya:</th>
                                <th class="text-end text-danger fw-bold">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection