@extends('layouts.master')
@section('title') Buat Kisi-kisi @endsection

@section('content')
    @php
        $levelOptions = [
            'C1_mengingat' => 'C1 — Mengingat',
            'C2_memahami' => 'C2 — Memahami',
            'C3_menerapkan' => 'C3 — Menerapkan',
            'C4_menganalisis' => 'C4 — Menganalisis',
            'C5_mengevaluasi' => 'C5 — Mengevaluasi',
            'C6_mencita' => 'C6 — Mencipta',
        ];
        $bentukSoalOptions = [
            'pg' => 'Pilihan Ganda',
            'mcc' => 'PG Kompleks',
            'bs' => 'Benar/Salah',
            'jodoh' => 'Menjodohkan',
            'isian' => 'Isian',
            'uraian' => 'Uraian',
        ];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kisi-kisi @endslot
        @slot('li_2') Buat @endslot
        @slot('title') Form Kisi-kisi @endslot
    @endcomponent

    <form action="{{ route('user.kisi-kisi-soal.store', ['userId' => $userId]) }}" method="POST">
        @csrf
        <div class="card mb-3">
            <div class="card-header">Informasi Umum</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror"
                               value="{{ old('judul') }}" placeholder="Contoh: Kisi-kisi SAS Matematika Kelas X Semester Ganjil" required>
                        @error('judul') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Mapel <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject-select" class="form-select @error('subject_id') is-invalid @enderror" required>
                            <option value="">-- Pilih --</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}" {{ old('subject_id', $subjects->count() === 1 ? $subjects->first()->id : null) === $sub->id ? 'selected' : '' }}>
                                    {{ $sub->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Fase / Tkt</label>
                        <select name="grade_level_id" id="grade-select" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach($gradeLevels as $gl)
                                <option value="{{ $gl->id }}" {{ old('grade_level_id', $gradeLevels->count() === 1 ? $gradeLevels->first()->id : null) === $gl->id ? 'selected' : '' }}>
                                    {{ $gl->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach($years as $y)
                                <option value="{{ $y->id }}" {{ old('academic_year_id', $defaultAyId) === $y->id ? 'selected' : '' }}>
                                    {{ $y->nama }}{{ $y->is_active ? ' (aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester" class="form-select" required>
                            <option value="ganjil" {{ old('semester', $defaultSemester) === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ old('semester', $defaultSemester) === 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Jenis Ujian <span class="text-danger">*</span></label>
                        <select name="jenis_ujian" class="form-select" required>
                            @foreach($jenisUjian as $key => $label)
                                <option value="{{ $key }}" {{ old('jenis_ujian') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tingkat Sekolah</label>
                        <select name="tingkat_sekolah" class="form-select">
                            <option value="sd">SD</option>
                            <option value="smp">SMP</option>
                            <option value="sma" selected>SMA</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Peminatan</label>
                        <select name="peminatan" class="form-select">
                            <option value="">-</option>
                            <option value="ipa">IPA</option>
                            <option value="ips">IPS</option>
                            <option value="bahasa">Bahasa</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Jumlah Soal Target</label>
                        <input type="number" name="total_soal_target" class="form-control"
                               value="{{ old('total_soal_target', 0) }}" min="0">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2">{{ old('deskripsi') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>Item Kisi-kisi (TP + Level Kognitif)</span>
                <button type="button" class="btn btn-sm btn-primary" id="btn-add-item">
                    <i class="ri-add-line"></i> Tambah Item
                </button>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="items-table">
                    <thead>
                        <tr>
                            <th>Indikator (TP)</th>
                            <th>Materi</th>
                            <th>Bentuk Soal</th>
                            <th>Nomor Soal</th>
                            <th>Level Kognitif</th>
                            <th>Jumlah Soal</th>
                            <th>Bobot/Soal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        @if(old('items'))
                            @foreach(old('items') as $idx => $oldItem)
                            <tr>
                                <td>
                                    <select name="items[{{ $idx }}][tp_id]" class="form-select form-select-sm" data-tp-select data-searchable required>
                                        <option value="">-- Pilih TP --</option>
                                        @foreach($tps as $tp)
                                            <option value="{{ $tp->id }}"
                                                    data-subject-id="{{ $tp->subject_id }}"
                                                    data-grade-level-id="{{ $tp->grade_level_id }}"
                                                    {{ ($oldItem['tp_id'] ?? null) === $tp->id ? 'selected' : '' }}>
                                                {{ $tp->kode_tp ? $tp->kode_tp.' — ' : '' }}{{ Str::limit($tp->deskripsi ?? $tp->id, 60) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="items[{{ $idx }}][materi]" class="form-control form-control-sm" value="{{ $oldItem['materi'] ?? '' }}" maxlength="255" placeholder="Materi"></td>
                                <td>
                                    <select name="items[{{ $idx }}][bentuk_soal]" class="form-select form-select-sm">
                                        <option value="">--</option>
                                        @foreach($bentukSoalOptions as $bsKey => $bsLabel)
                                            <option value="{{ $bsKey }}" {{ ($oldItem['bentuk_soal'] ?? '') === $bsKey ? 'selected' : '' }}>{{ $bsLabel }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="items[{{ $idx }}][nomor_soal]" class="form-control form-control-sm" value="{{ $oldItem['nomor_soal'] ?? '' }}" maxlength="40" placeholder="mis. 1-5"></td>
                                <td>
                                    <select name="items[{{ $idx }}][level_kognitif]" class="form-select form-select-sm" required>
                                        @foreach($levelOptions as $lvl => $lvlLabel)
                                            <option value="{{ $lvl }}" {{ ($oldItem['level_kognitif'] ?? '') === $lvl ? 'selected' : '' }}>{{ $lvlLabel }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="items[{{ $idx }}][jumlah_soal]" class="form-control form-control-sm" value="{{ $oldItem['jumlah_soal'] ?? 1 }}" min="1" required></td>
                                <td><input type="number" step="0.01" name="items[{{ $idx }}][bobot_per_soal]" class="form-control form-control-sm" value="{{ $oldItem['bobot_per_soal'] ?? 0 }}" min="0" required></td>
                                <td><button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="ri-delete-bin-line"></i></button></td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('user.kisi-kisi-soal.index', ['userId' => $userId]) }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary">Simpan Kisi-kisi</button>
        </div>
    </form>
@endsection

@push('script')
<script>
let itemIdx = {{ count(old('items') ?? []) }};

const tpOptions = `
    <option value="">-- Pilih TP --</option>
    @foreach($tps as $tp)
        <option value="{{ $tp->id }}" data-subject-id="{{ $tp->subject_id }}" data-grade-level-id="{{ $tp->grade_level_id }}">{{ $tp->kode_tp ? $tp->kode_tp.' — ' : '' }}{{ \Illuminate\Support\Str::limit($tp->deskripsi ?? $tp->id, 60) }}</option>
    @endforeach
`;

const bentukSoalOptions = `
    @foreach($bentukSoalOptions as $bsKey => $bsLabel)
        <option value="{{ $bsKey }}">{{ $bsLabel }}</option>
    @endforeach
`;

const levelOptions = `
    @foreach($levelOptions as $lvl => $lvlLabel)
        <option value="{{ $lvl }}">{{ $lvlLabel }}</option>
    @endforeach
`;

document.getElementById('btn-add-item').addEventListener('click', () => {
    const tbody = document.getElementById('items-tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="items[${itemIdx}][tp_id]" class="form-select form-select-sm" data-tp-select data-searchable required>${tpOptions}</select></td>
        <td><input type="text" name="items[${itemIdx}][materi]" class="form-control form-control-sm" maxlength="255" placeholder="Materi"></td>
        <td><select name="items[${itemIdx}][bentuk_soal]" class="form-select form-select-sm"><option value="">--</option>${bentukSoalOptions}</select></td>
        <td><input type="text" name="items[${itemIdx}][nomor_soal]" class="form-control form-control-sm" maxlength="40" placeholder="mis. 1-5"></td>
        <td><select name="items[${itemIdx}][level_kognitif]" class="form-select form-select-sm" required>${levelOptions}</select></td>
        <td><input type="number" name="items[${itemIdx}][jumlah_soal]" class="form-control form-control-sm" value="1" min="1" required></td>
        <td><input type="number" step="0.01" name="items[${itemIdx}][bobot_per_soal]" class="form-control form-control-sm" value="0" min="0" required></td>
        <td><button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="ri-delete-bin-line"></i></button></td>
    `;
    tbody.appendChild(tr);
    if (window.__filterTpOptions) { window.__filterTpOptions(tr); }
    itemIdx++;
});

document.addEventListener('click', e => {
    if (e.target.closest('.btn-remove-item')) {
        e.target.closest('tr').remove();
    }
});
</script>
@include('evalusi.kisi-kisi._tp-filter-script')
@endpush