@extends('layouts.master')
@section('title') {{ ($isEditMode ?? false) ? 'Edit Soal' : 'Input Soal' }} — {{ $bank->nama }} @endsection

@section('content')
    @php
        $isEditMode = $isEditMode ?? false;
        $builderQuestions = $builderQuestions ?? [];
        $formAction = $formAction ?? route('user.soal.store', ['userId' => $userId, 'bankId' => $bank->id]);
        $typeMap = [
            'pilihan_ganda' => 'pg',
            'multiple_choice_complex' => 'pg',
            'benar_salah' => 'bs',
            'menjodohkan' => 'jodoh',
            'isian_singkat' => 'isian',
            'uraian' => 'uraian',
        ];
        $bankType = $typeMap[$bank->jenis_soal] ?? 'pg';
        $isMixed = $bank->jenis_soal === 'campuran';
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}">Bank Soal</a> @endslot
        @slot('title') {{ $isEditMode ? 'Edit Soal' : 'Input Soal' }} @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Periksa kembali input soal:</div>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">{{ $bank->nama }}</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary-subtle text-primary">{{ $bank->subject?->name ?? '-' }}</span>
                        <span class="badge bg-info-subtle text-info">Bahasa: {{ \App\Models\Soal::BAHASA_OPTIONS[$bank->bahasa] ?? 'Latin' }}</span>
                        <span class="badge bg-secondary-subtle text-secondary">Bentuk: {{ ucfirst(str_replace('_', ' ', $bank->jenis_soal)) }}</span>
                        @if($bank->fase)<span class="badge bg-light text-dark border">Fase {{ $bank->fase }}</span>@endif
                    </div>
                </div>
                <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}" class="btn btn-light btn-sm">
                    <i class="ri-arrow-left-line me-1"></i> Kembali ke Bank
                </a>
            </div>
        </div>
    </div>

    <div class="alert alert-info d-flex gap-2 align-items-start">
        <i class="ri-sparkling-line fs-5 mt-1"></i>
        <div>
            <div class="fw-semibold">Level Bloom otomatis</div>
            <div class="small mb-0">
                C1–C2 = L1 · C3 = L2 · C4–C6 = L3. Kesulitan soal terpisah.
                @if($isEditMode)
                    Semua soal dimuat dan bisa disimpan sekaligus.
                @endif
            </div>
            <div class="d-flex flex-wrap gap-1 mt-2">
                <span class="badge bg-primary-subtle text-primary">C1 Mengingat · L1</span>
                <span class="badge bg-info-subtle text-info">C2 Memahami · L1</span>
                <span class="badge bg-success-subtle text-success">C3 Menerapkan · L2</span>
                <span class="badge bg-warning-subtle text-warning">C4 Menganalisis · L3</span>
                <span class="badge bg-danger-subtle text-danger">C5 Mengevaluasi · L3</span>
                <span class="badge bg-purple-subtle text-purple">C6 Mencipta · L3</span>
            </div>
            <details class="mt-2 small">
                <summary class="fw-semibold text-primary" style="cursor:pointer">Panduan singkat C1–C6 dan L1–L3</summary>
                <div class="row g-2 mt-1">
                    <div class="col-md-6"><strong>C1 — Mengingat (L1)</strong><br>Fakta, istilah, definisi.</div>
                    <div class="col-md-6"><strong>C2 — Memahami (L1)</strong><br>Menjelaskan, merangkum, memberi contoh.</div>
                    <div class="col-md-6"><strong>C3 — Menerapkan (L2)</strong><br>Menggunakan konsep/rumus.</div>
                    <div class="col-md-6"><strong>C4 — Menganalisis (L3)</strong><br>Membandingkan, menghubungkan, pola.</div>
                    <div class="col-md-6"><strong>C5 — Mengevaluasi (L3)</strong><br>Menilai, mengkritik, menyimpulkan.</div>
                    <div class="col-md-6"><strong>C6 — Mencipta (L3)</strong><br>Merancang, menyusun, mencipta.</div>
                </div>
            </details>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" id="questionBuilder">
        @csrf

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <h5 class="mb-1">{{ $isEditMode ? 'Edit Semua Soal' : 'Tambah Soal Baru' }}</h5>
                <p class="text-muted mb-0 small">
                    {{ $isEditMode
                        ? 'Semua soal bank dimuat di sini. Ubah beberapa soal sekaligus, lalu simpan sekali.'
                        : 'Susun soal dan wacana sesuai urutan cetak. Gunakan tombol naik/turun untuk memindahkan blok.' }}
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-soft-primary btn-sm" onclick="addQuestion()"><i class="ri-add-line me-1"></i>Tambah Soal</button>
                <button type="button" class="btn btn-soft-info btn-sm" onclick="addWacanaBlock()"><i class="ri-book-open-line me-1"></i>Tambah Wacana</button>
            </div>
        </div>

        <div id="blockList"></div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 mb-3">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addQuestion()"><i class="ri-add-line me-1"></i>Tambah Soal</button>
                <button type="button" class="btn btn-outline-info btn-sm" onclick="addWacanaBlock()"><i class="ri-book-open-line me-1"></i>Tambah Wacana</button>
            </div>
            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Semua Soal</button>
        </div>

        <div class="card" id="savedWacanaStrip">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small fw-semibold text-muted"><i class="ri-bookmark-line me-1"></i>Wacana tersimpan:</span>
                    <div id="savedWacanaList" class="d-flex flex-wrap gap-2">
                        @forelse($wacanas as $wacana)
                            <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1" data-saved-wacana="{{ $wacana->id }}">
                                {{ $wacana->judul ?: Str::limit(strip_tags($wacana->teks), 35) }}
                                @if($wacana->posisi_setelah_soal > 0)<small class="text-muted">setelah {{ $wacana->posisi_setelah_soal }}</small>@endif
                                <button type="button" class="btn btn-sm p-0 text-danger" onclick="deleteSavedWacana('{{ $wacana->id }}', this)" title="Hapus wacana"><i class="ri-close-line"></i></button>
                            </span>
                        @empty
                            <span class="text-muted small" id="savedWacanaEmpty">Belum ada wacana.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/ckeditor5/ckeditor.js') }}"></script>
