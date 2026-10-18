@extends('layouts.master')
@section('title', $plan ? 'Edit RPP' : 'Susun RPP')

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $isEdit = (bool) $plan;
        $action = $isEdit ? route('user.tahfidz.rpp.update', ['userId' => $userId, 'id' => $plan->id]) : route('user.tahfidz.rpp.store', ['userId' => $userId]);
        $value = fn (string $field, $default = null) => old($field, $plan?->{$field} ?? $default);
        $items = old('items', $plan?->items?->toArray() ?? []);
        $items = is_array($items) ? array_values($items) : [];
        while (count($items) < 4) { $items[] = []; }
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId]) }}">RPP</a> @endslot
        @slot('li_2') {{ $isEdit ? 'Edit' : 'Susun' }} @endslot
        @slot('title') RPP Tahfizh & Iqro’ @endslot
    @endcomponent

    <form method="POST" action="{{ $action }}">
        @csrf @if($isEdit) @method('PUT') @endif
        <div class="card">
            <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Identitas RPP</h5></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-2"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="type" class="form-select" required><option value="tahfizh" @selected($value('type','tahfizh')==='tahfizh')>Tahfizh</option><option value="iqro" @selected($value('type')==='iqro')>Iqro’</option></select></div>
                <div class="col-md-6"><label class="form-label">Judul <span class="text-danger">*</span></label><input name="title" class="form-control" value="{{ $value('title') }}" required maxlength="150" placeholder="Mis. RPP Tahfizh Kelas 7 — Pekan 1"></div>
                <div class="col-md-4"><label class="form-label">Bulan KBM</label><input name="kbm_month" class="form-control" value="{{ $value('kbm_month') }}" maxlength="30" placeholder="Mis. Januari 2026"></div>
                <div class="col-md-4"><label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label><select name="academic_year_id" class="form-select" required>@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected((string)$value('academic_year_id')===(string)$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">Semester <span class="text-danger">*</span></label><select name="semester" class="form-select" required><option value="ganjil" @selected($value('semester','ganjil')==='ganjil')>Ganjil</option><option value="genap" @selected($value('semester')==='genap')>Genap</option></select></div>
                <div class="col-md-3"><label class="form-label">Kelas</label><select name="grade_level_id" class="form-select"><option value="">—</option>@foreach($gradeLevels as $grade)<option value="{{ $grade->id }}" @selected((string)$value('grade_level_id')===(string)$grade->id)>{{ $grade->name }}</option>@endforeach</select></div>
                @if($schools->isNotEmpty())<div class="col-md-3"><label class="form-label">Sekolah</label><select name="school_id" class="form-select"><option value="">Departemen</option>@foreach($schools as $school)<option value="{{ $school->id }}" @selected((string)$value('school_id')===(string)$school->id)>{{ $school->name }}</option>@endforeach</select></div>@endif
                <div class="col-md-6"><label class="form-label">Target Bulanan</label><textarea name="monthly_target" class="form-control" rows="2">{{ $value('monthly_target') }}</textarea></div>
                <div class="col-md-6"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2">{{ $value('notes') }}</textarea></div>
            </div></div>
        </div>

        <div class="card">
            <div class="card-header border-bottom-dashed d-flex justify-content-between"><h5 class="card-title mb-0">Butir RPP</h5><span class="text-muted small">Untuk Iqro’ isi bagian/kegiatan; untuk Tahfizh isi pekan/hari/target.</span></div>
            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0" style="min-width:1500px;">
                <thead class="table-light"><tr><th>Pekan</th><th>Bagian</th><th>Hari</th><th>Target Pekanan</th><th>Target Harian</th><th>Kegiatan Guru</th><th>Kegiatan Siswa</th><th>Menit</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @foreach($items as $i => $item)
                        <tr>
                            <td><input type="number" min="1" max="5" name="items[{{ $i }}][week]" value="{{ data_get($item,'week') }}" class="form-control form-control-sm"></td>
                            <td><input type="text" name="items[{{ $i }}][section]" value="{{ data_get($item,'section') }}" class="form-control form-control-sm" placeholder="Pra/Inti/Penutup"></td>
                            <td><input type="text" name="items[{{ $i }}][learning_day]" value="{{ data_get($item,'learning_day') }}" class="form-control form-control-sm" placeholder="Senin"></td>
                            <td><input type="text" name="items[{{ $i }}][week_target]" value="{{ data_get($item,'week_target') }}" class="form-control form-control-sm"></td>
                            <td><textarea name="items[{{ $i }}][daily_target]" class="form-control form-control-sm" rows="1">{{ data_get($item,'daily_target') }}</textarea></td>
                            <td><textarea name="items[{{ $i }}][teacher_activity]" class="form-control form-control-sm" rows="1">{{ data_get($item,'teacher_activity') }}</textarea></td>
                            <td><textarea name="items[{{ $i }}][student_activity]" class="form-control form-control-sm" rows="1">{{ data_get($item,'student_activity') }}</textarea></td>
                            <td><input type="number" min="0" max="300" name="items[{{ $i }}][duration_minutes]" value="{{ data_get($item,'duration_minutes') }}" class="form-control form-control-sm"></td>
                            <td><textarea name="items[{{ $i }}][notes]" class="form-control form-control-sm" rows="1">{{ data_get($item,'notes') }}</textarea></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a><button class="btn btn-success"><i class="ri-save-line me-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan RPP' }}</button></div>
        </div>
    </form>
@endsection
