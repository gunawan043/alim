@extends('layouts.master')
@section('title', 'Absensi Halaqah')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $baseParams = $group ? [
            'userId' => $userId,
            'group_id' => $group->id,
            'date' => $dateString,
            'session_type' => $sessionType,
            'month' => $month,
        ] : ['userId' => $userId];
        $statusColors = ['hadir' => 'primary', 'izin' => 'success', 'sakit' => 'warning', 'alpa' => 'danger'];
        $sessionLabels = ['setoran' => 'Setoran', 'murajaah' => 'Murajaah', 'tasmi' => 'Tasmi’', 'evaluasi' => 'Evaluasi', 'tikror' => 'Tikror'];
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb')
        @slot('li_1') Tahfidz @endslot
        @slot('li_2') Buku Kerja @endslot
        @slot('title') Absensi Halaqah @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa data absensi.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-article-line me-1 text-primary"></i>Buku Kerja Tahfidz — Absensi Halaqah</h5>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if($group)
                            <span class="badge bg-primary-subtle text-primary"><i class="ri-team-line me-1"></i>{{ $group->name }}</span>
                            <span class="badge bg-secondary-subtle text-secondary"><i class="ri-building-line me-1"></i>Kelas {{ $group->studyGroup?->name ?? '—' }}</span>
                            <span class="badge bg-dark-subtle text-dark"><i class="ri-school-line me-1"></i>{{ $group->school?->name ?? '—' }}</span>
                            <span class="badge bg-warning-subtle text-warning"><i class="ri-calendar-2-line me-1"></i>{{ $academicYear?->name ?? 'Tahun aktif' }}</span>
                            <span class="badge bg-info-subtle text-info">{{ ucfirst($group->gender) }}</span>
                        @else
                            <span class="text-muted small">Pilih halaqah untuk membuka buku absensi.</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-auto d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('user.tahfidz.workspace.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i>Buku Kerja
                    </a>
                    @if($groups->isNotEmpty())
                        <form method="GET" action="{{ route('user.tahfidz.attendance.index', ['userId' => $userId]) }}">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <input type="hidden" name="date" value="{{ $dateString }}">
                            <input type="hidden" name="session_type" value="{{ $sessionType }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <select name="group_id" class="form-select form-select-sm" style="min-width:220px" onchange="this.form.submit()">
                                @foreach($groups as $availableGroup)
                                    <option value="{{ $availableGroup->id }}" @selected($group?->id === $availableGroup->id)>
                                        {{ $availableGroup->name }}{{ $availableGroup->studyGroup ? ' — '.$availableGroup->studyGroup->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(! $group)
        <div class="alert alert-info"><i class="ri-information-line me-1"></i>Belum ada halaqah yang dapat dibuka. Hubungi koordinator Tahfidz sekolah.</div>
    @else
        <div class="card">
            <div class="card-header border-bottom-dashed py-2">
                <div class="d-flex gap-1 flex-wrap">
                    <a href="{{ route('user.tahfidz.attendance.index', array_merge($baseParams, ['tab' => 'input'])) }}" class="btn {{ $tab === 'input' ? 'btn-primary' : 'btn-outline-secondary' }}">Absensi Halaqah</a>
                    <a href="{{ route('user.tahfidz.attendance.index', array_merge($baseParams, ['tab' => 'riwayat'])) }}" class="btn {{ $tab === 'riwayat' ? 'btn-primary' : 'btn-outline-secondary' }}">Riwayat</a>
                    <a href="{{ route('user.tahfidz.attendance.index', array_merge($baseParams, ['tab' => 'rekap'])) }}" class="btn {{ $tab === 'rekap' ? 'btn-primary' : 'btn-outline-secondary' }}">Rekap Bulanan</a>
                    <a href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId, 'group_id' => $group->id]) }}" class="btn btn-outline-secondary ms-md-auto"><i class="ri-file-add-line me-1"></i>Setoran</a>
                    <a href="{{ route('user.tahfidz.mutabaah.index', ['userId' => $userId, 'group_id' => $group->id]) }}" class="btn btn-outline-secondary"><i class="ri-calendar-todo-line me-1"></i>Mutaba’ah</a>
                </div>
            </div>

            @if($tab === 'input')
                <div class="card-body pb-0">
                    <form method="GET" action="{{ route('user.tahfidz.attendance.index', ['userId' => $userId]) }}" class="row g-3 align-items-end mb-3" id="attendanceFilterForm">
                        <input type="hidden" name="tab" value="input">
                        <input type="hidden" name="group_id" value="{{ $group->id }}">
                        <div class="col-md-3">
                            <label class="form-label small"><i class="ri-calendar-event-line text-primary me-1"></i>Tanggal Pertemuan</label>
                            <input type="date" name="date" class="form-control form-control-sm" value="{{ $dateString }}" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small"><i class="ri-bookmark-line text-primary me-1"></i>Jenis Sesi</label>
                            <select name="session_type" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($sessionLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($sessionType === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small"><i class="ri-search-line text-primary me-1"></i>Cari Nama Santri</label>
                            <input type="text" class="form-control form-control-sm" id="searchStudentInput" placeholder="Ketik nama santri..." oninput="filterAttendance(this.value)">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="ri-filter-3-line text-primary me-1"></i>Status</label>
                            <select id="statusFilter" class="form-select form-select-sm" onchange="filterAttendance(document.getElementById('searchStudentInput').value)">
                                <option value="">Semua</option>
                                <option value="hadir">Hadir</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="alpa">Alpa</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <span class="text-muted small"><span id="studentCount">{{ $members->count() }}</span> santri</span>
                        </div>
                    </form>

                    @if($holiday)<div class="alert alert-warning py-2"><i class="ri-calendar-close-line me-1"></i><strong>{{ $holiday->event_name }}</strong> — tanggal ini termasuk {{ str_replace('_', ' ', $holiday->event_type) }}. Presensi tetap dapat diisi untuk kegiatan khusus.</div>@endif
                    @if($schedule)<div class="alert alert-info py-2"><i class="ri-time-line me-1"></i>Jadwal halaqah: <strong>{{ $schedule->day_name }}, {{ substr((string) $schedule->time_start, 0, 5) }}–{{ substr((string) $schedule->time_end, 0, 5) }}</strong> · {{ $schedule->room ?? 'ruang belum diisi' }}</div>@endif
                    @unless($canInput)<div class="alert alert-warning py-2"><i class="ri-lock-line me-1"></i>Anda hanya dapat melihat absensi halaqah ini.</div>@endunless
                </div>

                <form method="POST" action="{{ route('user.tahfidz.attendance.store', ['userId' => $userId]) }}" id="attendanceForm">
                    @csrf
                    <input type="hidden" name="tahfidz_group_id" value="{{ $group->id }}">
                    <input type="hidden" name="attendance_date" value="{{ $dateString }}">
                    <input type="hidden" name="session_type" value="{{ $sessionType }}">

                    <div class="d-flex flex-wrap align-items-center gap-2 mx-3 mb-3">
                        @if($canInput)
                            <span class="text-muted small"><i class="ri-flashlight-line me-1"></i>Aksi Cepat:</span>
                            @foreach(['hadir' => ['Hadir', 'ri-checkbox-circle-line', 'primary'], 'izin' => ['Izin', 'ri-file-text-line', 'success'], 'sakit' => ['Sakit', 'ri-hospital-line', 'warning'], 'alpa' => ['Alpa', 'ri-close-circle-line', 'danger']] as $status => [$label, $icon, $color])
                                <button type="button" class="btn btn-sm btn-soft-{{ $color }}" onclick="setAllAttendance('{{ $status }}')"><i class="{{ $icon }} me-1"></i>Semua {{ $label }}</button>
                            @endforeach
                        @endif
                        <span class="ms-auto d-flex flex-wrap gap-1 align-items-center" title="Rekapitulasi hari ini">
                            <span class="text-muted small me-1"><i class="ri-bar-chart-2-line me-1"></i>Rekap:</span>
                            <span class="badge bg-primary-subtle text-primary">H <span id="count-hadir">{{ $stats['hadir'] }}</span></span>
                            <span class="badge bg-success-subtle text-success">I <span id="count-izin">{{ $stats['izin'] }}</span></span>
                            <span class="badge bg-warning-subtle text-warning">S <span id="count-sakit">{{ $stats['sakit'] }}</span></span>
                            <span class="badge bg-danger-subtle text-danger">A <span id="count-alpa">{{ $stats['alpa'] }}</span></span>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0" id="attendanceList">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px">No</th>
                                    <th>NIS</th>
                                    <th>Nama Santri</th>
                                    <th>Presensi Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($members as $index => $member)
                                    @php
                                        $record = $attendance->get($member->student_id);
                                        $currentStatus = $record?->status ?? 'hadir';
                                    @endphp
                                    <tr class="attendance-row" data-sid="{{ $member->student_id }}" data-name="{{ strtolower($member->student?->name ?? '') }}" data-status="{{ $currentStatus }}">
                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td><code>{{ $member->student?->nis ?? '—' }}</code></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary text-white">{{ strtoupper(mb_substr($member->student?->name ?? '?', 0, 1)) }}</span>
                                                </div>
                                                <span class="fw-medium">{{ $member->student?->name ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="status-group d-inline-flex gap-2">
                                                @foreach(['hadir' => 'H', 'izin' => 'I', 'sakit' => 'S', 'alpa' => 'A'] as $status => $letter)
                                                    @php $color = $statusColors[$status]; @endphp
                                                    <button type="button"
                                                            class="status-btn btn btn-sm btn-icon rounded-circle {{ $currentStatus === $status ? 'btn-'.$color.' on' : 'btn-soft-'.$color }}"
                                                            data-s="{{ $status }}" data-color="{{ $color }}"
                                                            title="{{ ucfirst($status) }}"
                                                            onclick="setAttendanceStatus('{{ $member->student_id }}', '{{ $status }}', this)"
                                                            @disabled(! $canInput)>{{ $letter }}</button>
                                                @endforeach
                                            </div>

                                            <div class="note-inline mt-2 {{ $currentStatus !== 'hadir' ? '' : 'd-none' }}" id="note-wrap-{{ $member->student_id }}">
                                                <input type="text"
                                                       name="notes[{{ $member->student_id }}]"
                                                       id="notes-{{ $member->student_id }}"
                                                       class="form-control form-control-sm"
                                                       placeholder="Keterangan / alasan {{ strtolower($currentStatus) }}"
                                                       value="{{ $record?->notes }}"
                                                       maxlength="1000"
                                                       @disabled(! $canInput)>
                                            </div>
                                            <input type="hidden" name="statuses[{{ $member->student_id }}]" id="status-{{ $member->student_id }}" value="{{ $currentStatus }}">
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada anggota aktif di halaqah ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer border-top-dashed bg-light">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="text-muted small">
                                <span class="badge bg-primary-subtle text-primary">H</span> Hadir
                                <span class="badge bg-success-subtle text-success">I</span> Izin
                                <span class="badge bg-warning-subtle text-warning">S</span> Sakit
                                <span class="badge bg-danger-subtle text-danger">A</span> Alpa
                                @if($attendance->isNotEmpty())
                                    <span class="text-success ms-2"><i class="ri-checkbox-circle-fill me-1"></i>Data tanggal ini sudah tersimpan</span>
                                @endif
                            </div>
                            @if($canInput && $members->isNotEmpty())
                                <div class="d-flex align-items-center gap-2">
                                    <span id="attendanceAutosave" class="badge bg-light text-muted border">Menunggu…</span>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i>Simpan Absensi</button>
                                </div>
                            @endif
                        </div>
                    </div>
                </form>
            @elseif($tab === 'riwayat')
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h5 class="card-title mb-1">Riwayat Absensi</h5>
                            <p class="text-muted mb-0 small">{{ $group->name }} · sesi {{ $sessionLabels[$sessionType] }} · {{ $history->count() }} pertemuan tercatat</p>
                        </div>
                        <a href="{{ route('user.tahfidz.attendance.index', array_merge($baseParams, ['tab' => 'input'])) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Input Absensi</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th>Tanggal</th>
                                    <th>Hari</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Izin</th>
                                    <th class="text-center">Sakit</th>
                                    <th class="text-center">Alpa</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $index => $meeting)
                                    <tr>
                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td><i class="ri-calendar-event-fill text-primary me-1"></i>{{ $meeting->date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $meeting->date->translatedFormat('l') }}</td>
                                        <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $meeting->hadir }}</span></td>
                                        <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $meeting->izin }}</span></td>
                                        <td class="text-center"><span class="badge bg-warning-subtle text-warning">{{ $meeting->sakit }}</span></td>
                                        <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $meeting->alpa }}</span></td>
                                        <td class="text-center fw-medium">{{ $meeting->total }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('user.tahfidz.attendance.index', array_merge($baseParams, ['tab' => 'input', 'date' => $meeting->date->toDateString()])) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line me-1"></i>Lihat</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-5"><i class="ri-inbox-line fs-3 d-block mb-2"></i>Belum ada riwayat absensi untuk sesi ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-5">
                            <h5 class="card-title mb-1">Rekap Bulanan per Santri</h5>
                            <p class="text-muted small mb-0">Kehadiran halaqah {{ $group->name }} · sesi {{ $sessionLabels[$sessionType] }}</p>
                        </div>
                        <div class="col-md-4 ms-auto">
                            <form method="GET" action="{{ route('user.tahfidz.attendance.index', ['userId' => $userId]) }}" id="recapMonthForm">
                                <input type="hidden" name="tab" value="rekap">
                                <input type="hidden" name="group_id" value="{{ $group->id }}">
                                <input type="hidden" name="date" value="{{ $dateString }}">
                                <input type="hidden" name="session_type" value="{{ $sessionType }}">
                                <label class="form-label small"><i class="ri-calendar-2-line text-primary me-1"></i>Pilih Bulan</label>
                                <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach($monthOptions as $option)
                                        <option value="{{ $option }}" @selected($month === $option)>{{ \Carbon\Carbon::createFromFormat('!Y-m', $option)->translatedFormat('F Y') }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>

                    @if($meetingDates->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center align-middle" style="width:40px">#</th>
                                        <th class="align-middle" style="min-width:180px">Nama Santri</th>
                                        @foreach($meetingDates as $meetingDate)
                                            <th class="text-center align-middle" style="min-width:42px" title="{{ \Carbon\Carbon::parse($meetingDate)->translatedFormat('l, d F Y') }}">{{ \Carbon\Carbon::parse($meetingDate)->format('d/m') }}</th>
                                        @endforeach
                                        <th class="text-center align-middle">Hadir</th>
                                        <th class="text-center align-middle">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recapRows as $index => $row)
                                        <tr>
                                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                                            <td>
                                                <span class="fw-semibold">{{ $row->student?->name ?? '—' }}</span>
                                                <br><small class="text-muted">{{ $row->student?->nis }}</small>
                                            </td>
                                            @foreach($row->per_meeting as $meeting)
                                                <td class="text-center small {{ match($meeting['raw']) { 'hadir' => 'text-primary', 'izin' => 'text-success', 'sakit' => 'text-warning', 'alpa' => 'text-danger', default => 'text-muted' } }}" title="{{ $meeting['raw'] ? ucfirst($meeting['raw']) : 'Belum presensi' }}">{{ $meeting['letter'] }}</td>
                                            @endforeach
                                            <td class="text-center fw-semibold">{{ $row->hadir }}/{{ $row->total_meetings }}</td>
                                            <td class="text-center fw-semibold {{ $row->percentage >= 90 ? 'text-success' : ($row->percentage >= 70 ? 'text-warning' : 'text-danger') }}">{{ number_format($row->percentage, 1) }}%</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="{{ 3 + $meetingDates->count() }}" class="text-center text-muted py-4">Belum ada anggota aktif.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="row g-2 mt-1">
                            @foreach([['Hadir', $recapTotals['hadir'], 'primary'], ['Izin', $recapTotals['izin'], 'success'], ['Sakit', $recapTotals['sakit'], 'warning'], ['Alpa', $recapTotals['alpa'], 'danger'], ['Pertemuan', $recapTotals['meetings'], 'secondary']] as [$label, $value, $color])
                                <div class="col-md-2 col-6"><div class="border rounded text-center py-2"><strong class="text-{{ $color }}">{{ $value }}</strong><small class="text-muted d-block">{{ $label }}</small></div></div>
                            @endforeach
                            <div class="col-md-2 col-6 d-flex align-items-center justify-content-center text-muted small">H = Hadir · I = Izin · S = Sakit · A = Alpa · – = Belum</div>
                        </div>
                    @else
                        <div class="text-center text-muted py-5"><i class="ri-bar-chart-box-line fs-1 d-block mb-2"></i>Belum ada pertemuan tercatat pada bulan ini.</div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if($group && $tab === 'input')
        <script>
            function filterAttendance(query) {
                query = (query || '').toLowerCase().trim();
                const statusFilter = document.getElementById('statusFilter')?.value ?? '';
                let visible = 0;
                document.querySelectorAll('#attendanceList .attendance-row').forEach(function (row) {
                    const name = row.dataset.name || '';
                    const status = row.dataset.status || 'hadir';
                    const match = name.includes(query) && (statusFilter === '' || status === statusFilter);
                    row.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                const count = document.getElementById('studentCount');
                if (count) count.textContent = visible;
            }

            function attendanceCounts() {
                const counts = { hadir: 0, izin: 0, sakit: 0, alpa: 0 };
                document.querySelectorAll('#attendanceList .attendance-row').forEach(function (row) {
                    const input = document.getElementById('status-' + row.dataset.sid);
                    if (input && counts[input.value] !== undefined) counts[input.value]++;
                });
                Object.keys(counts).forEach(function (status) {
                    const el = document.getElementById('count-' + status);
                    if (el) el.textContent = counts[status];
                });
            }

            function setAttendanceStatus(studentId, status, button) {
                const input = document.getElementById('status-' + studentId);
                const row = button.closest('.attendance-row');
                const wrap = document.getElementById('note-wrap-' + studentId);
                if (!input || !row) return;
                input.value = status;
                row.dataset.status = status;
                row.querySelectorAll('.status-btn').forEach(function (btn) {
                    const color = btn.dataset.color;
                    btn.classList.remove('btn-primary', 'btn-success', 'btn-warning', 'btn-danger', 'btn-soft-primary', 'btn-soft-success', 'btn-soft-warning', 'btn-soft-danger');
                    btn.classList.add(btn === button ? 'btn-' + color : 'btn-soft-' + color);
                });
                if (wrap) wrap.classList.toggle('d-none', status === 'hadir');
                attendanceCounts();
                scheduleAttendanceSave(studentId);
            }

            function setAllAttendance(status) {
                document.querySelectorAll('#attendanceList .attendance-row').forEach(function (row) {
                    const button = row.querySelector('.status-btn[data-s="' + status + '"]');
                    if (button) {
                        button.click();
                    }
                });
                attendanceCounts();
                saveAllAttendance();
            }

            (function () {
                const form = document.getElementById('attendanceForm');
                const badge = document.getElementById('attendanceAutosave');
                if (!form || !badge) return;

                const url = form.action;
                const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                let timer = null;
                let lastStudentId = null;

                function setBadge(style, text) {
                    badge.className = 'badge border ' + style;
                    badge.textContent = text;
                }

                function buildPayload(onlyStudentId) {
                    const data = new FormData();
                    data.append('_token', token);
                    data.append('tahfidz_group_id', form.querySelector('[name="tahfidz_group_id"]').value);
                    data.append('attendance_date', form.querySelector('[name="attendance_date"]').value);
                    data.append('session_type', form.querySelector('[name="session_type"]').value);
                    const rows = onlyStudentId
                        ? Array.from(form.querySelectorAll('.attendance-row')).filter(function (row) { return row.dataset.sid === onlyStudentId; })
                        : Array.from(form.querySelectorAll('.attendance-row'));
                    rows.forEach(function (row) {
                        const sid = row.dataset.sid;
                        data.append('statuses[' + sid + ']', document.getElementById('status-' + sid)?.value ?? 'hadir');
                        data.append('notes[' + sid + ']', document.getElementById('notes-' + sid)?.value ?? '');
                    });
                    return data;
                }

                function send(data) {
                    setBadge('bg-light text-muted', 'Menyimpan…');
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: data,
                    })
                    .then(function (response) {
                        if (!response.ok) throw new Error('save failed');
                        return response.json();
                    })
                    .then(function (result) {
                        if (result.saved) {
                            setBadge('bg-success-subtle text-success', 'Tersimpan ' + (result.saved_at ?? ''));
                            setTimeout(function () { setBadge('bg-light text-muted', 'Menunggu…'); }, 2500);
                        } else {
                            setBadge('bg-danger-subtle text-danger', result.message ?? 'Gagal menyimpan');
                        }
                    })
                    .catch(function () {
                        setBadge('bg-danger-subtle text-danger', 'Gagal menyimpan');
                    });
                }

                window.scheduleAttendanceSave = function (studentId) {
                    lastStudentId = studentId;
                    clearTimeout(timer);
                    setBadge('bg-light text-muted', 'Menyimpan…');
                    timer = setTimeout(function () { send(buildPayload(lastStudentId)); }, 800);
                };

                window.saveAllAttendance = function () {
                    clearTimeout(timer);
                    send(buildPayload(null));
                };

                form.addEventListener('input', function (event) {
                    if (event.target && event.target.name && event.target.name.indexOf('notes[') === 0) {
                        const studentId = event.target.name.replace('notes[', '').replace(']', '');
                        window.scheduleAttendanceSave(studentId);
                    }
                });

                document.addEventListener('DOMContentLoaded', attendanceCounts);
                attendanceCounts();
            })();
        </script>
    @endif
@endsection
