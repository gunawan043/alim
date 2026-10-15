@extends('layouts.master')
@section('title') Soal — {{ $exam->judul }} @endsection

@section('css')
    @include('ljk._styles')
    <style>
        .soal-card { border-left: 3px solid #0a5f9e; }
        .option-key { background: #198754; color: #fff; }
        .option-plain { background: #f1f5f9; color: #334155; }
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
@endsection

@section('content')
    @php
        $badge = ['draft' => 'secondary', 'aktif' => 'primary', 'pengumpulan' => 'warning', 'review' => 'warning', 'final' => 'success', 'tersinkron' => 'success', 'dibatalkan' => 'danger'][$exam->status] ?? 'secondary';
        $jenisLabel = ['pg' => 'Pilihan Ganda', 'mcc' => 'PG Kompleks', 'bs' => 'Benar/Salah', 'jodoh' => 'Menjodohkan', 'isian' => 'Isian', 'uraian' => 'Uraian'];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Bank Soal &amp; Asesmen @endslot
        @slot('li_2')
            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}">LJK Smart Scanner</a>
        @endslot
        @slot('li_3')
            <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}">{{ $exam->code }}</a>
        @endslot
        @slot('title') Lihat Soal @endslot
    @endcomponent

    {{-- KEPALA --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-lg">
                    <h5 class="card-title mb-1">
                        {{ $exam->judul }}
                        <span class="badge bg-info-subtle text-info ms-1"><code>{{ $exam->code }}</code></span>
                        <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $exam->statusLabel() }}</span>
                        @if ($digest['ok'])
                            <span class="badge bg-success-subtle text-success"><i class="ri-fingerprint-line me-1"></i>Naskah beku sesuai versi</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger"><i class="ri-alert-line me-1"></i>Naskah paket berubah</span>
                        @endif
                    </h5>
                    <p class="text-muted mb-0 stat-label">
                        {{ $exam->subject?->name ?? '-' }} · {{ $exam->studyGroup?->name ?? '-' }} ·
                        {{ $exam->paketSoal?->judul ?? '-' }}
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">v{{ $exam->paket_version }}</span>
                        · {{ count($questions) }} butir
                        @if ($canSeeKeys)
                            · <span class="text-success"><i class="ri-key-2-line me-1"></i>kunci ditampilkan</span>
                        @else
                            · <span class="text-muted"><i class="ri-lock-line me-1"></i>kunci disembunyikan</span>
                        @endif
                    </p>
                </div>
                <div class="col-lg-auto no-print">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('user.ljk.soal.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                           target="_blank" class="btn btn-success">
                            <i class="ri-file-pdf-2-line align-bottom me-1"></i> Cetak Lembar Soal (PDF)
                        </a>
                        <button type="button" class="btn btn-soft-secondary" onclick="window.print()">
                            <i class="ri-printer-line align-bottom me-1"></i> Cetak Halaman
                        </button>
                        <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" target="_blank" class="btn btn-soft-dark">
                            <i class="ri-file-pdf-2-line align-bottom me-1"></i> Cetak Lembar LJK
                        </a>
                        <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-light">
                            <i class="ri-arrow-left-line align-bottom me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border d-flex flex-wrap gap-3 no-print" style="font-size:0.8125rem;">
        <span><i class="ri-font-size-2 me-1"></i>Font PDF: <strong>{{ \App\Services\Ljk\LjkPdfFonts::activeLabel() }}</strong></span>
        <span class="text-muted">{{ \App\Services\Ljk\LjkPdfFonts::installHint() }}</span>
    </div>

    @if (! $digest['ok'] || ! ($canonical ?? true))
        <div class="alert alert-warning no-print" style="font-size:0.8125rem;">
            <i class="ri-alert-line me-1"></i>
            Lembar soal dicetak menurut <strong>urutan paket</strong>. Paket ini mengacak soal/opsi per peserta,
            jadi nomor pada lembar soal bisa berbeda dengan lembar jawaban peserta tertentu — gunakan
            <strong>Mode Fotokopi</strong> saat menerbitkan bila lembar soal dipakai bersama.
        </div>
    @endif

    @if (! $canSeeKeys)
        <div class="alert alert-info no-print">
            <i class="ri-information-line me-1"></i>
            Kunci jawaban hanya ditampilkan untuk tim kurikulum atau guru serumpun mapel ini
            (kebijakan kunci yang berlaku di ALIM).
        </div>
    @endif

    {{-- DAFTAR SOAL --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card soal-card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="mb-0"><i class="ri-file-list-3-line me-1 text-primary"></i>Naskah Soal</h5>
                </div>
                <div class="card-body">
                    @forelse ($questions as $question)
                        <div class="mb-4 pb-3 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-primary-subtle text-primary" style="min-width:34px;">{{ $question['nomor'] }}</span>
                                <div class="flex-grow-1">
                                    <div class="fw-medium">{!! nl2br(e($question['pertanyaan'])) !!}</div>

                                    <div class="text-muted stat-label mb-2">
                                        {{ $jenisLabel[$question['jenis']] ?? strtoupper($question['jenis']) }}
                                        · bobot {{ number_format($question['bobot'], 2) }}
                                        @if ($question['tp']) · <code>{{ $question['tp'] }}</code> @endif
                                    </div>

                                    @if (! empty($question['opsi']))
                                        <div class="row g-1">
                                            @foreach ($question['opsi'] as $option)
                                                <div class="col-md-6">
                                                    <span class="badge {{ ($option['kunci'] ?? false) ? 'option-key' : 'option-plain' }}">
                                                        {{ $option['label'] }}
                                                    </span>
                                                    <span style="font-size:0.875rem;">{{ $option['teks'] }}</span>
                                                    @if ($option['kunci'] ?? false)
                                                        <i class="ri-check-line text-success" title="Kunci jawaban"></i>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($question['pembahasan'])
                                        <div class="alert alert-light border mt-2 mb-0" style="font-size:0.8125rem;">
                                            <strong>Pembahasan:</strong> {!! nl2br(e($question['pembahasan'])) !!}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            Paket soal belum memiliki butir, atau naskah belum dibekukan.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
