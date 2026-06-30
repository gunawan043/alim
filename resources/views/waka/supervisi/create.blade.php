@extends('waka.master')
@section('title') Tambah Supervisi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Supervisi @endslot
        @slot('title') Tambah Data Supervisi @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Form Tambah Supervisi</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.supervisi.store') }}" method="POST" autocomplete="off">
                        @csrf
                        @php
                            $today = old('_today') ?? date('Y-m-d');
                        @endphp

                        {{-- GTK yang Disupervisi --}}
                        <h6 class="border-bottom pb-2 mb-3">GTK yang Disupervisi</h6>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Pilih GTK <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="gtk_id" id="gtk_select" class="form-select" required>
                                    <option value="">— Pilih GTK —</option>
                                    @foreach($gtks as $gtk)
                                        <option value="{{ $gtk->id }}"
                                            data-name="{{ $gtk->name }}"
                                            data-nupy="{{ $gtk->latest_nupy }}"
                                            {{ old('gtk_id')==$gtk->id ? 'selected' : '' }}>
                                            {{ $gtk->name }}{{ $gtk->latest_nupy ? ' | NUPY. '.$gtk->latest_nupy : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('gtk_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nama GTK <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="gtk_name" id="gtk_name" class="form-control" value="{{ old('gtk_name') }}" required>
                                @error('gtk_name')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Detail Supervisi</h6>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Observer <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="observer_id" id="observer_select" class="form-select" required>
                                    <option value="">— Pilih Observer —</option>
                                    @foreach($gtks as $gtk)
                                        <option value="{{ $gtk->id }}"
                                            data-name="{{ $gtk->name }}"
                                            data-nupy="{{ $gtk->latest_nupy }}"
                                            {{ old('observer_id')==$gtk->id ? 'selected' : '' }}>
                                            {{ $gtk->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('observer_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nama Observer</label>
                            <div class="col-sm-9">
                                <input type="text" name="observer_name" id="observer_name" class="form-control" value="{{ old('observer_name') }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="academic_year_id" class="form-select" required>
                                    <option value="">— Pilih —</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id',$ay->id)=='selected' ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Semester <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="semester" class="form-select" required>
                                    <option value="1" {{ old('semester')=='1' ? 'selected' : '' }}>1 (Ganjil)</option>
                                    <option value="2" {{ old('semester')=='2' ? 'selected' : '' }}>2 (Genap)</option>
                                </select>
                                @error('semester')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Mata Pelajaran</label>
                            <div class="col-sm-9">
                                <input type="text" name="mata_pelajaran" class="form-control" value="{{ old('mata_pelajaran') }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Supervisi <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_supervisi" class="form-control" value="{{ old('tanggal_supervisi') }}" required>
                                @error('tanggal_supervisi')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jam Mulai</label>
                            <div class="col-sm-9">
                                <input type="time" name="jam_mulai" class="form-control" value="{{ old('jam_mulai') }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jam Selesai</label>
                            <div class="col-sm-9">
                                <input type="time" name="jam_selesai" class="form-control" value="{{ old('jam_selesai') }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jenis <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="jenis_supervisi" class="form-select" required>
                                    <option value="">— Pilih Jenis —</option>
                                    @foreach(['perangkat_pembelajaran'=>'Perangkat Pembelajaran','proses_pembelajaran'=>'Proses Pembelajaran','penilaian'=>'Penilaian','lainnya'=>'Lainnya'] as $v => $l)
                                        <option value="{{ $v }}" {{ old('jenis_supervisi')==$v?'selected':'' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                                @error('jenis_supervisi')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tujuan</label>
                            <div class="col-sm-9">
                                <textarea name="tujuan" class="form-control" rows="2">{{ old('tujuan') }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Catatan Temuan</label>
                            <div class="col-sm-9">
                                <textarea name="catatan_temuan" class="form-control" rows="3">{{ old('catatan_temuan') }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Rekomendasi</label>
                            <div class="col-sm-9">
                                <textarea name="rekomendasi" class="form-control" rows="3">{{ old('rekomendasi') }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tindak Lanjut</label>
                            <div class="col-sm-9">
                                <textarea name="tindak_lanjut" class="form-control" rows="3">{{ old('tindak_lanjut') }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    <option value="terjadwal" {{ old('status')==='terjadwal'?'selected':'' }}>Terjadwal</option>
                                    <option value="berlangsung" {{ old('status')==='berlangsung'?'selected':'' }}>Berlangsung</option>
                                    <option value="selesai" {{ old('status')==='selesai'?'selected':'' }}>Selesai</option>
                                    <option value="dibatalkan" {{ old('status')==='dibatalkan'?'selected':'' }}>Dibatalkan</option>
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.supervisi.index') }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
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
    const populate = (selId, nameId) => {
        const sel = document.getElementById(selId);
        const name = document.getElementById(nameId);
        sel.addEventListener('change', () => {
            const opt = sel.options[sel.selectedIndex];
            name.value = opt.dataset.name || '';
        });
    };
    populate('gtk_select', 'gtk_name');
    populate('observer_select', 'observer_name');
});
</script>
@endsection