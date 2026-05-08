@extends('layouts.master')
@section('title')
    Edit Data Pensiun — {{ $gtk->name }}
@endsection

@section('content')
@php
    $userId = $userId ?? request()->route('userId') ?? auth()->id();
    $tglLahir = $gtk->gtkProfile?->tanggal_lahir;
    $bupAge = (int) ($settings['bup_age'] ?? 58);
    $tmtPensiun = $tglLahir ? \Carbon\Carbon::parse($tglLahir)->addYears($bupAge) : null;
    $sisaBulan = $tmtPensiun ? \Carbon\Carbon::now()->diffInMonths($tmtPensiun, false) : null;
    $pension = $gtk->pension;

    if ($sisaBulan !== null && $sisaBulan > 0) {
        $sisaValue = (int)$sisaBulan . ' bulan';
        $sisaColorClass = ($sisaBulan <= 12) ? 'text-danger' : (($sisaBulan <= 24) ? 'text-warning' : 'text-primary');
    } elseif ($sisaBulan !== null) {
        $sisaValue = 'Sudah BUP';
        $sisaColorClass = 'text-danger';
    } else {
        $sisaValue = '–';
        $sisaColorClass = 'text-muted';
    }

    $jenisLabel = match($pension?->pension_type) {
        'normal' => 'Normal',
        'dini' => 'Dini',
        'cacat' => 'Cacat',
        'janda' => 'Janda/Duda',
        default => '–'
    };

    $statusLabel = match($pension?->pension_status) {
        'draft' => 'Draft',
        'pending' => 'Pending',
        'approved' => 'Disetujui',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
        default => '–'
    };

    $benefitLabel = $pension?->benefit_amount
        ? 'Rp ' . number_format((float) $pension->benefit_amount, 0, ',', '.')
        : '–';
@endphp
@component('components.breadcrumb')
    @slot('li_1') GTK @endslot
    @slot('li_2') Pensiun @endslot
    @slot('title') Edit — {{ $gtk->name }} @endslot
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
                            @if($tglLahir){{ \Carbon\Carbon::parse($tglLahir)->age }} th @else–@endif
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
                        <h4 class="fw-bold ff-secondary mb-0">{{ $bupAge }} th</h4>
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
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">TMT Pensiun</p>
                        <h4 class="fw-bold ff-secondary mb-0">
                            @if($tmtPensiun){{ $tmtPensiun->format('d/m/Y') }}@else–@endif
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
                        <h4 class="fw-bold ff-secondary mb-0 {{ $sisaColorClass }}">{{ $sisaValue }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MAIN FORM --}}
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
                            <h5 class="card-title mb-0">Edit Data Pensiun</h5>
                            <p class="text-muted mb-0" style="font-size:0.8rem;">
                                {{ $gtk->name }} &middot; {{ $gtk->employment?->jabatan ?? '–' }}
                                <span class="badge bg-primary-subtle text-primary ms-2" style="font-size:10px;">{{ $jenisLabel }}</span>
                                @if($pension)
                                <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:10px;">{{ $statusLabel }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.pension.index', ['userId' => $userId]) }}"
                           class="btn btn-secondary btn-sm">
                            <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                        </a>
                        <a href="{{ route('user.profile.cv', ['userId' => $userId, 'uuid' => $gtk->id]) }}"
                           target="_blank"
                           class="btn btn-soft-primary btn-sm">
                            <i class="ri-file-pdf-2-line align-middle me-1"></i> Lihat CV
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('user.pension.update', ['userId' => $userId, 'uuid' => $gtk->id]) }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">Jenis Pensiun</label>
                            <select name="pension_type" class="form-select">
                                <option value="">— Pilih Jenis —</option>
                                <option value="normal" {{ old('pension_type', $pension?->pension_type ?? '') === 'normal' ? 'selected' : '' }}>Pensi Normal</option>
                                <option value="dini" {{ old('pension_type', $pension?->pension_type ?? '') === 'dini' ? 'selected' : '' }}>Pensi Dini (Early)</option>
                                <option value="cacat" {{ old('pension_type', $pension?->pension_type ?? '') === 'cacat' ? 'selected' : '' }}>Pensi Cacat</option>
                                <option value="janda" {{ old('pension_type', $pension?->pension_type ?? '') === 'janda' ? 'selected' : '' }}>Pensi Janda/Duda</option>
                            </select>
                            @error('pension_type')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Status Proses</label>
                            <select name="pension_status" class="form-select">
                                <option value="draft" {{ old('pension_status', $pension?->pension_status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="pending" {{ old('pension_status', $pension?->pension_status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ old('pension_status', $pension?->pension_status ?? '') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="completed" {{ old('pension_status', $pension?->pension_status ?? '') === 'completed' ? 'selected' : '' }}>Selesai</option>
                                <option value="cancelled" {{ old('pension_status', $pension?->pension_status ?? '') === 'cancelled' ? 'selected' : '' }}>Batal</option>
                            </select>
                            @error('pension_status')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">TMT Pensiun</label>
                            <input type="date" name="planned_pension_date" class="form-control"
                                   value="{{ old('planned_pension_date', $pension?->planned_pension_date?->toDateString() ?? '') }}">
                            <small class="text-muted" style="font-size:0.75rem;">Kosongkan = auto dari BUP</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">Nomor SK Pensiun</label>
                            <input type="text" name="pension_letter_no" class="form-control"
                                   value="{{ old('pension_letter_no', $pension?->pension_letter_no ?? '') }}"
                                   placeholder="Contoh: SK/2026/IV/001">
                            @error('pension_letter_no')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Tanggal SK</label>
                            <input type="date" name="pension_letter_date" class="form-control"
                                   value="{{ old('pension_letter_date', $pension?->pension_letter_date?->toDateString() ?? '') }}">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">Besaran Dana Pensiun (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="benefit_amount" class="form-control"
                                       value="{{ old('benefit_amount', $pension?->benefit_amount ?? '') }}"
                                       min="0" step="1000" placeholder="Kosongkan jika belum ada">
                            </div>
                            <small class="text-muted" style="font-size:0.75rem;">Boleh kosong</small>
                            @error('benefit_amount')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Catatan Benefit</label>
                            <input type="text" name="benefit_notes" class="form-control"
                                   value="{{ old('benefit_notes', $pension?->benefit_notes ?? '') }}"
                                   placeholder="Contoh: termasuk THT, BPJS, pesangon">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="4"
                                  placeholder="Catatan proses pensiun, alasan early retirement, dll">{{ old('notes', $pension?->notes ?? '') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('user.pension.index', ['userId' => $userId]) }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line align-middle me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
