{{-- Print classroom QR code --}}
@extends('layouts.master')
@section('title') Cetak QR Kelas @endsection

@push('css')
<style>
.print-area{padding:20px;border:2px dashed #e2e8f0;border-radius:12px;background:#fff}
.print-area .qr-wrap{text-align:center;margin-bottom:16px}
.print-area .qr-wrap img{max-width:280px;width:100%;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.08)}
.print-area .info-box{border-left:4px solid #6366f1;padding-left:12px;margin-top:12px}
.print-area .info-box p{margin:0;font-size:.9rem}
.print-area .info-box .label{color:#64748b;font-size:.8rem}
.print-area .info-box .value{font-weight:600;color:#1e293b}
@media print{
    body *{visibility:hidden}
    #printArea,#printArea *{visibility:visible}
    #printArea{position:absolute;left:0;top:0;width:100%}
    .no-print{display:none !important}
}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Guru @endslot
    @slot('li_2') QR Kelas @endslot
    @slot('title') Cetak QR Kelas @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-qr-code-line"></i>
                    <h5 class="mb-0">QR Kelas — {{ $studyGroup->full_name ?? $studyGroup->name }}</h5>
                </div>
                <div class="no-print d-flex gap-2">
                    <a href="{{ route('user.qr.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">
                        <i class="ri-arrow-left-line me-1"></i>Daftar QR
                    </a>
                    <form method="POST" action="{{ route('user.qr.regenerate', ['userId' => $userId, 'study_group_id' => $studyGroup->id]) }}"
                          class="d-inline" onsubmit="return confirm('Buat QR baru? QR lama tidak akan berlaku lagi.')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="ri-refresh-line me-1"></i>Buat Ulang
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-dark no-print" onclick="window.print()">
                        <i class="ri-printer-line me-1"></i>Cetak
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="printArea" class="print-area">
                    {{-- QR Image --}}
                    <div class="qr-wrap">
                        <img src="{{ route('user.qr.image', ['userId' => $userId, 'study_group_id' => $studyGroup->id]) }}"
                             alt="QR Code {{ $studyGroup->full_name ?? $studyGroup->name }}">
                    </div>

                    {{-- School Logo + Info --}}
                    <div class="text-center mb-3">
                        @if($studyGroup->school && $studyGroup->school->logo)
                            <img src="{{ asset('storage/' . $studyGroup->school->logo) }}"
                                 alt="{{ $studyGroup->school->name }}"
                                 style="height:50px;object-fit:contain">
                        @endif
                        <h5 class="mt-2 mb-1 fw-bold">{{ $studyGroup->school?->name ?? config('app.name') }}</h5>
                        <p class="text-muted small mb-0">
                            {{ $studyGroup->full_name ?? $studyGroup->name }}
                            @if($studyGroup->gradeLevel?->name) — {{ $studyGroup->gradeLevel->name }} @endif
                        </p>
                    </div>

                    {{-- Class Info --}}
                    <div class="info-box">
                        <p class="mb-1"><span class="label">Wali Kelas:</span> <span class="value">{{ $studyGroup->homeroomTeacher?->name ?? '—' }}</span></p>
                        <p class="mb-1"><span class="label">Tahun Ajaran:</span> <span class="value">{{ $token->academicYear?->name ?? '—' }}</span></p>
                        <p class="mb-0"><span class="label">Terakhir dibuat ulang:</span>
                            <span class="value">{{ $token->last_regenerated_at?->format('d M Y H:i') ?? $token->created_at?->format('d M Y H:i') ?? '—' }}</span>
                        </p>
                    </div>
                </div>

                <div class="mt-3 alert alert-info small no-print">
                    <i class="ri-information-line me-1"></i>
                    Cetak halaman ini dan tempel di depan kelas. Guru memindai QR ini melalui menu
                    <strong>Scan QR Kehadiran</strong> di aplikasi — absensi masuk/keluar kelas tercatat otomatis
                    sesuai jadwal mengajar hari itu.
                </div>

                <div class="text-center no-print">
                    <a href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="ri-qr-scan-2-line me-1"></i>Buka Halaman Scan QR
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
