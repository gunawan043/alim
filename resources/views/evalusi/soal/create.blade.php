@extends('layouts.master')
@section('title') Input Soal — {{ $bank->nama }} @endsection

@section('content')
    @php
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
        @slot('title') Input Soal @endslot
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

    <form method="POST" action="{{ route('user.soal.store', ['userId' => $userId, 'bankId' => $bank->id]) }}" enctype="multipart/form-data" id="questionBuilder">
        @csrf

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <h5 class="mb-1">Susunan Soal</h5>
                <p class="text-muted mb-0 small">Susun soal dan wacana sesuai urutan cetak. Gunakan tombol naik/turun untuk memindahkan blok.</p>
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
<script src="{{ URL::asset('build/libs/ckeditor4/ckeditor.js') }}"></script>
<script>
const bankType = @json($bankType);
const isMixed = @json($isMixed);
const typeOptions = @json($tipeSoal);
const tpOptions = @json($tps->map(fn ($tp) => ['id' => $tp->id, 'label' => ($tp->kode_tp ? $tp->kode_tp.' — ' : '').\Str::limit($tp->deskripsi ?? $tp->id, 65)])->values());
let savedWacanas = @json($wacanas->map(fn ($wacana) => ['id' => $wacana->id, 'label' => ($wacana->judul ?: \Str::limit(strip_tags($wacana->teks), 45)).($wacana->rentang_nomor ? ' · No. '.$wacana->rentang_nomor : '')])->values());
let questionIndex = 0;
let wacanaIndex = 0;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

function getEditor(id) {
    return window.CKEDITOR && CKEDITOR.instances[id] ? CKEDITOR.instances[id] : null;
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

function typeSelect(i) {
    if (! isMixed) return `<input type="hidden" name="questions[${i}][tipe_soal]" value="${bankType}">`;
    return `<select class="form-select form-select-sm" name="questions[${i}][tipe_soal]" onchange="toggleOptions(this)">
        ${Object.entries(typeOptions).map(([key, label]) => `<option value="${key}" ${key === bankType ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
    </select>`;
}

function optionRows(i, count = 4) {
    return Array.from({length: count}, (_, j) => `
        <div class="input-group input-group-sm mb-1 option-row">
            <span class="input-group-text fw-bold">${String.fromCharCode(65 + j)}</span>
            <input type="text" class="form-control" name="questions[${i}][options][${j}][teks_opsi]" placeholder="Teks pilihan ${String.fromCharCode(65 + j)}">
            <input type="hidden" class="correct-input" name="questions[${i}][options][${j}][is_correct]" value="0">
            <label class="input-group-text" title="Tandai jawaban benar">
                <input type="radio" name="correct_${i}" value="${j}" onchange="markCorrect(this, ${i}, ${j})">
                <i class="ri-key-2-line ms-1 text-warning"></i>
            </label>
        </div>`).join('');
}

function addQuestion() {
    const i = questionIndex++;
    const card = document.createElement('div');
    card.className = 'card mb-3 question-card block-item';
    card.dataset.key = `q${i}`;
    card.innerHTML = `
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold"><i class="ri-question-line me-1 text-primary"></i>Soal <span class="question-number"></span></span>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-icon btn-soft-secondary" onclick="moveBlock(this, -1)" title="Naikkan"><i class="ri-arrow-up-line"></i></button>
                <button type="button" class="btn btn-sm btn-icon btn-soft-secondary" onclick="moveBlock(this, 1)" title="Turunkan"><i class="ri-arrow-down-line"></i></button>
                <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="this.closest('.block-item').remove(); renumber()" title="Hapus soal"><i class="ri-delete-bin-line"></i></button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-lg-9">
                    <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="questions[${i}][pertanyaan]" rows="3" required placeholder="Tulis pertanyaan..."></textarea>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Level</label>
                    <select class="form-select" name="questions[${i}][tingkat_kesulitan_estimasi]">
                        <option value="mudah">Mudah</option><option value="sedang" selected>Sedang</option><option value="sulit">Sulit</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tujuan Pembelajaran</label>
                    <select class="form-select" name="questions[${i}][tp_id]" data-searchable>
                        <option value="">Tidak terkait</option>
                        ${tpOptions.map(tp => `<option value="${tp.id}">${escapeHtml(tp.label)}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipe Soal</label>
                    ${typeSelect(i)}
                </div>
                <div class="col-12">
                    <label class="form-label">Wacana</label>
                    <select class="form-select wacana-select" name="questions[${i}][wacana_id]">${wacanaOptionsHtml('')}</select>
                </div>
            </div>
            <div class="options-block mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Pilihan Jawaban <small class="text-muted">(klik kunci untuk jawaban benar)</small></label>
                    <button type="button" class="btn btn-sm btn-link p-0" onclick="addOption(this)">+ Pilihan</button>
                </div>
                <div class="options-list">${optionRows(i)}</div>
            </div>
            <div class="mt-2"><button type="button" class="btn btn-sm btn-soft-secondary" onclick="this.closest('.card-body').querySelector('.details-block').classList.toggle('d-none')"><i class="ri-settings-3-line me-1"></i>Pembahasan & Media</button></div>
            <div class="details-block d-none mt-3 border-top pt-3">
                <div class="mb-2"><label class="form-label">Pembahasan</label><textarea class="form-control" name="questions[${i}][pembahasan]" rows="2"></textarea></div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">Gambar Soal</label><input type="file" class="form-control" name="questions[${i}][gambar]" accept="image/*"></div>
                    <div class="col-md-6"><label class="form-label">Keterangan Gambar / Nomor</label><input type="text" class="form-control" name="questions[${i}][gambar_keterangan]" maxlength="255" placeholder="mis. Gambar untuk soal 1–5"></div>
                    <div class="col-12"><label class="form-label">Tags</label><input type="text" class="form-control" name="questions[${i}][tags]" placeholder="pisahkan dengan koma"></div>
                </div>
            </div>
        </div>`;
    document.getElementById('blockList').appendChild(card);
    renumber();
    if (window.initSearchableSelects) window.initSearchableSelects(card);
    card.scrollIntoView({behavior: 'smooth', block: 'center'});
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
    if (window.CKEDITOR) {
        CKEDITOR.replace(`wacanaTeks_${key}`, ckEditorConfig());
    }
    card.scrollIntoView({behavior: 'smooth', block: 'center'});
}

function ckEditorConfig() {
    return {
        language: 'id',
        height: 220,
        toolbar: [
            ['Bold', 'Italic', 'Underline', 'Strike'],
            ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Link', 'Unlink'], ['Image', 'Table', 'HorizontalRule'], ['RemoveFormat', 'Source']
        ],
        filebrowserImageUploadUrl: '{{ route('user.soal.wacana.upload', ['userId' => $userId, 'bankId' => $bank->id]) }}?_token={{ csrf_token() }}'
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
    wrapper.innerHTML = optionRows(i, 1);
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

document.addEventListener('DOMContentLoaded', function () {
    addQuestion();
    renumber();
});
</script>
@endsection
