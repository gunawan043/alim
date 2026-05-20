@extends('layouts.master')
@section('title') Laporan Nilai Aset @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
    @slot('title') Nilai Aset @endslot
@endcomponent

{{-- SUMMARY CARDS --}}
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card card-body text-center">
            <p class="text-muted small mb-1">Total Aset</p>
            <h3 class="mb-0">{{ $assets->count() }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <p class="text-muted small mb-1">Total Nilai Perolehan</p>
            <h4 class="mb-0 text-primary">Rp {{ number_format($totalPerolehan, 0, ',', '.') }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <p class="text-muted small mb-1">Total Nilai Buku</p>
            <h4 class="mb-0 text-success">Rp {{ number_format($totalNilaiBuku, 0, ',', '.') }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <p class="text-muted small mb-1">Total Penyusutan</p>
            <h4 class="mb-0 text-danger">Rp {{ number_format($totalPenyusutan, 0, ',', '.') }}</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detail Nilai Aset</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('sarpras.laporan.export') }}" class="btn btn-success btn-sm">
                        <i class="ri-file-excel-line me-1"></i> Export Excel
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-printer-line me-1"></i> Cetak
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    @if($schools->isNotEmpty())
                    <div class="col-md-4">
                        <select name="school_id" class="form-select">
                            <option value="">Semua Satuan Pendidikan</option>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i> Filter</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('sarpras.laporan.nilai-aset') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Kode</th>
                                <th>Nama Aset</th>
                                <th>Kategori</th>
                                <th>Ruang</th>
                                <th class="text-end">Nilai Perolehan (Rp)</th>
                                <th class="text-end">Nilai Buku (Rp)</th>
                                <th class="text-end">Penyusutan (Rp)</th>
                                <th class="text-center">Usia (Thn)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $a)
                            @php
                                $acquisitionDate = $a->acquisition_date ?? null;
                                $age = $acquisitionDate ? now()->diffInYears(Carbon\Carbon::parse($acquisitionDate)) : null;
                                $depreciation = ($a->acquisition_price ?? 0) - ($a->current_value ?? 0);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><code>{{ $a->asset_code ?? '-' }}</code></td>
                                <td>
                                    <a href="{{ route('sarpras.aset.show', ['id' => $a->id]) }}" class="fw-medium">{{ $a->asset_name }}</a>
                                </td>
                                <td>{{ $a->category?->name ?? '-' }}</td>
                                <td>{{ $a->room?->room_name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($a->acquisition_price ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($a->current_value ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">{{ number_format($depreciation, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($age !== null)
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $age }}</span>
                                    @else - @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center py-4">Tidak ada data aset.</td></tr>
                            @endforelse
                        </tbody>
                        @if($assets->isNotEmpty())
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <th colspan="5" class="text-end">Total:</th>
                                <th class="text-end">Rp {{ number_format($totalPerolehan, 0, ',', '.') }}</th>
                                <th class="text-end">Rp {{ number_format($totalNilaiBuku, 0, ',', '.') }}</th>
                                <th class="text-end text-danger">Rp {{ number_format($totalPenyusutan, 0, ',', '.') }}</th>
                                <th></th>
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