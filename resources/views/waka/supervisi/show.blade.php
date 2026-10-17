@extends('waka.master')
@section('title') Detail Supervisi @endsection

@section('css')
    @include('shared._styles')
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
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Supervisi</h5>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('waka.supervisi.penilaian', $supervisi->id) }}" class="btn btn-primary btn-sm"><i class="ri-checkbox-multiple-line"></i> Isi Penilaian</a>
                        <a href="{{ route('waka.supervisi.cetak', $supervisi->id) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="ri-printer-line"></i> Cetak</a>
                        <a href="{{ route('waka.supervisi.edit', $supervisi->id) }}" class="btn btn-warning btn-sm"><i class="ri-edit-line"></i> Edit</a>
                        <a href="{{ route('waka.supervisi.index') }}" class="btn btn-secondary btn-sm"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 detail-section">
                            <h6 class="mb-2">Objek Supervisi</h6>
                            <div class="meta-line"><strong>GTK:</strong> {{ $supervisi->gtk?->user?->name ?? $supervisi->gtk_name }}</div>
                            @if($supervisi->gtk?->latest_nupy)<div class="meta-line text-muted small">NUPY. {{ $supervisi->gtk->latest_nupy }}</div>@endif
                        </div>
                        <div class="col-md-6 detail-section">
                            <h6 class="mb-2">Observer</h6>
                            <div class="meta-line">{{ $supervisi->observer?->user?->name ?? $supervisi->observer_name }}</div>
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
                        <div class="col-md-12 detail-section">
                            <h6 class="mb-2">Hasil Penilaian</h6>
                            @if($supervisi->instrumen)
                                @php $nilaiAkhir = (float) $supervisi->nilai_akhir; @endphp
                                <table class="table table-borderless mb-0">
                                    <tr><th style="width:200px">Instrumen</th><td>{{ $supervisi->instrumen->nama }}</td></tr>
                                    <tr><th>Jumlah Skor</th><td>{{ number_format((float) $supervisi->skor_total, 2) }} / {{ number_format((float) $supervisi->skor_maks, 2) }}</td></tr>
                                    <tr><th>Nilai Akhir</th><td><span class="fw-bold fs-5">{{ number_format($nilaiAkhir, 2) }}</span></td></tr>
                                    <tr>
                                        <th>Ketercapaian</th>
                                        <td>
                                            <span class="badge {{
                                                $nilaiAkhir >= 86 ? 'bg-success-subtle text-success' :
                                                ($nilaiAkhir >= 70 ? 'bg-primary-subtle text-primary' :
                                                ($nilaiAkhir >= 55 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger'))
                                            }}">{{ $supervisi->ketercapaian ?? '—' }}</span>
                                        </td>
                                    </tr>
                                </table>
                                <div class="text-muted mt-2" style="font-size:.75rem;">
                                    86–100 Amat baik &bull; 70–85 Baik &bull; 55–69 Cukup &bull; &lt;55 Kurang
                                </div>
                            @else
                                <p class="mb-0 text-muted">Belum dinilai — klik <strong>Isi Penilaian</strong> untuk mengisi instrumen.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection