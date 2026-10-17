@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Tambah GTK Recruitment</h4>
        <a href="{{ route('user.recruitment.index', request('userId')) }}" class="btn btn-secondary">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('user.recruitment.store', request('userId')) }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Work Unit</label>
                        <select name="work_unit_id" class="form-select" required>
                            <option value="">— pilih work unit —</option>
                            @foreach(\App\Models\WorkUnit::where('is_active', true)->orderBy('name')->get() as $wu)
                                <option value="{{ $wu->id }}" {{ old('work_unit_id') === $wu->id ? 'selected' : '' }}>
                                    {{ $wu->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis GTK</label>
                        <select name="jenis_gtk" id="jenisGtk" class="form-select" required>
                            <option value="">— pilih jenis GTK —</option>
                            @foreach($jenisGtk as $j)
                                <option value="{{ $j->nama }}" {{ old('jenis_gtk') === $j->nama ? 'selected' : '' }}>
                                    {{ $j->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jabatan</label>
                        <select name="jabatan" id="jabatanSelect" class="form-select" required>
                            <option value="">— pilih jabatan —</option>
                        </select>
                        <small class="text-muted">Pilih Jenis GTK terlebih dahulu.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kebutuhan (jumlah)</label>
                        <input type="number" name="kebutuhan" min="1" class="form-control" value="{{ old('kebutuhan') }}" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Kualifikasi</label>
                        <textarea name="kualifikasi" class="form-control" rows="3" required>{{ old('kualifikasi') }}</textarea>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Dibutuhkan</label>
                        <input type="date" name="tanggal_dibutuhkan" class="form-control" value="{{ old('tanggal_dibutuhkan') }}" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const jabatanByJenis = @json($jabatan->groupBy('jenis_gtk_id')->map(fn($items) => $items->pluck('nama', 'id')));
    const jenisMap = @json($jenisGtk->pluck('id', 'nama'));
    const jenisEl = document.getElementById('jenisGtk');
    const jabatanEl = document.getElementById('jabatanSelect');

    function refreshJabatan() {
        const jenisNama = jenisEl.value;
        const jenisId = jenisMap[jenisNama];
        jabatanEl.innerHTML = '<option value="">— pilih jabatan —</option>';
        if (jenisId && jabatanByJenis[jenisId]) {
            for (const [id, nama] of Object.entries(jabatanByJenis[jenisId])) {
                const opt = document.createElement('option');
                opt.value = nama;
                opt.textContent = nama;
                opt.dataset.id = id;
                jabatanEl.appendChild(opt);
            }
        }
    }

    jenisEl.addEventListener('change', refreshJabatan);
    refreshJabatan();
});
</script>
@endsection