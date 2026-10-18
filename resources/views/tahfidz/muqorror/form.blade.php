@extends('layouts.master')
@section('title', $plan ? 'Edit Muqorror' : 'Susun Muqorror')

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $isEdit = (bool) $plan;
        $action = $isEdit ? route('user.tahfidz.muqorror.update', ['userId' => $userId, 'id' => $plan->id]) : route('user.tahfidz.muqorror.store', ['userId' => $userId]);
        $value = fn (string $field, $default = null) => old($field, $plan?->{$field} ?? $default);
        $rowValue = fn (int $month, string $field) => old('months.'.$month.'.'.$field, data_get($monthRows, $month.'.'.$field));
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}">Muqorror</a> @endslot
        @slot('li_2') {{ $isEdit ? 'Edit' : 'Susun' }} @endslot
        @slot('title') Muqorror Tahfizh @endslot
    @endcomponent

    <form method="POST" action="{{ $action }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="card">
            <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Identitas Muqorror</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label">Judul <span class="text-danger">*</span></label><input name="title" class="form-control" value="{{ $value('title') }}" required maxlength="150" placeholder="Mis. Muqorror Tahfizh Kelas 7 Semester Ganjil"></div>
                    <div class="col-md-2"><label class="form-label">Jenjang</label><select name="jenjang" class="form-select"><option value="">—</option>@foreach(['sd'=>'SD','smp'=>'SMP','sma'=>'SMA'] as $k=>$v)<option value="{{ $k }}" @selected($value('jenjang')===$k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Paket</label><select name="tahfidz_package_id" class="form-select"><option value="">—</option>@foreach($packages as $package)<option value="{{ $package->id }}" @selected((string)$value('tahfidz_package_id')===(string)$package->id)>{{ $package->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">Kelas</label><select name="grade_level_id" class="form-select"><option value="">—</option>@foreach($gradeLevels as $grade)<option value="{{ $grade->id }}" @selected((string)$value('grade_level_id')===(string)$grade->id)>{{ $grade->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label><select name="academic_year_id" class="form-select" required>@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected((string)$value('academic_year_id')===(string)$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">Semester <span class="text-danger">*</span></label><select name="semester" class="form-select" required><option value="ganjil" @selected($value('semester','ganjil')==='ganjil')>Ganjil</option><option value="genap" @selected($value('semester')==='genap')>Genap</option></select></div>
                    @if($schools->isNotEmpty())
                        <div class="col-md-4"><label class="form-label">Sekolah</label><select name="school_id" class="form-select"><option value="">Departemen / semua sekolah</option>@foreach($schools as $school)<option value="{{ $school->id }}" @selected((string)$value('school_id')===(string)$school->id)>{{ $school->name }}</option>@endforeach</select></div>
                    @endif
                    <div class="col-md-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2">{{ $value('notes') }}</textarea></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Target Bulanan</h5>
                <span class="text-muted small">Standar Mushaf Madinah: 15 baris/halaman, 604 halaman</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" style="min-width:2100px;">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:110px">Bulan</th>
                            <th>Juz Awal</th><th>Juz Akhir</th><th>Halaman</th><th>Baris/Bulan</th><th>Baris/Hari</th><th>Hari Aktif</th>
                            <th>Dari Surah</th><th>Ayat</th><th>Sampai Surah</th><th>Ayat</th><th>STS/SAS</th>
                            <th>Target Semester</th><th>Target Tengah Semester</th><th>TM Ziyadah</th><th>TM Rabth</th><th>Pertemuan Ke-</th>
                            <th>Materi Alumni</th><th>Materi Non Alumni</th><th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $month => $label)
                            <tr>
                                <td class="fw-semibold">{{ $label }}<input type="hidden" name="months[{{ $month }}][month]" value="{{ $month }}"></td>
                                <td><input type="number" min="1" max="30" name="months[{{ $month }}][target_juz_start]" value="{{ $rowValue($month,'target_juz_start') }}" class="form-control form-control-sm"></td>
                                <td><input type="number" min="1" max="30" name="months[{{ $month }}][target_juz_end]" value="{{ $rowValue($month,'target_juz_end') }}" class="form-control form-control-sm"></td>
                                <td><input type="number" step="0.1" min="0" max="604" name="months[{{ $month }}][target_pages]" value="{{ $rowValue($month,'target_pages') }}" class="form-control form-control-sm"></td>
                                <td><input type="number" min="0" name="months[{{ $month }}][target_lines_month]" value="{{ $rowValue($month,'target_lines_month') }}" class="form-control form-control-sm"></td>
                                <td><input type="number" step="0.1" min="0" name="months[{{ $month }}][target_lines_day]" value="{{ $rowValue($month,'target_lines_day') }}" class="form-control form-control-sm"></td>
                                <td><input type="number" min="0" max="31" name="months[{{ $month }}][active_days]" value="{{ $rowValue($month,'active_days') }}" class="form-control form-control-sm"></td>
                                <td><input type="text" name="months[{{ $month }}][from_surah_name]" value="{{ $rowValue($month,'from_surah_name') }}" class="form-control form-control-sm" placeholder="Surah"></td>
                                <td><input type="number" min="0" name="months[{{ $month }}][from_ayat]" value="{{ $rowValue($month,'from_ayat') }}" class="form-control form-control-sm"></td>
                                <td><input type="text" name="months[{{ $month }}][to_surah_name]" value="{{ $rowValue($month,'to_surah_name') }}" class="form-control form-control-sm" placeholder="Surah"></td>
                                <td><input type="number" min="0" name="months[{{ $month }}][to_ayat]" value="{{ $rowValue($month,'to_ayat') }}" class="form-control form-control-sm"></td>
                                <td><select name="months[{{ $month }}][exam_type]" class="form-select form-select-sm"><option value="">—</option><option value="sts" @selected($rowValue($month,'exam_type')==='sts')>STS</option><option value="sas" @selected($rowValue($month,'exam_type')==='sas')>SAS</option></select></td>
                                <td><input type="text" name="months[{{ $month }}][target_semester]" value="{{ $rowValue($month,'target_semester') }}" class="form-control form-control-sm"></td>
                                <td><input type="text" name="months[{{ $month }}][target_mid_semester]" value="{{ $rowValue($month,'target_mid_semester') }}" class="form-control form-control-sm"></td>
                                <td><input type="number" min="0" max="31" name="months[{{ $month }}][ziyadah_meetings]" value="{{ $rowValue($month,'ziyadah_meetings') }}" class="form-control form-control-sm"></td>
                                <td><input type="number" min="0" max="31" name="months[{{ $month }}][rabth_meetings]" value="{{ $rowValue($month,'rabth_meetings') }}" class="form-control form-control-sm"></td>
                                <td><input type="number" min="0" max="31" name="months[{{ $month }}][meeting_no]" value="{{ $rowValue($month,'meeting_no') }}" class="form-control form-control-sm"></td>
                                <td><textarea name="months[{{ $month }}][alumni_material]" class="form-control form-control-sm" rows="1">{{ $rowValue($month,'alumni_material') }}</textarea></td>
                                <td><textarea name="months[{{ $month }}][non_alumni_material]" class="form-control form-control-sm" rows="1">{{ $rowValue($month,'non_alumni_material') }}</textarea></td>
                                <td><textarea name="months[{{ $month }}][notes]" class="form-control form-control-sm" rows="1">{{ $rowValue($month,'notes') }}</textarea></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                <button class="btn btn-success"><i class="ri-save-line me-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Muqorror' }}</button>
            </div>
        </div>
    </form>
@endsection
