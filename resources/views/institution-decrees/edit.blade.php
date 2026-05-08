@extends('layouts.master')
@section('title') Edit {{ $decree->decree_number }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">Surat Keputusan</a> @endslot
        @slot('title') Edit {{ $decree->decree_number }} @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.institution-decrees.update', ['userId' => $userId, 'id' => $decree->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Edit Surat Keputusan</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor SK <span class="text-danger">*</span></label>
                                <input type="text" name="decree_number" class="form-control" value="{{ old('decree_number', $decree->decree_number) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis SK <span class="text-danger">*</span></label>
                                <select name="decree_type" class="form-control" required>
                                    <option value="SK Pembagian Tugas" {{ old('decree_type', $decree->decree_type) == 'SK Pembagian Tugas' ? 'selected' : '' }}>SK Pembagian Tugas</option>
                                    <option value="SK Kepsek" {{ old('decree_type', $decree->decree_type) == 'SK Kepsek' ? 'selected' : '' }}>SK Kepsek</option>
                                    <option value="SK Mutasi" {{ old('decree_type', $decree->decree_type) == 'SK Mutasi' ? 'selected' : '' }}>SK Mutasi</option>
                                    <option value="SK promosi" {{ old('decree_type', $decree->decree_type) == 'SK promosi' ? 'selected' : '' }}>SK Promosi</option>
                                    <option value="SK Lainnya" {{ old('decree_type', $decree->decree_type) == 'SK Lainnya' ? 'selected' : '' }}>SK Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Judul SK <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $decree->title) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-control" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id', $decree->academic_year_id) == $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester_text }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Dikeluarkan <span class="text-danger">*</span></label>
                                <input type="date" name="issued_date" class="form-control" value="{{ old('issued_date', $decree->issued_date?->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Efektif <span class="text-danger">*</span></label>
                                <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', $decree->effective_date?->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Berakhir</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $decree->end_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Penandatangan</label>
                                <select name="signed_by" class="form-control">
                                    <option value="">— Pilih —</option>
                                    @foreach($signers as $s)
                                        <option value="{{ $s->id }}" {{ old('signed_by', $decree->signed_by) == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->getRoleNames()->first() ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan Penandatangan</label>
                                <input type="text" name="signed_position" class="form-control" value="{{ old('signed_position', $decree->signed_position) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="2">{{ old('description', $decree->description) }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="draft" {{ old('status', $decree->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="active" {{ old('status', $decree->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="archived" {{ old('status', $decree->status) == 'archived' ? 'selected' : '' }}>Arsip</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection