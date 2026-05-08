@extends('layouts.master')
@section('title') Ajukan Izin Sakit @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}">Izin Sakit</a> @endslot
        @slot('title') Ajukan Izin Sakit @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.health-permits.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Ajukan Izin Sakit</h5></div>
                    <div class="card-body">
                        @component('components.student-select', [
                            'label' => 'Nama Santri',
                            'inputId' => 'studentFilter',
                            'selectId' => 'studentSelect',
                            'selectName' => 'student_id',
                            'groupedStudents' => $groupedStudents,
                            'errorName' => 'student_id',
                        ])@endcomponent

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Jenis Izin <span class="text-danger">*</span></label>
                                    <select name="permit_type" class="form-control" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="sakit_ringan" {{ old('permit_type')=='sakit_ringan'?'selected':'' }}>Sakit Ringan</option>
                                        <option value="sakit_sedang" {{ old('permit_type')=='sakit_sedang'?'selected':'' }}>Sakit Sedang</option>
                                        <option value="sakit_berat" {{ old('permit_type')=='sakit_berat'?'selected':'' }}>Sakit Berat</option>
                                        <option value="kontrol_dokter" {{ old('permit_type')=='kontrol_dokter'?'selected':'' }}>Kontrol Dokter</option>
                                        <option value="isolasi" {{ old('permit_type')=='isolasi'?'selected':'' }}>Isolasi</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Asrama</label>
                                    <select name="dormitory_id" class="form-control">
                                        <option value="">-- Pilih (opsional) --</option>
                                        @foreach($dormitories as $d)
                                            <option value="{{ $d->id }}" {{ old('dormitory_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Hari Istirahat</label>
                                    <input type="number" name="rest_days" class="form-control" value="{{ old('rest_days', 0) }}" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi / Keluhan</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Uraian keluhan atau diagnosis awal">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Lampiran (scan surat dokter dll)</label>
                            <input type="text" name="attachment_path" class="form-control" value="{{ old('attachment_path') }}" placeholder="URL/path file">
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Ajukan</button>
                            <a href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection