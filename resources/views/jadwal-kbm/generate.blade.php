@extends('layouts.horizontal')

@section('page-title', 'Generate Jadwal KBM')

@section('page-heading')
    <div class="row px-xl-1">
        <div class="col-xl-12">
            <ol class="d-flex flex-wrap list-inline list-inline-breadcrumb mb-1">
                <li class="list-inline-item"><a href="{{ route('home', ['userId' => $userId ?? auth()->user()->id]) }}">Home</a></li>
                <li class="list-inline-item"><a href="{{ route('jadwal-kbm.index', ['userId' => $userId ?? auth()->user()->id]) }}">Jadwal KBM</a></li>
                <li class="list-inline-item"><span>Generate Jadwal</span></li>
            </ol>
            <h5 class="app-page-title h3">Generate Jadwal Kegiatan Belajar</h5>
        </div>
    </div>
@endsection

@section('page-content')
<div class="app-card p-4">
    <form method="POST" id="generateForm">
        @csrf
        <input type="hidden" name="academic_year_id" value="{{ $activeAy?->id ?? old('academic_year_id') }}">

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" name="overwrite" value="1" id="overwrite">
                    <label class="form-check-label" for="overwrite">Timpa jadwal yang sudah ada</label>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label>Pilih Rombongan Belajar</label>
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllSG(true)">Pilih Semua</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllSG(false)">Hapus Pilihan</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllCb" onchange="selectAllSG(this.checked)"></th>
                                <th>Kelas</th>
                                <th>Grade</th>
                                <th>Wali Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studyGroups as $sg)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="study_group_ids[]" value="{{ $sg->id }}" class="sg-cb">
                                    </td>
                                    <td>{{ $sg->full_name }}</td>
                                    <td>{{ $sg->gradeLevel->name ?? '-' }}</td>
                                    <td>{{ $sg->homeroomTeacher?->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Jadwal
        </button>
    </form>
</div>

@push('scripts')
<script>
function selectAllSG(checked) {
    document.querySelectorAll('.sg-cb').forEach(cb => cb.checked = checked);
    document.getElementById('selectAllCb').checked = checked;
}
</script>
@endpush
@endsection
