@extends('waka.master')
@section('title') Tambah Ekstrakurikuler @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ekstrakurikuler @endslot
        @slot('title') Tambah Ekstrakurikuler @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Form Ekstrakurikuler</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.ekstrakurikuler.store') }}" method="POST" autocomplete="off">
                        @csrf

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required>
                                @error('nama')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Pembimbing (GTK)</label>
                            <div class="col-sm-9">
                                <select name="gtk_id" id="gtk_select" class="form-select">
                                    <option value="">— Pilih GTK (opsional) —</option>
                                    @foreach($gtks as $gtk)
                                        <option value="{{ $gtk->id }}"
                                            data-name="{{ $gtk->name }}"
                                            {{ old('gtk_id')==$gtk->id ? 'selected' : '' }}>
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
                                <input type="text" name="pembimbing" id="pembimbing_name" class="form-control" value="{{ old('pembimbing') }}" placeholder="Akan terisi otomatis jika GTK dipilih">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Hari</label>
                            <div class="col-sm-9">
                                <select name="hari" class="form-select">
                                    <option value="">— Pilih hari —</option>
                                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h)
                                        <option value="{{ $h }}" {{ old('hari')===$h ? 'selected' : '' }}>{{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jam</label>
                            <div class="col-sm-3">
                                <input type="time" name="jam_mulai" class="form-control" value="{{ old('jam_mulai') }}">
                            </div>
                            <label class="col-sm-3 col-form-label text-end">s/d</label>
                            <div class="col-sm-3">
                                <input type="time" name="jam_selesai" class="form-control" value="{{ old('jam_selesai') }}">
                                @error('jam_selesai')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Lokasi</label>
                            <div class="col-sm-9">
                                <input type="text" name="lokasi" class="form-control" value="{{ old('lokasi') }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Periode Mulai</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Periode Selesai</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai') }}">
                                @error('tanggal_selesai')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Kuota</label>
                            <div class="col-sm-9">
                                <input type="number" name="kuota" class="form-control" value="{{ old('kuota') }}" min="1">
                                @error('kuota')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    <option value="aktif"    {{ old('status','aktif')==='aktif'    ? 'selected' : '' }}>Aktif</option>
                                    <option value="berhenti" {{ old('status')==='berhenti' ? 'selected' : '' }}>Berhenti</option>
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Deskripsi</label>
                            <div class="col-sm-9">
                                <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.ekstrakurikuler.index') }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan</button>
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
    sel.addEventListener('change', () => {
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.dataset.name && !pembimbing.value) {
            pembimbing.value = opt.dataset.name;
        }
    });
});
</script>
@endsection