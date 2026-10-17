@extends('layouts.master')
@section('title') Tambah Lamaran GTK @endsection
@section('css')
<style>
    .form-section { border: 1px solid var(--bs-border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; background: #fafafa; }
    .form-section-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: .6px; color: #6c757d; font-weight: 700; margin-bottom: 16px; }
    .step-indicator { background: #eee; height: 4px; width: 100%; }
    .step-indicator .progress { background: #0d6efd; height: 4px; transition: width 0.3s; }
</style>
@endsection

@section('content')
@php $userId = $userId ?? request('userId'); @endphp
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('li_2_link') {{ route('user.ats.applications.index', ['userId' => $userId]) }} @endslot
    @slot('li_2') Lamaran @endslot
    @slot('title') Tambah Baru @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-add-line me-2"></i>Buat Lamaran Baru</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.ats.applications.store', $userId) }}" method="POST" id="applicationForm">
                    @csrf

                    {{-- Pilih Profil --}}
                    <div class="form-section">
                        <div class="form-section-title">1. Pilih Profil Pelamar</div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Profile Pelamar <span class="text-danger">*</span></label>
                            <select class="form-select" name="recruitment_profile_id" id="profileSelect" required>
                                <option value="">-- Pilih Profil --</option>
                                @foreach($profiles as $profile)
                                    <option value="{{ $profile->id }}"
                                        data-nama="{{ $profile->user->name ?? '' }}"
                                        data-nik="{{ $profile->nik ?? '' }}">
                                        {{ $profile->user->name ?? 'N/A' }} (NIK: {{ $profile->nik ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pilih profil pelamar yang sudah terdaftar.</small>
                        </div>
                    </div>

                    {{-- Pilih Lowongan --}}
                    <div class="form-section">
                        <div class="form-section-title">2. Detail Lamaran</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Lowongan <span class="text-danger">*</span></label>
                                <select class="form-select" name="recruitment_job_id" required>
                                    <option value="">-- Pilih Lowongan --</option>
                                    @foreach($jobs as $job)
                                        <option value="{{ $job->id }}">{{ $job->judul }} @if($job->status !== 'aktif')[{{ $job->status }}]@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. Lamaran</label>
                                <input type="text" class="form-control" name="no_lamaran" placeholder="Otomatis jika kosong">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <input type="text" class="form-control" name="status" placeholder="Pending / Baru / ..." value="pending">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tanggal Melamar</label>
                                <input type="date" class="form-control" name="tanggal_melamar" value="{{ today()->toDateString() }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Catatan Pelamar</label>
                                <textarea class="form-control" name="catatan_pelamar" rows="3" placeholder="Catatan tambahan..."></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('user.ats.applications.index', $userId) }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Simpan Lamaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
