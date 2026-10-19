@extends('layouts.master')
@section('title') Laporan Inventaris Per Ruang @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
    @slot('title') Inventaris Per Ruang @endslot
@endcomponent

<x-sarpras-stats :items="[
    ['label'=>'Total Ruang','value'=>$rooms->count(),'tone'=>'primary','icon'=>'ri-door-open-line'],
    ['label'=>'Total Aset','value'=>$totalAset,'tone'=>'info','icon'=>'ri-archive-2-line'],
    ['label'=>'Total Nilai','value'=>'Rp '.number_format($totalNilai,0,',','.'),'tone'=>'success','icon'=>'ri-money-dollar-circle-line'],
    ['label'=>'Rata-rata Aset/Ruang','value'=>$rooms->count() ? round($totalAset/$rooms->count()) : 0,'tone'=>'warning','icon'=>'ri-bar-chart-line'],
]" />

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">Inventaris Per Ruang</h5>
                <a href="{{ route('sarpras.laporan.inventaris-per-ruang.pdf') }}" class="btn btn-danger btn-sm"><i class="ri-file-pdf-line me-1"></i> Export PDF</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
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
                            <x-empty-state colspan="6" title="Belum ada data ruang" message="Tambahkan ruang dan aset untuk melihat laporan." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
