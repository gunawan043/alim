@extends('waka.master')

@section('title') {{ $surat->nomor_surat }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-masuk.index') }}">Surat Masuk</a> @endslot
        @slot('title') Detail Surat @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $verifyUrl = $surat->verification_token
            ? route('surat.verify', ['token' => $surat->verification_token])
            : null;
        $qrSvg = $verifyUrl
            ? \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(150)->margin(1)->generate($verifyUrl)
            : null;
        $statusTone = match ($surat->status) {
            'baru' => 'warning',
            'didisposisi' => 'info',
            'selesai' => 'success',
            default => 'secondary',
        };
        $sifatTone = match ($surat->sifat) {
            'rahasia' => 'danger',
            'penting' => 'warning',
            default => 'secondary',
        };
    @endphp

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="card-title mb-0"><i class="ri-mail-open-line me-2 text-primary"></i>{{ $surat->nomor_surat }}</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('waka.surat-masuk.edit', $surat->id) }}" class="btn btn-warning btn-sm">
                            <i class="ri-edit-line me-1"></i>Edit
                        </a>
                        <a href="{{ route('waka.surat-masuk.index') }}" class="btn btn-light btn-sm">
                            <i class="ri-arrow-go-back-line me-1"></i>Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <tbody>
                                <tr><th style="width:210px">Nomor Surat</th><td><strong>{{ $surat->nomor_surat }}</strong></td></tr>
                                <tr><th>Tanggal Surat</th><td>{{ $surat->tanggal_surat?->translatedFormat('d F Y') ?? '-' }}</td></tr>
                                <tr><th>Tanggal Diterima</th><td>{{ $surat->tanggal_diterima?->translatedFormat('d F Y') ?? '-' }}</td></tr>
                                <tr><th>Pengirim</th><td>{{ $surat->pengirim }}</td></tr>
                                <tr><th>Perihal</th><td>{{ $surat->perihal }}</td></tr>
                                <tr>
                                    <th>Sifat</th>
                                    <td><span class="badge bg-{{ $sifatTone }}-subtle text-{{ $sifatTone }}">{{ \App\Models\SuratMasuk::SIFAT_OPTIONS[$surat->sifat] ?? ucfirst($surat->sifat) }}</span></td>
                                </tr>
                                <tr>
                                    <th>Penyelesaian</th>
                                    <td>{{ \App\Models\SuratMasuk::PENYELESAIAN_OPTIONS[$surat->sifat_penyelesaian] ?? ucfirst($surat->sifat_penyelesaian) }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}">{{ \App\Models\SuratMasuk::STATUS_OPTIONS[$surat->status] ?? ucfirst($surat->status) }}</span></td>
                                </tr>
                                <tr>
                                    <th>Lampiran</th>
                                    <td>
                                        @if($surat->file_lampiran)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($surat->file_lampiran) }}"
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="ri-attachment-2 me-1"></i>Buka Lampiran
                                            </a>
                                        @else
                                            <span class="text-muted">Tidak ada lampiran</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr><th>Dibuat</th><td>{{ $surat->created_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                                <tr><th>Diperbarui</th><td>{{ $surat->updated_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-share-forward-line me-2 text-primary"></i>Disposisi</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Diteruskan Kepada</div>
                        <div class="fw-medium">{{ $surat->disposisi_to ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Catatan</div>
                        <div style="white-space: pre-line;">{{ $surat->disposisi_catatan ?: '—' }}</div>
                    </div>

                    @if($qrSvg)
                        <hr>
                        <div class="text-center">
                            {!! $qrSvg !!}
                            <div class="small text-muted mt-2">Pindai untuk verifikasi keaslian surat.</div>
                            <a href="{{ $verifyUrl }}" target="_blank" class="small">Buka halaman verifikasi</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
