@extends('layouts.master')
@section('title') Detail Penilaian Kinerja @endsection
@section('css')
<style>
.score-ring{width:120px;height:120px;border-radius:50%;background:conic-gradient(var(--ring-color) 0% var(--ring-pct) ,#e2e8f0 var(--ring-pct) 100%);display:flex;align-items:center;justify-content:center;margin:0 auto}
.score-ring-inner{width:96px;height:96px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;flex-direction:column}
.timeline-item{position:relative;padding-left:2rem;border-left:2px solid #e2e8f0;padding-bottom:1.5rem}
.timeline-item::before{content:'';position:absolute;left:-7px;top:4px;width:12px;height:12px;border-radius:50%;background:#0d6efd;border:2px solid #fff}
.timeline-item:last-child{border-left-color:transparent;padding-bottom:0}
</style>
@endsection
@section('content')
@php
$userId = request()->route('userId') ?? Auth::id();
$currentUser = auth()->user();
$isAdmin = $currentUser->hasAnyRole(['Personalia','Super Admin']);
@endphp
@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Kinerja @endslot
    @slot('li_3') Detail @endslot
    @slot('title') {{ $penilaian->user->name ?? '-' }} @endslot
@endcomponent

<div class="row">
    {{-- Info Card --}}
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="avatar-lg mx-auto mb-3">
                    <span class="avatar-title bg-primary-subtle rounded-circle text-primary fs-1">{{ strtoupper(substr($penilaian->user->name??'?',0,1)) }}</span>
                </div>
                <h5 class="fw-bold mb-1">{{ $penilaian->user->name ?? '-' }}</h5>
                <p class="text-muted mb-3">{{ $penilaian->periode->nama ?? '-' }}</p>
                @php
                $skor = $penilaian->total_skor ?? 0;
                $pct = min(100, $skor);
                $color = $skor>=90?'#22c55e':($skor>=80?'#0d6efd':($skor>=70?'#f59e0b':'#ef4444'));
                @endphp
                <div class="score-ring" style="--ring-color:{{ $color }};--ring-pct:{{ $pct }}%">
                    <div class="score-ring-inner">
                        <span class="fw-bold fs-4">{{ number_format($skor,0) }}</span>
                        <span class="text-muted small">/ 100</span>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge badge-status {{ $penilaian->nilai_huruf=='A'?'bg-success-subtle text-success':($penilaian->nilai_huruf=='B'?'bg-primary-subtle text-primary':'bg-secondary-subtle') }} fs-6 px-3 py-2">{{ $penilaian->nilai_huruf ?? '-' }}</span>
                    <span class="badge bg-info-subtle text-info badge-status ms-1">{{ $penilaian->kategori_hasil ?? '-' }}</span>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><h6 class="card-title mb-0"><i class="ri-information-line me-1"></i> Info</h6></div>
            <div class="card-body py-2">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Status</td><td class="fw-semibold">{{ $penilaian->getStatusLabelAttribute() }}</td></tr>
                    <tr><td class="text-muted">Penilai</td><td class="fw-semibold">{{ $penilaian->penilai->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Tanggal</td><td class="fw-semibold">{{ $penilaian->tanggal_penilaian?->format('d M Y') ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Detail Content --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-file-chart-line me-1"></i> Detail Penilaian</h5>
                @if($isAdmin)
                <a href="{{ route('user.ats.kinerja.edit', [$userId, $penilaian->id]) }}" class="btn btn-soft-warning btn-sm"><i class="ri-edit-2-line me-1"></i> Edit</a>
                @endif
            </div>
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Skor per Komponen</h6>
                @forelse(\App\Models\KinerjaKomponen::with('indikator')->where('is_active',true)->orderBy('urutan')->get() as $komponen)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold small">{{ $komponen->nama }}</span>
                        <span class="badge bg-primary-subtle text-primary">{{ $komponen->bobot_persen }}%</span>
                    </div>
                    @forelse($komponen->indikator as $indikator)
                    @php $skorItem = $penilaian->skors->firstWhere('kinerja_indikator_id', $indikator->id); @endphp
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="small text-muted" style="min-width:200px">{{ $indikator->nama }}</span>
                        <div class="progress flex-grow-1" style="height:6px"><div class="progress-bar" style="width:{{ $skorItem?->skor ??0 }}%;background:{{ ($skorItem?->skor??0)>=80?'#22c55e':(($skorItem?->skor??0)>=70?'#f59e0b':'#ef4444') }}"></div></div>
                        <span class="fw-semibold small" style="min-width:40px;text-align:right">{{ $skorItem?->skor ?? 0 }}</span>
                    </div>
                    @empty
                    <span class="text-muted small">-</span>
                    @endforelse
                </div>
                @empty
                <p class="text-muted">Belum ada komponen penilaian.</p>
                @endforelse

                @if($penilaian->catatan_penilai)
                <hr>
                <h6 class="fw-semibold">Catatan Penilai</h6>
                <p class="text-muted">{{ $penilaian->catatan_penilai }}</p>
                @endif
 </div>
        </div>
    </div>
</div>
@endsection