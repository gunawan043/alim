@extends('layouts.master')
@section('title') Edit Soal @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2')
            <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}">
                {{ $bank->nama }}
            </a>
        @endslot
        @slot('title') Edit Soal #{{ substr($soal->id, 0, 8) }} @endslot
    @endcomponent

    <form method="POST" id="soal-main-form" action="{{ route('user.soal.update', ['userId' => $userId, 'bankId' => $bank->id, 'id' => $soal->id]) }}">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Konten Soal</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipe Soal <span class="text-danger">*</span></label>
                                <select name="tipe_soal" class="form-control" id="tipe_soal" required>
                                    @foreach($tipeSoal as $key => $label)
                                        <option value="{{ $key }}" {{ $soal->tipe_soal === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tujuan Pembelajaran</label>
                                <select name="tp_id" class="form-control">
                                    <option value="">— Tidak terkait —</option>
                                    @foreach($tps as $tp)
                                        <option value="{{ $tp->id }}" {{ $soal->tp_id === $tp->id ? 'selected' : '' }}>
                                            {{ $tp->kode_tp }} · {{ Str::limit($tp->deskripsi, 50) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Materi / Topik</label>
                                <input type="text" name="materi" class="form-control" maxlength="150"
                                       value="{{ old('materi', $soal->materi) }}" placeholder="Contoh: Perkalian bilangan bulat">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Pertanyaan / Stimulus <span class="text-danger">*</span></label>
                                <textarea name="pertanyaan" id="pertanyaan" class="form-control" rows="6" required>{{ old('pertanyaan', $soal->pertanyaan) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Pembahasan</label>
                                <textarea name="pembahasan" rows="3" class="form-control"
                                          placeholder="Penjelasan kunci jawaban...">{{ old('pembahasan', $soal->pembahasan) }}</textarea>
                                <small class="text-muted">Membantu reviewer dan analisis butir soal.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Path Gambar (opsional)</label>
                                <input type="text" name="gambar_path" class="form-control" value="{{ old('gambar_path', $soal->gambar_path) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Path Audio (opsional)</label>
                                <input type="text" name="audio_path" class="form-control" value="{{ old('audio_path', $soal->audio_path) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tags (opsional)</label>
                                <input type="text" name="tags" class="form-control"
                                       value="{{ old('tags', is_array($soal->tags) ? implode(', ', $soal->tags) : ($soal->tags ?? '')) }}"
                                       placeholder="aljabar, persamaan-linear">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" id="options-card" style="display:none">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Opsi Jawaban</h5>
                        <button type="button" class="btn btn-success btn-sm" id="btn-add-option">
                            <i class="ri-add-line"></i> Tambah Opsi
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="options-container"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pengaturan Bobot</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Bobot Default</label>
                            <input type="number" name="bobot_default" min="0" max="100" step="0.5"
                                   class="form-control" value="{{ old('bobot_default', $soal->bobot_default) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tingkat Kesulitan</label>
                            <select name="tingkat_kesulitan_estimasi" class="form-control" required>
                                <option value="mudah" {{ $soal->tingkat_kesulitan_estimasi === 'mudah' ? 'selected' : '' }}>Mudah</option>
                                <option value="sedang" {{ $soal->tingkat_kesulitan_estimasi === 'sedang' ? 'selected' : '' }}>Sedang</option>
                                <option value="sulit" {{ $soal->tingkat_kesulitan_estimasi === 'sulit' ? 'selected' : '' }}>Sulit</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waktu Estimasi (menit)</label>
                            <input type="number" name="waktu_estimasi_menit" min="1" max="120"
                                   class="form-control" value="{{ old('waktu_estimasi_menit', $soal->waktu_estimasi_menit) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status Saat Ini</label>
                            <span class="badge
                                @if($soal->status === 'approved') bg-success
                                @elseif($soal->status === 'draft') bg-warning
                                @elseif($soal->status === 'submitted') bg-info
                                @else bg-secondary @endif">
                                {{ ucfirst($soal->status ?? 'unknown') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card mb-3" id="similarity-card">
                    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ri-scan-2-line text-warning me-1"></i>Pemeriksaan Kemiripan</h5>
                        @if($soal->similarity_checked_at)
                            <span class="badge bg-light text-muted">{{ $soal->similarity_checked_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @php $simSummary = $soal->similarity_summary ?? []; @endphp
                        @if(! $soal->similarity_checked_at)
                            <p class="text-muted small mb-0">
                                <i class="ri-information-line me-1"></i>Belum diperiksa. Pemeriksaan otomatis dijalankan saat soal diajukan untuk review.
                            </p>
                        @elseif(($simSummary['total'] ?? 0) === 0)
                            <p class="text-success small mb-0"><i class="ri-checkbox-circle-line me-1"></i>Tidak ada kemiripan signifikan dengan soal historis.</p>
                        @else
                            <div class="alert alert-warning py-2 small mb-2">
                                <i class="ri-error-warning-line me-1"></i>Ditemukan <strong>{{ $simSummary['total'] }}</strong> soal historis perlu ditinjau.
                                Skor tertinggi <strong>{{ $simSummary['highest'] ?? 0 }}%</strong>.
                                Similarity adalah indikator — bukan penolakan otomatis.
                            </div>
                            @foreach($soal->similarities as $sim)
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-warning-subtle text-warning">{{ $sim->score }}% · {{ $sim->levelLabel() }}</span>
                                        <button type="button" class="btn btn-sm btn-soft-secondary"
                                                onclick="showComparison('{{ $soal->id }}', '{{ $sim->compared_soal_id }}')">
                                            Bandingkan
                                        </button>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ $sim->comparedSoal?->bankSoal?->subject?->name ?? '—' }}
                                        · {{ $sim->comparedSoal?->bankSoal?->academicYear?->name ?? '—' }}
                                        · {{ ucfirst($sim->comparedSoal?->bankSoal?->semester ?? '—') }}
                                        · {{ strtoupper((string) $sim->comparedSoal?->bankSoal?->jenis_soal) }}
                                    </div>
                                    <div class="small text-muted">{{ $sim->comparedSoal?->creator?->name ?? '—' }}</div>
                                </div>
                            @endforeach
                        @endif

                        @if($soal->similarity_ack_note)
                            <div class="border rounded p-2 bg-light-subtle small">
                                <div class="fw-semibold"><i class="ri-chat-check-line me-1"></i>Alasan melanjutkan (tercatat)</div>
                                <div>{{ $soal->similarity_ack_note }}</div>
                                <div class="text-muted">{{ $soal->similarity_ack_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" form="soal-main-form" class="btn btn-success w-100 mb-2">
                            <i class="ri-save-line me-1"></i> Perbarui Soal
                        </button>
                        @if(in_array($soal->workflow_status, ['draft', 'revisi'], true))
                        <div class="border rounded p-2 mb-2 bg-light-subtle">
                            <label class="form-label small mb-1">Ajukan Review (catatan alasan opsional)</label>
                            <textarea name="ack_note" form="submit-review-form" rows="2" class="form-control form-control-sm mb-2"
                                      maxlength="2000"
                                      placeholder="Isi bila tetap melanjutkan meski ada kemiripan...">{{ old('ack_note', $soal->similarity_ack_note) }}</textarea>
                            <button type="submit" form="submit-review-form" class="btn btn-primary w-100">
                                <i class="ri-send-plane-line me-1"></i> Submit untuk Review
                            </button>
                        </div>
                        @endif
                        <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}"
                           class="btn btn-light w-100">
                            <i class="ri-close-line me-1"></i> Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if(in_array($soal->workflow_status, ['draft', 'revisi'], true))
        <form method="POST" id="submit-review-form"
              action="{{ route('user.soal.submit-review', ['userId' => $userId, 'bankId' => $bank->id, 'id' => $soal->id]) }}">
            @csrf
        </form>
    @endif

    {{-- Modal perbandingan soal --}}
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
                            <h6 class="text-uppercase text-muted small">Soal Ini</h6>
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
document.addEventListener('DOMContentLoaded', function () {
    const tipeSelect = document.getElementById('tipe_soal');
    const optionsCard = document.getElementById('options-card');
    const container = document.getElementById('options-container');
    const btnAdd = document.getElementById('btn-add-option');

    let optIndex = 0;

    function makeOptionRow(i, label = '', text = '', isCorrect = false, existingId = '') {
        return `
            <div class="option-row border rounded p-2 mb-2" data-i="${i}">
                <div class="d-flex align-items-center gap-2">
                    <input type="text" name="options[${i}][label]" class="form-control form-control-sm"
                           style="max-width:60px" placeholder="A" value="${label}" required>
                    <input type="text" name="options[${i}][teks_opsi]" class="form-control form-control-sm"
                           placeholder="Teks opsi" value="${text.replace(/"/g, '&quot;')}" required>
                    <div class="form-check">
                        <input type="checkbox" name="options[${i}][is_correct]" value="1" class="form-check-input"
                               id="opt_correct_${i}" ${isCorrect ? 'checked' : ''}>
                        <label class="form-check-label" for="opt_correct_${i}">Benar</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-opt">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function rebuildOptions() {
        container.innerHTML = '';
        optIndex = 0;
        const seedCount = (tipeSelect.value === 'bs') ? 2 : 4;
        for (let i = 0; i < seedCount; i++) {
            container.insertAdjacentHTML('beforeend', makeOptionRow(optIndex, String.fromCharCode(65 + i)));
            optIndex++;
        }
    }

    tipeSelect.addEventListener('change', function () {
        const needsOptions = ['pg', 'bs', 'jodoh'].includes(this.value);
        optionsCard.style.display = needsOptions ? 'block' : 'none';
        if (needsOptions) rebuildOptions();
    });

    btnAdd.addEventListener('click', function () {
        container.insertAdjacentHTML('beforeend', makeOptionRow(optIndex, String.fromCharCode(65 + optIndex)));
        optIndex++;
    });

    container.addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-opt')) {
            e.target.closest('.option-row').remove();
        }
    });

    // Seed existing options
    tipeSelect.dispatchEvent(new Event('change'));
    @if($soal->options->isNotEmpty())
        container.innerHTML = '';
        {!! $soal->options->map(function($opt, $i) {
            return 'container.insertAdjacentHTML(\'beforeend\', makeOptionRow(' . $i . ', "' . addslashes(htmlspecialchars($opt->label, ENT_QUOTES)) . '", "' . addslashes(htmlspecialchars($opt->teks_opsi, ENT_QUOTES)) . '", ' . ($opt->is_correct ? 'true' : 'false') . ', "' . $opt->id . '"));';
        })->join("\n") !!}
        optIndex = {{ $soal->options->count() }};
    @elseif(in_array($soal->tipe_soal, ['pg', 'bs', 'jodoh']))
        rebuildOptions();
    @endif
});

function renderCompareSoal(data) {
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
            document.getElementById('compare-left').innerHTML = renderCompareSoal(data.soal);
            document.getElementById('compare-right').innerHTML = renderCompareSoal(data.compared);
            document.getElementById('compare-score').textContent = data.score + '%';
            document.getElementById('compare-meta').textContent =
                (data.level_label || '') + ' · ' + (data.compared.academic_year || '-') + ' · ' + (data.compared.subject || '-') + ' · ' + (data.compared.pembuat || '-');
            new bootstrap.Modal(document.getElementById('comparison-modal')).show();
        });
}
</script>
@endsection