@extends('waka.master')

@section('title') {{ $surat->nomor_surat }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-keluar.index') }}">Surat Keluar</a> @endslot
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
            'draft' => 'secondary',
            'terkirim' => 'success',
            'dibatalkan' => 'danger',
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
                    <h5 class="card-title mb-0"><i class="ri-send-plane-line me-2 text-primary"></i>{{ $surat->nomor_surat }}</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('waka.surat-keluar.cetak', $surat->id) }}" target="_blank" class="btn btn-info btn-sm">
                            <i class="ri-printer-line me-1"></i>Cetak
                        </a>
                        <a href="{{ route('waka.surat-keluar.edit', $surat->id) }}" class="btn btn-warning btn-sm">
                            <i class="ri-edit-line me-1"></i>Edit
                        </a>
                        <a href="{{ route('waka.surat-keluar.index') }}" class="btn btn-light btn-sm">
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
                                <tr><th>Tanggal Kirim</th><td>{{ $surat->tanggal_kirim?->translatedFormat('d F Y') ?? 'Belum dikirim' }}</td></tr>
                                <tr><th>Tujuan</th><td>{{ $surat->tujuan }}</td></tr>
                                <tr><th>Perihal</th><td>{{ $surat->perihal }}</td></tr>
                                <tr>
                                    <th>Sifat</th>
                                    <td><span class="badge bg-{{ $sifatTone }}-subtle text-{{ $sifatTone }}">{{ \App\Models\SuratKeluar::SIFAT_OPTIONS[$surat->sifat] ?? ucfirst($surat->sifat) }}</span></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}">{{ \App\Models\SuratKeluar::STATUS_OPTIONS[$surat->status] ?? ucfirst($surat->status) }}</span></td>
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

                    @if($surat->isi_surat)
                        <hr>
                        <h6 class="mb-2"><i class="ri-file-text-line me-1 text-primary"></i>Isi Surat</h6>
                        <div style="white-space: pre-line; line-height: 1.7;">{{ $surat->isi_surat }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-pen-nib-line me-2 text-primary"></i>Penandatangan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Nama</div>
                        <div class="fw-medium">{{ $surat->penandatangan ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Jabatan</div>
                        <div>{{ $surat->jabatan_penandatangan ?: '—' }}</div>
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
