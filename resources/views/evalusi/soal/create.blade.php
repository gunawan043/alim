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
                <h5 class="mb-1">Daftar Soal</h5>
                <p class="text-muted mb-0 small">Semua soal dibuat dalam satu halaman. Tambahkan baris soal, soal cerita, atau gambar sesuai kebutuhan.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-soft-primary btn-sm" onclick="addQuestion()"><i class="ri-add-line me-1"></i>Tambah Soal</button>
                <button type="button" class="btn btn-soft-info btn-sm" onclick="openWacanaModal()"><i class="ri-book-open-line me-1"></i>Tambah Soal Cerita</button>
            </div>
        </div>

        <div id="questionList"></div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 mb-4">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addQuestion()"><i class="ri-add-line me-1"></i>Tambah Soal</button>
                <button type="button" class="btn btn-outline-info btn-sm" onclick="openWacanaModal()"><i class="ri-book-open-line me-1"></i>Tambah Soal Cerita</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openImagePicker()"><i class="ri-image-add-line me-1"></i>Tambah Gambar</button>
            </div>
            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Semua Soal</button>
        </div>
    </form>

    <div class="modal fade" id="wacanaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-book-open-line me-1"></i>Tambah Soal Cerita / Wacana</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">Wacana dapat dipakai bersama oleh beberapa soal. Setelah dibuat, pilih wacana pada baris soal yang membutuhkannya.</div>
                    <div class="mb-3">
                        <label class="form-label">Judul Wacana</label>
                        <input type="text" id="wacanaJudul" class="form-control" maxlength="150" placeholder="mis. Teks Bacaan 1">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sumber</label>
                            <input type="text" id="wacanaSumber" class="form-control" maxlength="150" placeholder="mis. Buku Bahasa Arab hal. 12">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Untuk Soal Nomor</label>
                            <input type="text" id="wacanaRentang" class="form-control" maxlength="100" placeholder="mis. 1–5">
                            <small class="text-muted">Keterangan rentang nomor pada naskah.</small>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Teks Wacana</label>
                        <textarea id="wacanaTeks" class="form-control" rows="6" placeholder="Tulis paragraf/bacaan di sini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="saveWacana()">Simpan Wacana</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
const bankType = @json($bankType);
const isMixed = @json($isMixed);
const typeOptions = @json($tipeSoal);
const tpOptions = @json($tps->map(fn ($tp) => ['id' => $tp->id, 'label' => ($tp->kode_tp ? $tp->kode_tp.' — ' : '').\Str::limit($tp->deskripsi ?? $tp->id, 65)])->values());
const wacanaOptions = @json($wacanas->map(fn ($wacana) => ['id' => $wacana->id, 'label' => ($wacana->judul ?: \Str::limit(strip_tags($wacana->teks), 50)).($wacana->rentang_nomor ? ' · No. '.$wacana->rentang_nomor : '')])->values());
let questionIndex = 0;
let currentQuestionIndex = null;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
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
    card.className = 'card mb-3 question-card';
    card.dataset.index = i;
    card.innerHTML = `
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold"><i class="ri-question-line me-1 text-primary"></i>Soal <span class="question-number"></span></span>
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="this.closest('.question-card').remove(); renumber()" title="Hapus soal"><i class="ri-delete-bin-line"></i></button>
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
                        <option value="mudah">Mudah</option>
                        <option value="sedang" selected>Sedang</option>
                        <option value="sulit">Sulit</option>
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
                    <label class="form-label">Wacana / Soal Cerita</label>
                    <select class="form-select wacana-select" name="questions[${i}][wacana_id]">
                        <option value="">Tidak memakai wacana</option>
                        ${wacanaOptions.map(w => `<option value="${w.id}">${escapeHtml(w.label)}</option>`).join('')}
                    </select>
                </div>
            </div>

            <div class="options-block mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Pilihan Jawaban <small class="text-muted">(klik ikon kunci untuk jawaban benar)</small></label>
                    <button type="button" class="btn btn-sm btn-link p-0" onclick="addOption(this)">+ Pilihan</button>
                </div>
                <div class="options-list">${optionRows(i)}</div>
            </div>

            <div class="mt-2 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-soft-secondary" onclick="toggleDetails(this)"><i class="ri-settings-3-line me-1"></i>Pembahasan & Media</button>
            </div>
            <div class="details-block d-none mt-3 border-top pt-3">
                <div class="mb-2">
                    <label class="form-label">Pembahasan</label>
                    <textarea class="form-control" name="questions[${i}][pembahasan]" rows="2" placeholder="Penjelasan kunci jawaban..."></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Gambar Soal</label>
                        <input type="file" class="form-control" name="questions[${i}][gambar]" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Keterangan Gambar / Nomor</label>
                        <input type="text" class="form-control" name="questions[${i}][gambar_keterangan]" maxlength="255" placeholder="mis. Gambar untuk soal 1–5">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tags</label>
                        <input type="text" class="form-control" name="questions[${i}][tags]" placeholder="pisahkan dengan koma">
                    </div>
                </div>
            </div>
        </div>`;
    document.getElementById('questionList').appendChild(card);
    const typeField = card.querySelector(`[name="questions[${i}][tipe_soal]"]`);
    if (typeField) toggleOptions(typeField);
    if (window.initSearchableSelects) window.initSearchableSelects(card);
    renumber();
    card.scrollIntoView({behavior: 'smooth', block: 'center'});
}

function renumber() {
    document.querySelectorAll('.question-card').forEach((card, index) => {
        card.querySelector('.question-number').textContent = index + 1;
    });
}

function toggleDetails(button) {
    button.closest('.card-body').querySelector('.details-block').classList.toggle('d-none');
}

function toggleOptions(select) {
    const card = select.closest('.question-card');
    const block = card.querySelector('.options-block');
    if (!block) return;
    block.classList.toggle('d-none', ['isian', 'uraian'].includes(select.value));
}

function addOption(button) {
    const card = button.closest('.question-card');
    const list = card.querySelector('.options-list');
    const count = list.querySelectorAll('.option-row').length;
    const i = card.dataset.index;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = optionRows(i, 1);
    const row = wrapper.firstElementChild;
    row.querySelector('.input-group-text').textContent = String.fromCharCode(65 + count);
    row.querySelector('input[type="text"]').placeholder = 'Teks pilihan ' + String.fromCharCode(65 + count);
    // Correct the names for the new option index
    row.querySelectorAll('[name]').forEach(input => {
        input.name = input.name.replace(/options\[\d+\]/, 'options[' + count + ']');
    });
    const radio = row.querySelector('input[type="radio"]');
    radio.value = count;
    radio.onchange = function () { markCorrect(this, i, count); };
    list.appendChild(row);
}

function markCorrect(radio, question, optionIndex) {
    const card = radio.closest('.question-card');
    card.querySelectorAll('.correct-input').forEach(input => input.value = '0');
    const target = card.querySelectorAll('.correct-input')[optionIndex];
    if (target) target.value = '1';
}

function openWacanaModal() {
    currentQuestionIndex = document.querySelectorAll('.question-card').length ? document.querySelector('.question-card:last-of-type')?.dataset.index : null;
    document.getElementById('wacanaJudul').value = '';
    document.getElementById('wacanaSumber').value = '';
    document.getElementById('wacanaRentang').value = '';
    document.getElementById('wacanaTeks').value = '';
    new bootstrap.Modal(document.getElementById('wacanaModal')).show();
}

async function saveWacana() {
    const teks = document.getElementById('wacanaTeks').value.trim();
    if (!teks) { alert('Teks wacana wajib diisi.'); return; }
    const response = await fetch('{{ route('user.soal.wacana.store', ['userId' => $userId, 'bankId' => $bank->id]) }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify({
            judul: document.getElementById('wacanaJudul').value,
            sumber: document.getElementById('wacanaSumber').value,
            rentang_nomor: document.getElementById('wacanaRentang').value,
            teks: teks
        })
    });
    if (!response.ok) { alert('Gagal menyimpan wacana.'); return; }
    const data = await response.json();
    document.querySelectorAll('.wacana-select').forEach(select => {
        const option = new Option(data.judul, data.id);
        select.add(option);
    });
    if (currentQuestionIndex !== null) {
        const card = document.querySelector(`.question-card[data-index="${currentQuestionIndex}"]`);
        const select = card?.querySelector('.wacana-select');
        if (select) select.value = data.id;
    }
    bootstrap.Modal.getInstance(document.getElementById('wacanaModal')).hide();
}

function openImagePicker() {
    if (!document.querySelector('.question-card')) addQuestion();
    const card = document.querySelector('.question-card:last-of-type');
    card?.querySelector('.details-block')?.classList.remove('d-none');
    card?.querySelector('input[type=file]')?.click();
}

document.addEventListener('DOMContentLoaded', function () {
    addQuestion();
});
</script>
@endsection
