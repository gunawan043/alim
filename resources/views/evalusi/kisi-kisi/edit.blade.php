@extends('layouts.master')
@section('title') Edit Kisi-kisi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kisi-kisi @endslot
        @slot('li_2') Edit @endslot
        @slot('title') {{ $kisi->judul }} @endslot
    @endcomponent

    <form action="{{ route('user.kisi-kisi.update', $kisi->id) }}" method="POST">
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
                        <tr><th>TP</th><th>Level</th><th>Jumlah</th><th>Bobot</th><th>Aksi</th></tr>
                    </thead>
                    <tbody id="items-tbody">
                        @foreach($kisi->items as $idx => $item)
                        <tr>
                            <td>
                                <select name="items[{{ $idx }}][id]" style="display:none">
                                    <option value="{{ $item->id }}">{{ $item->id }}</option>
                                </select>
                                <select name="items[{{ $idx }}][tp_id]" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Models\TujuanPembelajaran::all() as $tp)
                                        <option value="{{ $tp->id }}" {{ $item->tp_id === $tp->id ? 'selected':'' }}>
                                            {{ Str::limit($tp->deskripsi ?? $tp->nama ?? $tp->id, 60) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="items[{{ $idx }}][level_kognitif]" class="form-select form-select-sm" required>
                                    @foreach(['C1_mengingat','C2_memahami','C3_menerapkan','C4_menganalisis','C5_mengevaluasi','C6_mencipta'] as $lvl)
                                        <option value="{{ $lvl }}" {{ $item->level_kognitif===$lvl ? 'selected':'' }}>{{ $lvl }}</option>
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
            <a href="{{ route('user.kisi-kisi.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
@endsection

@push('script')
<script>
let itemIdx = {{ count($kisi->items) }};
const tpOptionsHtml = `@foreach(\App\Models\TujuanPembelajaran::all() as $tp)<option value="{{ $tp->id }}">{{ \Illuminate\Support\Str::limit($tp->deskripsi ?? $tp->nama ?? $tp->id, 60) }}</option>@endforeach`;
document.getElementById('btn-add-item').addEventListener('click', () => {
    const tbody = document.getElementById('items-tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><select name="items[${itemIdx}][tp_id]" class="form-select form-select-sm">${tpOptionsHtml}</select></td>
        <td><select name="items[${itemIdx}][level_kognitif]" class="form-select form-select-sm">@foreach(['C1_mengingat','C2_memahami','C3_menerapkan','C4_menganalisis','C5_mengevaluasi','C6_mencipta'] as $lvl)<option value="{{ $lvl }}">{{ $lvl }}</option>@endforeach</select></td>
        <td><input type="number" name="items[${itemIdx}][jumlah_soal]" class="form-control form-control-sm" value="1" min="1" required></td>
        <td><input type="number" step="0.01" name="items[${itemIdx}][bobot_per_soal]" class="form-control form-control-sm" value="0" min="0" required></td>
        <td><button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="ri-delete-bin-line"></i></button></td>`;
    tbody.appendChild(tr);
    itemIdx++;
});
document.addEventListener('click', e => { if(e.target.closest('.btn-remove-item')) e.target.closest('tr').remove(); });
</script>
@endpush