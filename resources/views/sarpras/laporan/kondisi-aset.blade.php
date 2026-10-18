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
                <x-sarpras-stats :items="[
                    ['label'=>'Total Aset','value'=>$summary['total'],'tone'=>'primary','icon'=>'ri-archive-2-line'],
                    ['label'=>'Kondisi Baik','value'=>$summary['baik'],'tone'=>'success','icon'=>'ri-checkbox-circle-line'],
                    ['label'=>'Rusak Ringan/Sedang','value'=>$summary['rusak_ringan']+$summary['rusak_sedang'],'tone'=>'warning','icon'=>'ri-error-warning-line'],
                    ['label'=>'Rusak Berat/Hilang','value'=>$summary['rusak_berat']+$summary['hilang'],'tone'=>'danger','icon'=>'ri-close-circle-line'],
                ]" />
                <x-sarpras-filter-card :action="route('sarpras.laporan.kondisi-aset')" :reset="route('sarpras.laporan.kondisi-aset')">
                    <div class="col-md-3"><label class="form-label small fw-semibold">Kondisi</label><select name="condition" class="form-select"><option value="">Semua</option>@foreach(['baik'=>'Baik','rusak_ringan'=>'Rusak Ringan','rusak_sedang'=>'Rusak Sedang','rusak_berat'=>'Rusak Berat','hilang'=>'Hilang'] as $k=>$v)<option value="{{ $k }}" {{ request('condition')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
                    @if($schools->count())<div class="col-md-4"><label class="form-label small fw-semibold">Sekolah</label><select name="school_id" class="form-select"><option value="">Semua</option>@foreach($schools as $school)<option value="{{ $school->id }}" {{ request('school_id')==$school->id?'selected':'' }}>{{ $school->name }}</option>@endforeach</select></div>@endif
                </x-sarpras-filter-card>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>No</th><th>Nama Aset</th><th>Kode</th><th>Ruang</th><th>Kondisi</th></tr>
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
                            <x-empty-state colspan="5" title="Tidak ada data aset" message="Sesuaikan filter atau tambahkan aset terlebih dahulu." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($assets->hasPages())<div class="mt-3">@include('shared._pagination', ['paginator' => $assets])</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
