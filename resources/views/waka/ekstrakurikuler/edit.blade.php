@extends('waka.master')
@section('title') Edit Ekstrakurikuler @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ekstrakurikuler @endslot
        @slot('li_2') <a href="{{ route('waka.ekstrakurikuler.index') }}">Daftar</a> @endslot
        @slot('title') Edit {{ $ekskul->nama }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Edit Ekstrakurikuler</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.ekstrakurikuler.update', $ekskul->id) }}" method="POST" autocomplete="off">
                        @csrf @method('PUT')

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="nama" class="form-control" value="{{ old('nama', $ekskul->nama) }}" required>
                                @error('nama')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Pembimbing (GTK)</label>
                            <div class="col-sm-9">
                                <select name="gtk_id" id="gtk_select" class="form-select">
                                    <option value="">— Pilih GTK —</option>
                                    @foreach($gtks as $gtk)
                                        <option value="{{ $gtk->id }}"
                                            data-name="{{ $gtk->name }}"
                                            {{ old('gtk_id', $ekskul->gtk_id)==$gtk->id ? 'selected' : '' }}>
                                            {{ $gtk->name }}{{ $gtk->latest_nupy ? ' | NUPY. '.$gtk->latest_nupy : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('gtk_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nama Pembimbing (manual)</label>
                            <div class="col-sm-9">
                                <input type="text" name="pembimbing" id="pembimbing_name" class="form-control" value="{{ old('pembimbing', $ekskul->pembimbing) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Hari</label>
                            <div class="col-sm-9">
                                <select name="hari" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h)
                                        <option value="{{ $h }}" {{ old('hari', $ekskul->hari)===$h ? 'selected' : '' }}>{{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jam</label>
                            <div class="col-sm-3">
                                <input type="time" name="jam_mulai" class="form-control" value="{{ old('jam_mulai', $ekskul->jam_mulai) }}">
                            </div>
                            <label class="col-sm-3 col-form-label text-end">s/d</label>
                            <div class="col-sm-3">
                                <input type="time" name="jam_selesai" class="form-control" value="{{ old('jam_selesai', $ekskul->jam_selesai) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Lokasi</label>
                            <div class="col-sm-9">
                                <input type="text" name="lokasi" class="form-control" value="{{ old('lokasi', $ekskul->lokasi) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Periode Mulai</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai', $ekskul->tanggal_mulai?->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Periode Selesai</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai', $ekskul->tanggal_selesai?->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Kuota</label>
                            <div class="col-sm-9">
                                <input type="number" name="kuota" class="form-control" value="{{ old('kuota', $ekskul->kuota) }}" min="1">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    <option value="aktif"    {{ old('status', $ekskul->status)=== 'aktif'    ? 'selected' : '' }}>Aktif</option>
                                    <option value="berhenti" {{ old('status', $ekskul->status)=== 'berhenti' ? 'selected' : '' }}>Berhenti</option>
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Deskripsi</label>
                            <div class="col-sm-9">
                                <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $ekskul->deskripsi) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.ekstrakurikuler.show', $ekskul->id) }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('gtk_select');
    const pembimbing = document.getElementById('pembimbing_name');
    if (sel && pembimbing) {
        sel.addEventListener('change', () => {
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.name) pembimbing.value = opt.dataset.name;
        });
    }
});
</script>
@endsection