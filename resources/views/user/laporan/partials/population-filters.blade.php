<form method="GET" action="{{ route($filterRoute, ['userId' => $userId]) }}" class="row g-2 align-items-end">
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Tahun Ajaran</label>
        <select name="academic_year_id" class="form-select">
            @foreach($academicYears as $year)
                <option value="{{ $year->id }}" {{ $filters['academic_year_id'] === $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
            @endforeach
        </select>
    </div>
    @if(!$schoolLocked)
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Unit / Satuan</label>
            <select name="school_id" class="form-select">
                <option value="">Semua unit</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}" {{ $filters['school_id'] === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Jenjang</label>
        <select name="grade_level_id" class="form-select">
            <option value="">Semua jenjang</option>
            @foreach($gradeLevels as $grade)
                <option value="{{ $grade->id }}" {{ $filters['grade_level_id'] === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Kelas / Rombel</label>
        <select name="study_group_id" class="form-select">
            <option value="">Semua kelas</option>
            @foreach($studyGroups as $group)
                <option value="{{ $group->id }}" {{ $filters['study_group_id'] === $group->id ? 'selected' : '' }}>{{ $group->name }}{{ $group->school ? ' · '.$group->school->name : '' }}</option>
            @endforeach
        </select>
    </div>
    @if(!empty($showDates))
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Dari Tanggal</label>
            <input type="date" name="dari" class="form-control" value="{{ $filters['dari'] }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Sampai Tanggal</label>
            <input type="date" name="sampai" class="form-control" value="{{ $filters['sampai'] }}">
        </div>
    @endif
    @if(!empty($showGender))
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Jenis Kelamin</label>
            <select name="gender" class="form-select">
                <option value="">Semua</option>
                <option value="L" {{ $filters['gender'] === 'L' ? 'selected' : '' }}>Laki-laki</option>
                <option value="P" {{ $filters['gender'] === 'P' ? 'selected' : '' }}>Perempuan</option>
            </select>
        </div>
    @endif
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Cari Santri</label>
        <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Nama / NISN / NIS">
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1"><i class="ri-filter-3-line me-1"></i>Filter</button>
        <a href="{{ route($filterRoute, ['userId' => $userId]) }}" class="btn btn-light border" title="Reset"><i class="ri-refresh-line"></i></a>
    </div>
</form>
