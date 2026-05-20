@extends('layouts.master')
@section('title') Laporan Inventaris Per Ruang @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
    @slot('title') Inventaris Per Ruang @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">Inventaris Per Ruang</h5>
                <a href="{{ route('sarpras.laporan.inventaris-per-ruang.pdf') }}" class="btn btn-danger btn-sm"><i class="ri-file-pdf-line me-1"></i> Export PDF</a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Total Aset:</strong> {{ number_format($totalAset') }} &nbsp;|&nbsp;
                        <strong>Total Nilai:</strong> {{ 'Rp '.number_format($totalNilai, 0, ',', '.') }}
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr><th>#</th><th>Ruang</th><th>Gedung</th><th>Tipe</th><th>Jumlah Aset</th><th>Total Nilai</th></tr>
                        </thead>
                        <tbody>
                            @forelse($rooms as $r)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $r->room_name }}</td>
                                <td>{{ $r->building?->building_name ?? '-' }}</td>
                                <td>{{ ucfirst($r->room_type) }}</td>
                                <td>{{ $r->assets_count }}</td>
                                <td>{{ 'Rp '.number_format($r->assets->sum('acquisition_price'), 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
