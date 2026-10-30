@php
    /** @var array $dashboard */
    $filters = $dashboard['filters'];
    $analytics = $dashboard['analytics'] ?? [];
    $slides = $dashboard['info_slides'] ?? collect();
    $broadcasts = $dashboard['broadcasts'] ?? collect();
    $updatedAt = $filters['updated_at'] ? \Illuminate\Support\Carbon::parse($filters['updated_at']) : null;
@endphp

<div class="dash-header card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div style="min-width: 260px;">
                <h4 class="mb-1">{{ $jabatanLabel }}</h4>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge bg-primary-subtle text-primary">
                        <i class="ri-calendar-2-line me-1"></i>{{ $filters['period_label'] }}
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary">
                        <i class="ri-building-2-line me-1"></i>{{ $filters['school']?->name ?? 'Semua unit' }}
                    </span>
                    @if($filters['grade_level'])
                        <span class="badge bg-info-subtle text-info">
                            <i class="ri-stack-line me-1"></i>{{ $filters['grade_level']->name }}
                        </span>
                    @endif
                    <span class="badge {{ $updatedAt ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                        <i class="ri-time-line me-1"></i>
                        {{ $updatedAt ? 'Data diperbarui '.$updatedAt->diffForHumans() : 'Waktu pembaruan belum tersedia' }}
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $dashboard['export_url'] }}" class="btn btn-sm btn-success">
                    <i class="ri-file-excel-2-line me-1"></i>Excel
                </a>
                <a href="{{ route('user.dashboard.export.pdf', array_merge(['userId' => $user->id, 'role' => $roleSlug], array_filter([
                        'academic_year_id' => $filters['academic_year_id'],
                        'semester' => $filters['semester'],
                        'school_id' => $filters['school_id'],
                        'grade_level_id' => $filters['grade_level_id'],
                    ]))) }}" target="_blank" class="btn btn-sm btn-danger">
                    <i class="ri-file-pdf-2-line me-1"></i>PDF
                </a>
            </div>
        </div>

        <form method="GET" class="row g-2 align-items-end mt-3" id="dashboardFilterForm">
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @forelse($filters['academic_years'] as $year)
                        <option value="{{ $year->id }}" @selected($filters['academic_year_id'] === $year->id)>{{ $year->name }}</option>
                    @empty
                        <option value="">Tahun ajaran belum tersedia</option>
                    @endforelse
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small text-muted mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ganjil" @selected($filters['semester'] === 'ganjil')>Ganjil</option>
                    <option value="genap" @selected($filters['semester'] === 'genap')>Genap</option>
                </select>
            </div>
            @if(! $filters['school_locked'] && $filters['schools']->isNotEmpty())
                <div class="col-6 col-md-3 col-xl-3">
                    <label class="form-label small text-muted mb-1">Unit / Satuan</label>
                    <select name="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua unit</option>
                        @foreach($filters['schools'] as $school)
                            <option value="{{ $school->id }}" @selected($filters['school_id'] === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small text-muted mb-1">Jenjang</label>
                <select name="grade_level_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua jenjang</option>
                    @foreach($filters['grade_levels'] as $grade)
                        <option value="{{ $grade->id }}" @selected($filters['grade_level_id'] === $grade->id)>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                <a href="{{ url()->current() }}" class="btn btn-sm btn-light border" title="Reset filter"><i class="ri-refresh-line"></i></a>
            </div>
        </form>
    </div>
</div>

@if($slides->isNotEmpty())
    @include('dashboard._shared.partials.info-carousel', ['slides' => $slides])
@endif

@if($broadcasts->isNotEmpty())
    @include('dashboard._shared.partials.broadcast-modal', ['broadcasts' => $broadcasts])
@endif
