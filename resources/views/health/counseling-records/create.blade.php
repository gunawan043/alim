@extends('layouts.master')
@section('title') Tambah Catatan Konseling @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}">Konseling</a> @endslot
        @slot('title') Tambah Catatan Konseling @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.counseling-records.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Form Catatan Konseling</h5></div>
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
                                    <label class="form-label">Tanggal Sesi <span class="text-danger">*</span></label>
                                    <input type="date" name="session_date" class="form-control @error('session_date') is-invalid @enderror" value="{{ old('session_date', now()->format('Y-m-d')) }}" required>
                                    @error('session_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tipe Sesi <span class="text-danger">*</span></label>
                                    <select name="session_type" class="form-control @error('session_type') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach(['individu','kelompok','krisis'] as $t)
                                            <option value="{{ $t }}" {{ old('session_type')==$t?'selected':'' }}>{{ ucfirst($t) }}</option>
                                        @endforeach
                                    </select>
                                    @error('session_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Topik</label>
                                    <input type="text" name="topic" class="form-control" value="{{ old('topic') }}" placeholder="Topik konseling">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi / Kronologi</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Uraian singkat">{!! old('description') !!}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Analisis</label>
                            <textarea name="analysis" class="form-control" rows="3">{!! old('analysis') !!}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rencana Tindak Lanjut</label>
                            <textarea name="follow_up_plan" class="form-control" rows="2">{!! old('follow_up_plan') !!}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Sesi Berikutnya</label>
                                    <input type="date" name="next_session_date" class="form-control" value="{{ old('next_session_date') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dirujuk Ke</label>
                                    <input type="text" name="referred_to" class="form-control" value="{{ old('referred_to') }}" placeholder="Nama faskes / konselor">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Wali Diberitahu (tanggal)</label>
                                    <input type="datetime-local" name="parent_informed_at" class="form-control" value="{{ old('parent_informed_at') }}">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-4 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="referral_needed" class="form-check-input" id="referralNeeded" value="1" {{ old('referral_needed') ? 'checked' : '' }}>
                                <label class="form-check-label" for="referralNeeded">Perlu dirujuk</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="parent_informed" class="form-check-input" id="parentInformed" value="1" {{ old('parent_informed') ? 'checked' : '' }}>
                                <label class="form-check-label" for="parentInformed">Wali sudah diberitahu</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="is_confidential" class="form-check-input" id="isConfidential" value="1" {{ old('is_confidential', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isConfidential">Rahasia</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection