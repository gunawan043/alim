@php
    /** @var array $dashboard */
    $filters = $dashboard['filters'];
    $slides = $dashboard['info_slides'] ?? collect();
    $broadcasts = $dashboard['broadcasts'] ?? collect();
    $updatedAt = $filters['updated_at'] ? \Illuminate\Support\Carbon::parse($filters['updated_at']) : null;
@endphp

<div class="dash-header card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div style="min-width: 260px;">
                <h4 class="mb-2">{{ $jabatanLabel }}</h4>
                <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
                    <span>{{ $filters['period_label'] }}</span>
                    <span>{{ $filters['school']?->name ?? 'Semua unit' }}</span>
                    @if($filters['grade_level'])
                        <span>{{ $filters['grade_level']->name }}</span>
                    @endif
                    <span class="{{ $updatedAt ? '' : 'text-warning' }}">
                        {{ $updatedAt
                            ? 'Diperbarui '.$updatedAt->locale('id')->diffForHumans()
                            : 'Waktu pembaruan belum tersedia' }}
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $dashboard['export_url'] }}" class="btn btn-export">
                    <i class="ri-file-excel-2-line me-1"></i>Excel
                </a>
                <a href="{{ route('user.dashboard.export.pdf', array_merge(['userId' => $user->id, 'role' => $roleSlug], array_filter([
                        'academic_year_id' => $filters['academic_year_id'],
                        'semester' => $filters['semester'],
                        'school_id' => $filters['school_id'],
                        'grade_level_id' => $filters['grade_level_id'],
                    ]))) }}" target="_blank" class="btn btn-export">
                    <i class="ri-file-pdf-2-line me-1"></i>PDF
                </a>
            </div>
        </div>

        <form method="GET" class="row g-2 align-items-end mt-3" id="dashboardFilterForm">
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="filter-academic-year">Tahun ajaran</label>
                <select name="academic_year_id" id="filter-academic-year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @forelse($filters['academic_years'] as $year)
                        <option value="{{ $year->id }}" @selected($filters['academic_year_id'] === $year->id)>{{ $year->name }}</option>
                    @empty
                        <option value="">Tahun ajaran belum tersedia</option>
                    @endforelse
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="filter-semester">Semester</label>
                <select name="semester" id="filter-semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ganjil" @selected($filters['semester'] === 'ganjil')>Ganjil</option>
                    <option value="genap" @selected($filters['semester'] === 'genap')>Genap</option>
                </select>
            </div>
            @if(! $filters['school_locked'] && $filters['schools']->isNotEmpty())
                <div class="col-6 col-md-3 col-xl-3">
                    <label class="form-label" for="filter-school">Unit</label>
                    <select name="school_id" id="filter-school" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua unit</option>
                        @foreach($filters['schools'] as $school)
                            <option value="{{ $school->id }}" @selected($filters['school_id'] === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="filter-grade">Jenjang</label>
                <select name="grade_level_id" id="filter-grade" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua jenjang</option>
                    @foreach($filters['grade_levels'] as $grade)
                        <option value="{{ $grade->id }}" @selected($filters['grade_level_id'] === $grade->id)>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                <a href="{{ url()->current() }}" class="btn btn-sm btn-light border" title="Atur ulang filter" aria-label="Atur ulang filter">
                    <i class="ri-refresh-line"></i>
                </a>
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
