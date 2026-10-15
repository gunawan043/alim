@extends('layouts.master')
@section('title') Periksa LJK — {{ $participant->student?->name }} @endsection

@section('css')
    @include('ljk._styles')
@endsection

@section('content')
    @php
        $statusBadge = ['terbaca' => 'success', 'kosong' => 'secondary', 'ganda' => 'danger', 'meragukan' => 'warning', 'tidak_dapat_diproses' => 'dark', 'manual' => 'info'];
        $totalNomor = $answers->count();
        $perluReview = $answers->where('needs_review', true)->count();
        $manualCount = $answers->where('status', \App\Models\LjkScanAnswer::MANUAL)->count();
        $benarCount = $answers->where('is_correct', true)->count();
        $salahCount = $answers->where('is_correct', false)->count();
        $koreksiCount = $answers->where('review_action', 'corrected')->count();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Bank Soal &amp; Asesmen @endslot
        @slot('li_2')
            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}">LJK Smart Scanner</a>
        @endslot
        @slot('li_3')
            <a href="{{ route('user.ljk.hasil', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}">Hasil</a>
        @endslot
        @slot('title') {{ $participant->student?->name }} @endslot
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

    {{-- RINGKASAN PESERTA --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-award-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Nilai Sementara</p>
                            <h3 class="fw-bold ff-secondary mb-0">
                                {{ $participant->nilai_akhir !== null ? number_format((float) $participant->nilai_akhir, 2) : '-' }}
                            </h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>Total poin {{ $participant->total_point ?? 0 }}
                    </p>
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
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Perlu Diperiksa</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($perluReview) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-list-check-2 me-1"></i>dari {{ number_format($totalNomor) }} nomor
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
                                <i class="ri-check-double-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Benar / Salah</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $benarCount }} / {{ $salahCount }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-edit-line me-1"></i>{{ $koreksiCount }} jawaban dikoreksi guru
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
                                <i class="ri-quill-pen-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Manual (Uraian/Isian)</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($manualCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-information-line me-1"></i>Isi skor lewat tombol Koreksi
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="mb-0"><i class="ri-user-line me-1 text-primary"></i>Identitas</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 gy-2" style="font-size:0.875rem;">
                        <dt class="col-5 text-muted fw-semibold">Nama</dt>
                        <dd class="col-7 mb-0">{{ $participant->student?->name ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-semibold">NIS</dt>
                        <dd class="col-7 mb-0">{{ $participant->student?->nis ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-semibold">No. Peserta</dt>
                        <dd class="col-7 mb-0"><code>{{ $participant->nomor_peserta }}</code></dd>
                        <dt class="col-5 text-muted fw-semibold">Kode Lembar</dt>
                        <dd class="col-7 mb-0"><code>{{ $participant->sheet?->sheet_code ?? '-' }}</code></dd>
                        <dt class="col-5 text-muted fw-semibold">Ujian</dt>
                        <dd class="col-7 mb-0">{{ $exam->judul }}</dd>
                        <dt class="col-5 text-muted fw-semibold">Rombel</dt>
                        <dd class="col-7 mb-0">{{ $exam->studyGroup?->name ?? '-' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($scan)
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="ri-image-line me-1 text-primary"></i>Gambar Pemindaian</h5>
                            <a href="{{ route('user.ljk.scan.image', ['userId' => $userId, 'scanUuid' => $scan->id]) }}"
                               target="_blank" class="btn btn-sm btn-soft-secondary">
                                <i class="ri-zoom-in-line me-1"></i> Perbesar
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('user.ljk.scan.image', ['userId' => $userId, 'scanUuid' => $scan->id]) }}" target="_blank">
                            <img src="{{ route('user.ljk.scan.image', ['userId' => $userId, 'scanUuid' => $scan->id]) }}"
                                 alt="Scan {{ $participant->sheet?->sheet_code }}" class="img-fluid rounded border">
                        </a>
                        <div class="text-muted stat-label mt-2">
                            Dipindai {{ $scan->scanned_at?->format('d/m/Y H:i') }} · {{ $scan->processing_ms }} ms ·
                            {{ $scan->image_width }}×{{ $scan->image_height }}px
                            @if ($scan->quality['strip_mismatch'] ?? false)
                                <span class="badge bg-danger-subtle text-danger">identitas strip tidak cocok</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($previousScans->count() > 1)
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="mb-0"><i class="ri-history-line me-1 text-primary"></i>Riwayat Pemindaian</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach ($previousScans as $previous)
                                <li class="d-flex align-items-center gap-2 py-1 {{ $previous->status === 'superseded' ? 'text-muted' : '' }}"
                                    style="font-size:0.8125rem;">
                                    <i class="ri-camera-line"></i>
                                    <span>{{ $previous->scanned_at?->format('d/m/Y H:i') }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $previous->status }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Hasil Baca per Nomor</h5>
                            <p class="text-muted mb-0 stat-label">
                                ⚠ perlu diperiksa · klik <strong>Koreksi</strong> untuk mengubah (tercatat ber-audit)
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <span class="badge bg-success-subtle text-success">{{ $benarCount }} benar</span>
                            <span class="badge bg-danger-subtle text-danger">{{ $salahCount }} salah</span>
                            <span class="badge bg-warning-subtle text-warning">{{ $perluReview }} ⚠</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 70vh;">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>No</th>
                                <th>Opsi Tercetak</th>
                                <th>Terbaca</th>
                                <th>Status</th>
                                <th>Skor</th>
                                <th class="text-end">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($answers as $answer)
                                @php
                                    $map = $mapping->get($answer->nomor);
                                    $labels = $map['opsi'] ?? [];
                                    $options = $answer->evidence['options'] ?? [];
                                    $statusColor = $statusBadge[$answer->status] ?? 'secondary';
                                @endphp
                                <tr class="{{ $answer->needs_review ? 'ljk-row-review' : '' }}">
                                    <td><span class="fw-bold">{{ $answer->nomor }}</span></td>
                                    <td style="min-width:190px;">
                                        @foreach ($labels as $i => $label)
                                            @php $score = $options[chr(65 + $i)]['score'] ?? null; @endphp
                                            <span class="ljk-option-chip {{ ($answer->selected_label === $label && ! $answer->needs_review) ? 'is-read' : (($score !== null && $score > 0.33) ? 'is-candidate' : '') }}"
                                                  title="Skor bukti {{ $score !== null ? number_format((float) $score, 2) : '-' }}">
                                                {{ $label }}@if ($score !== null && $score > 0.33) {{ number_format((float) $score, 2) }}@endif
                                            </span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if ($answer->selected_label)
                                            <span class="fw-bold">{{ $answer->selected_label }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                        <div class="text-muted stat-label">keyakinan {{ number_format((float) $answer->confidence, 2) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                            {{ $answer->statusLabel() }}{{ $answer->needs_review ? ' ⚠' : '' }}
                                        </span>
                                        @if ($answer->review_action === 'corrected')
                                            <span class="badge bg-info-subtle text-info">dikoreksi</span>
                                        @endif
                                        @if ($answer->is_correct === true)
                                            <i class="ri-check-line text-success ms-1"></i>
                                        @elseif ($answer->is_correct === false)
                                            <i class="ri-close-line text-danger ms-1"></i>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float) $answer->point_final, 2) }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="button" class="btn btn-sm btn-soft-primary"
                                                    data-bs-toggle="modal" data-bs-target="#review-modal"
                                                    data-action="{{ route('user.ljk.review', ['userId' => $userId, 'ljkUuid' => $exam->id, 'answerUuid' => $answer->id]) }}"
                                                    data-nomor="{{ $answer->nomor }}"
                                                    data-status="{{ $answer->status }}"
                                                    data-label="{{ $answer->selected_label }}"
                                                    data-point="{{ $answer->point_final }}"
                                                    data-manual="{{ $answer->status === 'manual' ? '1' : '0' }}"
                                                    data-options="{{ implode(',', $labels) }}">
                                                <i class="ri-edit-line me-1"></i> Koreksi
                                            </button>
                                            @if ($answer->needs_review)
                                                <form method="POST" action="{{ route('user.ljk.review', ['userId' => $userId, 'ljkUuid' => $exam->id, 'answerUuid' => $answer->id]) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="accept">
                                                    <button class="btn btn-sm btn-soft-success" title="Terima hasil baca">
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @if ($answer->corrections->isNotEmpty())
                                    <tr>
                                        <td></td>
                                        <td colspan="5" class="bg-light">
                                            @foreach ($answer->corrections as $correction)
                                                <div class="text-muted stat-label py-1">
                                                    <i class="ri-history-line me-1"></i>
                                                    {{ $correction->corrected_at?->format('d/m/Y H:i') }} —
                                                    <span class="fw-semibold">{{ $correction->before['selected'] ?? '—' }}</span>
                                                    →
                                                    <span class="fw-semibold text-primary">{{ $correction->after['selected'] ?? '—' }}</span>
                                                    ({{ $correction->after['status'] ?? '-' }})
                                                    oleh {{ $correction->corrector?->name ?? 'guru' }}
                                                    @if ($correction->reason) · "{{ $correction->reason }}" @endif
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada pemindaian untuk peserta ini. Gunakan tombol Pindai LJK.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL KOREKSI --}}
    <div class="modal fade" id="review-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" id="review-form">
                @csrf
                <input type="hidden" name="action" value="correct">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-edit-line me-1"></i>Koreksi Nomor <span id="modal-nomor"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status jawaban</label>
                        <select name="status" id="modal-status" class="form-select">
                            @foreach (\App\Models\LjkScanAnswer::STATUSES as $status)
                                <option value="{{ $status }}">{{ config("ljk.statuses.{$status}", $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opsi terpilih (label paket)</label>
                        <select name="label" id="modal-label" class="form-select">
                            <option value="">— kosong / tidak ada —</option>
                        </select>
                    </div>
                    <div class="mb-3" id="modal-point-wrap">
                        <label class="form-label">Skor manual (soal uraian/isian)</label>
                        <input type="number" step="0.01" min="0" name="point_final" id="modal-point" class="form-control">
                        <div class="form-text">Isi sesuai bobot soal maksimal. Kosongkan untuk pilihan ganda.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Alasan koreksi (opsional)</label>
                        <input type="text" name="reason" id="modal-reason" class="form-control" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Koreksi</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            const modal = document.getElementById('review-modal');
            const form = document.getElementById('review-form');

            modal.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                form.action = trigger.dataset.action;
                document.getElementById('modal-nomor').textContent = trigger.dataset.nomor;
                document.getElementById('modal-status').value = trigger.dataset.status || 'terbaca';

                const labelSelect = document.getElementById('modal-label');
                labelSelect.innerHTML = '<option value="">— kosong / tidak ada —</option>';
                (trigger.dataset.options || '').split(',').filter(Boolean).forEach(function (label) {
                    const option = document.createElement('option');
                    option.value = label.trim();
                    option.textContent = label.trim();
                    labelSelect.appendChild(option);
                });
                labelSelect.value = trigger.dataset.label || '';

                const isManual = trigger.dataset.manual === '1';
                document.getElementById('modal-point-wrap').style.display = isManual ? '' : 'none';
                document.getElementById('modal-point').value = isManual ? trigger.dataset.point : '';
                document.getElementById('modal-reason').value = '';
            });
        })();
    </script>
@endsection