<script>
const bankType = @json($bankType);
const isMixed = @json($isMixed);
const typeOptions = @json($tipeSoal);
const bloomLevelOptions = @json(\App\Services\Evaluasi\BloomTaxonomyService::levelOptions());
const tpOptions = @json($tps->map(fn ($tp) => ['id' => $tp->id, 'label' => ($tp->kode_tp ? $tp->kode_tp.' — ' : '').\Str::limit($tp->deskripsi ?? $tp->id, 65)])->values());
let savedWacanas = @json($wacanas->map(fn ($wacana) => ['id' => $wacana->id, 'label' => ($wacana->judul ?: \Str::limit(strip_tags($wacana->teks), 45)).($wacana->rentang_nomor ? ' · No. '.$wacana->rentang_nomor : '')])->values());
let questionIndex = 0;
let wacanaIndex = 0;
const wacanaEditors = {};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

// Preview ringan di browser. Perhitungan final tetap dilakukan server agar
// indikator TP + pertanyaan + materi ikut dipertimbangkan.
const bloomVerbs = {
    C1: ['mengingat','menyebutkan','sebutkan','menyebut','menuliskan','tuliskan','menunjukkan','mendefinisikan','mencocokkan','mengidentifikasi','memilih','menyatakan','mendaftar','mengenali','menandai','apa yang dimaksud','pengertian','istilah'],
    C2: ['menjelaskan','jelaskan','menguraikan','uraikan','menafsirkan','merangkum','meringkas','mengklasifikasikan','mengelompokkan','memberi contoh','mendeskripsikan','menerangkan','membedakan','mengubah','memperkirakan','mengapa','bagaimana'],
    C3: ['menghitung','hitunglah','menerapkan','menggunakan','mendemonstrasikan','menyelesaikan','menentukan','mengoperasikan','mengukur','melaksanakan','mempraktikkan','menentukan nilai','berapa'],
    C4: ['menganalisis','analisis','membandingkan','bandingkan','mengkorelasikan','memeriksa','menguji','memecahkan','mendiagnosis','mengorganisasikan','menemukan pola','menelaah','menghubungkan','menentukan hubungan'],
    C5: ['mengevaluasi','evaluasi','menilai','mengkritik','menyimpulkan','simpulkan','memutuskan','mempertimbangkan','menimbang','memvalidasi','menjustifikasi','merekomendasikan','menentukan kelayakan'],
    C6: ['merancang','rancanglah','menyusun','susunlah','membuat','buatlah','menciptakan','mengembangkan','mendesain','memproduksi','mengarang','merumuskan','mengombinasikan','mengkreasikan','menyusun kembali'],
};
const bloomMeta = {
    C1: ['C1 — Mengingat', 'L1 — LOTS', 'primary', 'Mengingat fakta, istilah, definisi, atau informasi yang sudah dipelajari.'],
    C2: ['C2 — Memahami', 'L1 — LOTS', 'info', 'Menjelaskan, merangkum, memberi contoh.'],
    C3: ['C3 — Menerapkan', 'L2 — MOTS', 'success', 'Menggunakan konsep/rumus.'],
    C4: ['C4 — Menganalisis', 'L3 — HOTS', 'warning', 'Membandingkan, menghubungkan, memeriksa, dan menemukan pola atau sebab akibat.'],
    C5: ['C5 — Mengevaluasi', 'L3 — HOTS', 'danger', 'Menilai, mengkritik, menyimpulkan.'],
    C6: ['C6 — Mencipta', 'L3 — HOTS', 'purple', 'Merancang, menyusun, mencipta.'],
};

