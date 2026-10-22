@props([
    'schools' => collect(),
    'gradeLevels' => collect(),
    'studyGroups' => collect(),
    'showSchool' => false,
    'schoolId' => null,
    'gradeLevelId' => null,
    'studyGroupId' => null,
    'autoSubmit' => true,
])

@if($showSchool)
    <div class="col-md-3">
        <label class="form-label small mb-1">Satuan Pendidikan</label>
        <select name="school_id" class="form-select" @if($autoSubmit) onchange="this.form.submit()" @endif>
            <option value="">Semua satuan pendidikan</option>
            @foreach($schools as $school)
                <option value="{{ $school->id }}" @selected((string) $schoolId === (string) $school->id)>{{ $school->name }}</option>
            @endforeach
        </select>
    </div>
@endif

<div class="col-md-3">
    <label class="form-label small mb-1">Tingkat</label>
    <select name="grade_level_id" class="form-select" @if($autoSubmit) onchange="this.form.submit()" @endif>
        <option value="">Semua tingkat</option>
        @foreach($gradeLevels as $grade)
            <option value="{{ $grade->id }}" @selected((string) $gradeLevelId === (string) $grade->id)>{{ $grade->name }}{{ $grade->school ? ' — '.$grade->school->name : '' }}</option>
        @endforeach
    </select>
</div>

<div class="col-md-3">
    <label class="form-label small mb-1">Kelas</label>
    <select name="study_group_id" class="form-select" @if($autoSubmit) onchange="this.form.submit()" @endif>
        <option value="">Semua kelas</option>
        @foreach($studyGroups as $group)
            <option value="{{ $group->id }}" @selected((string) $studyGroupId === (string) $group->id)>{{ $group->name }}{{ $group->school ? ' — '.$group->school->name : '' }}</option>
        @endforeach
    </select>
</div>
