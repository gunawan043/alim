@extends('layouts.master')
@section('title') Detail Pensiun — {{ $gtk->name }} @endsection

@section('content')
@php
    $userId = $userId ?? request()->route('userId') ?? auth()->id();
    $tglLahir = $gtk->gtkProfile?->tanggal_lahir;
    $bupAge = (int) ($settings['bup_age'] ?? 58);
    $tmtPensiun = $tglLahir ? \Carbon\Carbon::parse($tglLahir)->addYears($bupAge) : null;
    $sisaBulan = $tmtPensiun ? \Carbon\Carbon::now()->diffInMonths($tmtPensiun, false) : null;
    $sisaTahun = $sisaBulan !== null ? intdiv($sisaBulan, 12) : null;
    $sisaBulanSisa = $sisaBulan !== null ? $sisaBulan % 12 : null;

    if ($sisaBulan !== null && $sisaBulan > 0) {
        $sisaText = '';
        if ($sisaTahun > 0) $sisaText .= $sisaTahun . ' tahun ';
        if ($sisaBulanSisa > 0) $sisaText .= $sisaBulanSisa . ' bulan';
        $sisaText = trim($sisaText) ?: '–';
        $sisaColorClass = ($sisaBulan <= 12) ? 'text-danger' : (($sisaBulan <= 24) ? 'text-warning' : 'text-primary');
    } elseif ($sisaBulan !== null) {
        $sisaText = 'SUDAH BUP';
        $sisaColorClass = 'text-danger';
    } else {
        $sisaText = '–';
        $sisaColorClass = 'text-muted';
    }

    $pension = $gtk->pension;

    $jenisLabel = match($pension?->pension_type) {
        'normal' => 'Normal',
        'dini' => 'Dini (Early)',
        'cacat' => 'Cacat',
        'janda' => 'Janda/Duda',
        default => 'Belum diatur'
    };

    $statusBadge = match($pension?->pension_status) {
        'draft'     => 'bg-secondary-subtle text-secondary',
        'pending'   => 'bg-warning-subtle text-warning',
        'approved'  => 'bg-success-subtle text-success',
        'completed' => 'bg-primary-subtle text-primary',
        'cancelled' => 'bg-danger-subtle text-danger',
        default     => 'bg-secondary-subtle text-secondary'
    };

    $statusLabel = match($pension?->pension_status) {
        'draft' => 'Draft',
        'pending' => 'Pending',
        'approved' => 'Disetujui',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
        default => '–'
    };

    $benefitAmount = $pension?->benefit_amount
        ? 'Rp ' . number_format((float) $pension->benefit_amount, 0, ',', '.')
        : '–';

@endphp

@component('components.breadcrumb')
    @slot('li_1') GTK @endslot
    @slot('li_2') <a href="{{ route('user.pension.index', ['userId' => $userId]) }}">Pensiun</a> @endslot
    @slot('title') Detail — {{ $gtk->name }} @endslot
@endcomponent

