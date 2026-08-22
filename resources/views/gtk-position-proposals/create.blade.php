@extends('layouts.master')
@section('title') Ajukan Kenaikan Jabatan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('li_2') <a href="{{ route('user.gtk-position-proposals.index') }}">Pengajuan Jabatan</a> @endslot
        @slot('title') Ajukan Jabatan Baru @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Form Pengajuan Kenaikan Jabatan</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.gtk-position-proposals.store') }}" method="POST">
                        @csrf

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Jenis Pengajuan <span class="text-danger">*</span></label>
                                <select name="proposal_type" class="form-control" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    @foreach($proposalTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('proposal_type') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('proposal_type') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">GTK yang Diajukan <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">-- Pilih GTK --</option>
                                    @foreach($proposers as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} - {{ $user->gtkEmployment?->jabatan ?? '-' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Jabatan Tujuan</label>
                                <select name="proposed_jabatan_text" class="form-control">
                                    <option value="">-- Pilih / Ketik Manual --</option>
                                    @foreach($positions as $pos)
                                        <option value="{{ $pos->nama }}" {{ old('proposed_jabatan_text') == $pos->nama ? 'selected' : '' }}>
                                            {{ $pos->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Ketik manual jika jabatan tidak ada di daftar</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Sekolah Tujuan</label>
                                <select name="proposed_school_id" class="form-control">
                                    <option value="">-- Pilih Sekolah --</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ old('proposed_school_id') == $school->id ? 'selected' : '' }}>
                                            {{ $school->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Unit Kerja</label>
                                <input type="text" name="proposed_work_unit" class="form-control"
                                       value="{{ old('proposed_work_unit') }}" maxlength="100">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Alasan Pengajuan</label>
                                <textarea name="reason" class="form-control" rows="3"
                                          maxlength="1000">{{ old('reason') }}</textarea>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Nomor SK</label>
                                <input type="text" name="nomor_sk" class="form-control"
                                       value="{{ old('nomor_sk') }}" maxlength="100">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">TMT (Terhitung Mulai Tanggal)</label>
                                <input type="date" name="tmt" class="form-control"
                                       value="{{ old('tmt') }}">
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="ri-send-plane-line me-1"></i> Kirim Pengajuan
                            </button>
                            <a href="{{ route('user.gtk-position-proposals.index') }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
