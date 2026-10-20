@extends('waka.master')
@section('title') Edit Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Supervisi @endslot
        @slot('li_2') <a href="{{ route('waka.supervisi.index') }}">Daftar</a> @endslot
        @slot('title') Edit Supervisi @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Form Edit Supervisi</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.supervisi.update', $supervisi->id) }}" method="POST" autocomplete="off">
                        @csrf @method('PUT')

                        <h6 class="border-bottom pb-2 mb-3">Pasangan Supervisor</h6>
                        @if($selectedPair)
                            <input type="hidden" name="observer_id" value="{{ $selectedPair['observer_id'] }}">
                            <input type="hidden" name="gtk_id" value="{{ $selectedPair['gtk_id'] }}">
                            <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="small text-muted">Supervisor</div>
                                    <div class="fw-semibold">{{ $selectedPair['supervisor_name'] }}</div>
                                </div>
                                <i class="ri-arrow-right-line text-muted"></i>
                                <div>
                                    <div class="small text-muted">Guru yang Disupervisi</div>
                                    <div class="fw-semibold">{{ $selectedPair['supervisee_name'] }}</div>
                                </div>
                                <a href="{{ route('waka.supervisi-pengaturan.index', ['academic_year_id' => $supervisi->academic_year_id]) }}" class="btn btn-sm btn-outline-secondary">Ubah Pengaturan</a>
                            </div>
                        @else
                            <div class="alert alert-warning small">
                                <i class="ri-alert-line me-1"></i>Pasangan supervisor untuk jadwal ini belum tersedia. Atur pasangan terlebih dahulu di
                                <a href="{{ route('waka.supervisi-pengaturan.index', ['academic_year_id' => $supervisi->academic_year_id]) }}">Pengaturan Supervisor</a>.
                            </div>
                        @endif

                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Jadwal Supervisi</h6>
                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Detail Supervisi</h6>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ (string) old('academic_year_id', $supervisi->academic_year_id) === (string) $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Semester <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="semester" class="form-select" required>
                                    <option value="1" {{ old('semester', $supervisi->semester)=='1' ? 'selected' : '' }}>1 (Ganjil)</option>
                                    <option value="2" {{ old('semester', $supervisi->semester)=='2' ? 'selected' : '' }}>2 (Genap)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Mata Pelajaran</label>
                            <div class="col-sm-9">
                                <input type="text" name="mata_pelajaran" class="form-control" value="{{ old('mata_pelajaran', $supervisi->mata_pelajaran) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_supervisi" class="form-control" value="{{ old('tanggal_supervisi', $supervisi->tanggal_supervisi?->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jam Mulai</label>
                            <div class="col-sm-9">
                                <input type="time" name="jam_mulai" class="form-control" value="{{ old('jam_mulai', $supervisi->jam_mulai) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jam Selesai</label>
                            <div class="col-sm-9">
                                <input type="time" name="jam_selesai" class="form-control" value="{{ old('jam_selesai', $supervisi->jam_selesai) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jenis <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="jenis_supervisi" class="form-select" required>
                                    @foreach(['perangkat_pembelajaran'=>'Perangkat Pembelajaran','proses_pembelajaran'=>'Proses Pembelajaran','penilaian'=>'Penilaian','lainnya'=>'Lainnya'] as $v=>$l)
                                        <option value="{{ $v }}" {{ old('jenis_supervisi', $supervisi->jenis_supervisi)==$v?'selected':'' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                                @error('jenis_supervisi')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tujuan</label>
                            <div class="col-sm-9">
                                <textarea name="tujuan" class="form-control" rows="2">{{ old('tujuan', $supervisi->tujuan) }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Catatan Temuan</label>
                            <div class="col-sm-9">
                                <textarea name="catatan_temuan" class="form-control" rows="3">{{ old('catatan_temuan', $supervisi->catatan_temuan) }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Rekomendasi</label>
                            <div class="col-sm-9">
                                <textarea name="rekomendasi" class="form-control" rows="3">{{ old('rekomendasi', $supervisi->rekomendasi) }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tindak Lanjut</label>
                            <div class="col-sm-9">
                                <textarea name="tindak_lanjut" class="form-control" rows="3">{{ old('tindak_lanjut', $supervisi->tindak_lanjut) }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    @foreach(['terjadwal','berlangsung','selesai','dibatalkan'] as $s)
                                        <option value="{{ $s }}" {{ old('status', $supervisi->status)===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.supervisi.index') }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