function bloomResultFromLevel(level) {
    const code = String(level || '').slice(0, 2);
    const meta = bloomMeta[code];
    if (!meta) return null;
    return {code, label: meta[0], rumpun: meta[1], color: meta[2], description: meta[3], manual: true};
}

function detectBloomPreview(text) {
    const normalized = String(text ?? '').toLowerCase().replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    if (!normalized) return null;

    const scores = {C1: 0, C2: 0, C3: 0, C4: 0, C5: 0, C6: 0};
    Object.entries(bloomVerbs).forEach(([level, verbs]) => {
        verbs.forEach(verb => { if (normalized.includes(verb)) scores[level] += 1; });
    });
    const order = ['C6', 'C5', 'C4', 'C3', 'C2', 'C1'];
    const found = order.find(level => scores[level] > 0) || null;

    if (!found) {
        return {code: 'C2', label: bloomMeta.C2[0], rumpun: bloomMeta.C2[1], color: bloomMeta.C2[2], description: bloomMeta.C2[3], fallback: true};
    }

    return {code: found, label: bloomMeta[found][0], rumpun: bloomMeta[found][1], color: bloomMeta[found][2], description: bloomMeta[found][3]};
}

function updateBloomPreview(textarea) {
    if (!textarea) return;
    const card = textarea.closest('.question-card');
    const box = textarea.closest('.card-body')?.querySelector('[data-bloom-preview]');
    if (!box) return;

    const select = card?.querySelector('.bloom-level-select');
    const manual = select?.value;
    let result = manual && manual !== 'auto'
        ? bloomResultFromLevel(manual)
        : detectBloomPreview(textarea.value);

    if (!result) {
        box.innerHTML = '<span class="badge bg-light text-muted border"><i class="ri-sparkling-line me-1"></i>Tulis pertanyaan</span>';
        return;
    }

    const note = result.manual ? 'Manual' : (result.fallback ? 'Perkiraan C2' : 'Otomatis');

    box.innerHTML = `<span class="badge bg-${result.color}-subtle text-${result.color}" title="${result.description || ''}"><i class="ri-sparkling-line me-1"></i>${result.label} · ${result.rumpun}</span>
        <small class="text-muted d-block mt-1">${note}</small>`;
}

function updateBloomPreviewFromSelect(select) {
    const card = select.closest('.question-card');
    updateBloomPreview(card?.querySelector('textarea[name$="[pertanyaan]"]'));
}

function getEditor(id) {
    return wacanaEditors[id] || null;
}

function wacanaOptionsHtml(selected) {
    const current = selected || '';
    return '<option value="">Tidak memakai wacana</option>' + savedWacanas.map(w =>
        `<option value="${w.id}" ${String(current) === String(w.id) ? 'selected' : ''}>${escapeHtml(w.label)}</option>`
    ).join('');
}

