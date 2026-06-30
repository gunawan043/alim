@extends('waka.master')
@section('title') Detail Supervisi @endsection

@section('css')
<style>
    .detail-section { padding: 14px 18px; background: #f8fafc; border-radius: 8px; }
    .detail-section h6 { font-size: 13px; text-transform: uppercase; letter-spacing: .5px; color: #475569; }
    .meta-line { font-size: 14px; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Supervisi @endslot
        @slot('li_2') <a href="{{ route('waka.supervisi.index') }}">Daftar</a> @endslot
        @slot('title') Detail Supervisi @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Supervisi</h5>
                    <div>
                        <a href="{{ route('waka.supervisi.edit', $supervisi->id) }}" class="btn btn-warning btn-sm"><i class="ri-edit-line"></i> Edit</a>
                        <a href="{{ route('waka.supervisi.index') }}" class="btn btn-secondary btn-sm"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 detail-section">
                            <h6 class="mb-2">Objek Supervisi</h6>
                            <div class="meta-line"><strong>GTK:</strong> {{ $supervisi->gtk?->name ?? $supervisi->gtk_name }}</div>
                            @if($supervisi->gtk?->latest_nupy)<div class="meta-line text-muted small">NUPY. {{ $supervisi->gtk->latest_nupy }}</div>@endif
                        </div>
                        <div class="col-md-6 detail-section">
                            <h6 class="mb-2">Observer</h6>
                            <div class="meta-line">{{ $supervisi->observer?->name ?? $supervisi->observer_name }}</div>
                        </div>
                        <div class="col-md-12 detail-section">
                            <h6 class="mb-2">Pelaksanaan</h6>
                            <table class="table table-borderless mb-0">
                                <tr><th style="width:200px">Tanggal</th><td>{{ $supervisi->tanggal_supervisi?->format('d F Y') }}</td></tr>
                                <tr><th>Jam</th><td>{{ $supervisi->jam_mulai }}–{{ $supervisi->jam_selesai }}</td></tr>
                                <tr><th>Tahun Ajaran</th><td>{{ $supervisi->academicYear?->name ?? '-' }} ({{ ucfirst(['1'=>'Ganjil','2'=>'Genap'][$supervisi->semester] ?? $supervisi->semester) }})</td></tr>
                                <tr><th>Mata Pelajaran</th><td>{{ $supervisi->mata_pelajaran ?: '-' }}</td></tr>
                                <tr><th>Jenis</th><td>{{ \Illuminate\Support\Str::headline($supervisi->jenis_supervisi) }}</td></tr>
                                <tr><th>Status</th><td><span class="badge bg-secondary">{{ ucfirst($supervisi->status) }}</span></td></tr>
                            </table>
                        </div>
                        <div class="col-md-12 detail-section">
                            <h6 class="mb-2">Tujuan</h6>
                            <p class="mb-0">{!! nl2br(e($supervisi->tujuan)) ?: '<span class="text-muted">-</span>' !!}</p>
                        </div>
                        <div class="col-md-6 detail-section">
                            <h6 class="mb-2">Catatan Temuan</h6>
                            <p class="mb-0">{!! nl2br(e($supervisi->catatan_temuan)) ?: '<span class="text-muted">-</span>' !!}</p>
                        </div>
                        <div class="col-md-6 detail-section">
                            <h6 class="mb-2">Rekomendasi</h6>
                            <p class="mb-0">{!! nl2br(e($supervisi->rekomendasi)) ?: '<span class="text-muted">-</span>' !!}</p>
                        </div>
                        <div class="col-md-12 detail-section">
                            <h6 class="mb-2">Tindak Lanjut</h6>
                            <p class="mb-0">{!! nl2br(e($supervisi->tindak_lanjut)) ?: '<span class="text-muted">-</span>' !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection