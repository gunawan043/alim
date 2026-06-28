@extends('layouts.master')
@section('title') Buat Soal - {{ $bank->nama }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2')
            <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}">
                {{ $bank->nama }}
            </a>
        @endslot
        @slot('title') Buat Soal Baru @endslot
    @endcomponent

    <form method="POST" action="{{ route('user.soal.store', ['userId' => $userId, 'bankId' => $bank->id]) }}">
        @csrf

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
                                <select name="tipe_soal" class="form-control @error('tipe_soal') is-invalid @enderror" id="tipe_soal" required>
                                    <option value="">Pilih Tipe</option>
                                    @foreach($tipeSoal as $key => $label)
                                        <option value="{{ $key }}" {{ old('tipe_soal') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('tipe_soal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tujuan Pembelajaran</label>
                                <select name="tp_id" class="form-control">
                                    <option value="">— Tidak terkait —</option>
                                    @foreach($tps as $tp)
                                        <option value="{{ $tp->id }}" {{ old('tp_id') === $tp->id ? 'selected' : '' }}>
                                            {{ $tp->kode_tp }} · {{ Str::limit($tp->deskripsi, 50) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Pertanyaan / Stimulus <span class="text-danger">*</span></label>
                                <textarea name="pertanyaan" id="pertanyaan" class="form-control @error('pertanyaan') is-invalid @enderror"
                                          rows="6" required>{{ old('pertanyaan') }}</textarea>
                                <small class="text-muted">Mendukung HTML dasar untuk equation/gambar: <code>&lt;img src="..."&gt;</code></small>
                                @error('pertanyaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Path Gambar (opsional)</label>
                                <input type="text" name="gambar_path" class="form-control" value="{{ old('gambar_path') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Path Audio (opsional)</label>
                                <input type="text" name="audio_path" class="form-control" value="{{ old('audio_path') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tags (opsional, pisahkan koma)</label>
                                <input type="text" name="tags" class="form-control"
                                       value="{{ old('tags') }}" placeholder="aljabar, persamaan-linear, fase-e">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Options Section: visible only for pg/bs/jodoh --}}
                <div class="card" id="options-card" style="display:none">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Opsi Jawaban</h5>
                        <button type="button" class="btn btn-success btn-sm" id="btn-add-option">
                            <i class="ri-add-line"></i> Tambah Opsi
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="options-container"></div>
                        <small class="text-muted">
                            Centang checkbox <strong>"Benar"</strong> pada opsi yang merupakan kunci jawaban.
                            Untuk <em>Menjodohkan</em>, semua opsi boleh benar.
                        </small>
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
                            <label class="form-label">Bobot Default <span class="text-danger">*</span></label>
                            <input type="number" name="bobot_default" min="0" max="100" step="0.5"
                                   class="form-control @error('bobot_default') is-invalid @enderror"
                                   value="{{ old('bobot_default', 1) }}" required>
                            @error('bobot_default') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tingkat Kesulitan <span class="text-danger">*</span></label>
                            <select name="tingkat_kesulitan_estimasi" class="form-control" required>
                                <option value="mudah" {{ old('tingkat_kesulitan_estimasi', 'sedang') === 'mudah' ? 'selected' : '' }}>Mudah</option>
                                <option value="sedang" {{ old('tingkat_kesulitan_estimasi', 'sedang') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                                <option value="sulit" {{ old('tingkat_kesulitan_estimasi', 'sedang') === 'sulit' ? 'selected' : '' }}>Sulit</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Waktu Estimasi (menit) <span class="text-danger">*</span></label>
                            <input type="number" name="waktu_estimasi_menit" min="1" max="120"
                                   class="form-control" value="{{ old('waktu_estimasi_menit', 2) }}" required>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="ri-save-line me-1"></i> Simpan (Status: Draft)
                        </button>
                        <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}"
                           class="btn btn-light w-100">
                            <i class="ri-close-line me-1"></i> Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipeSelect = document.getElementById('tipe_soal');
    const optionsCard = document.getElementById('options-card');
    const container = document.getElementById('options-container');
    const btnAdd = document.getElementById('btn-add-option');

    let optIndex = 0;

    function makeOptionRow(i, label = '', text = '', isCorrect = false) {
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

    // Trigger on initial load if there's a value (after validation error)
    if (tipeSelect.value && ['pg', 'bs', 'jodoh'].includes(tipeSelect.value)) {
        optionsCard.style.display = 'block';
        rebuildOptions();
        @if(old('options'))
            // Re-populate from old() if validation failed
            const oldOpts = @json(old('options'));
            if (oldOpts && oldOpts.length) {
                container.innerHTML = '';
                oldOpts.forEach((opt, i) => {
                    container.insertAdjacentHTML('beforeend',
                        makeOptionRow(i, opt.label || '', opt.teks_opsi || '',
                            opt.is_correct === '1' || opt.is_correct === true || opt.is_correct === 1));
                });
                optIndex = oldOpts.length;
            }
        @endif
    }
});
</script>
@endsection