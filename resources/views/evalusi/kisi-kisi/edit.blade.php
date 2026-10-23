@extends('layouts.master')
@section('title') Edit Kisi-kisi @endsection

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
        @slot('li_2') Edit @endslot
        @slot('title') {{ $kisi->judul }} @endslot
    @endcomponent

    <form action="{{ route('user.kisi-kisi-soal.update', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card mb-3">
            <div class="card-header">Informasi Umum</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Judul</label>
                        <input type="text" name="judul" class="form-control" value="{{ old('judul', $kisi->judul) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mapel <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject-select" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}" {{ old('subject_id', $kisi->subject_id) === $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tingkat / Fase</label>
                        <select name="grade_level_id" id="grade-select" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach($gradeLevels as $gl)
                                <option value="{{ $gl->id }}" {{ old('grade_level_id', $kisi->grade_level_id) === $gl->id ? 'selected' : '' }}>{{ $gl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach($years as $y)
                                <option value="{{ $y->id }}" {{ old('academic_year_id', $kisi->academic_year_id ?? $defaultAyId) === $y->id ? 'selected' : '' }}>{{ $y->nama }}{{ $y->is_active ? ' (aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="ganjil" {{ $kisi->semester==='ganjil' ? 'selected':'' }}>Ganjil</option>
                            <option value="genap" {{ $kisi->semester==='genap' ? 'selected':'' }}>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Jenis Ujian</label>
                        <select name="jenis_ujian" class="form-select" required>
                            @foreach(['sts','sas','ulangan_harian','try_out','latihan'] as $j)
                                <option value="{{ $j }}" {{ $kisi->jenis_ujian===$j ? 'selected':'' }}>{{ ucfirst(str_replace('_', ' ', $j)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2">{{ old('deskripsi', $kisi->deskripsi) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>Item Kisi-kisi</span>
                <button type="button" class="btn btn-sm btn-primary" id="btn-add-item">Tambah</button>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="items-table">
                    <thead>
                        <tr><th>Indikator (TP)</th><th>Materi</th><th>Bentuk Soal</th><th>Nomor Soal</th><th>Level</th><th>Jumlah</th><th>Bobot</th><th>Aksi</th></tr>
                    </thead>
                    <tbody id="items-tbody">
                        @foreach($kisi->items as $idx => $item)
                        <tr>
                            <td>
                                <select name="items[{{ $idx }}][id]" style="display:none">
                                    <option value="{{ $item->id }}">{{ $item->id }}</option>
                                </select>
                                <select name="items[{{ $idx }}][tp_id]" class="form-select form-select-sm" data-tp-select required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($tps as $tp)
                                        <option value="{{ $tp->id }}"
                                                data-subject-id="{{ $tp->subject_id }}"
                                                data-grade-level-id="{{ $tp->grade_level_id }}"
                                                {{ $item->tp_id === $tp->id ? 'selected':'' }}>
                                            {{ $tp->kode_tp ? $tp->kode_tp.' — ' : '' }}{{ Str::limit($tp->deskripsi ?? $tp->id, 60) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                                <td><input type="text" name="items[{{ $idx }}][materi]" class="form-control form-control-sm" value="{{ old('items.'.$idx.'.materi', $item->materi) }}" maxlength="255" placeholder="Materi"></td>
                                <td>
                                    <select name="items[{{ $idx }}][bentuk_soal]" class="form-select form-select-sm">
                                        <option value="">--</option>
                                        @foreach($bentukSoalOptions as $bsKey => $bsLabel)
                                            <option value="{{ $bsKey }}" {{ old('items.'.$idx.'.bentuk_soal', $item->bentuk_soal) === $bsKey ? 'selected' : '' }}>{{ $bsLabel }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="items[{{ $idx }}][nomor_soal]" class="form-control form-control-sm" value="{{ old('items.'.$idx.'.nomor_soal', $item->nomor_soal) }}" maxlength="40" placeholder="mis. 1-5"></td>
                                <td>
                                <select name="items[{{ $idx }}][level_kognitif]" class="form-select form-select-sm" required>
                                    @foreach($levelOptions as $lvl => $lvlLabel)
                                        <option value="{{ $lvl }}" {{ $item->level_kognitif===$lvl ? 'selected':'' }}>{{ $lvlLabel }}</option>
                                    @endforeach
                                </select>
                                </td>
                            <td><input type="number" name="items[{{ $idx }}][jumlah_soal]" class="form-control form-control-sm" value="{{ $item->jumlah_soal }}" min="1" required></td>
                            <td><input type="number" step="0.01" name="items[{{ $idx }}][bobot_per_soal]" class="form-control form-control-sm" value="{{ $item->bobot_per_soal }}" min="0" required></td>
                            <td><button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="ri-delete-bin-line"></i></button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('user.kisi-kisi-soal.index', ['userId' => $userId]) }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
@endsection

@push('script')
<script>
let itemIdx = {{ count($kisi->items) }};
const bentukSoalOptions = `
    @foreach($bentukSoalOptions as $bsKey => $bsLabel)
        <option value="{{ $bsKey }}">{{ $bsLabel }}</option>
    @endforeach
`;

const tpOptionsHtml = `@foreach($tps as $tp)<option value="{{ $tp->id }}" data-subject-id="{{ $tp->subject_id }}" data-grade-level-id="{{ $tp->grade_level_id }}">{{ $tp->kode_tp ? $tp->kode_tp.' — ' : '' }}{{ \Illuminate\Support\Str::limit($tp->deskripsi ?? $tp->id, 60) }}</option>@endforeach`;
document.getElementById('btn-add-item').addEventListener('click', () => {
    const tbody = document.getElementById('items-tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><select name="items[${itemIdx}][tp_id]" class="form-select form-select-sm" data-tp-select><option value="">-- Pilih --</option>${tpOptionsHtml}</select></td>
        <td><input type="text" name="items[${itemIdx}][materi]" class="form-control form-control-sm" maxlength="255" placeholder="Materi"></td>
        <td><select name="items[${itemIdx}][bentuk_soal]" class="form-select form-select-sm"><option value="">--</option>${bentukSoalOptions}</select></td>
        <td><input type="text" name="items[${itemIdx}][nomor_soal]" class="form-control form-control-sm" maxlength="40" placeholder="mis. 1-5"></td>
        <td><select name="items[${itemIdx}][level_kognitif]" class="form-select form-select-sm">@foreach($levelOptions as $lvl => $lvlLabel)<option value="{{ $lvl }}">{{ $lvlLabel }}</option>@endforeach</select></td>
        <td><input type="number" name="items[${itemIdx}][jumlah_soal]" class="form-control form-control-sm" value="1" min="1" required></td>
        <td><input type="number" step="0.01" name="items[${itemIdx}][bobot_per_soal]" class="form-control form-control-sm" value="0" min="0" required></td>
        <td><button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="ri-delete-bin-line"></i></button></td>`;
    tbody.appendChild(tr);
    if (window.__filterTpOptions) { window.__filterTpOptions(tr); }
    itemIdx++;
});
document.addEventListener('click', e => { if(e.target.closest('.btn-remove-item')) e.target.closest('tr').remove(); });
</script>
@include('evalusi.kisi-kisi._tp-filter-script')
@endpush