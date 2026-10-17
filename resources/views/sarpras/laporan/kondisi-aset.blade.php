@extends('layouts.master')
@section('title') Laporan Kondisi Aset @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
    @slot('title') Kondisi Aset @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">Laporan Kondisi Aset</h5>
                <a href="{{ route('sarpras.laporan.kondisi-aset.pdf') }}" class="btn btn-danger btn-sm"><i class="ri-file-pdf-line me-1"></i> Export PDF</a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <span class="badge bg-success-subtle text-success me-1">Baik: {{ $summary['baik'] }}</span>
                        <span class="badge bg-warning-subtle text-warning me-1">Rusak Ringan: {{ $summary['rusak_ringan'] }}</span>
                        <span class="badge bg-orange-subtle text-orange me-1">Rusak Sedang: {{ $summary['rusak_sedang'] }}</span>
                        <span class="badge bg-danger-subtle text-danger me-1">Rusak Berat: {{ $summary['rusak_berat'] }}</span>
                        <span class="badge bg-secondary-subtle text-secondary">Hilang: {{ $summary['hilang'] }}</span>
                        <strong class="ms-2">Total: {{ $summary['total'] }}</strong>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr><th>#</th><th>Nama Aset</th><th>Kode</th><th>Ruang</th><th>Kondisi</th></tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $a)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $a->asset_name }}</td>
                                <td><code>{{ $a->asset_code ?? '-' }}</code></td>
                                <td>{{ $a->room?->room_name ?? '-' }}</td>
                                <td>
                                    @php $kc=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'orange','rusak_berat'=>'danger','hilang'=>'secondary']; @endphp
                                    <span class="badge bg-{{ $kc[$a->condition] ?? 'secondary' }}-subtle text-{{ $kc[$a->condition] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$a->condition)) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $assets->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
