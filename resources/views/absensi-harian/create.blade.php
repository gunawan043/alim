@extends('layouts.master')
@section('title') Input Absensi Harian @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">Absensi Harian</a> @endslot
        @slot('title') Input Absensi Harian</span>
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('user.absensi.harian.store', ['userId' => $userId]) }}" method="POST" id="absensi-form">
        @csrf

        {{-- ══ Info Card — rombel & stats ═════════════════════════════ --}}
        @if($selectedStudyGroupId && $students->isNotEmpty())
            @php
                $rombel = $studyGroups->firstWhere('id', $selectedStudyGroupId);
                $totalStudents = $students->count();
                $stats = [
                    'hadir' => $existingRecords->where('status','hadir')->count(),
                    'terlambat' => $existingRecords->where('status','terlambat')->count(),
                    'sakit' => $existingRecords->where('status','sakit')->count(),
                    'izin' => $existingRecords->where('status','izin')->count(),
                    'alpa' => $existingRecords->where('status','alpa')->count(),
                ];
            @endphp
            <div class="row">
                {{-- Info utama --}}
                <div class="col-md-6">
                    <div class="card h-80" style="border-left:4px solid #405189;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                                     style="width:48px;height:48px;background:#dbeafe">
                                    <i class="ri-group-line text-primary" style="font-size:1.3rem"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h5 class="mb-0">{{ $rombel->full_name ?? '-' }}</h5>
                                        @if($rombel->homeroomTeacher)
                                            <span class="badge bg-light text-muted border" style="font-size:0.68rem">
                                                <i class="ri-user-star-line me-1"></i>{{ $rombel->homeroomTeacher->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-3 text-muted flex-wrap" style="font-size:0.78rem">
                                        <span><i class="ri-calendar-line me-1"></i>TA {{ $activeYear->name ?? '-' }}</span>
                                        <span><i class="ri-time-line me-1"></i>Semester {{ ucfirst($selectedSemester) }}</span>
                                        <span>
                                            <i class="ri-calendar-todo-line me-1"></i>
                                            {{ $selectedDate->locale('id')->dayName }}, {{ $selectedDate->locale('id')->format('d F Y') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Counter --}}
                <div class="col-md-6">
                    <div class="card h-80" style="border-left:4px solid #405189;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                                     style="width:48px;height:48px;background:#d0e8ff">
                                    <i class="ri-user-follow-line text-primary" style="font-size:1.3rem"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Total Siswa :{{ $totalStudents }}</h5>
                                    <div class="d-flex gap-2 flex-wrap mt-2">
                                        <span class="badge bg-success-subtle text-success" style="font-size:0.7rem;padding:4px 10px">Hadir {{ $stats['hadir'] }}</span>
                                        <span class="badge bg-warning-subtle text-warning" style="font-size:0.7rem;padding:4px 10px">Terlambat {{ $stats['terlambat'] }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.7rem;padding:4px 10px">Sakit {{ $stats['sakit'] }}</span>
                                        <span class="badge bg-primary-subtle text-primary" style="font-size:0.7rem;padding:4px 10px">Izin {{ $stats['izin'] }}</span>
                                        <span class="badge bg-danger-subtle text-danger" style="font-size:0.7rem;padding:4px 10px">Alpa {{ $stats['alpa'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══ Filter Card ════════════════════════════════════════════ --}}
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed">
                <div class="row align-items-center g-3">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">
                            <i class="ri-filter-line me-1"></i>Filter
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Rombel</label>
                        <select name="study_group_id" id="study_group_select" class="form-select form-select-sm">
                            <option value="">— Pilih Rombel —</option>
                            @forelse($studyGroups as $sg)
                                <option value="{{ $sg->id }}"
                                    {{ $selectedStudyGroupId == $sg->id ? 'selected' : '' }}>
                                    {{ $sg->full_name }}
                                </option>
                            @empty
                                <option value="" disabled>Tidak ada rombel aktif</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="attendance_date" class="form-control form-control-sm"
                            value="{{ $selectedDate->toDateString() }}" max="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select form-select-sm">
                            <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tampilan</label>
                        <select name="mode" class="form-select form-select-sm" id="mode_select">
                            <option value="dropdown" {{ $inputMode == 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                            <option value="radio" {{ $inputMode == 'radio' ? 'selected' : '' }}>Radio Button</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="btn-load-students" class="btn btn-primary btn-sm w-100">
                            <i class="ri-search-line me-1"></i> Tampilkan Siswa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Table Card ═════════════════════════════════════════════ --}}
        <div id="students-panel">
            @if(!$selectedStudyGroupId)
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        <i class="ri-arrow-down-s-line d-block mb-2" style="font-size:2.5rem;color:#e2e8f0"></i>
                        <strong>Pilih rombel di atas, lalu klik <strong>Tampilkan Siswa</strong></strong>
                    </div>
                </div>
            @elseif($students->isEmpty())
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        <i class="ri-user-search-line d-block mb-2" style="font-size:2.5rem;color:#e2e8f0"></i>
                        <strong>Belum ada siswa di rombel ini</strong><br>
                        <small>Pastikan rombel memiliki siswa aktif.</small>
                    </div>
                </div>
            @else
                <div class="card">
                    {{-- Toolbar --}}
                    <div class="card-header border-bottom-dashed py-2">
                        <div class="row align-items-center g-3">
                            <div class="col-sm">
                                <h5 class="card-title mb-0">
                                    @if($rombel->homeroomTeacher)
                                        <div class="text-muted" style="font-size:0.85rem">
                                            <i class="ri-user-star-line me-1"></i>{{ $rombel->homeroomTeacher->name }}
                                        </div>
                                    @endif
                                </h5>
                            </div>
                            <div class="col-sm-auto">
                                @if($inputMode === 'radio')
                                <div class="d-flex gap-1">
                                    @foreach(['hadir','terlambat','sakit','izin','alpa'] as $s)
                                        <button type="button" class="btn btn-sm btn-outline-{{ ['hadir'=>'success','terlambat'=>'warning','sakit'=>'secondary','izin'=>'primary','alpa'=>'danger'][$s] }}"
                                            onclick="setAllStatus('{{ $s }}')"
                                            style="font-size:0.72rem;padding:2px 8px">
                                            {{ strtoupper(substr($s,0,1)) }}
                                        </button>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:60vh">
                            <table class="table table-bordered table-hover align-middle mb-0" style="font-size:0.82rem">
                                <thead class="table-light text-center" style="position:sticky;top:0;z-index:5;background:#f3f4f6">
                                    <tr>
                                        <th class="text-center" style="width:40px">No</th>
                                        <th style="width:90px">NIS</th>
                                        <th>Nama Lengkap</th>
                                        <th class="text-center" style="width:50px">JK</th>
                                        <th class="text-center" style="min-width:320px">
                                            Status Kehadiran
                                        </th>
                                        <th style="min-width:150px">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $idx => $student)
                                        @php
                                            $existing = $existingRecords->get($student->id);
                                        @endphp
                                        <tr class="{{ $existing ? 'table-success' : '' }}">
                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                            <td><code>{{ $student->nis ?? '-' }}</code></td>
                                            <td>
                                                <div class="fw-semibold">{{ $student->name }}</div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $student->gender === 'L' ? 'info' : 'danger' }}"
                                                    style="font-size:0.72rem">{{ $student->gender }}</span>
                                            </td>
                                            <td>
                                                <input type="hidden" name="records[{{ $idx }}][student_id]" value="{{ $student->id }}">
                                                @if($inputMode === 'radio')
                                                <div class="d-flex justify-content-center gap-0">
                                                    @foreach(['hadir','terlambat','sakit','izin','alpa'] as $status)
                                                        <div class="text-center border-end px-2 py-1 status-cell"
                                                            id="cell-{{ $student->id }}-{{ $status }}"
                                                            style="min-width:60px">
                                                            <div class="mb-1" style="font-size:0.58rem;font-weight:600;color:#64748b;text-transform:uppercase">
                                                                {{ $status === 'terlambat' ? 'Telat' : ($status === 'alpa' ? 'Alpa' : ucfirst($status)) }}
                                                            </div>
                                                            <div class="d-flex justify-content-center">
                                                                <input class="form-check-input status-radio"
                                                                    type="radio"
                                                                    name="records[{{ $idx }}][status]"
                                                                    value="{{ $status }}"
                                                                    {{ ($existing?->status ?? 'hadir') == $status ? 'checked' : '' }}>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @else
                                                <select name="records[{{ $idx }}][status]"
                                                    class="form-select form-select-sm"
                                                    style="min-width:140px"
                                                    required>
                                                    <option value="hadir"      {{ ($existing?->status ?? 'hadir') == 'hadir'      ? 'selected' : '' }}>Hadir</option>
                                                    <option value="terlambat"  {{ ($existing?->status ?? '') == 'terlambat'   ? 'selected' : '' }}>Terlambat</option>
                                                    <option value="sakit"      {{ ($existing?->status ?? '') == 'sakit'       ? 'selected' : '' }}>Sakit</option>
                                                    <option value="izin"      {{ ($existing?->status ?? '') == 'izin'       ? 'selected' : '' }}>Izin</option>
                                                    <option value="alpa"       {{ ($existing?->status ?? '') == 'alpa'       ? 'selected' : '' }}>Alpa</option>
                                                </select>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="records[{{ $idx }}][notes]"
                                                    class="form-control form-control-sm"
                                                    placeholder="Keterangan..."
                                                    value="{{ $existing?->notes ?? '' }}"
                                                    maxlength="255">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Legend --}}
                        <div class="card-footer border-top bg-light py-2">
                            <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:0.75rem">
                                <span class="text-muted">Legend:</span>
                                <span class="badge bg-success-subtle text-success">H = Hadir</span>
                                <span class="badge bg-warning-subtle text-warning">T = Terlambat</span>
                                <span class="badge bg-secondary-subtle text-secondary">S = Sakit</span>
                                <span class="badge bg-primary-subtle text-primary">I = Izin</span>
                                <span class="badge bg-danger-subtle text-danger">A = Alpa</span>
                                @if($existingRecords->count() > 0)
                                    <span class="text-muted ms-auto"><em>Baris hijau = sudah diinput</em></span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Sticky Action Bar --}}
                    <div class="card-footer border-top py-2 px-3"
                         style="position:sticky;bottom:0;background:#fff;box-shadow:0 -2px 8px rgba(0,0,0,0.06);z-index:4">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}"
                                class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <div class="text-muted" style="font-size:0.75rem">
                                <i class="ri-information-line me-1"></i> Scroll ke bawah untuk lihat semua siswa
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="ri-save-line me-1"></i> Simpan Absensi
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    document.getElementById('btn-load-students').addEventListener('click', function() {
        const sgId = document.getElementById('study_group_select').value;
        if (!sgId) { alert('Silakan pilih rombel terlebih dahulu.'); return; }
        const form = document.getElementById('absensi-form');
        const url = new URL(window.location.href);
        url.searchParams.set('study_group_id', sgId);
        url.searchParams.set('date', document.querySelector('input[name="attendance_date"]').value);
        url.searchParams.set('semester', document.querySelector('select[name="semester"]').value);
        url.searchParams.set('mode', document.getElementById('mode_select').value);
        window.location.href = url.toString();
    });

    const cellColors = {
        'hadir':    '#d1fae5',
        'terlambat':'#fef9c3',
        'sakit':    '#f3f4f6',
        'izin':     '#dbeafe',
        'alpa':     '#fee2e2',
    };
    const selectColors = {
        'hadir':    'success',
        'terlambat':'warning',
        'sakit':    'secondary',
        'izin':     'primary',
        'alpa':     'danger',
    };

    // Radio color
    function applyColor(radio) {
        const cells = document.querySelectorAll('.status-cell');
        cells.forEach(function(c) { c.style.background = ''; });
        if (radio.checked) {
            const cell = radio.closest('.status-cell');
            if (cell) cell.style.background = cellColors[radio.value] || '';
        }
    }

    document.querySelectorAll('.status-radio').forEach(function(radio) {
        radio.addEventListener('change', function() { applyColor(radio); });
        if (radio.checked) applyColor(radio);
    });

    // Dropdown color
    document.querySelectorAll('select[name$="][status]"]').forEach(function(sel) {
        sel.addEventListener('change', function() {
            sel.className = 'form-select form-select-sm border-' + (selectColors[sel.value] || 'secondary');
        });
        sel.className = 'form-select form-select-sm border-' + (selectColors[sel.value] || 'secondary');
    });

    document.getElementById('btn-reset')?.addEventListener('click', function() {
        document.querySelectorAll('.status-radio').forEach(function(r) {
            if (r.value === 'hadir') r.checked = true;
        });
        document.querySelectorAll('.status-cell').forEach(function(c) { c.style.background = ''; });
        document.querySelectorAll('select[name$="][status]"]').forEach(function(sel) {
            sel.value = 'hadir';
            sel.className = 'form-select form-select-sm border-success';
        });
    });

    window.setAllStatus = function(status) {
        document.querySelectorAll('.status-radio').forEach(function(radio) {
            radio.checked = (radio.value === status);
        });
        document.querySelectorAll('.status-cell').forEach(function(c) {
            c.style.background = c.id.endsWith('-' + status) ? cellColors[status] : '';
        });
        document.querySelectorAll('select[name$="][status]"]').forEach(function(sel) {
            sel.value = status;
            sel.className = 'form-select form-select-sm border-' + (selectColors[status] || 'secondary');
        });
    };
});
</script>
@endpush
