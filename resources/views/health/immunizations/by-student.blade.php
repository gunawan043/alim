{{-- Immunizations by Student --}}
@extends('layouts.master')
@section('title') Riwayat Imunisasi - {{ $student->name ?? 'Student' }} @endsection

@push('css')
<style>
.patient-banner{background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);border:1px solid #6ee7b7;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .patient-banner{background:linear-gradient(135deg,#022c22 0%,#064e3b 100%);border-color:#059669}
.record-card{border-left:3px solid #10b981;margin-bottom:.75rem}
.record-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.06)}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') UKS @endslot
    @slot('li_2') Imunisasi @endslot
    @slot('title') Riwayat Imunisasi Siswa @endslot
@endcomponent

<div class="patient-banner mb-4 d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <div style="width:52px;height:52px;background:#05966918;color:#047857;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-user-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">{{ $student->name ?? '-' }}</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">
                @if($student->nisn) NISN: {{ $student->nisn }} @endif
                @if($student->currentClassHistory)
                    &middot; {{ $student->currentClassHistory->studyGroup->full_name ?? '-' }}
                @endif
            </p>
        </div>
    </div>
    <div class="flex-shrink-0">
        <a href="{{ route('user.uks.immunizations.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-light-subtle border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-shield-check-line text-success me-1"></i> Riwayat Imunisasi ({{ $records->count() }})</h5>
    </div>
    <div class="card-body p-0">
        @forelse($records as $record)
            <div class="record-card px-4 py-3 d-flex flex-wrap align-items-center gap-4 bg-white">
                <div class="d-flex flex-column align-items-center" style="min-width:80px">
                    <div class="avatar-sm bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                        <i class="ri-syringe-line fs-4"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success mt-2" style="font-size:.7rem">
                        {{ \Carbon\Carbon::parse($record->date_given)->format('d M Y') }}
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">{{ $record->immunization_type }}</div>
                    <div class="text-muted small">
                        @if($record->vaccine_name) Vaksin: {{ $record->vaccine_name }} @endif
                        @if($record->batch_number) &middot; Batch: {{ $record->batch_number }} @endif
                    </div>
                    <div class="text-muted small mt-1">
                        @if($record->place) <i class="ri-map-pin-line me-1"></i>{{ $record->place }} @endif
                        @if($record->medical_staff) &middot; <i class="ri-user-nurse-line me-1"></i>{{ $record->medical_staff }} @endif
                    </div>
                    @if($record->notes)
                        <div class="text-muted small mt-1"><i class="ri-sticky-note-line me-1"></i>{{ $record->notes }}</div>
                    @endif
                    @if($record->side_effects)
                        <div class="text-warning small mt-1"><i class="ri-alert-line me-1"></i>Selera Efek: {{ $record->side_effects }}</div>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    <a href="{{ route('user.uks.immunizations.show', [$userId, $record->uuid]) }}" class="btn btn-sm btn-light"><i class="ri-eye-line me-1"></i>Detail</a>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div style="color:#10b981;opacity:.4"><i class="ri-syringe-line" style="font-size:3rem"></i></div>
                <h5 class="mt-2 fw-semibold">Belum ada riwayat imunisasi</h5>
                <p class="text-muted mb-0 small">Data imunisasi siswa akan muncul di sini</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
