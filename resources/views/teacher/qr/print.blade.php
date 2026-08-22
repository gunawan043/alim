{{-- Print classroom QR code --}}
@extends('layouts.master')
@section('title') Cetak QR Kelas @endsection

@push('css')
<style>
.print-area{padding:20px;border:2px dashed #e2e8f0;border-radius:12px;background:#fff}
.print-area .qr-wrap{text-align:center;margin-bottom:16px}
.print-area .qr-wrap img{max-width:280px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.08)}
.print-area .info-box{border-left:4px solid #6366f1;padding-left:12px;margin-top:12px}
.print-area .info-box p{margin:0;font-size:.9rem}
.print-area .info-box .label{color:#64748b;font-size:.8rem}
.print-area .info-box .value{font-weight:600;color:#1e293b}
.scan-btn-preview{display:inline-block;margin-top:8px;padding:6px 20px;border-radius:20px;background:#6366f1;color:#fff;font-size:.85rem;text-decoration:none}
.scan-btn-preview:hover{background:#4f46e5;color:#fff}
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
    @slot('li_2') Cetak QR @endslot
    @slot('title') Cetak QR Kelas @endslot
@endcomponent

<div class="row g-4">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-printer-line"></i>
                    <h5 class="mb-0">Cetak QR Kelas</h5>
                </div>
                <div class="no-print d-flex gap-2">
                    <a href="{{ route('user.teacher-qr.qr.regenerate', ['userId' => $userId, 'study_group_id' => $studyGroup->id]) }}"
                       class="btn btn-sm btn-warning" onclick="return confirm('Buat QR baru? QR lama akan tidak berlaku.')">
                        <i class="ri-refresh-line me-1"></i>Buat Ulang
                    </a>
                    <button type="button" class="btn btn-sm btn-dark no-print" onclick="window.print()">
                        <i class="ri-printer-line me-1"></i>Cetak
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="printArea" class="print-area">
                    {{-- QR Image --}}
                    <div class="qr-wrap">
                        <img src="{{ route('user.teacher-qr.qr.image', ['userId' => $userId, 'study_group_id' => $studyGroup->id]) }}"
                             alt="QR Code" style="max-width:280px">
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
                            {{ $studyGroup->name ?? '' }}
                            {{ $studyGroup->gradeLevel?->name ? ' (' . $studyGroup->gradeLevel->name . ')' : '' }}
                        </p>
                    </div>

                    {{-- Schedule Info --}}
                    <div class="info-box">
                        <p class="mb-1"><span class="label">Wali Kelas:</span> <span class="value">{{ $studyGroup->homeroomTeacher?->name ?? '—' }}</span></p>
                        <p class="mb-1"><span class="label">Token:</span> <span class="value font-monospace text-break">{{ $token->token ?? '—' }}</span></p>
                        <p class="mb-1"><span class="label">Berlaku sampai:</span> <span class="value">{{ $token->qr_url_expires_at?->format('Y-m-d H:i') ?? '—' }}</span></p>
                        <p class="mb-0"><span class="label">URL Scan:</span></p>
                    </div>

                    {{-- Signed URL preview --}}
                    <div class="text-center mt-3">
                        <a href="{{ $signedUrl }}" target="_blank" class="scan-btn-preview no-print">
                            <i class="ri-qr-scan-2-line me-1"></i>Pratinjau Link Scan
                        </a>
                    </div>
                </div>

                <div class="mt-3 alert alert-info small no-print">
                    <i class="ri-information-line me-1"></i>
                    Cetak halaman ini dan tempel di depan kelas. Guru dapat memindai QR dengan HP untuk absen masuk/keluar. QR berlaku {{ $token->qr_url_expires_at?->diffForHumans() ?? 'satu minggu' }}.
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
