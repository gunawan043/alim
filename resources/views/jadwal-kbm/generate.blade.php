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
    Jadwal sekolah disusun dari <strong>penugasan mengajar</strong> (SK guru: guru × mapel × rombel × JP)
    untuk seluruh rombel sekaligus, dengan validasi bentrok guru/rombel.
</p>

@if(! $selectedAy)
    <div class="alert alert-danger">
        <i class="ri-error-warning-line me-1"></i>
        Tahun ajaran tidak ditemukan. Buat/aktifkan tahun ajaran terlebih dahulu sebelum generate jadwal.
    </div>
@endif

{{-- Pemilih Tahun Ajaran (mengikuti penugasan yang sudah ada) --}}
@if($selectedAy)
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('user.jadwal-kbm.generate', ['userId' => $userId]) }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label mb-1 small text-muted">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedAy->id === $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }}{{ $ay->is_active ? ' (aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small text-muted">Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted small">
                        <i class="ri-information-line me-1"></i>
                        Jadwal dibuat untuk <strong>seluruh mapel &amp; rombel</strong> — semua rombel tercentang otomatis.
                    </span>
                </div>
            </form>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <i class="ri-error-warning-line me-1"></i><strong>Generate gagal:</strong>
        <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('user.jadwal-kbm.generate.store', ['userId' => $userId]) }}" id="generateForm">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $selectedAy?->id }}">
            <input type="hidden" name="semester" value="{{ $semester }}">

            <div class="alert alert-light border small mb-3">
                <i class="ri-calendar-2-line me-1"></i>
                Menyusun untuk <strong>{{ $selectedAy->name ?? '—' }}</strong> · Semester <strong>{{ ucfirst($semester) }}</strong>
                — ubah lewat pemilih di atas.
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

            @if(($readyCount ?? 0) === 0)
                <div class="alert alert-warning small">
                    <i class="ri-error-warning-line me-1"></i>
                    Belum ada penugasan mengajar pada TA ini. Isi dulu di
                    <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}" class="alert-link">Plotting Guru Mengajar</a>
                    atau
                    <a href="{{ route('user.teaching-assignments.bulk-create', ['userId' => $userId]) }}" class="alert-link">Input Massal</a>.
                </div>
            @else
                <div class="alert alert-success small">
                    <i class="ri-checkbox-circle-line me-1"></i>
                    {{ $readyCount }} rombel siap digenerate — seluruh rombel terpilih otomatis.
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div>
                    <label class="form-label mb-0 fw-semibold">Rombongan Belajar</label>
                    <span id="sgSelectedCount" class="badge bg-primary-subtle text-primary ms-2">0 terpilih</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllSG(true)">Pilih Semua</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllSG(false)">Kosongkan</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:42px"><input type="checkbox" id="selectAllCb" onchange="selectAllSG(this.checked)" checked></th>
                            <th>Rombel</th>
                            <th>Tingkat</th>
                            <th>Wali Kelas</th>
                            <th class="text-center">Mapel (Penugasan)</th>
                            <th class="text-center">Total JP (Penugasan)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studyGroups as $sg)
                            @php $summary = $assignmentSummary[$sg->id] ?? null; @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="study_group_ids[]" value="{{ $sg->id }}" class="sg-cb" checked>
                                </td>
                                <td class="fw-medium">{{ $sg->full_name ?? $sg->name }}</td>
                                <td>{{ $sg->gradeLevel->name ?? '-' }}</td>
                                <td>{{ $sg->homeroomTeacher?->name ?? '-' }}</td>
                                <td class="text-center">
                                    @if($summary)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $summary->total_subjects }} mapel</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary" title="Sumber jadwal adalah penugasan mengajar (SK/pembagian tugas)">Belum ada penugasan</span>
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

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" name="overwrite" value="1" id="overwrite">
                        <label class="form-check-label" for="overwrite">Timpa jadwal yang sudah ada</label>
                    </div>
                    <button type="submit" class="btn btn-primary" {{ (! $selectedAy || $studyGroups->isEmpty()) ? 'disabled' : '' }}>
                        <i class="ri-magic-line me-1"></i>Generate Jadwal Sekolah
                    </button>
                </div>
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
function updateSgCount() {
    const total = document.querySelectorAll('.sg-cb').length;
    const checked = document.querySelectorAll('.sg-cb:checked').length;
    const el = document.getElementById('sgSelectedCount');
    if (el) el.textContent = checked + ' terpilih dari ' + total + ' rombel';
}

function selectAllSG(checked) {
    document.querySelectorAll('.sg-cb').forEach(function (cb) { cb.checked = checked; });
    const master = document.getElementById('selectAllCb');
    if (master) master.checked = checked;
    updateSgCount();
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sg-cb').forEach(function (cb) { cb.addEventListener('change', updateSgCount); });
    updateSgCount();
});
</script>
@endpush