{{-- INFO CARDS --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2">
                            <i class="bx bx-user text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Usia Sekarang</p>
                        <h4 class="fw-bold ff-secondary mb-0">
                            @if($tglLahir){{ \Carbon\Carbon::parse($tglLahir)->age }} tahun @else–@endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2">
                            <i class="bx bx-time-five text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">BUP</p>
                        <h4 class="fw-bold ff-secondary mb-0">{{ $bupAge }} tahun</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-2">
                            <i class="bx bx-calendar-check text-info"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">TMT Estimasi</p>
                        <h4 class="fw-bold ff-secondary mb-0">
                            @if($tmtPensiun){{ $tmtPensiun->format('d M Y') }}@else–@endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2">
                            <i class="bx bx-hourglass-bottom text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Sisa Waktu</p>
                        <h4 class="fw-bold ff-secondary mb-0 {{ $sisaColorClass }}">{{ $sisaText }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MAIN DETAIL CARD --}}
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-4">
                                {{ strtoupper(substr($gtk->name, 0, 1)) }}
                            </div>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Detail Pensiun</h5>
                            <p class="text-muted mb-0" style="font-size:0.8rem;">
                                {{ $gtk->name }} &middot; {{ $gtk->employment?->jabatan ?? '–' }}
                                <span class="badge bg-primary-subtle text-primary ms-2" style="font-size:10px;">{{ $jenisLabel }}</span>
                                @if($pension)
                                <span class="badge {{ $statusBadge }} ms-1" style="font-size:10px;">{{ $statusLabel }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.pension.edit', ['userId' => $userId, 'uuid' => $gtk->id]) }}"
                           class="btn btn-primary btn-sm">
                            <i class="ri-pencil-line align-middle me-1"></i> Edit
                        </a>
                        <a href="{{ route('user.profile.cv', ['userId' => $userId, 'uuid' => $gtk->id]) }}"
                           target="_blank"
                           class="btn btn-soft-primary btn-sm">
                            <i class="ri-file-pdf-2-line align-middle me-1"></i> CV
                        </a>
                        <a href="{{ route('user.pension.index', ['userId' => $userId]) }}"
                           class="btn btn-light btn-sm">
                            <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    {{-- PENGHITUNGAN --}}
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-primary mb-3"><i class="ri-calculator-line me-1"></i> Perhitungan Pensiun</h6>
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <th class="text-muted" style="width:160px">Tanggal Lahir</th>
                                    <td>{{ $tglLahir ? \Carbon\Carbon::parse($tglLahir)->format('d F Y') : '–' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Usia Saat Ini</th>
                                    <td>@if($tglLahir){{ \Carbon\Carbon::parse($tglLahir)->age }} tahun @else–@endif</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Batas Pensiun (BUP)</th>
                                    <td>{{ $bupAge }} tahun</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Estimasi TMT Pensiun</th>
                                    <td>
                                        @if($tmtPensiun)
                                            <span class="fw-medium">{{ $tmtPensiun->format('d F Y') }}</span>
                                        @else
                                            –
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Sisa Waktu</th>
                                    <td>
                                        <span class="{{ $sisaColorClass }} fw-medium">{{ $sisaText }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- INFORMASI PENSIUN --}}
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-primary mb-3"><i class="ri-file-list-3-line me-1"></i> Data Pensiun</h6>
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <th class="text-muted" style="width:160px">Jenis Pensiun</th>
                                    <td>{{ $jenisLabel }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Status Proses</th>
                                    <td>
                                        @if($pension)
                                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                        @else
                                            <span class="text-muted">Belum diatur</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">TMT Pensiun (Aktual)</th>
                                    <td>
                                        @if($pension?->planned_pension_date)
                                            {{ \Carbon\Carbon::parse($pension->planned_pension_date)->format('d F Y') }}
                                        @else
                                            <span class="text-muted">Mengikuti BUP</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Besaran Benefit</th>
                                    <td>
                                        @if($pension?->benefit_amount)
                                            <span class="fw-semibold">{{ $benefitAmount }}</span>
                                            @if($pension->benefit_notes)
                                                <br><small class="text-muted">{{ $pension->benefit_notes }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Belum ditentukan</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- SURAT KEUTPAAN --}}
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-primary mb-3"><i class="ri-stamp-line me-1"></i> Surat Keputusan Pensiun</h6>
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <th class="text-muted" style="width:160px">No. SK Pensiun</th>
                                    <td>
                                        @if($pension?->pension_letter_no)
                                            <code>{{ $pension->pension_letter_no }}</code>
                                        @else
                                            <span class="text-muted">Belum ada SK</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Tanggal SK</th>
                                    <td>
                                        @if($pension?->pension_letter_date)
                                            {{ \Carbon\Carbon::parse($pension->pension_letter_date)->format('d F Y') }}
                                        @else
                                            <span class="text-muted">Belum ada</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- CATATAN --}}
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-primary mb-3"><i class="ri-sticky-note-line me-1"></i> Catatan</h6>
                            @if($pension?->notes)
                                <p class="mb-0 text-muted">{{ $pension->notes }}</p>
                            @else
                                <p class="text-muted mb-0">Belum ada catatan.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-12">
                        <h6 class="text-muted mb-2">Meta Informasi</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th class="text-muted" style="width:140px">Dibuat</th>
                                <td>{{ $pension?->created_at?->format('d F Y H:i') ?? '–' }} WIB</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Dihasilkan Oleh</th>
                                <td>{{ $pension?->processed_by ?? '–' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Terakhir Diubah</th>
                                <td>{{ $pension?->updated_at?->format('d F Y H:i') ?? '–' }} WIB</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
