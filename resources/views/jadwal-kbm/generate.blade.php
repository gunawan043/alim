@extends('layouts.master')

@section('title', 'Generate Jadwal KBM')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">Jadwal KBM</a> @endslot
    @slot('li_2') Generate Jadwal @endslot
    @slot('title') Generate Jadwal Kegiatan Belajar @endslot
@endcomponent

<p class="text-muted small mb-3">
    Jadwal disusun dari assignment SK guru (guru × mapel × rombel × JP) dengan validasi bentrok guru/rombel.
</p>

@if(! $activeAy)
    <div class="alert alert-danger">
        <i class="ri-error-warning-line me-1"></i>
        Tahun ajaran aktif belum ditetapkan. Aktifkan tahun ajaran terlebih dahulu sebelum generate jadwal.
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('user.jadwal-kbm.generate.store', ['userId' => $userId]) }}" id="generateForm">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $activeAy?->id }}">

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Tahun Ajaran</label>
                    <input type="text" class="form-control" value="{{ $activeAy->name ?? '—' }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="ganjil" {{ ($activeAy->semester ?? '') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ ($activeAy->semester ?? '') === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Opsi</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" role="switch" name="overwrite" value="1" id="overwrite">
                        <label class="form-check-label" for="overwrite">Timpa jadwal yang sudah ada</label>
                    </div>
                </div>
            </div>

            <div class="alert {{ $masterSlotCount > 0 ? 'alert-info' : 'alert-warning' }} small">
                <i class="ri-information-line me-1"></i>
                @if($masterSlotCount > 0)
                    Master slot sekolah: <strong>{{ $masterSlotCount }} slot</strong> (jam & istirahat mengikuti <code>class_schedule_slots</code>).
                    <a href="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}" class="alert-link">Atur jam pelajaran</a>.
                @else
                    Sekolah belum memiliki master slot. Atur jam pelajaran terlebih dahulu di
                    <a href="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}" class="alert-link">menu Jam Pelajaran</a>
                    — generator memakai grid default: mulai 07:00, 45 menit per jam, istirahat 30 menit setelah jam ke-4.
                @endif
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <label class="form-label mb-0 fw-semibold">Pilih Rombongan Belajar</label>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllSG(true)">Pilih Semua</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllSG(false)">Kosongkan</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:42px"><input type="checkbox" id="selectAllCb" onchange="selectAllSG(this.checked)"></th>
                            <th>Rombel</th>
                            <th>Tingkat</th>
                            <th>Wali Kelas</th>
                            <th class="text-center">Mapel (SK)</th>
                            <th class="text-center">Total JP (SK)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studyGroups as $sg)
                            @php $summary = $assignmentSummary[$sg->id] ?? null; @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="study_group_ids[]" value="{{ $sg->id }}" class="sg-cb">
                                </td>
                                <td class="fw-medium">{{ $sg->full_name ?? $sg->name }}</td>
                                <td>{{ $sg->gradeLevel->name ?? '-' }}</td>
                                <td>{{ $sg->homeroomTeacher?->name ?? '-' }}</td>
                                <td class="text-center">
                                    @if($summary)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $summary->total_subjects }} mapel</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Belum ada SK</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($summary && (int) $summary->total_hours > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ (int) $summary->total_hours }} JP</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada rombel aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
                <button type="submit" class="btn btn-primary" {{ (! $activeAy || $studyGroups->isEmpty()) ? 'disabled' : '' }}>
                    <i class="ri-magic-line me-1"></i>Generate Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

@if($otherTasks->isNotEmpty())
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h6 class="card-title mb-0">
                <i class="ri-folders-line text-secondary me-1"></i> Tugas Mengajar Tambahan
                <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $otherTasks->count() }}</span>
            </h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-2">
                Data pendukung dari <code>other_teacher_tasks</code> — tidak dihitung sebagai JP generator, tetapi menjadi konteks beban mengajar guru.
            </p>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Guru</th>
                            <th>Tugas</th>
                            <th>Rombel</th>
                            <th class="text-center">JP/Minggu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($otherTasks as $task)
                            <tr>
                                <td>{{ $task->teacher?->name ?? '-' }}</td>
                                <td>{{ $task->task_name }}</td>
                                <td>{{ $task->studyGroup?->name ?? '—' }}</td>
                                <td class="text-center">{{ $task->weekly_hours ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
function selectAllSG(checked) {
    document.querySelectorAll('.sg-cb').forEach(function (cb) { cb.checked = checked; });
    const master = document.getElementById('selectAllCb');
    if (master) master.checked = checked;
}
</script>
@endpush
