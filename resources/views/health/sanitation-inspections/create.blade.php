@extends('layouts.master')
@section('title') Tambah Inspeksi Sanitasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}">Inspeksi Sanitasi</a> @endslot
        @slot('title') Tambah Inspeksi @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.sanitation-inspections.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Form Inspeksi Sanitasi</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <select name="academic_year_id" class="form-control @error('academic_year_id') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ old('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Inspeksi <span class="text-danger">*</span></label>
                                    <input type="date" name="inspection_date" class="form-control @error('inspection_date') is-invalid @enderror" value="{{ old('inspection_date', now()->format('Y-m-d')) }}" required>
                                    @error('inspection_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Petugas <span class="text-danger">*</span></label>
                                    <select name="inspected_by" class="form-control @error('inspected_by') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach($staff as $s)
                                            <option value="{{ $s->id }}" {{ old('inspected_by') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('inspected_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                                    <select name="location_type" class="form-control @error('location_type') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach(['asrama','kantin','toilet','tempat_sampah','sumber_air','ruang_kelas','halaman','dapur'] as $loc)
                                            <option value="{{ $loc }}" {{ old('location_type')==$loc?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$loc)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('location_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Skor (0-100) <span class="text-danger">*</span></label>
                                    <input type="number" name="score" class="form-control @error('score') is-invalid @enderror" value="{{ old('score') }}" min="0" max="100" placeholder="0 - 100" required>
                                    @error('score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Temuan</label>
                            <textarea name="findings" class="form-control" rows="3" placeholder="Deskripsi temuan inspeksi">{{ old('findings') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rekomendasi</label>
                            <textarea name="recommendations" class="form-control" rows="2" placeholder="Saran perbaikan">{{ old('recommendations') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Deadline Follow-up</label>
                                    <input type="date" name="follow_up_deadline" class="form-control" value="{{ old('follow_up_deadline') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Path Foto</label>
                                    <input type="text" name="photo_path" class="form-control" value="{{ old('photo_path') }}" placeholder="URL/path">
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_passed" class="form-check-input" id="isPassed" value="1" {{ old('is_passed') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="isPassed">Lulus Standar</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection