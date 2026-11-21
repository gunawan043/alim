{{--
    Saringan laporan populasi. Memakai flex-wrap supaya jumlah field yang
    berbeda antar halaman tidak menyisakan sel kosong seperti grid Bootstrap.
--}}
<form method="GET" action="{{ route($filterRoute, ['userId' => $userId]) }}" class="lap-filters">
    <div class="lap-field">
        <label class="form-label" for="lap-ay">Tahun ajaran</label>
        <select id="lap-ay" name="academic_year_id" class="form-select">
            @foreach ($academicYears as $year)
                <option value="{{ $year->id }}" {{ $filters['academic_year_id'] === $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
            @endforeach
        </select>
    </div>
    @if (! $schoolLocked)
        <div class="lap-field">
            <label class="form-label" for="lap-school">Unit</label>
            <select id="lap-school" name="school_id" class="form-select">
                <option value="">Semua unit</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" {{ $filters['school_id'] === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="lap-field">
        <label class="form-label" for="lap-grade">Jenjang</label>
        <select id="lap-grade" name="grade_level_id" class="form-select">
            <option value="">Semua jenjang</option>
            @foreach ($gradeLevels as $grade)
                <option value="{{ $grade->id }}" {{ $filters['grade_level_id'] === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="lap-field">
        <label class="form-label" for="lap-group">Kelas</label>
        <select id="lap-group" name="study_group_id" class="form-select">
            <option value="">Semua kelas</option>
            @foreach ($studyGroups as $group)
                <option value="{{ $group->id }}" {{ $filters['study_group_id'] === $group->id ? 'selected' : '' }}>{{ $group->name }}{{ $group->school ? ' · '.$group->school->name : '' }}</option>
            @endforeach
        </select>
    </div>
    @if (! empty($showDates))
        <div class="lap-field">
            <label class="form-label" for="lap-dari">Dari tanggal</label>
            <input type="date" id="lap-dari" name="dari" class="form-control" value="{{ $filters['dari'] }}">
        </div>
        <div class="lap-field">
            <label class="form-label" for="lap-sampai">Sampai tanggal</label>
            <input type="date" id="lap-sampai" name="sampai" class="form-control" value="{{ $filters['sampai'] }}">
        </div>
    @endif
    @if (! empty($showGender))
        <div class="lap-field">
            <label class="form-label" for="lap-gender">Jenis kelamin</label>
            <select id="lap-gender" name="gender" class="form-select">
                <option value="">Semua</option>
                <option value="L" {{ $filters['gender'] === 'L' ? 'selected' : '' }}>Laki-laki</option>
                <option value="P" {{ $filters['gender'] === 'P' ? 'selected' : '' }}>Perempuan</option>
            </select>
        </div>
    @endif
    <div class="lap-field">
        <label class="form-label" for="lap-q">Cari santri</label>
        <input type="text" id="lap-q" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Nama, NISN, atau NIS">
    </div>
    <div class="lap-actions">
        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
        <a href="{{ route($filterRoute, ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
            <i class="ri-refresh-line"></i>
        </a>
    </div>
</form>
