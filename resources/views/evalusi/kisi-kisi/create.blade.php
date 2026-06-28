@extends('layouts.master')
@section('title') Buat Kisi-kisi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kisi-kisi @endslot
        @slot('li_2') Buat @endslot
        @slot('title') Form Kisi-kisi @endslot
    @endcomponent

    <form action="{{ route('user.kisi-kisi.store') }}" method="POST">
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
                        <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                            <option value="">-- Pilih --</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}" {{ old('subject_id') === $sub->id ? 'selected' : '' }}>
                                    {{ $sub->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Fase / Tkt</label>
                        <select name="grade_level_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach($gradeLevels as $gl)
                                <option value="{{ $gl->id }}" {{ old('grade_level_id') === $gl->id ? 'selected' : '' }}>
                                    {{ $gl->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach($years as $y)
                                <option value="{{ $y->id }}" {{ old('academic_year_id') === $y->id ? 'selected' : '' }}>
                                    {{ $y->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester" class="form-select" required>
                            <option value="ganjil" {{ old('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ old('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
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
                            <th>TP</th>
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
                                    <select name="items[{{ $idx }}][tp_id]" class="form-select form-select-sm" required>
                                        <option value="">-- Pilih TP --</option>
                                        @foreach(\App\Models\TujuanPembelajaran::all() as $tp)
                                            <option value="{{ $tp->id }}" {{ $oldItem['tp_id'] === $tp->id ? 'selected' : '' }}>
                                                {{ Str::limit($tp->deskripsi ?? $tp->nama ?? $tp->id, 60) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="items[{{ $idx }}][level_kognitif]" class="form-select form-select-sm" required>
                                        @foreach(['C1_mengingat','C2_memahami','C3_menerapkan','C4_menganalisis','C5_mengevaluasi','C6_mencipta'] as $lvl)
                                            <option value="{{ $lvl }}" {{ ($oldItem['level_kognitif'] ?? '') === $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
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
            <a href="{{ route('user.kisi-kisi.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary">Simpan Kisi-kisi</button>
        </div>
    </form>
@endsection

@push('script')
<script>
let itemIdx = {{ count(old('items') ?? []) }};

const tpOptions = `
    <option value="">-- Pilih TP --</option>
    @foreach(\App\Models\TujuanPembelajaran::all() as $tp)
        <option value="{{ $tp->id }}">{{ \Illuminate\Support\Str::limit($tp->deskripsi ?? $tp->nama ?? $tp->id, 60) }}</option>
    @endforeach
`;

const levelOptions = `
    @foreach(['C1_mengingat','C2_memahami','C3_menerapkan','C4_menganalisis','C5_mengevaluasi','C6_mencipta'] as $lvl)
        <option value="{{ $lvl }}">{{ $lvl }}</option>
    @endforeach
`;

document.getElementById('btn-add-item').addEventListener('click', () => {
    const tbody = document.getElementById('items-tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="items[${itemIdx}][tp_id]" class="form-select form-select-sm" required>${tpOptions}</select></td>
        <td><select name="items[${itemIdx}][level_kognitif]" class="form-select form-select-sm" required>${levelOptions}</select></td>
        <td><input type="number" name="items[${itemIdx}][jumlah_soal]" class="form-control form-control-sm" value="1" min="1" required></td>
        <td><input type="number" step="0.01" name="items[${itemIdx}][bobot_per_soal]" class="form-control form-control-sm" value="0" min="0" required></td>
        <td><button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="ri-delete-bin-line"></i></button></td>
    `;
    tbody.appendChild(tr);
    itemIdx++;
});

document.addEventListener('click', e => {
    if (e.target.closest('.btn-remove-item')) {
        e.target.closest('tr').remove();
    }
});
</script>
@endpush