@extends('layouts.master')
@section('title') Edit Lamaran @endsection
@section('css')
<style>
    .form-section { border: 1px solid var(--bs-border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; background: #fafafa; }
    .form-section-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: .6px; color: #6c757d; font-weight: 700; margin-bottom: 16px; }
</style>
@endsection

@section('content')
@php $userId = request('userId', ''); @endphp
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('li_2_link') {{ route('user.ats.applications.index', ['userId' => $userId]) }} @endslot
    @slot('li_2') Lamaran @endslot
    @slot('li_3_link') {{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $application]) }} @endslot
    @slot('li_3') {{ $application->no_lamaran ?? $application->id }} @endslot
    @slot('title') Edit @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-edit-line me-2"></i>Edit Lamaran #{{ $application->no_lamaran ?? $application->id }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.ats.applications.update', ['userId' => $userId, 'application' => $application]) }}" method="POST" id="applicationForm">
                    @csrf
                    @method('PUT')

                    {{-- Pilih Profil --}}
                    <div class="form-section">
                        <div class="form-section-title">1. Profil Pelamar</div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Profile Pelamar</label>
                            <select class="form-select" name="recruitment_profile_id" id="profileSelect" disabled>
                                <option value="{{ $application->recruitment_profile_id }}" selected>
                                    {{ $application->recruitmentProfile->user->name ?? 'N/A' }} (NIK: {{ $application->recruitmentProfile->nik ?? '-' }})
                                </option>
                            </select>
                            <small class="text-muted">Profil pelamar tidak dapat diubah setelah lamaran dibuat.</small>
                        </div>
                    </div>

                    {{-- Detail Lamaran --}}
                    <div class="form-section">
                        <div class="form-section-title">2. Detail Lamaran</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Lowongan <span class="text-danger">*</span></label>
                                <select class="form-select" name="recruitment_job_id" required>
                                    @foreach($jobs as $job)
                                        <option value="{{ $job->id }}" {{ $application->recruitment_job_id == $job->id ? 'selected' : '' }}>
                                            {{ $job->judul }} @if($job->status !== 'aktif')[{{ $job->status }}]@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. Lamaran</label>
                                <input type="text" class="form-control" name="no_lamaran" value="{{ $application->no_lamaran ?? '' }}" placeholder="Otomatis jika kosong">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <input type="text" class="form-control" name="status" value="{{ $application->status ?? 'pending' }}" placeholder="Pending / Baru / ...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tanggal Melamar</label>
                                <input type="date" class="form-control" name="tanggal_melamar" value="{{ $application->tanggal_melamar ? $application->tanggal_melamar->format('Y-m-d') : today()->toDateString() }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Skor Administrasi</label>
                                <input type="number" class="form-control" name="skor_administrasi" value="{{ $application->skor_administrasi ?? '' }}" min="0" max="100" step="any">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nilai Tes</label>
                                <input type="number" class="form-control" name="nilai_tes" value="{{ $application->nilai_tes ?? '' }}" min="0" max="100" step="any">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nilai Wawancara</label>
                                <input type="number" class="form-control" name="nilai_wawancara" value="{{ $application->nilai_wawancara ?? '' }}" min="0" max="100" step="any">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nilai Praktikum</label>
                                <input type="number" class="form-control" name="nilai_praktikum" value="{{ $application->nilai_praktikum ?? '' }}" min="0" max="100" step="any">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ranking</label>
                                <input type="number" class="form-control" name="ranking" value="{{ $application->ranking ?? '' }}" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status Akhir</label>
                                <input type="text" class="form-control" name="status_akhir" value="{{ $application->status_akhir ?? '' }}" placeholder="Aktif / Lulus / ...">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Catatan Pelamar</label>
                                <textarea class="form-control" name="catatan_pelamar" rows="3" placeholder="Catatan tambahan...">{{ $application->catatan_pelamar ?? '' }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Catatan Rekruter</label>
                                <textarea class="form-control" name="catatan_rekruter" rows="3" placeholder="Catatan internal rekruter...">{{ $application->catatan_rekruter ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $application]) }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Perbarui Lamaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
