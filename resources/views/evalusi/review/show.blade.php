@extends('layouts.master')
@section('title', 'Tinjau Dokumen Review')

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $isSoal = $reviewable instanceof \App\Models\Soal;
        $bank = $isSoal ? $reviewable->bankSoal : null;
        $kisi = ! $isSoal ? $reviewable->kisiKisi : null;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.review-soal.index', ['userId' => $userId]) }}">Review</a> @endslot
        @slot('title') {{ $isSoal ? 'Tinjau Soal' : 'Tinjau Paket Soal' }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Progres Review</p>
                    <h3 class="fw-bold ff-secondary mb-0">{{ $progress['approved'] }}/{{ $progress['total'] }}</h3>
                    <p class="text-muted mb-0 stat-label">{{ $progress['pending'] }} menunggu · {{ $progress['revision'] }} perlu perbaikan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Status Workflow</p>
                    @php
                        $wLabel = $isSoal
                            ? (\App\Models\Soal::WORKFLOW_OPTIONS[$reviewable->workflow_status] ?? $reviewable->workflow_status)
                            : (\App\Models\PaketSoal::WORKFLOW_OPTIONS[$reviewable->workflow_status] ?? $reviewable->workflow_status);
                    @endphp
                    <h4 class="fw-bold ff-secondary mb-0">{{ $wLabel }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Kemiripan Historis</p>
                    @php $sim = $reviewable->similarity_summary ?? []; @endphp
                    <h3 class="fw-bold ff-secondary mb-0 {{ ($sim['total'] ?? 0) > 0 ? 'text-warning' : 'text-success' }}">
                        {{ $sim['total'] ?? 0 }}
                    </h3>
                    <p class="text-muted mb-0 stat-label">
                        @if(($sim['total'] ?? 0) > 0)
                            Tertinggi {{ $sim['highest'] ?? 0 }}% — periksa sebelum menyetujui
                        @else
                            Tidak ada kemiripan signifikan
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Keputusan Anda</p>
                    <h4 class="fw-bold ff-secondary mb-0">{{ \App\Models\ReviewAssignment::STATUS_OPTIONS[$assignment->status] ?? $assignment->status }}</h4>
                    @if($assignment->decided_at)<p class="text-muted mb-0 stat-label">{{ $assignment->decided_at->format('d/m/Y H:i') }}</p>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">
                        @if($isSoal)
                            <i class="ri-question-line text-primary me-1"></i>Soal — {{ $bank?->subject?->name ?? '-' }}
                            <span class="badge bg-info-subtle text-info ms-1">{{ $bank?->gradeLevel?->name ?? 'Semua Kelas' }}</span>
                        @else
                            <i class="ri-stack-line text-primary me-1"></i>Paket — {{ $reviewable->judul }}
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if($isSoal)
                        <table class="table table-sm mb-3">
                            <tr><th style="width:140px">Konteks</th><td>
                                {{ $bank?->academicYear?->name ?? '—' }} · {{ ucfirst($bank?->semester ?? '—') }}
                                @if($reviewable->materi) · Materi: {{ $reviewable->materi }} @endif
                                · Kesulitan: {{ ucfirst($reviewable->tingkat_kesulitan_estimasi) }}
                            </td></tr>
                            <tr><th>CP / TP</th><td>
                                @if($reviewable->tujuanPembelajaran)
                                    <span class="badge bg-primary-subtle text-primary">{{ $reviewable->tujuanPembelajaran->kode_tp }}</span>
                                    {{ $reviewable->tujuanPembelajaran->deskripsi }}
                                    @if($reviewable->tujuanPembelajaran->capaianPembelajaran)
                                        <div class="small text-muted mt-1">CP: {{ \Illuminate\Support\Str::limit($reviewable->tujuanPembelajaran->capaianPembelajaran->deskripsi, 140) }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">Tidak dikaitkan TP</span>
                                @endif
                            </td></tr>
                            <tr><th>Pembuat</th><td>{{ $reviewable->creator?->name ?? '—' }} · {{ $bank?->school?->name ?? 'Institusi' }}</td></tr>
                        </table>

                        <div class="border rounded p-3 mb-3 bg-light-subtle">
                            <div class="fw-semibold mb-2">Pertanyaan</div>
                            <div>{!! $reviewable->pertanyaan !!}</div>
                        </div>

                        @if($reviewable->options->isNotEmpty())
                            <table class="table table-sm">
                                <thead class="table-light"><tr><th style="width:40px">#</th><th>Opsi</th><th class="text-center" style="width:90px">Kunci</th></tr></thead>
                                <tbody>
                                    @foreach($reviewable->options as $option)
                                        <tr class="{{ $option->is_correct ? 'table-success' : '' }}">
                                            <td class="fw-semibold">{{ $option->label }}</td>
                                            <td>{{ $option->teks_opsi }}</td>
                                            <td class="text-center">
                                                @if($option->is_correct)<span class="badge bg-success-subtle text-success"><i class="ri-check-line"></i> Kunci</span>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if($reviewable->pembahasan)
                            <div class="border rounded p-3 bg-light-subtle">
                                <div class="fw-semibold mb-1">Pembahasan</div>
                                <div class="small">{!! $reviewable->pembahasan !!}</div>
                            </div>
                        @endif
                    @else
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th style="width:40px">#</th><th>Soal</th><th class="text-center">Status Soal</th></tr></thead>
                            <tbody>
                                @foreach($reviewable->items as $item)
                                    <tr>
                                        <td>{{ $item->urutan }}</td>
                                        <td class="small">{{ \Illuminate\Support\Str::limit(strip_tags($item->soal?->pertanyaan ?? ''), 140) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $item->soal?->isApproved() ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                                {{ $item->soal?->isApproved() ? 'Approved' : 'Belum' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            {{-- Similarity warnings + comparison --}}
            @if($isSoal)
                <div class="card mb-3">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-scan-2-line text-warning me-1"></i>Pemeriksaan Kemiripan</h5>
                    </div>
                    <div class="card-body">
                        @if($similarities->isEmpty())
                            <p class="text-muted small mb-0"><i class="ri-checkbox-circle-line text-success me-1"></i>Tidak ada kemiripan signifikan dengan soal historis.</p>
                        @else
                            <p class="text-warning small mb-1"><i class="ri-error-warning-line me-1"></i>Potensi kemiripan dengan {{ $similarities->count() }} soal historis (indikator, bukan penolakan otomatis):</p>
                            <p class="small text-muted mb-2"><strong>Status:</strong> Perlu ditinjau reviewer</p>
                            @foreach($similarities as $sim)
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-warning-subtle text-warning">{{ $sim->score }}% · {{ $sim->levelLabel() }}</span>
                                        <button type="button" class="btn btn-sm btn-soft-secondary"
                                                onclick="showComparison('{{ $reviewable->id }}', '{{ $sim->compared_soal_id }}')">
                                            Bandingkan
                                        </button>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ $sim->comparedSoal?->bankSoal?->subject?->name ?? '—' }}
                                        · {{ $sim->comparedSoal?->bankSoal?->gradeLevel?->name }}
                                        · {{ $sim->comparedSoal?->bankSoal?->academicYear?->name ?? '—' }}
                                        · {{ ucfirst($sim->comparedSoal?->bankSoal?->semester ?? '—') }}
                                        · {{ strtoupper((string) $sim->comparedSoal?->bankSoal?->jenis_soal) }}
                                    </div>
                                    @if($sim->comparedSoal?->materi)
                                        <div class="small text-muted">Materi: {{ $sim->comparedSoal->materi }}</div>
                                    @endif
                                    <div class="small text-muted">Pembuat: {{ $sim->comparedSoal?->creator?->name ?? '—' }} · Status: {{ \App\Models\Soal::WORKFLOW_OPTIONS[$sim->comparedSoal?->workflow_status] ?? '—' }}</div>
                                    <div class="small mt-1">{{ \Illuminate\Support\Str::limit(strip_tags($sim->comparedSoal?->pertanyaan ?? ''), 90) }}</div>
                                </div>
                            @endforeach
                        @endif

                        @if($reviewable->similarity_ack_note)
                            <div class="border rounded p-2 bg-light-subtle small mt-2">
                                <div class="fw-semibold"><i class="ri-chat-check-line me-1"></i>Alasan penyusun melanjutkan</div>
                                <div>{{ $reviewable->similarity_ack_note }}</div>
                                <div class="text-muted">{{ $reviewable->similarity_ack_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Keputusan --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-gavel-line text-primary me-1"></i>Keputusan Reviewer</h5>
                </div>
                <div class="card-body">
                    @if($assignment->status !== \App\Models\ReviewAssignment::STATUS_PENDING)
                        <div class="alert alert-{{ $assignment->status === 'approved' ? 'success' : 'warning' }} small mb-2">
                            Keputusan: <strong>{{ \App\Models\ReviewAssignment::STATUS_OPTIONS[$assignment->status] }}</strong>
                            @if($assignment->note)<div class="mt-1">Catatan: {{ $assignment->note }}</div>@endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.review-soal.decide', ['userId' => $userId, 'assignmentId' => $assignment->id]) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Catatan Reviewer</label>
                            <textarea name="note" rows="3" class="form-control" maxlength="2000" placeholder="Opsional — alasan keputusan / saran perbaikan">{{ $assignment->note }}</textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="status" value="approved" class="btn btn-success flex-grow-1">
                                <i class="ri-check-line me-1"></i> Setujui
                            </button>
                            <button type="submit" name="status" value="revision" class="btn btn-outline-danger flex-grow-1">
                                <i class="ri-arrow-go-back-line me-1"></i> Minta Perbaikan
                            </button>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            Dokumen menjadi <strong>Approved</strong> hanya bila seluruh reviewer menyetujui. Satu permintaan perbaikan menjadikan status <strong>Perlu Perbaikan</strong>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal comparison --}}
    <div class="modal fade zoomIn" id="comparison-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle p-3">
                    <h5 class="modal-title"><i class="ri-scan-2-line me-1"></i>Pemeriksaan Kemiripan Soal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted small">Soal Baru</h6>
                            <div class="border rounded p-3" id="compare-left">—</div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted small">Soal Historis</h6>
                            <div class="border rounded p-3" id="compare-right">—</div>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">
                        Kemiripan: <strong id="compare-score">-</strong>
                        <span id="compare-meta" class="small text-muted ms-2"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function renderSoal(data) {
            var html = '<div class="mb-2">' + (data.pertanyaan || '-') + '</div>';
            if (data.options && data.options.length) {
                html += '<ul class="list-unstyled small mb-2">';
                data.options.forEach(function (o) {
                    html += '<li><strong>' + (o.label || '') + '.</strong> ' + (o.teks || '') + (o.correct ? ' <span class="badge bg-success-subtle text-success">Kunci</span>' : '') + '</li>';
                });
                html += '</ul>';
            }
            if (data.pembahasan) {
                html += '<div class="small text-muted"><strong>Pembahasan:</strong> ' + data.pembahasan + '</div>';
            }
            return html;
        }

        function showComparison(soalId, comparedId) {
            var url = {{ route('user.bank-soal-terpusat.compare', ['userId' => $userId, 'soalId' => '__SOAL__', 'comparedId' => '__CMP__']) }};
            url = url.replace('__SOAL__', soalId).replace('__CMP__', comparedId);

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    document.getElementById('compare-left').innerHTML = renderSoal(data.soal);
                    document.getElementById('compare-right').innerHTML = renderSoal(data.compared);
                    document.getElementById('compare-score').textContent = data.score + '%';
                    document.getElementById('compare-meta').textContent =
                        (data.level_label || '') + ' · ' + (data.compared.academic_year || '-') + ' · ' + (data.compared.subject || '-') + ' · ' + (data.compared.pembuat || '-');
                    new bootstrap.Modal(document.getElementById('comparison-modal')).show();
                });
        }
    </script>
@endsection
