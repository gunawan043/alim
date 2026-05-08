@extends('layouts.master')
@section('title') Tambah Kaldik / Agenda Kegiatan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') Kaldik & Agenda @endslot
        @slot('title') Tambah @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-calendar-event-line me-2"></i>Form Kaldik / Agenda Kegiatan</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.kaldik.store', ['userId' => $userId]) }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    placeholder="Contoh: Libur Semester Ganjil" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category" class="form-control @error('category') is-invalid @enderror"
                                    id="categorySelect" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categoryOptions as $val => $label)
                                        <option value="{{ $val }}" {{ old('category') == $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if(isset($forcedCategory))
                                    <input type="hidden" name="category" value="{{ $forcedCategory }}">
                                    <small class="text-muted">Anda membuat agenda untuk satuan kerja Anda.</small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran</label>
                                <select name="academic_year_id" class="form-control">
                                    <option value="">-- Pilih Tahun Ajaran --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id') == $ay->id ? 'selected' : '' }}>
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
                                        <option value="{{ $val }}" {{ old('type') == $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date') }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($workUnits && isset($selectedWorkUnitId))
                                <input type="hidden" name="work_unit_id" value="{{ $selectedWorkUnitId }}">
                            @elseif($workUnits)
                            <div class="col-md-12">
                                <label class="form-label">Satuan Kerja</label>
                                <select name="work_unit_id" class="form-control">
                                    <option value="">Pondok (Semua Satuan Kerja)</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}" {{ old('work_unit_id') == $wu->id ? 'selected' : '' }}>
                                            {{ $wu->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk Kaldik pondok. Pilih satuan kerja untuk Agenda Kegiatan.</small>
                            </div>
                            @endif

                            <div class="col-md-12">
                                <label class="form-label">Deskripsi / Keterangan</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="3" placeholder="Opsional">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="isActiveSwitch"
                                        value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActiveSwitch">Aktif</label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
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