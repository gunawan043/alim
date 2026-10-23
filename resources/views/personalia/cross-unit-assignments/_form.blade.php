@php
    $selectedUser = old('user_id', $assignment?->user_id);
    $selectedHomeUnit = old('home_work_unit_id', $assignment?->home_work_unit_id);
    $selectedSchool = old('school_id', $assignment?->school_id);
    $selectedYear = old('academic_year_id', $assignment?->academic_year_id ?? $activeAcademicYearId);
    $selectedSubject = old('subject_id', $assignment?->subject_id);
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">GTK <span class="text-danger">*</span></label>
        <select name="user_id" class="form-select" required>
            <option value="">-- Pilih GTK --</option>
            @foreach($gtkUsers as $gtk)
                <option value="{{ $gtk->id }}" {{ (string) $selectedUser === (string) $gtk->id ? 'selected' : '' }}>{{ $gtk->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Work Unit Asal <span class="text-danger">*</span></label>
        <select name="home_work_unit_id" class="form-select" required>
            <option value="">-- Pilih Work Unit --</option>
            @foreach($workUnits as $workUnit)
                <option value="{{ $workUnit->id }}" {{ (string) $selectedHomeUnit === (string) $workUnit->id ? 'selected' : '' }}>{{ $workUnit->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Satuan Pendidikan Tujuan <span class="text-danger">*</span></label>
        <select name="school_id" class="form-select" required>
            <option value="">-- Pilih Satuan Pendidikan --</option>
            @foreach($schools as $school)
                <option value="{{ $school->id }}" {{ (string) $selectedSchool === (string) $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
        <select name="academic_year_id" class="form-select" required>
            <option value="">-- Pilih Tahun Ajaran --</option>
            @foreach($academicYears as $ay)
                <option value="{{ $ay->id }}" {{ (string) $selectedYear === (string) $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Mapel Rekomendasi</label>
        <select name="subject_id" class="form-select">
            <option value="">Ditentukan satuan pendidikan</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ (string) $selectedSubject === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Nomor SK Penugasan</label>
        <input type="text" name="decree_number" class="form-control" maxlength="100" value="{{ old('decree_number', $assignment?->decree_number) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Tanggal Mulai</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $assignment?->start_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Tanggal Selesai</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $assignment?->end_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control" rows="2" maxlength="500">{{ old('notes', $assignment?->notes) }}</textarea>
    </div>
</div>
