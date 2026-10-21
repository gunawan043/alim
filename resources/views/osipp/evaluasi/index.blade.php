@extends('layouts.master')
@section('title', 'Evaluasi Kepengurusan OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Evaluasi Kepengurusan @endslot
        @endcomponent

        @include('osipp._header')

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-lg">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">{{ $latest?->grade ?: '—' }}</span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase stat-label mb-1">Nilai Evaluasi Terakhir</p>
                            <h3 class="mb-0">{{ $latest ? number_format((float) $latest->total_score, 2) : '—' }}<small class="text-muted fs-12">/100</small></h3>
                            <div class="text-muted small">{{ $latest?->type_label ?? 'Belum ada evaluasi' }}{{ $latest?->evaluation_date ? ' · '.$latest->evaluation_date->translatedFormat('d M Y') : '' }}</div>
                        </div>
                    </div>
                    @if($canEvaluate)
                        <button type="button" class="btn btn-primary" onclick="openEvaluationModal(null)">
                            <i class="ri-add-line me-1"></i>Tambah Evaluasi
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-award-line me-1 text-success"></i>Riwayat Evaluasi</h5>
                <p class="text-muted small mb-0">Penilaian memakai lima aspek kepengurusan dengan bobot total 100.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Nilai</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Evaluator</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evaluations as $evaluation)
                            <tr>
                                <td>{{ $evaluation->evaluation_date?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td>{{ $evaluation->type_label }}</td>
                                <td class="fw-semibold">{{ number_format((float) $evaluation->total_score, 2) }}</td>
                                <td><span class="badge bg-success-subtle text-success fs-6">{{ $evaluation->grade ?: '—' }}</span></td>
                                <td><span class="badge bg-{{ $evaluation->status === 'final' ? 'success' : 'secondary' }}">{{ $evaluation->status_label }}</span></td>
                                <td>{{ $evaluation->evaluator?->name ?? '—' }}</td>
                                <td class="text-end">
                                    @if($canEvaluate)
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="openEvaluationModal('{{ $evaluation->id }}')" title="Edit"><i class="ri-edit-line"></i></button>
                                            @if($evaluation->status !== 'final')
                                                <form method="POST" action="{{ route('osipp.evaluasi.finalize', ['userId' => $userId, 'evaluation' => $evaluation->id]) }}" onsubmit="return confirm('Finalkan evaluasi ini?');">
                                                    @csrf
                                                    <input type="hidden" name="period_id" value="{{ $period->id }}">
                                                    <button class="btn btn-sm btn-outline-success" title="Finalkan"><i class="ri-check-double-line"></i></button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('osipp.evaluasi.destroy', ['userId' => $userId, 'evaluation' => $evaluation->id]) }}" onsubmit="return confirm('Hapus evaluasi ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="period_id" value="{{ $period->id }}">
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-award-line fs-3 d-block mb-2"></i>Belum ada evaluasi kepengurusan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($evaluations->hasPages())
                <div class="card-footer">{{ $evaluations->links() }}</div>
            @endif
        </div>

        @if($canEvaluate)
            <div class="modal fade" id="evaluationModal" tabindex="-1" aria-labelledby="evaluationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('osipp.evaluasi.store', ['userId' => $userId]) }}" id="evaluationForm">
                            @csrf
                            <input type="hidden" name="_method" value="POST" id="evaluationMethod">
                            <input type="hidden" name="period_id" value="{{ $period->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="evaluationModalLabel">Tambah Evaluasi Kepengurusan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Jenis Evaluasi <span class="text-danger">*</span></label>
                                        <select name="evaluation_type" id="evaluationType" class="form-select" required>
                                            @foreach($typeOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                        <input type="date" name="evaluation_date" id="evaluationDate" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="evaluationStatus" class="form-select" required>
                                            @foreach($statusOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead><tr><th>Aspek</th><th>Bobot</th><th style="width: 120px;">Skor (1–5)</th><th>Kontribusi</th></tr></thead>
                                        <tbody>
                                            @foreach($aspects as $key => $aspect)
                                                <tr>
                                                    <td>{{ $aspect['label'] }}</td>
                                                    <td class="text-muted">{{ $aspect['weight'] }}%</td>
                                                    <td><input type="number" class="form-control form-control-sm osipp-score-input" name="scores[{{ $key }}]" id="score_{{ $key }}" min="1" max="5" value="3" required data-key="{{ $key }}" data-weight="{{ $aspect['weight'] }}"></td>
                                                    <td class="text-muted small"><span id="contribution_{{ $key }}">0</span> / {{ $aspect['weight'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr><th colspan="3" class="text-end">Total</th><th><span id="evaluationTotal">60</span> / 100 <span class="badge bg-success-subtle text-success ms-1" id="evaluationGrade">C</span></th></tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Kekuatan / Hal Positif</label>
                                    <textarea name="strengths" id="evaluationStrengths" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Rekomendasi</label>
                                    <textarea name="recommendations" id="evaluationRecommendations" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('script')
<script>
    const osippEvaluations = @json($evaluationsJson);
    const osippEvaluationStoreUrl = @json(route('osipp.evaluasi.store', ['userId' => $userId]));
    const osippAspects = @json($aspects);

    function scoreTotal() {
        let total = 0;
        document.querySelectorAll('.osipp-score-input').forEach(function (input) {
            const score = Math.max(1, Math.min(5, parseFloat(input.value || 1)));
            const key = input.dataset.key;
            const contribution = (score / 5) * parseFloat(input.dataset.weight || 0);
            total += contribution;
            const cell = document.getElementById('contribution_' + key);
            if (cell) cell.textContent = contribution.toFixed(1);
        });
        total = Math.round(total * 100) / 100;
        const totalEl = document.getElementById('evaluationTotal');
        const gradeEl = document.getElementById('evaluationGrade');
        if (totalEl) totalEl.textContent = total.toFixed(2);
        if (gradeEl) gradeEl.textContent = total >= 90 ? 'A' : (total >= 80 ? 'B' : (total >= 70 ? 'C' : 'D'));
    }

    document.querySelectorAll('.osipp-score-input').forEach(function (input) {
        input.addEventListener('input', scoreTotal);
        input.addEventListener('change', scoreTotal);
    });
    scoreTotal();

    function openEvaluationModal(id) {
        const form = document.getElementById('evaluationForm');
        if (!form) return;
        const data = id ? osippEvaluations.find(function (item) { return item.id === id; }) : null;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('evaluationModal'));

        form.action = data ? data.update_url : osippEvaluationStoreUrl;
        document.getElementById('evaluationMethod').value = data ? 'PUT' : 'POST';
        document.getElementById('evaluationModalLabel').textContent = data ? 'Edit Evaluasi Kepengurusan' : 'Tambah Evaluasi Kepengurusan';
        document.getElementById('evaluationType').value = data ? (data.evaluation_type || 'akhir') : 'akhir';
        document.getElementById('evaluationDate').value = data ? (data.evaluation_date || '') : '';
        document.getElementById('evaluationStatus').value = data ? (data.status || 'draft') : 'draft';
        document.getElementById('evaluationStrengths').value = data ? (data.strengths || '') : '';
        document.getElementById('evaluationRecommendations').value = data ? (data.recommendations || '') : '';

        Object.keys(osippAspects).forEach(function (key) {
            const input = document.getElementById('score_' + key);
            if (input) input.value = data && data.scores && data.scores[key] ? data.scores[key] : 3;
        });

        scoreTotal();
        modal.show();
    }
</script>
@endsection
