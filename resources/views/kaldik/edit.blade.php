@extends('layouts.master')
@section('title') Edit Kaldik / Agenda Kegiatan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') Kaldik & Agenda @endslot
        @slot('title') Edit @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-pencil-line me-2"></i>Edit Kaldik / Agenda Kegiatan</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.kaldik.update', ['userId' => $userId, 'kaldikId' => $kaldik->id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $kaldik->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <select class="form-control" disabled>
                                    <option value="">{{ \App\Models\Kaldik::CATEGORY_OPTIONS[$kaldik->category] ?? $kaldik->category }}</option>
                                </select>
                                <input type="hidden" name="category" value="{{ $kaldik->category }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran</label>
                                <select name="academic_year_id" class="form-control">
                                    <option value="">-- Pilih Tahun Ajaran --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id', $kaldik->academic_year_id) == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester_text }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tipe</label>
                                <select name="type" class="form-control">
                                    <option value="">-- Pilih Tipe --</option>
                                    @foreach(\App\Models\Kaldik::TYPE_OPTIONS as $val => $label)
                                        <option value="{{ $val }}" {{ old('type', $kaldik->type) == $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date', $kaldik->start_date?->format('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date', $kaldik->end_date?->format('Y-m-d')) }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($workUnits)
                            <div class="col-md-12">
                                <label class="form-label">Satuan Kerja</label>
                                <select name="work_unit_id" class="form-control">
                                    <option value="">Pondok (Semua Satuan Kerja)</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}" {{ old('work_unit_id', $kaldik->work_unit_id) == $wu->id ? 'selected' : '' }}>
                                            {{ $wu->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div class="col-md-12">
                                <label class="form-label">Deskripsi / Keterangan</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="3">{{ old('description', $kaldik->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="isActiveSwitch"
                                        value="1" {{ old('is_active', $kaldik->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActiveSwitch">Aktif</label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('user.kaldik.index', ['userId' => $userId]) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection