@extends('layouts.master')
@section('title') {{ $exam->judul }} — LJK @endsection

@section('css')
    @include('ljk._styles')
@endsection

@section('content')
    @php
        $badge = ['draft' => 'secondary', 'aktif' => 'primary', 'pengumpulan' => 'warning', 'review' => 'warning', 'final' => 'success', 'tersinkron' => 'success', 'dibatalkan' => 'danger'][$exam->status] ?? 'secondary';
        $pctScan = $summary['sheets'] > 0 ? round($summary['scanned'] / $summary['sheets'] * 100) : 0;
        $pctReview = $summary['scanned'] > 0 ? round($summary['needs_review'] / $summary['scanned'] * 100) : 0;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Bank Soal &amp; Asesmen @endslot
        @slot('li_2')
            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}">LJK Smart Scanner</a>
        @endslot
        @slot('title') {{ $exam->judul }} @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KEPALA UJIAN --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-lg">
                    <h5 class="card-title mb-1">
                        {{ $exam->judul }}
                        <span class="badge bg-info-subtle text-info ms-1"><code>{{ $exam->code }}</code></span>
                        <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $exam->statusLabel() }}</span>
                        @if ($digest['ok'])
                            <span class="badge bg-success-subtle text-success"><i class="ri-fingerprint-line me-1"></i>Naskah sesuai versi</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger"><i class="ri-alert-line me-1"></i>Naskah paket berubah</span>
                        @endif
                    </h5>
                    <p class="text-muted mb-0 stat-label">
                        {{ $exam->subject?->name ?? '-' }} · {{ $exam->studyGroup?->name ?? '-' }} ·
                        {{ strtoupper($exam->jenis_ujian) }} · Semester {{ ucfirst($exam->semester) }}
                        @if ($exam->tanggal) · {{ $exam->tanggal->format('d/m/Y') }} @endif
                    </p>
                </div>
                <div class="col-lg-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('user.ljk.soal', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-soft-info">
                            <i class="ri-file-text-line align-bottom me-1"></i> Lihat Soal
                        </a>
                        <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                           target="_blank" class="btn btn-soft-secondary">
                            <i class="ri-printer-line align-bottom me-1"></i> Cetak Lembar PDF
                        </a>
                        <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id, 'download' => 1]) }}"
                           class="btn btn-soft-success" title="Unduh lembar jawaban sebagai PDF">
                            <i class="ri-download-2-line align-bottom me-1"></i> Unduh PDF
                        </a>
                        <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id, 'master' => 1]) }}"
                           target="_blank" class="btn btn-soft-warning"
                           title="Lembar tanpa nama/QR untuk diperbanyak lalu diisi manual">
                            <i class="ri-file-copy-2-line align-bottom me-1"></i> Master
                        </a>
                        <a href="{{ route('user.ljk.scan', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-success">
                            <i class="ri-camera-line align-bottom me-1"></i> Pindai LJK
                        </a>
                        <a href="{{ route('user.ljk.hasil', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-primary">
                            <i class="ri-list-check-2 align-bottom me-1"></i> Hasil &amp; Verifikasi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-group-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Peserta</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($summary['sheets']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-file-list-3-line me-1"></i>Lembar diterbitkan per peserta
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-camera-3-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Sudah Dipindai</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($summary['scanned']) }}</h3>
                        </div>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-success" style="width:{{ $pctScan }}%"></div>
                    </div>
                    <p class="text-muted mb-0 mt-1 stat-label">{{ $pctScan }}% dari {{ $summary['sheets'] }} lembar</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-error-warning-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Perlu Pemeriksaan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($summary['needs_review']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>{{ $pctReview }}% dari lembar terpindai
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-edit-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Jawaban Manual</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($summary['manual']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-quill-pen-line me-1"></i>Uraian/isian dinilai guru
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="mb-0"><i class="ri-links-line me-1 text-primary"></i>Konfigurasi &amp; Versi</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 gy-2" style="font-size:0.875rem;">
                        <dt class="col-5 text-muted fw-semibold">Paket Soal</dt>
                        <dd class="col-7 mb-0">
                            {{ $exam->paketSoal?->judul ?? '-' }}
                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">v{{ $exam->paket_version }}</span>
                            <div class="text-muted stat-label"><code>{{ $exam->paketSoal?->kode_paket }}</code></div>
                        </dd>

                        <dt class="col-5 text-muted fw-semibold">Guru Buku Nilai</dt>
                        <dd class="col-7 mb-0">{{ $exam->adminBook?->teacher?->name ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-semibold">Jumlah Soal</dt>
                        <dd class="col-7 mb-0">
                            {{ $exam->jumlah_soal }} butir
                            <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">
                                {{ $exam->scoringSummary()['manual_count'] }} manual
                            </span>
                        </dd>

                        <dt class="col-5 text-muted fw-semibold">Bobot Total (auto)</dt>
                        <dd class="col-7 mb-0">{{ number_format($exam->scoringSummary()['total_weight'], 2) }}</dd>

                        <dt class="col-5 text-muted fw-semibold">Template Lembar</dt>
                        <dd class="col-7 mb-0">
                            <code>{{ $exam->template?->code }}</code>
                            <div class="text-muted stat-label">versi {{ $exam->template?->version }} · {{ $exam->template?->columns }} kolom</div>
                        </dd>

                        <dt class="col-5 text-muted fw-semibold">Snapshot Paket</dt>
                        <dd class="col-7 mb-0">
                            {{ $exam->paket_snapshot_at?->format('d/m/Y H:i') ?? 'live (belum beku)' }}
                            @if (! $digest['ok'])
                                <div class="text-danger stat-label">Naskah berubah sejak diterbitkan</div>
                            @endif
                        </dd>
                    </dl>

                    @if (! $canonical)
                        <div class="alert alert-warning py-2 mb-2" style="font-size:0.8125rem;">
                            <i class="ri-shuffle-line me-1"></i>
                            Paket ini <strong>mengacak urutan soal/opsi</strong> — “Master” hanya sah untuk peserta
                            yang tersalin. Terbitkan ujian baru dengan <strong>Mode Fotokopi</strong> bila ingin satu master
                            berlaku untuk semua santri.
                        </div>
                    @endif

                    <hr>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('user.ljk.preview', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                           target="_blank" class="btn btn-sm btn-soft-primary">
                            <i class="ri-eye-line me-1"></i> Preview Lembar
                        </a>
                        @if (! in_array($exam->status, [\App\Models\LjkExam::STATUS_TERSINKRON, \App\Models\LjkExam::STATUS_DIBATALKAN]))
                            <form method="POST" action="{{ route('user.ljk.cancel', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                                  onsubmit="return confirm('Batalkan ujian LJK ini? Riwayat tetap tersimpan.')">
                                @csrf
                                <button class="btn btn-sm btn-soft-danger">
                                    <i class="ri-close-circle-line me-1"></i> Batalkan Ujian
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100" id="ljkParticipants">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Peserta &amp; Lembar</h5>
                            <p class="text-muted mb-0 stat-label">
                                Satu lembar per peserta — cetak ulang atau periksa kapan saja.
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                               target="_blank" class="btn btn-sm btn-success">
                                <i class="ri-printer-line me-1"></i> Cetak Semua
                            </a>
                            <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id, 'download' => 1]) }}"
                               class="btn btn-sm btn-soft-success" title="Unduh semua lembar jawaban (PDF)">
                                <i class="ri-download-2-line me-1"></i> Unduh
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 520px;">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Peserta</th>
                                <th>Kode Lembar</th>
                                <th>Status</th>
                                <th>Nilai</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($participants as $participant)
                                <tr>
                                    <td><code>{{ $participant->nomor_peserta }}</code></td>
                                    <td>
                                        {{ $participant->student?->name ?? '-' }}
                                        <div class="text-muted stat-label">NIS {{ $participant->student?->nis ?? '-' }}</div>
                                    </td>
                                    <td><code>{{ $participant->sheet?->sheet_code ?? '-' }}</code></td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ \App\Models\LjkParticipant::STATUS_OPTIONS[$participant->status] ?? $participant->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">
                                            {{ $participant->nilai_akhir !== null ? number_format((float) $participant->nilai_akhir, 2) : '-' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if ($participant->sheet)
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id, 'participant' => $participant->id]) }}"
                                                   target="_blank" class="btn btn-sm btn-soft-secondary" title="Cetak lembar">
                                                    <i class="ri-printer-line"></i>
                                                </a>
                                                <a href="{{ route('user.ljk.hasil.peserta', ['userId' => $userId, 'ljkUuid' => $exam->id, 'participantUuid' => $participant->id]) }}"
                                                   class="btn btn-sm btn-soft-primary" title="Periksa hasil">
                                                    <i class="ri-search-eye-line"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada peserta terdaftar pada ujian ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
