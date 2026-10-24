@extends('layouts.master')
@php $userId = $userId ?? request()->route('userId') ?? auth()->id(); @endphp
@section('title') Naskah Soal @endsection

@push('css')
<style>
    .naskah-question { padding: 14px 0; border-bottom: 1px dashed #dee2e6; }
    .naskah-question:last-child { border-bottom: 0; }
    .naskah-question-row { display: flex; gap: 10px; align-items: flex-start; }
    .naskah-number { width: 28px; flex-shrink: 0; font-weight: 700; }
    .naskah-question-body { flex: 1; min-width: 0; }
    table.qwrap { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.qwrap td { vertical-align: top; padding: 0; }
    table.qwrap td.qno { width: 4.5%; font-weight: 700; }
    table.qwrap td.qbody { width: 95.5%; }
    table.opts { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 4px 0 0; }
    table.opts td { vertical-align: top; padding: 0 10px 0 0; }
    table.opts td .letter { font-weight: 600; }
    .naskah-wacana { padding: 12px; margin-bottom: 10px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 10px; }
</style>
@endpush

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}">Paket Soal</a> @endslot
        @slot('li_2') <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}">{{ $paket->judul }}</a> @endslot
        @slot('title') Naskah Soal @endslot
    @endcomponent

    <x-soal-flow current="cetak" :user-id="$userId" />

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-1">Naskah Soal — {{ $paket->judul }}</h5>
                <p class="text-muted small mb-0">
                    {{ $paket->kisiKisi?->subject?->name ?? '-' }}
                    &bull; {{ $paket->kisiKisi?->gradeLevel?->name ?? '-' }}
                    &bull; {{ $paket->kisiKisi?->academicYear?->name ?? '-' }}
                    &bull; Semester {{ ucfirst($paket->kisiKisi?->semester ?? '-') }}
                    &bull; {{ strtoupper($paket->kisiKisi?->jenis_ujian ?? '-') }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
                <a href="{{ route('user.paket-soal.naskah.cetak', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" target="_blank" class="btn btn-primary btn-sm">
                    <i class="ri-printer-line me-1"></i>Cetak Naskah
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between gap-2">
            <div><strong>{{ $paket->judul }}</strong> <span class="badge bg-info ms-1">{{ $paket->kode_paket }}</span></div>
            <div class="small text-muted">Waktu: {{ $paket->waktu_pengerjaan_menit }} menit · {{ $rows->count() }} butir</div>
        </div>
        <div class="card-body" style="font-size: {{ ($layout['font_size'] ?? 11) }}pt; line-height: {{ ($layout['line_height'] ?? 1.4) }};">
            @if($paket->instruksi_umum)
                <div class="alert alert-light border small"><strong>Petunjuk Umum:</strong><br>{!! nl2br(e($paket->instruksi_umum)) !!}</div>
            @endif

            @include('evalusi.paket-soal.partials.naskah-questions', ['rows' => $rows])
        </div>
    </div>
@endsection
