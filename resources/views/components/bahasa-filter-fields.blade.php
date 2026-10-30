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

@php
    $autoSubmitAttr = $autoSubmit ? 'onchange=this.form.submit()' : '';
@endphp

{{--
    Komponen filter bahasa — mengeluarkan grid row g-2 dengan 3 kolom:
    Satuan Pendidikan (jika showSchool), Tingkat, Kelas.

    Pemakaian di parent (WAJIB di dalam form GET):
        <form method="GET">
            <x-bahasa-filter-fields
                :schools="$schools"
                :grade-levels="$gradeLevels"
                :study-groups="$studyGroups"
                :show-school="$showSchool"
                :school-id="$schoolId"
                :grade-level-id="$gradeLevelId"
                :study-group-id="$studyGroupId"
            />
        </form>

    JANGAN bungkus komponen ini dengan col-* — komponen sudah mengeluarkan
    row g-2 sendiri dengan col-12 col-md-4 di tiap field.
--}}

<div {{ $attributes->merge(['class' => 'row g-2']) }}>
    @if($showSchool)
        <div class="col-12 col-md-4">
            <label class="form-label small mb-1">Satuan Pendidikan</label>
            <select name="school_id" class="form-select" {{ $autoSubmitAttr }}>
                <option value="">Semua satuan pendidikan</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) $schoolId === (string) $school->id)>
                        {{ $school->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="col-12 {{ $showSchool ? 'col-md-4' : 'col-md-6' }}">
        <label class="form-label small mb-1">Tingkat</label>
        <select name="grade_level_id" class="form-select" {{ $autoSubmitAttr }}>
            <option value="">Semua tingkat</option>
            @foreach($gradeLevels as $grade)
                <option value="{{ $grade->id }}" @selected((string) $gradeLevelId === (string) $grade->id)>
                    {{ $grade->name }}{{ $grade->school ? ' — '.$grade->school->name : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 {{ $showSchool ? 'col-md-4' : 'col-md-6' }}">
        <label class="form-label small mb-1">Kelas</label>
        <select name="study_group_id" class="form-select" {{ $autoSubmitAttr }}>
            <option value="">Semua kelas</option>
            @foreach($studyGroups as $group)
                <option value="{{ $group->id }}" @selected((string) $studyGroupId === (string) $group->id)>
                    {{ $group->name }}{{ $group->school ? ' — '.$group->school->name : '' }}
                </option>
            @endforeach
        </select>
    </div>
</div>