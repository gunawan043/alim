@php
    $isEdit = $prefix === 'edit';
@endphp

@unless($isEdit)
    <div class="mb-3">
        <label class="form-label">Pilih Staf <span class="text-danger">*</span></label>
        <select name="user_id" id="{{ $prefix }}_user_id" class="form-select" required>
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
        <select name="position_id" id="{{ $prefix }}_position_id" class="form-select" required>
            <option value="">— Pilih Jabatan —</option>
            @foreach($positions as $position)
                <option value="{{ $position->id }}" @selected(old('position_id') === $position->id)>
                    {{ $position->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Jabatan mengikuti master jabatan struktural satuan kerja asrama.</div>
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

    {{-- Tugas tambahan dari master additional_task_types --}}
    <div class="col-12">
        <label class="form-label d-block">
            Tugas Tambahan
            <span class="text-muted fw-normal small">(dari master, boleh lebih dari satu)</span>
        </label>
        @if($taskTypes->isEmpty())
            <div class="alert alert-info mb-0 small py-2">
                <i class="ri-information-line me-1"></i>
                Belum ada master jenis tugas tambahan yang cocok dengan jenis GTK staf asrama ini.
            </div>
        @else
            <div class="row g-2">
                @foreach($taskTypes as $taskType)
                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100 d-flex align-items-center gap-2">
                            <div class="form-check flex-grow-1 mb-0">
                                <input type="checkbox"
                                       class="form-check-input {{ $isEdit ? 'edit-task-check' : '' }}"
                                       name="task_type_ids[]"
                                       value="{{ $taskType->id }}"
                                       id="{{ $prefix }}_task_{{ $taskType->id }}"
                                       @checked(in_array($taskType->id, old('task_type_ids', []), true))>
                                <label class="form-check-label" for="{{ $prefix }}_task_{{ $taskType->id }}">
                                    {{ $taskType->nama }}
                                </label>
                            </div>
                            <div style="width: 96px;">
                                <input type="number" min="0" max="80" step="1"
                                       class="form-control form-control-sm {{ $isEdit ? 'edit-jp-input' : '' }}"
                                       name="jp[{{ $taskType->id }}]"
                                       id="{{ $prefix }}_jp_{{ $taskType->id }}"
                                       value="{{ old('jp.'.$taskType->id) }}"
                                       placeholder="JP" title="JP per minggu" aria-label="JP per minggu {{ $taskType->nama }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="form-text">Kolom kanan diisi JP per minggu bila tugas tersebut mempunyai beban jam.</div>
        @endif
    </div>

    {{-- Rincian pekerjaan khas asrama (bukan master) --}}
    <div class="col-12">
        <label class="form-label d-block">Rincian Bidang Tugas</label>
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

    <div class="col-md-4">
        <label class="form-label">Nomor SK</label>
        <input type="text" name="nomor_sk" id="{{ $prefix }}_nomor_sk" class="form-control"
               value="{{ old('nomor_sk') }}" maxlength="100" placeholder="Mis. SK/ASR/2026/001">
    </div>
    <div class="col-md-4">
        <label class="form-label">TMT</label>
        <input type="date" name="tmt" id="{{ $prefix }}_tmt" class="form-control" value="{{ old('tmt') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">TST</label>
        <input type="date" name="tst" id="{{ $prefix }}_tst" class="form-control" value="{{ old('tst') }}">
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