function refreshWacanaSelects() {
    document.querySelectorAll('.wacana-select').forEach(select => {
        const current = select.value;
        select.innerHTML = wacanaOptionsHtml(current);
    });
}

function blockPosition(card) {
    let count = 0;
    let node = card.previousElementSibling;
    while (node) {
        if (node.classList.contains('question-card')) count++;
        node = node.previousElementSibling;
    }
    return count;
}

function renumber() {
    let n = 1;
    document.querySelectorAll('.question-card').forEach(card => {
        card.querySelector('.question-number').textContent = n++;
    });
}

function moveBlock(button, direction) {
    const card = button.closest('.block-item');
    if (!card) return;
    if (direction < 0 && card.previousElementSibling) {
        card.parentNode.insertBefore(card, card.previousElementSibling);
    } else if (direction > 0 && card.nextElementSibling) {
        card.parentNode.insertBefore(card.nextElementSibling, card);
    }
    renumber();
    if (card.classList.contains('wacana-block') && card.dataset.wacanaId) {
        persistWacana(card, true);
    }
}

function typeSelect(i, selected = null) {
    const chosen = selected || bankType;
    if (! isMixed) return `<input type="hidden" name="questions[${i}][tipe_soal]" value="${chosen}">`;
    return `<select class="form-select form-select-sm" name="questions[${i}][tipe_soal]" onchange="toggleOptions(this)">
        ${Object.entries(typeOptions).map(([key, label]) => `<option value="${key}" ${key === chosen ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
    </select>`;
}

function optionRows(i, options = [], count = null) {
    const total = count ?? Math.max(4, options.length);
    return Array.from({length: total}, (_, j) => {
        const option = options[j] || {};
        const checked = option.is_correct ? 'checked' : '';
        const correctValue = option.is_correct ? '1' : '0';
        return `
        <div class="input-group input-group-sm mb-1 option-row">
            <span class="input-group-text fw-bold">${escapeHtml(option.label || String.fromCharCode(65 + j))}</span>
            <input type="text" class="form-control" name="questions[${i}][options][${j}][teks_opsi]" value="${escapeHtml(option.teks_opsi || '')}" placeholder="Teks pilihan ${String.fromCharCode(65 + j)}">
            <input type="hidden" class="correct-input" name="questions[${i}][options][${j}][is_correct]" value="${correctValue}">
            <label class="input-group-text" title="Tandai jawaban benar">
                <input type="radio" name="correct_${i}" value="${j}" ${checked} onchange="markCorrect(this, ${i}, ${j})">
                <i class="ri-key-2-line ms-1 text-warning"></i>
            </label>
        </div>`;
    }).join('');
}

function addQuestion(data = {}, scroll = true) {
    const i = questionIndex++;
    const id = data.id || '';
    const type = data.tipe_soal || bankType;
    const options = Array.isArray(data.options) ? data.options : [];
    const card = document.createElement('div');
    card.className = 'card mb-3 question-card block-item';
    card.dataset.key = `q${i}`;
    card.dataset.id = id;
    card.dataset.currentLevel = data.level_kognitif || '';
    card.innerHTML = `
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold"><i class="ri-question-line me-1 text-primary"></i>Soal <span class="question-number"></span>
                ${id ? '<span class="badge bg-success-subtle text-success ms-1">Tersimpan</span>' : ''}
            </span>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-icon btn-soft-secondary" onclick="moveBlock(this, -1)" title="Naikkan"><i class="ri-arrow-up-line"></i></button>
                <button type="button" class="btn btn-sm btn-icon btn-soft-secondary" onclick="moveBlock(this, 1)" title="Turunkan"><i class="ri-arrow-down-line"></i></button>
                <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="this.closest('.block-item').remove(); renumber()" title="Hapus soal"><i class="ri-delete-bin-line"></i></button>
            </div>
        </div>
        <div class="card-body">
            <input type="hidden" name="questions[${i}][id]" value="${escapeHtml(id)}">
            <div class="row g-2">
                <div class="col-lg-9">
                    <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="questions[${i}][pertanyaan]" rows="3" required
                              oninput="updateBloomPreview(this)" placeholder="Tulis pertanyaan...">${escapeHtml(data.pertanyaan || '')}</textarea>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Level Bloom <span class="text-muted fw-normal">(otomatis)</span></label>
                    <div data-bloom-preview class="bloom-preview">
                        <span class="badge bg-light text-muted border"><i class="ri-sparkling-line me-1"></i>Akan terdeteksi otomatis</span>
                    </div>
                    <select class="form-select form-select-sm mt-2 bloom-level-select"
                            name="questions[${i}][level_kognitif]"
                            onchange="updateBloomPreviewFromSelect(this)">
                        <option value="auto">Otomatis</option>
                        ${Object.entries(bloomLevelOptions).map(([key, label]) => `<option value="${key}">${escapeHtml(label)}</option>`).join('')}
                    </select>
                    <small class="text-muted d-block mt-1">Otomatis dari soal + TP.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tujuan Pembelajaran</label>
                    <select class="form-select" name="questions[${i}][tp_id]" data-searchable>
                        <option value="">Tidak terkait</option>
                        ${tpOptions.map(tp => `<option value="${tp.id}" ${String(data.tp_id || '') === String(tp.id) ? 'selected' : ''}>${escapeHtml(tp.label)}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Materi / Topik</label>
                    <input type="text" class="form-control" name="questions[${i}][materi]" maxlength="150" value="${escapeHtml(data.materi || '')}" placeholder="Contoh: Perkalian bilangan bulat">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipe Soal</label>
                    ${typeSelect(i, type)}
                </div>
                <div class="col-12">
                    <label class="form-label">Wacana</label>
                    <select class="form-select wacana-select" name="questions[${i}][wacana_id]">${wacanaOptionsHtml(data.wacana_id || '')}</select>
                </div>
            </div>
            <div class="options-block mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Opsi Jawaban <small class="text-muted">(klik kunci untuk jawaban benar)</small></label>
                    <button type="button" class="btn btn-sm btn-link p-0" onclick="addOption(this)">+ Pilihan</button>
                </div>
                <div class="options-list">${optionRows(i, options)}</div>
            </div>
            <div class="mt-2"><button type="button" class="btn btn-sm btn-soft-secondary" onclick="this.closest('.card-body').querySelector('.details-block').classList.toggle('d-none')"><i class="ri-settings-3-line me-1"></i>Pembahasan & Media</button></div>
            <div class="details-block d-none mt-3 border-top pt-3">
                <div class="mb-2"><label class="form-label">Pembahasan</label><textarea class="form-control" name="questions[${i}][pembahasan]" rows="2">${escapeHtml(data.pembahasan || '')}</textarea></div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">Gambar Soal</label><input type="file" class="form-control" name="questions[${i}][gambar]" accept="image/*"></div>
                    <div class="col-md-6"><label class="form-label">Keterangan Gambar / Nomor</label><input type="text" class="form-control" name="questions[${i}][gambar_keterangan]" maxlength="255" value="${escapeHtml(data.gambar_keterangan || '')}" placeholder="mis. Gambar untuk soal 1–5"></div>
                    <div class="col-12"><label class="form-label">Tags</label><input type="text" class="form-control" name="questions[${i}][tags]" value="${escapeHtml(data.tags || '')}" placeholder="pisahkan dengan koma"></div>
                </div>
            </div>
        </div>`;
    document.getElementById('blockList').appendChild(card);
    renumber();
    if (['isian', 'uraian'].includes(type)) {
        card.querySelector('.options-block').classList.add('d-none');
    }
    if (window.initSearchableSelects) window.initSearchableSelects(card);
    updateBloomPreview(card.querySelector('textarea[name$="[pertanyaan]"]'));
    if (scroll) card.scrollIntoView({behavior: 'smooth', block: 'center'});
}

function addWacanaBlock(data = {}) {
    const key = wacanaIndex++;
    const id = data.id || '';
    const card = document.createElement('div');
    card.className = 'card mb-3 wacana-block block-item';
    card.dataset.key = `w${key}`;
    card.dataset.wacanaId = id;
    card.innerHTML = `
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold"><i class="ri-book-open-line me-1 text-info"></i>Wacana <span class="wacana-number"></span> <span class="badge bg-success-subtle text-success saved-label ${id ? '' : 'd-none'}">Tersimpan</span></span>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-icon btn-soft-secondary" onclick="moveBlock(this, -1)" title="Naikkan"><i class="ri-arrow-up-line"></i></button>
                <button type="button" class="btn btn-sm btn-icon btn-soft-secondary" onclick="moveBlock(this, 1)" title="Turunkan"><i class="ri-arrow-down-line"></i></button>
                <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="removeWacanaBlock(this)" title="Hapus wacana"><i class="ri-delete-bin-line"></i></button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-2">
                <div class="col-md-5"><label class="form-label">Judul Wacana</label><input type="text" class="form-control wacana-judul" value="${escapeHtml(data.judul || '')}" maxlength="150" placeholder="mis. Teks Bacaan 1"></div>
                <div class="col-md-3"><label class="form-label">Sumber</label><input type="text" class="form-control wacana-sumber" value="${escapeHtml(data.sumber || '')}" maxlength="150"></div>
                <div class="col-md-4"><label class="form-label">Untuk Soal Nomor</label><input type="text" class="form-control wacana-rentang" value="${escapeHtml(data.rentang_nomor || '')}" maxlength="100" placeholder="mis. 1–5"></div>
            </div>
            <label class="form-label">Teks Wacana</label>
            <textarea class="form-control wacana-teks" id="wacanaTeks_${key}" rows="6">${escapeHtml(data.teks || '')}</textarea>
            <div class="d-flex justify-content-end mt-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="saveWacanaBlock(this)"><i class="ri-save-line me-1"></i>Simpan Wacana</button>
            </div>
        </div>`;
    document.getElementById('blockList').appendChild(card);
    if (data.posisi_setelah_soal) {
        const questions = document.querySelectorAll('.question-card');
        if (questions[data.posisi_setelah_soal - 1]) questions[data.posisi_setelah_soal - 1].after(card);
    }
    if (window.ClassicEditor) {
        ClassicEditor.create(card.querySelector('.wacana-teks'), ckEditorConfig())
            .then(editor => { wacanaEditors[`wacanaTeks_${key}`] = editor; })
            .catch(error => console.error('CKEditor gagal dimuat', error));
    }
    card.scrollIntoView({behavior: 'smooth', block: 'center'});
}

function ckEditorConfig() {
    return {
        licenseKey: 'GPL',
        language: 'en',
        toolbar: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', 'uploadImage', 'insertTable', 'blockQuote', '|', 'bulletedList', 'numberedList', 'outdent', 'indent'],
        image: {
            toolbar: ['imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|', 'toggleImageCaption', 'imageTextAlternative']
        },
        simpleUpload: {
            uploadUrl: '{{ route('user.soal.wacana.upload', ['userId' => $userId, 'bankId' => $bank->id]) }}',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        }
    };
}

async function persistWacana(card, silent = false) {
    const editor = getEditor(card.querySelector('.wacana-teks').id);
    const teks = (editor ? editor.getData() : card.querySelector('.wacana-teks').value).trim();
    if (!teks) { if (!silent) alert('Teks wacana wajib diisi.'); return false; }

    const id = card.dataset.wacanaId;
    const payload = {
        judul: card.querySelector('.wacana-judul').value,
        sumber: card.querySelector('.wacana-sumber').value,
        rentang_nomor: card.querySelector('.wacana-rentang').value,
        posisi_setelah_soal: blockPosition(card),
        teks: teks
    };
    const url = id
        ? '{{ route('user.soal.wacana.update', ['userId' => $userId, 'bankId' => $bank->id, 'wacanaId' => '__ID__']) }}'.replace('__ID__', id)
        : '{{ route('user.soal.wacana.store', ['userId' => $userId, 'bankId' => $bank->id]) }}';

    const response = await fetch(url, {
        method: id ? 'PUT' : 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    });
    if (!response.ok) { if (!silent) alert('Gagal menyimpan wacana.'); return false; }
    const data = await response.json();
    card.dataset.wacanaId = data.id;
    card.querySelector('.saved-label').classList.remove('d-none');
    const label = data.judul || payload.judul || 'Wacana';
    const index = savedWacanas.findIndex(w => String(w.id) === String(data.id));
    if (index >= 0) savedWacanas[index] = {id: data.id, label}; else savedWacanas.push({id: data.id, label});
    refreshWacanaSelects();
    if (!silent) {
        const btn = card.querySelector('button.btn-primary');
        btn.innerHTML = '<i class="ri-check-line me-1"></i>Tersimpan';
        setTimeout(() => btn.innerHTML = '<i class="ri-save-line me-1"></i>Simpan Wacana', 1500);
    }
    return true;
}

function saveWacanaBlock(button) { persistWacana(button.closest('.wacana-block')); }

async function removeWacanaBlock(button) {
    const card = button.closest('.wacana-block');
    const id = card.dataset.wacanaId;
    if (id && !confirm('Hapus wacana ini? Soal yang memakainya akan menjadi tanpa wacana.')) return;
    if (id) await deleteWacanaRequest(id);
    card.remove();
    renumber();
}

async function deleteWacanaRequest(id) {
    await fetch('{{ route('user.soal.wacana.destroy', ['userId' => $userId, 'bankId' => $bank->id, 'wacanaId' => '__ID__']) }}'.replace('__ID__', id), {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
    });
    savedWacanas = savedWacanas.filter(w => String(w.id) !== String(id));
    refreshWacanaSelects();
    document.querySelectorAll(`[data-saved-wacana="${id}"]`).forEach(el => el.remove());
}

async function deleteSavedWacana(id, button) {
    if (!confirm('Hapus wacana ini? Soal yang memakainya akan menjadi tanpa wacana.')) return;
    await deleteWacanaRequest(id);
    button.closest('[data-saved-wacana]')?.remove();
    if (!document.querySelector('[data-saved-wacana]')) {
        document.getElementById('savedWacanaList').innerHTML = '<span class="text-muted small" id="savedWacanaEmpty">Belum ada wacana.</span>';
    }
}

function toggleOptions(select) {
    const card = select.closest('.question-card');
    card.querySelector('.options-block').classList.toggle('d-none', ['isian', 'uraian'].includes(select.value));
}

function addOption(button) {
    const card = button.closest('.question-card');
    const list = card.querySelector('.options-list');
    const count = list.querySelectorAll('.option-row').length;
    const i = card.dataset.key.replace('q', '');
    const wrapper = document.createElement('div');
    wrapper.innerHTML = optionRows(i, [], 1);
    const row = wrapper.firstElementChild;
    row.querySelector('.input-group-text').textContent = String.fromCharCode(65 + count);
    row.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/options\[\d+\]/, 'options[' + count + ']'); });
    const radio = row.querySelector('input[type="radio"]');
    radio.value = count;
    radio.onchange = function () { markCorrect(this, i, count); };
    list.appendChild(row);
}

function markCorrect(radio, question, optionIndex) {
    const card = radio.closest('.question-card');
    card.querySelectorAll('.correct-input').forEach(input => input.value = '0');
    card.querySelectorAll('.correct-input')[optionIndex].value = '1';
}

document.getElementById('questionBuilder').addEventListener('submit', async function (event) {
    if (this.dataset.submitting === '1') return;

    const unsaved = Array.from(document.querySelectorAll('.wacana-block')).filter(card => {
        const editor = getEditor(card.querySelector('.wacana-teks').id);
        const text = (editor ? editor.getData() : card.querySelector('.wacana-teks').value).trim();
        return !card.dataset.wacanaId && text !== '';
    });

    if (unsaved.length) {
        event.preventDefault();
        for (const card of unsaved) {
            const ok = await persistWacana(card, true);
            if (!ok) return;
        }
        this.dataset.submitting = '1';
        this.submit();
    }
});

const builderQuestions = @json($builderQuestions ?? []);

document.addEventListener('DOMContentLoaded', function () {
    if (builderQuestions.length > 0) {
        builderQuestions.forEach(question => addQuestion(question, false));
    } else {
        addQuestion();
    }
    renumber();
});
</script>
@endsection
