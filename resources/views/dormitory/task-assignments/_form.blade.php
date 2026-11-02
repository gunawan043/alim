@php
    $isEdit = $prefix === 'edit';
@endphp

@unless($isEdit)
    <div class="mb-3">
        <label class="form-label">Pilih Staf <span class="text-danger">*</span></label>
        <select name="user_id" class="form-select" required>
            <option value="">— Pilih Staf —</option>
            @foreach($availableUsers as $user)
                <option value="{{ $user->id }}" @selected(old('user_id') === $user->id)>
                    {{ $user->name }}{{ $user->employment?->jabatan ? ' — '.$user->employment->jabatan : '' }}
                </option>
            @endforeach
        </select>
    </div>
@endunless

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Jabatan Asrama <span class="text-danger">*</span></label>
        <select name="position" id="{{ $prefix }}_position" class="form-select" required>
            <option value="">— Pilih Jabatan —</option>
            @foreach($positions as $value => $label)
                <option value="{{ $value }}" @selected(old('position', $isEdit ? '' : \App\Support\DormitoryPosition::STAF) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Shift Tugas</label>
        <select name="shift" id="{{ $prefix }}_shift" class="form-select">
            <option value="">— Pilih Shift —</option>
            @foreach($shifts as $value => $shift)
                <option value="{{ $value }}" @selected(old('shift', $isEdit ? '' : 'semua') === $value)>
                    {{ $shift['label'] }} ({{ $shift['time'] }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label d-block">Bidang Tugas</label>
        <div class="row g-2">
            @foreach($duties as $value => $label)
                <div class="col-md-4 col-sm-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input {{ $isEdit ? 'edit-duty-check' : '' }}"
                               name="duties[]" value="{{ $value }}" id="{{ $prefix }}_duty_{{ $value }}"
                               @checked(in_array($value, old('duties', []), true))>
                        <label class="form-check-label" for="{{ $prefix }}_duty_{{ $value }}">{{ $label }}</label>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="form-text">Bisa pilih lebih dari satu bidang tugas.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Mulai Tugas <span class="text-danger">*</span></label>
        <input type="date" name="start_date" id="{{ $prefix }}_start_date" class="form-control"
               value="{{ old('start_date', now()->toDateString()) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Selesai Tugas</label>
        <input type="date" name="end_date" id="{{ $prefix }}_end_date" class="form-control"
               value="{{ old('end_date') }}">
        <div class="form-text">Kosongkan jika penugasan berlanjut.</div>
    </div>
    <div class="col-12">
        <label class="form-label">Keterangan</label>
        <textarea name="notes" id="{{ $prefix }}_notes" class="form-control" rows="2"
                  maxlength="1000" placeholder="Catatan tambahan tentang penugasan...">{{ old('notes') }}</textarea>
    </div>
</div>
