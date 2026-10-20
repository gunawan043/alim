@extends('layouts.master')
@section('title', 'Dashboard & Pemetaan Profil Kurikulum')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $kokurikulerOptions = $profiles->flatMap(fn ($profile) => (array) $profile->configValue('kokurikuler_options', []))->unique()->all();
        $countLabels = ['cp' => 'CP', 'tp' => 'TP', 'atp' => 'ATP', 'prota' => 'Prota', 'prosem' => 'Prosem', 'perangkat' => 'Perangkat'];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') <a href="{{ route('user.schools-global.index', ['userId' => $userId]) }}">Schools Global</a> @endslot
        @slot('title') Pemetaan Profil Kurikulum @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa pemetaan.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-map-2-line me-1 text-primary"></i>Dashboard & Pemetaan Profil Kurikulum</h5>
                    <p class="text-muted mb-0 small">Tetapkan profil per sekolah dan tahun ajaran. Sekolah yang dikunci tidak akan berubah.</p>
                </div>
                <div class="col-md-auto">
                    <form method="GET" action="{{ route('user.schools-global.kurikulum-profile.index', ['userId' => $userId]) }}">
                        <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-2">
        @foreach([['Sekolah', $schools->count(), 'ri-building-2-line', 'primary'], ['Sudah dipetakan', $settings->whereNotNull('curriculum_profile_id')->count(), 'ri-checkbox-circle-line', 'success'], ['Belum dipetakan', $unmappedCount, 'ri-error-warning-line', 'warning'], ['Total Dokumen', array_sum($documentTotals), 'ri-folder-3-line', 'info']] as [$label, $value, $icon, $color])
            <div class="col-xl-3 col-md-6"><div class="card"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div><div><div class="text-muted small text-uppercase">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div></div></div></div></div>
        @endforeach
    </div>

    <div class="row g-2 mb-3">
        @foreach($countLabels as $key => $label)
            <div class="col-xl-2 col-md-4 col-6"><div class="border rounded bg-white p-2 text-center"><div class="text-muted small text-uppercase">{{ $label }}</div><div class="fw-bold fs-5">{{ (int) ($documentTotals[$key] ?? 0) }}</div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small"><i class="ri-pie-chart-line me-1"></i>Distribusi profil:</span>
            @foreach($profiles as $profile)
                <span class="badge bg-light text-dark border">{{ $profile->name }} <strong>{{ (int) ($profileDistribution[$profile->id] ?? 0) }}</strong></span>
            @endforeach
            <span class="badge bg-warning-subtle text-warning">Belum dipetakan {{ $unmappedCount }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('user.schools-global.kurikulum-profile.store', ['userId' => $userId]) }}" id="bulkProfileForm">
        @csrf
        <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
        <div class="card">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h5 class="card-title mb-0">Daftar Sekolah</h5><p class="text-muted mb-0 small">{{ $academicYear?->name }} · {{ $schools->count() }} sekolah aktif</p></div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select id="applyAllProfile" class="form-select form-select-sm" style="width:260px">
                        <option value="">— Terapkan profil ke semua —</option>
                        @foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->name }}</option>@endforeach
                    </select>
                    <button type="button" class="btn btn-soft-primary btn-sm" onclick="applyProfileToAll()">Terapkan</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i>Simpan Pemetaan</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sekolah</th>
                            <th style="min-width:240px">Profil Kurikulum</th>
                            <th style="min-width:150px">Kokurikuler</th>
                            <th class="text-center">Dokumen ({{ $academicYear?->name }})</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                            @php $setting = $settings->get($school->id); @endphp
                            <tr>
                                <td><div class="fw-semibold">{{ $school->name }}</div><div class="text-muted small">{{ $school->npsn }} · {{ $school->workUnit?->name ?? '—' }}</div></td>
                                <td>
                                    <select name="assignments[{{ $school->id }}][curriculum_profile_id]" class="form-select form-select-sm profile-select" @disabled($setting?->is_locked)>
                                        <option value="">— Belum dipetakan —</option>
                                        @foreach($profiles as $profile)<option value="{{ $profile->id }}" @selected((string) $setting?->curriculum_profile_id === (string) $profile->id)>{{ $profile->name }}</option>@endforeach
                                    </select>
                                    @if($setting?->is_locked)<div class="small text-warning mt-1"><i class="ri-lock-line me-1"></i>Terkunci</div>@endif
                                </td>
                                <td>
                                    <select name="assignments[{{ $school->id }}][kokurikuler_type]" class="form-select form-select-sm" @disabled($setting?->is_locked)>
                                        <option value="">Ikuti profil</option>
                                        @foreach($kokurikulerOptions as $value => $label)<option value="{{ $value }}" @selected($setting?->kokurikuler_type === $value)>{{ $label }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="text-center small">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        @foreach($countLabels as $key => $label)
                                            <span class="badge bg-light text-dark border" title="{{ $label }}">{{ $label }} {{ (int) (($counts[$key][$school->id] ?? 0)) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    @if($setting?->is_locked)
                                        <span class="badge bg-warning-subtle text-warning">Terkunci</span>
                                    @elseif($setting)
                                        <span class="badge bg-success-subtle text-success">Dipetakan</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Belum</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">Belum ada sekolah aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end"><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Pemetaan</button></div>
        </div>
    </form>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-history-line me-1 text-primary"></i>Riwayat Batch Pemetaan</h5><p class="text-muted mb-0 small">Rollback mengembalikan profil sekolah pada batch tersebut, kecuali sekolah yang terkunci.</p></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Waktu</th><th>Oleh</th><th class="text-center">Sekolah</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td class="small">{{ $batch->changed_at ? \Illuminate\Support\Carbon::parse($batch->changed_at)->translatedFormat('d M Y H:i') : '—' }}</td>
                            <td>{{ $batch->changer ?: '—' }}</td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $batch->schools_count }}</span></td>
                            <td>
                                @if($batch->pending === 0)
                                    <span class="badge bg-secondary-subtle text-secondary">Sudah di-rollback</span>
                                @elseif($batch->rolled_back > 0)
                                    <span class="badge bg-warning-subtle text-warning">{{ $batch->rolled_back }} dibatalkan, {{ $batch->pending }} aktif</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($batch->pending > 0)
                                    <form method="POST" action="{{ route('user.schools-global.kurikulum-profile.rollback', ['userId' => $userId, 'batchId' => $batch->batch_id]) }}" onsubmit="return confirm('Rollback batch pemetaan ini? Profil sekolah akan dikembalikan ke kondisi sebelumnya.');">
                                        @csrf
                                        <button class="btn btn-sm btn-soft-danger"><i class="ri-arrow-go-back-line me-1"></i>Rollback</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat pemetaan pada tahun ajaran ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function applyProfileToAll() {
            var value = document.getElementById('applyAllProfile').value;
            if (!value) return;
            document.querySelectorAll('.profile-select').forEach(function (select) {
                if (!select.disabled) select.value = value;
            });
        }
    </script>
@endsection
