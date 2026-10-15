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
                                        <span class="badge bg-success-subtle text-success" style="font-size:0.7rem;padding:4px 10px">Hadir <span id="absen-count-hadir">0</span></span>
                                        <span class="badge bg-warning-subtle text-warning" style="font-size:0.7rem;padding:4px 10px">Terlambat <span id="absen-count-terlambat">0</span></span>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.7rem;padding:4px 10px">Sakit <span id="absen-count-sakit">0</span></span>
                                        <span class="badge bg-primary-subtle text-primary" style="font-size:0.7rem;padding:4px 10px">Izin <span id="absen-count-izin">0</span></span>
                                        <span class="badge bg-danger-subtle text-danger" style="font-size:0.7rem;padding:4px 10px">Alpa <span id="absen-count-alpa">0</span></span>
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
                    @if($canFilterRombel ?? false)
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
                    @else
                        {{-- Wali kelas: langsung ke kelasnya tanpa filter --}}
                        <input type="hidden" name="study_group_id" id="study_group_select" value="{{ $selectedStudyGroupId }}">
                    @endif
                    <div class="col-md-{{ ($canFilterRombel ?? false) ? 2 : 4 }}">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="attendance_date" class="form-control form-control-sm"
                            value="{{ $selectedDate->toDateString() }}" max="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-{{ ($canFilterRombel ?? false) ? 2 : 4 }}">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select form-select-sm">
                            <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-{{ ($canFilterRombel ?? false) ? 2 : 4 }}">
                        <label class="form-label">Tampilan</label>
                        <select name="mode" class="form-select form-select-sm" id="mode_select">
                            <option value="dropdown" {{ $inputMode == 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                            <option value="radio" {{ $inputMode == 'radio' ? 'selected' : '' }}>Radio Button</option>
                        </select>
                    </div>
                    @if($canFilterRombel ?? false)
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="btn-load-students" class="btn btn-primary btn-sm w-100">
                            <i class="ri-search-line me-1"></i> Tampilkan Siswa
                        </button>
                    </div>
                    @endif
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
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="text-muted small d-none d-lg-inline"><i class="ri-flashlight-line me-1"></i>Aksi Cepat:</span>
                                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="setAllStatus('hadir')">
                                        <i class="ri-checkbox-circle-line me-1"></i>Semua Hadir
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-warning" onclick="setAllStatus('terlambat')">
                                        <i class="ri-timer-line me-1"></i>Semua Telat
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-warning" onclick="setAllStatus('sakit')">
                                        <i class="ri-hospital-line me-1"></i>Semua Sakit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-success" onclick="setAllStatus('izin')">
                                        <i class="ri-file-text-line me-1"></i>Semua Izin
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-danger" onclick="setAllStatus('alpa')">
                                        <i class="ri-close-circle-line me-1"></i>Semua Alfa
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Pencarian & filter (seperti wizard 1) --}}
                    <div class="card-header border-bottom-dashed py-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div>
                                <input type="text" id="absenSearchInput" class="form-control form-control-sm"
                                       placeholder="Cari nama santri..." style="min-width:220px;"
                                       oninput="filterAbsenRows()">
                            </div>
                            <div>
                                <select id="absenStatusFilter" class="form-select form-select-sm"
                                        style="min-width:170px;" onchange="filterAbsenRows()">
                                    <option value="">Semua Status</option>
                                    <option value="hadir">Hadir</option>
                                    <option value="terlambat">Terlambat</option>
                                    <option value="sakit">Sakit</option>
                                    <option value="izin">Izin</option>
                                    <option value="alpa">Alpa</option>
                                </select>
                            </div>
                            <span class="text-muted small ms-auto">
                                <span id="absenVisibleCount">{{ $students->count() }}</span> / {{ $students->count() }} santri
                            </span>
                        </div>
                    </div>

                    {{-- Table (persis gaya tabel wizard 1 Buku Nilai) --}}
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:50px">No</th>
                                        <th style="width:110px">NIS</th>
                                        <th>Nama</th>
                                        <th style="">Presensi Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($students as $idx => $student)
                                        @php
                                            $existing = $existingRecords->get($student->id);
                                            $cur = $existing?->status ?? 'hadir';
                                            $showNotes = $cur !== 'hadir';
                                            $statusColors = ['hadir' => 'primary', 'terlambat' => 'warning', 'sakit' => 'warning', 'izin' => 'success', 'alpa' => 'danger'];
                                        @endphp
                                        <tr data-name="{{ strtolower($student->name) }}" data-status="{{ $cur }}">
                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                            <td class="text-muted text-center"><code>{{ $student->nis ?? '-' }}</code></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-xs">
                                                        <span class="avatar-title rounded-circle bg-primary text-white">
                                                            {{ strtoupper(mb_substr(($__w = preg_split('/\s+/', trim((string) $student->name)))[0] ?? '', 0, 1) . (count($__w) > 1 ? mb_substr($__w[count($__w) - 1], 0, 1) : mb_substr($__w[0] ?? '', 1, 1))) }}
                                                        </span>
                                                    </div>
                                                    <span class="">{{ $student->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="hidden" name="records[{{ $idx }}][student_id]" value="{{ $student->id }}">
                                                @if($inputMode === 'radio')
                                                <div class="status-group d-inline-flex gap-2" data-idx="{{ $idx }}">
                                                    @foreach(['hadir' => 'H', 'terlambat' => 'T', 'sakit' => 'S', 'izin' => 'I', 'alpa' => 'A'] as $status => $letter)
                                                        @php $statusColor = $statusColors[$status]; @endphp
                                                        <button type="button"
                                                                class="status-btn btn btn-sm btn-icon rounded-circle {{ $cur === $status ? 'btn-'.$statusColor.' on' : 'btn-soft-'.$statusColor }}"
                                                                data-s="{{ $status }}" data-color="{{ $statusColor }}"
                                                                title="{{ ucfirst($status) }}"
                                                                onclick="setRowStatus({{ $idx }}, '{{ $status }}', this)">{{ $letter }}</button>
                                                    @endforeach
                                                </div>
                                                <input type="hidden" name="records[{{ $idx }}][status]" id="status-input-{{ $idx }}" value="{{ $cur }}">
                                                @else
                                                <select name="records[{{ $idx }}][status]"
                                                    class="form-select form-select-sm"
                                                    style="min-width:140px"
                                                    required>
                                                    <option value="hadir"     {{ $cur == 'hadir'     ? 'selected' : '' }}>Hadir</option>
                                                    <option value="terlambat" {{ $cur == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                                                    <option value="sakit"     {{ $cur == 'sakit'     ? 'selected' : '' }}>Sakit</option>
                                                    <option value="izin"      {{ $cur == 'izin'      ? 'selected' : '' }}>Izin</option>
                                                    <option value="alpa"      {{ $cur == 'alpa'      ? 'selected' : '' }}>Alpa</option>
                                                </select>
                                                @endif

                                                {{-- Keterangan/Alasan — muncul otomatis saat status bukan Hadir --}}
                                                <div class="note-inline mt-2">
                                                    <input type="text"
                                                        name="records[{{ $idx }}][notes]"
                                                        id="notes-{{ $idx }}"
                                                        class="form-control form-control-sm{{ $showNotes ? '' : ' d-none' }}"
                                                        placeholder="Keterangan/Alasan ({{ $cur }})"
                                                        value="{{ $existing?->notes ?? '' }}"
                                                        maxlength="255">
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada santri di rombel ini.</td>
                                        </tr>
                                    @endforelse
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
                                    <span class="text-muted ms-auto"><em>Keterangan muncul otomatis saat status bukan Hadir</em></span>
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
                            <span id="absen-autosave-indicator" class="badge bg-light text-muted border me-2">Menunggu…</span>
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

    function loadStudents() {
        const sgEl = document.getElementById('study_group_select');
        const sgId = sgEl ? sgEl.value : '';
        if (!sgId) { alert('Silakan pilih rombel terlebih dahulu.'); return; }
        const url = new URL(window.location.href);
        url.searchParams.set('study_group_id', sgId);
        url.searchParams.set('date', document.querySelector('input[name="attendance_date"]').value);
        url.searchParams.set('semester', document.querySelector('select[name="semester"]').value);
        url.searchParams.set('mode', document.getElementById('mode_select').value);
        window.location.href = url.toString();
    }

    const btnLoad = document.getElementById('btn-load-students');
    if (btnLoad) btnLoad.addEventListener('click', loadStudents);

    @if(!($canFilterRombel ?? false))
    // Wali kelas: tanpa filter — ubah tanggal/semester/tampilan langsung memuat ulang.
    ['input[name="attendance_date"]', 'select[name="semester"]', '#mode_select'].forEach(function (sel) {
        var el = document.querySelector(sel);
        if (el) el.addEventListener('change', loadStudents);
    });
    @endif

    const selectColors = {
        'hadir':    'success',
        'terlambat':'warning',
        'sakit':    'secondary',
        'izin':     'primary',
        'alpa':     'danger',
    };

    // ── Rekap live ──
    function updateRecap() {
        var counts = { hadir: 0, terlambat: 0, sakit: 0, izin: 0, alpa: 0 };

        document.querySelectorAll('.status-group').forEach(function (group) {
            var idx = group.getAttribute('data-idx');
            var input = document.getElementById('status-input-' + idx);
            var v = input ? input.value : 'hadir';
            if (counts[v] !== undefined) counts[v]++;
        });

        document.querySelectorAll('select[name$="][status]"]').forEach(function (sel) {
            if (counts[sel.value] !== undefined) counts[sel.value]++;
        });

        Object.keys(counts).forEach(function (k) {
            var el = document.getElementById('absen-count-' + k);
            if (el) el.textContent = counts[k];
        });
    }

    // ── Autosave ──
    var ABEN_COLORS = { hadir: 'primary', terlambat: 'warning', sakit: 'warning', izin: 'success', alpa: 'danger' };
    var absenForm = document.getElementById('absensi-form');
    var absenUrl = absenForm ? absenForm.getAttribute('action') : '';
    var absenToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    var absenBadge = document.getElementById('absen-autosave-indicator');
    var absenTimer = null;

    function setAbsenBadge(cls, text) {
        if (!absenBadge) return;
        absenBadge.className = 'badge me-2 ' + cls;
        absenBadge.textContent = text;
    }

    function buildAbsenFd(idx) {
        var fd = new FormData();
        fd.append('_token', absenToken);
        var sg = document.getElementById('study_group_select');
        fd.append('study_group_id', sg ? sg.value : '');
        fd.append('attendance_date', document.querySelector('input[name="attendance_date"]').value);
        fd.append('semester', document.querySelector('select[name="semester"]').value);
        var mode = document.getElementById('mode_select');
        fd.append('mode', mode ? mode.value : 'radio');

        function addRow(i) {
            var sidInput = document.querySelector('input[name="records[' + i + '][student_id]"]');
            var st = document.getElementById('status-input-' + i) || document.querySelector('select[name="records[' + i + '][status]"]');
            var nt = document.getElementById('notes-' + i);
            if (sidInput) fd.append('records[' + i + '][student_id]', sidInput.value);
            if (st) fd.append('records[' + i + '][status]', st.value);
            if (nt) fd.append('records[' + i + '][notes]', nt.value || '');
        }

        if (idx === null) {
            var seen = {};
            document.querySelectorAll('.status-group').forEach(function (g) {
                var i = g.getAttribute('data-idx');
                seen[i] = true;
                addRow(i);
            });
            document.querySelectorAll('select[name$="][status]"]').forEach(function (sel) {
                var m = sel.name.match(/records\[(\d+)\]\[status\]/);
                if (m && !seen[m[1]]) { seen[m[1]] = true; addRow(m[1]); }
            });
        } else {
            addRow(idx);
        }

        return fd;
    }

    function sendAbsen(fd) {
        fetch(absenUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': absenToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: fd,
        })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function (d) {
            if (d && d.saved) {
                var t = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                setAbsenBadge('bg-success-subtle text-success border', 'Tersimpan ' + t);
                setTimeout(function () { setAbsenBadge('bg-light text-muted border', 'Menunggu…'); }, 2500);
            } else {
                setAbsenBadge('bg-danger-subtle text-danger border', (d && d.message) ? d.message : 'Gagal menyimpan');
            }
        })
        .catch(function () { setAbsenBadge('bg-danger-subtle text-danger border', 'Gagal menyimpan'); });
    }

    function scheduleAbsen(idx) {
        clearTimeout(absenTimer);
        setAbsenBadge('bg-light text-muted border', 'Menyimpan…');
        absenTimer = setTimeout(function () { sendAbsen(buildAbsenFd(idx)); }, 900);
    }

    function saveAllAbsen() {
        clearTimeout(absenTimer);
        setAbsenBadge('bg-light text-muted border', 'Menyimpan semua…');
        sendAbsen(buildAbsenFd(null));
    }

    // ── Pencarian & filter status (seperti wizard 1) ──
    window.filterAbsenRows = function () {
        var q = (document.getElementById('absenSearchInput')?.value || '').toLowerCase().trim();
        var st = document.getElementById('absenStatusFilter')?.value || '';
        var visible = 0;

        document.querySelectorAll('#students-panel table tbody tr[data-name]').forEach(function (row) {
            var name = (row.getAttribute('data-name') || '').toLowerCase();
            var status = row.getAttribute('data-status') || 'hadir';
            var match = name.indexOf(q) !== -1 && (st === '' || status === st);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        var el = document.getElementById('absenVisibleCount');
        if (el) el.textContent = visible;
    };

    function paintGroup(group, status) {
        group.querySelectorAll('.status-btn').forEach(function (b) {
            var c = b.getAttribute('data-color') || 'primary';
            b.classList.remove('on');
            b.classList.remove('btn-' + c);
            b.classList.add('btn-soft-' + c);
        });

        var btn = group.querySelector('.status-btn[data-s="' + status + '"]');
        if (!btn) return;

        var color = btn.getAttribute('data-color') || ABEN_COLORS[status] || 'primary';
        btn.classList.remove('btn-soft-' + color);
        btn.classList.add('btn-' + color, 'on');
    }

    // Tombol huruf per santri (mode radio) — kelas Velzon
    window.setRowStatus = function (idx, status, btn) {
        var group = btn.closest('.status-group');
        if (group) paintGroup(group, status);

        var input = document.getElementById('status-input-' + idx);
        if (input) input.value = status;

        var notes = document.getElementById('notes-' + idx);
        if (notes) {
            if (status === 'hadir') notes.classList.add('d-none');
            else { notes.classList.remove('d-none'); notes.focus(); }
        }

        var tr = btn.closest('tr');
        if (tr) tr.setAttribute('data-status', status);

        updateRecap();
        filterAbsenRows();
        scheduleAbsen(idx);
    };

    // Dropdown mode: warna border + autosave + rekap
    document.querySelectorAll('select[name$="][status]"]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            sel.className = 'form-select form-select-sm border-' + (selectColors[sel.value] || 'secondary');
            var m = sel.name.match(/records\[(\d+)\]\[status\]/);
            if (m) {
                var notes = document.getElementById('notes-' + m[1]);
                if (notes) notes.classList.toggle('d-none', sel.value === 'hadir');
                var tr = sel.closest('tr');
                if (tr) tr.setAttribute('data-status', sel.value);
                updateRecap();
                filterAbsenRows();
                scheduleAbsen(m[1]);
            }
        });
        sel.className = 'form-select form-select-sm border-' + (selectColors[sel.value] || 'secondary');
    });

    // Catatan diketik → autosave
    document.querySelectorAll('input[name$="][notes]"]').forEach(function (inp) {
        inp.addEventListener('input', function () {
            var m = inp.name.match(/records\[(\d+)\]\[notes\]/);
            if (m) scheduleAbsen(m[1]);
        });
    });

    // Aksi cepat: semua status sekaligus + simpan borongan
    window.setAllStatus = function (status) {
        document.querySelectorAll('.status-group').forEach(function (group) {
            var idx = group.getAttribute('data-idx');
            paintGroup(group, status);

            var input = document.getElementById('status-input-' + idx);
            if (input) input.value = status;

            var notes = document.getElementById('notes-' + idx);
            if (notes) notes.classList.toggle('d-none', status === 'hadir');

            var tr = group.closest('tr');
            if (tr) tr.setAttribute('data-status', status);
        });

        document.querySelectorAll('select[name$="][status]"]').forEach(function (sel) {
            sel.value = status;
            sel.className = 'form-select form-select-sm border-' + (selectColors[status] || 'secondary');
            var m = sel.name.match(/records\[(\d+)\]\[status\]/);
            if (m) {
                var notes = document.getElementById('notes-' + m[1]);
                if (notes) notes.classList.toggle('d-none', status === 'hadir');
            }
        });

        updateRecap();
        filterAbsenRows();
        saveAllAbsen();
    };

    // Reset → semua hadir
    document.getElementById('btn-reset')?.addEventListener('click', function () {
        window.setAllStatus('hadir');
    });

    updateRecap();
    filterAbsenRows();
});
</script>
@endpush
