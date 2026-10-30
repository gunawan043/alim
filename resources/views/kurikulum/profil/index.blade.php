@extends('layouts.master')
@section('title', 'Profil Kurikulum')
@section('css')
    @include('kurikulum._styles')
@endsection
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') Pengaturan @endslot
        @slot('title') Profil Kurikulum @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa pengaturan.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-settings-4-line me-1 text-primary"></i>Profil Kurikulum Satuan Pendidikan</h5>
                    <p class="text-muted mb-0 small">Profil menentukan label, struktur perangkat, nilai karakter, dan format cetak. Profil disimpan per tahun ajaran agar dokumen lama tetap utuh.</p>
                </div>
                <div class="col-md-auto d-flex flex-wrap gap-2">
                    <span class="badge bg-primary-subtle text-primary p-2">{{ $school->name }}</span>
                    @if($resolvedProfile)
                        <span class="badge bg-success-subtle text-success p-2">{{ $resolvedProfile->name }}</span>
                    @endif
                    <span class="badge bg-info-subtle text-info p-2">Dapat dibaca seluruh GTK</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @if($canManage)
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div><h5 class="card-title mb-0">Pengaturan Profil</h5><p class="text-muted mb-0 small">Pilih profil yang sesuai dengan otoritas kurikulum satuan pendidikan.</p></div>
                    <form method="GET" action="{{ route('user.kurikulum.profil.index', ['userId' => $userId]) }}">
                        <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    @if($setting?->is_locked)
                        <div class="alert alert-warning py-2"><i class="ri-lock-line me-1"></i>Profil tahun ajaran ini sudah dikunci. Perubahan profil tidak dapat dilakukan sampai kunci dibuka.</div>
                    @endif

                    <form method="POST" action="{{ route('user.kurikulum.profil.store', ['userId' => $userId]) }}">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">

                        <label class="form-label fw-semibold">Profil Kurikulum <span class="text-danger">*</span></label>
                        <div class="row g-2 mb-3">
                            @foreach($profileList as $profile)
                                @php
                                    $isSelected = old('curriculum_profile_id', $setting?->curriculum_profile_id ?? $resolvedProfile?->id) === $profile->id;
                                    $profileLabels = (array) $profile->configValue('labels', []);
                                @endphp
                                <div class="col-md-6">
                                    <label class="border rounded p-3 d-block h-100 cursor-pointer {{ $isSelected ? 'border-primary bg-primary-subtle' : '' }}" for="profile-{{ $profile->id }}">
                                        <div class="d-flex align-items-start gap-2">
                                            <input class="form-check-input mt-1" type="radio" name="curriculum_profile_id" id="profile-{{ $profile->id }}" value="{{ $profile->id }}" @checked($isSelected) required>
                                            <div>
                                                <div class="fw-semibold">{{ $profile->name }}</div>
                                                <div class="text-muted small mt-1">
                                                    {{ $profile->isKemenag() ? 'Kementerian Agama' : 'Dinas Pendidikan' }}
                                                    · {{ $profile->version ?? '—' }}
                                                </div>
                                                <div class="d-flex flex-wrap gap-1 mt-2">
                                                    <span class="badge bg-light text-dark">{{ $profileLabels['unit_label'] ?? 'Satuan' }}</span>
                                                    <span class="badge bg-light text-dark">{{ $profileLabels['perangkat_short'] ?? 'Perangkat' }}</span>
                                                    <span class="badge bg-light text-dark">{{ $profileLabels['kokurikuler_label'] ?? 'Kokurikuler' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kokurikuler</label>
                                <select name="kokurikuler_type" class="form-select">
                                    <option value="">— Ikuti profil —</option>
                                    @foreach($kokurikulerOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('kokurikuler_type', $setting?->kokurikuler_type) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Penetapan</label>
                                <div class="form-control bg-light">
                                    {{ $setting ? 'Ditetapkan' : 'Belum ditetapkan — memakai profil default' }}
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $setting?->notes) }}</textarea>
                            </div>
                        </div>

                        @if($canManage)
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Profil</button>
                            </div>
                        @else
                            <div class="alert alert-info py-2 mt-3 mb-0"><i class="ri-information-line me-1"></i>Hanya KSP, Wakil Kepala, dan tim kurikulum yang dapat mengubah profil.</div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        @endif

        <div class="{{ $canManage ? 'col-lg-5' : 'col-lg-12' }}">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-profile-line text-primary me-1"></i>Profil Efektif</h5></div>
                <div class="card-body">
                    @if($resolvedProfile)
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-sm"><span class="avatar-title rounded-circle bg-primary-subtle text-primary"><i class="ri-book-2-line"></i></span></span>
                            <div>
                                <div class="fw-semibold">{{ $resolvedProfile->name }}</div>
                                <div class="text-muted small">{{ $resolvedProfile->authority === 'kemenag' ? 'Kementerian Agama' : 'Dinas Pendidikan' }} · {{ $resolvedProfile->version }}</div>
                            </div>
                        </div>
                        <table class="table table-sm mb-3">
                            <tr><td class="text-muted">Satuan</td><td class="fw-medium">{{ $labels['unit_label'] ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Perangkat</td><td class="fw-medium">{{ $labels['perangkat_label'] ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Kokurikuler</td><td class="fw-medium">{{ $labels['kokurikuler_label'] ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Nilai</td><td class="fw-medium">{{ $labels['values_label'] ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Cetak</td><td class="fw-medium">{{ $labels['print_template'] ?? '—' }}</td></tr>
                        </table>
                        <h6 class="text-muted text-uppercase small mb-2">{{ $labels['values_label'] ?? 'Nilai' }}</h6>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($resolvedProfile->values() as $value)
                                <span class="badge bg-light text-dark border">{{ $value }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">Profil kurikulum belum tersedia. Jalankan <code>CurriculumProfileSeeder</code>.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('kurikulum.partials.curriculum-updates', [
        'curriculumUpdates' => $curriculumUpdates ?? [],
        'curriculumUpdatesAt' => $curriculumUpdatesAt ?? '',
    ])

    @include('kurikulum.partials.curriculum-flow', [
        'curriculumFlow' => $curriculumFlow ?? [],
    ])

    <div class="alert alert-info mt-3 mb-0 small">
        <i class="ri-information-line me-1"></i>
        Profil hanya mengubah <strong>label dan template</strong>, bukan menghapus data. CP, TP, ATP, Prota, Prosem, dan perangkat lama tetap tersimpan. Dokumen baru akan otomatis membawa profil yang berlaku pada tahun ajaran tersebut.
    </div>
@endsection
