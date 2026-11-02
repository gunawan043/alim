@extends('layouts.master')
@section('title') Detail Laporan Penugasan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarpras @endslot
    @slot('li_2') Laporan Penugasan @endslot
    @slot('title') Detail @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        {{ $errors->first() }}<button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-1">Laporan {{ $report->workOrder?->order_number ?? 'Pekerjaan' }}</h5>
        <div class="text-muted small">
            {{ $report->report_date?->format('d M Y') }} · dilaporkan oleh {{ $report->reporter?->name ?? '-' }}
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('sarpras.assignment-report.index') }}" class="btn btn-sm btn-light border"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="ri-printer-line me-1"></i>Cetak</button>
        @if($canVerify && $report->status !== 'verified')
            <form method="POST" action="{{ route('sarpras.assignment-report.verify', ['reportId' => $report->id]) }}">
                @csrf
                <button class="btn btn-sm btn-success"><i class="ri-shield-check-line me-1"></i>Verifikasi</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Hasil Pekerjaan</h6>
                <span class="badge bg-{{ $report->status_tone }}-subtle text-{{ $report->status_tone }}">{{ $report->status_label }}</span>
            </div>
            <div class="card-body">
                <h6 class="text-muted text-uppercase fs-12 mb-1">Apa yang dikerjakan</h6>
                <p class="mb-4" style="white-space:pre-line">{{ $report->work_done }}</p>

                <h6 class="text-muted text-uppercase fs-12 mb-1">Kendala</h6>
                <p class="mb-4" style="white-space:pre-line">{{ $report->obstacles ?: 'Tidak ada kendala.' }}</p>

                <h6 class="text-muted text-uppercase fs-12 mb-1">Tindak lanjut</h6>
                <p class="mb-0" style="white-space:pre-line">{{ $report->follow_up ?: '—' }}</p>
            </div>
        </div>

        @if($report->status === 'verified')
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="mb-2"><i class="ri-shield-check-line me-1 text-success"></i>Verifikasi</h6>
                    <div class="small text-muted">
                        Diverifikasi oleh {{ $report->verifier?->name ?? '-' }}
                        pada {{ $report->verified_at?->format('d/m/Y H:i') ?? '-' }}
                    </div>
                    @if($report->verification_note)
                        <div class="small mt-1">Catatan: {{ $report->verification_note }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Rincian</h6></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Work order</td><td class="text-end">
                        @if($report->workOrder)
                            <a href="{{ route('sarpras.teknisi.show', ['orderId' => $report->workOrder->id]) }}">{{ $report->workOrder->order_number }}</a>
                        @else — @endif
                    </td></tr>
                    <tr><td class="text-muted">Aset</td><td class="text-end">{{ $report->asset?->asset_name ?? $report->workOrder?->target_name ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Lokasi</td><td class="text-end">{{ $report->workOrder?->target_location ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Unit</td><td class="text-end">{{ $report->workUnit?->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Durasi</td><td class="text-end">{{ $report->duration_minutes ? $report->duration_minutes.' menit' : '—' }}</td></tr>
                    <tr><td class="text-muted">Kondisi setelah</td><td class="text-end">{{ $report->condition_after ? ucfirst(str_replace('_', ' ', $report->condition_after)) : '—' }}</td></tr>
                    <tr><td class="text-muted">Suku cadang</td><td class="text-end">{{ $report->parts_replaced ?: '—' }}</td></tr>
                    <tr><td class="text-muted">Biaya</td><td class="text-end">Rp {{ number_format((float) $report->cost, 0, ',', '.') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
