@extends('layouts.master')

@section('title', $report->title)

@section('content')
    @php
        $statusColors = [
            'baru' => 'warning',
            'diproses' => 'info',
            'menunggu_informasi' => 'secondary',
            'selesai' => 'success',
            'ditolak' => 'danger',
        ];
        $priorityColors = [
            'rendah' => 'secondary',
            'sedang' => 'info',
            'tinggi' => 'warning',
            'kritis' => 'danger',
        ];
        $statusColor = $statusColors[$report->status] ?? 'secondary';
        $priorityColor = $priorityColors[$report->priority] ?? 'secondary';
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.feedback.index', ['userId' => $userId]) }}">Kritik & Saran</a> @endslot
        @slot('title') Detail Laporan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <a href="{{ route('user.feedback.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Kembali ke Daftar
        </a>
        @if($canManage)
            <span class="badge bg-primary-subtle text-primary"><i class="ri-shield-user-line me-1"></i>Mode Pengelola</span>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <h5 class="card-title mb-2">{{ $report->title }}</h5>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-light text-dark">{{ $report->categoryLabel() }}</span>
                                <span class="badge bg-{{ $priorityColor }}-subtle text-{{ $priorityColor }}">{{ $report->priorityLabel() }}</span>
                                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $report->statusLabel() }}</span>
                            </div>
                        </div>
                        <div class="text-muted small text-lg-end">
                            <div><i class="ri-calendar-line me-1"></i>{{ $report->created_at?->format('d M Y H:i') }}</div>
                            @if($report->page_url)
                                <div class="mt-1 text-break"><i class="ri-links-line me-1"></i>{{ $report->page_url }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small fw-semibold">Uraian Masalah</h6>
                    <p class="mb-4">{!! nl2br(e($report->description)) !!}</p>

                    <h6 class="text-uppercase text-muted small fw-semibold">Kronologi / Proses</h6>
                    @if($report->process)
                        <p class="mb-4">{!! nl2br(e($report->process)) !!}</p>
                    @else
                        <p class="text-muted mb-4">Tidak ada kronologi tambahan.</p>
                    @endif

                    <h6 class="text-uppercase text-muted small fw-semibold">Hasil yang Diharapkan</h6>
                    @if($report->expected)
                        <p class="mb-0">{!! nl2br(e($report->expected)) !!}</p>
                    @else
                        <p class="text-muted mb-0">Tidak dicantumkan.</p>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-attachment-2 me-1"></i>Bukti Pendukung</h5>
                </div>
                <div class="card-body">
                    @forelse($report->attachments as $attachment)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-file-line fs-5 text-muted"></i>
                                    <div>
                                        <div class="fw-medium">{{ $attachment->original_name }}</div>
                                        <div class="text-muted small">{{ $attachment->humanSize() }}</div>
                                    </div>
                                </div>
                                <a href="{{ route('user.feedback.attachment', ['userId' => $userId, 'id' => $report->id, 'attachmentId' => $attachment->id]) }}"
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="ri-external-link-line me-1"></i>Buka
                                </a>
                            </div>
                            @if($attachment->isImage())
                                <a href="{{ route('user.feedback.attachment', ['userId' => $userId, 'id' => $report->id, 'attachmentId' => $attachment->id]) }}"
                                   target="_blank">
                                    <img src="{{ route('user.feedback.attachment', ['userId' => $userId, 'id' => $report->id, 'attachmentId' => $attachment->id]) }}"
                                         alt="{{ $attachment->original_name }}" class="img-fluid rounded border mt-2"
                                         style="max-height:260px;object-fit:contain;">
                                </a>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Tidak ada bukti yang diunggah.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-1"></i>Riwayat Proses</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        @forelse($report->updates as $update)
                            @php
                                $isStatus = $update->type === 'status';
                                $fromColor = $statusColors[$update->status_from] ?? 'secondary';
                                $toColor = $statusColors[$update->status_to] ?? 'secondary';
                            @endphp
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <span class="avatar-xs">
                                        <span class="avatar-title rounded-circle bg-{{ $isStatus ? 'primary' : 'secondary' }}-subtle text-{{ $isStatus ? 'primary' : 'secondary' }}">
                                            <i class="{{ $isStatus ? 'ri-refresh-line' : 'ri-chat-1-line' }}"></i>
                                        </span>
                                    </span>
                                </div>
                                <div class="flex-grow-1 border-bottom pb-3">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <div class="fw-semibold">
                                            {{ $update->user?->name ?? 'Sistem' }}
                                            @if($isStatus && $update->status_to)
                                                mengubah status
                                                @if($update->status_from)
                                                    <span class="badge bg-{{ $fromColor }}-subtle text-{{ $fromColor }}">{{ $report->statusLabelFor($update->status_from) }}</span>
                                                    <i class="ri-arrow-right-line mx-1"></i>
                                                @endif
                                                <span class="badge bg-{{ $toColor }}-subtle text-{{ $toColor }}">{{ $report->statusLabelFor($update->status_to) }}</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small">{{ $update->created_at?->format('d M Y H:i') }}</div>
                                    </div>
                                    @if($update->message)
                                        <div class="mt-1">{!! nl2br(e($update->message)) !!}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Belum ada riwayat proses.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-user-settings-line me-1"></i>Informasi Pelapor</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Nama</div>
                        <div class="fw-semibold">{{ $report->submitter?->name ?? '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Divisi / Satuan Kerja</div>
                        <div class="fw-semibold">{{ $report->division ?: '—' }}</div>
                        @if($report->workUnit)
                            <div class="text-muted small">{{ $report->workUnit->name }}</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Jabatan</div>
                        <div class="fw-semibold">{{ $report->jabatan ?: '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Satuan Pendidikan</div>
                        <div class="fw-semibold">{{ $report->school?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Ditangani Oleh</div>
                        <div class="fw-semibold">{{ $report->assignee?->name ?? 'Belum ditugaskan' }}</div>
                        @if($report->resolved_at)
                            <div class="text-success small">Selesai {{ $report->resolved_at->format('d M Y H:i') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            @if($canManage)
                <div class="card mt-3">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-edit-2-line me-1"></i>Tindak Lanjut</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('user.feedback.status', ['userId' => $userId, 'id' => $report->id]) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    @foreach(\App\Models\AppFeedbackReport::STATUSES as $value => $label)
                                        <option value="{{ $value }}" @selected($report->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Keterangan Proses</label>
                                <textarea name="note" rows="4" class="form-control" required
                                          placeholder="Tuliskan tindakan, penyebab, atau informasi yang diperlukan...">{{ old('note') }}</textarea>
                            </div>
                            <button class="btn btn-primary w-100">
                                <i class="ri-save-3-line me-1"></i>Simpan Tindak Lanjut
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="alert alert-info small mt-3 mb-0">
                <i class="ri-information-line me-1"></i>
                Setiap perubahan status dan keterangan proses akan tercatat pada riwayat laporan.
            </div>
        </div>
    </div>
@endsection
