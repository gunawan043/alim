@extends('layouts.master')
@section('title') Catat Absensi Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? '' }}</a> @endslot
        @slot('li_4') <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Absensi</a> @endslot
        @slot('title') Catat @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.attendance.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
          id="attendanceForm">
        @csrf

        {{-- ============================================================
             HEADER — DATE & SESSION FIELDS
        ============================================================ --}}
        <div class="card mb-4">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Absensi <span class="text-danger">*</span></label>
                        <input type="date"
                               name="date"
                               id="attendance_date"
                               class="form-control @error('date') is-invalid @enderror"
                               value="{{ old('date', $selectedDate ?? now()->toDateString()) }}"
                               required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sesi <span class="text-danger">*</span></label>
                        <select name="session"
                                id="attendance_session"
                                class="form-select @error('session') is-invalid @enderror"
                                required>
                            <option value="">-- Pilih Sesi --</option>
                            @foreach(['pagi', 'siang', 'sore', 'malam'] as $s)
                                <option value="{{ $s }}" {{ (old('session', $selectedSession ?? '') === $s) ? 'selected' : '' }}>
                                    {{ ucfirst($s) }}
                                </option>
                            @endforeach
                        </select>
                        @error('session')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Informasi</label>
                        <div class="bg-light border rounded p-2 small">
                            <i class="ri-information-line text-primary me-1"></i>
                            <span id="recordCountInfo">
                                {{ $residentsByRoom->flatten()->count() }} penghuni aktif siap diabsen.
                            </span>
                            @if($existingCount > 0)
                                <span class="text-warning ms-2">
                                    <i class="ri-alert-line"></i> {{ $existingCount }} sudah punya absensi hari ini (sesi {{ $selectedSession ?? '-' }}).
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             ATTENDANCE TABLE — GROUPED BY ROOM
        ============================================================ --}}
        @forelse($residentsByRoom as $roomName => $residents)
            <div class="card mb-4">
                <div class="card-header bg-primary-subtle border-primary-subtle">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="ri-home-4-line me-2"></i>{{ $roomName }}
                        </h5>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            {{ $residents->count() }} penghuni
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 4%">
                                        <input type="checkbox" class="form-check-input room-check-all" data-room="{{ $loop->index }}">
                                    </th>
                                    <th style="width: 5%">No</th>
                                    <th>Nama Santri</th>
                                    <th style="width: 10%">Bed</th>
                                    <th style="width: 18%">Status <span class="text-danger">*</span></th>
                                    <th style="width: 25%">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($residents as $idx => $resident)
                                    @php
                                        $existing = $existingAttendance[$resident->id] ?? null;
                                        $prefilledStatus = $existing?->status ?? null;
                                    @endphp
                                    <tr class="{{ $prefilledStatus ? 'table-success-subtle' : '' }}">
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input resident-checkbox"
                                                   name="attendances[{{ $resident->id }}][selected]"
                                                   value="1"
                                                   data-resident-id="{{ $resident->id }}"
                                                   {{ $prefilledStatus ? 'checked' : '' }}>
                                            <input type="hidden"
                                                   name="attendances[{{ $resident->id }}][resident_id]"
                                                   value="{{ $resident->id }}">
                                        </td>
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-3">
                                                    <div class="avatar-title rounded-circle bg-{{ $resident->student?->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $resident->student?->gender === 'P' ? 'danger' : 'primary' }} fw-bold fs-10">
                                                        {{ strtoupper(substr($resident->student?->name ?? '?', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $resident->student?->name ?? '-' }}</span>
                                                    @if($resident->student?->nisn)
                                                        <br><small class="text-muted">{{ $resident->student->nisn }}</small>
                                                    @endif
                                                    @if($prefilledStatus)
                                                        <span class="badge bg-success-subtle text-success ms-2">
                                                            <i class="ri-checkbox-circle-line me-1"></i>Sudah Ada
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">#{{ $resident->bed_number }}</span>
                                        </td>
                                        <td>
                                            @if($prefilledStatus)
                                                <input type="hidden" name="attendances[{{ $resident->id }}][status]" value="{{ $prefilledStatus }}">
                                                <span class="badge @switch($prefilledStatus)
                                                    @case('hadir') bg-success-subtle text-success @break
                                                    @case('izin') bg-warning-subtle text-warning @break
                                                    @case('sakit') bg-info-subtle text-info @break
                                                    @case('alpa') bg-danger-subtle text-danger @break
                                                    @case('pulang') bg-secondary-subtle text-secondary @break
                                                    @default bg-light text-muted
                                                @endswitch">
                                                    {{ ucfirst($prefilledStatus) }}
                                                </span>
                                            @else
                                                <select name="attendances[{{ $resident->id }}][status]"
                                                        class="form-select form-select-sm status-select @error('attendances.' . $resident->id . '.status') is-invalid @enderror"
                                                        data-resident="{{ $resident->id }}"
                                                        required>
                                                    <option value="">-- Status --</option>
                                                    <option value="hadir" {{ old('attendances.' . $resident->id . '.status') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                                                    <option value="izin" {{ old('attendances.' . $resident->id . '.status') === 'izin' ? 'selected' : '' }}>Izin</option>
                                                    <option value="sakit" {{ old('attendances.' . $resident->id . '.status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                                                    <option value="alpa" {{ old('attendances.' . $resident->id . '.status') === 'alpa' ? 'selected' : '' }}>Alpa</option>
                                                    <option value="pulang" {{ old('attendances.' . $resident->id . '.status') === 'pulang' ? 'selected' : '' }}>Pulang</option>
                                                </select>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="text"
                                                   name="attendances[{{ $resident->id }}][notes]"
                                                   class="form-control form-control-sm"
                                                   placeholder="Catatan opsional..."
                                                   value="{{ old('attendances.' . $resident->id . '.notes', $existing?->notes ?? '') }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="ri-user-search-line fs-1 d-block text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="text-muted mb-2">Tidak Ada Penghuni Aktif</h5>
                    <p class="text-muted">Belum ada penghuni aktif di asrama ini. Check-in santri terlebih dahulu.</p>
                    <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-primary">
                        <i class="ri-add-line me-1"></i> Check-in Santri
                    </a>
                </div>
            </div>
        @endforelse

        {{-- ============================================================
             ACTION BUTTONS
        ============================================================ --}}
        @if($residentsByRoom->flatten()->isNotEmpty())
            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                <i class="ri-information-line me-1"></i>
                                Baris dengan status <span class="badge bg-success-subtle text-success">Sudah Ada</span> sudah tercatat dan tidak akan diupdate kecuali Anda ubah statusnya.
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-light">
                                    <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitAttendanceBtn">
                                    <i class="ri-save-line align-middle me-1"></i> Simpan Absensi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </form>
@endsection

@section('script')
<script>
(function() {
    'use strict';

    // --- Select-all per room ---
    document.querySelectorAll('.room-check-all').forEach(function(master) {
        master.addEventListener('change', function() {
            var room = master.dataset.room;
            var table = master.closest('.card');
            var checkboxes = table.querySelectorAll('.resident-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = master.checked;
                toggleRowStyle(cb.closest('tr'), master.checked);
            });
        });
    });

    // --- Toggle row style on checkbox change ---
    document.querySelectorAll('.resident-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            toggleRowStyle(cb.closest('tr'), cb.checked);
        });
    });

    function toggleRowStyle(tr, checked) {
        if (checked) {
            tr.classList.add('table-primary-subtle');
        } else {
            tr.classList.remove('table-primary-subtle');
        }
    }

    // --- Status color coding on select change ---
    document.querySelectorAll('.status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            var colorMap = {
                'hadir': 'success',
                'izin': 'warning',
                'sakit': 'info',
                'alpa': 'danger',
                'pulang': 'secondary'
            };
            var val = select.value;
            select.className = 'form-select form-select-sm status-select';
            if (val && colorMap[val]) {
                select.classList.add('border-' + colorMap[val]);
            }
        });
    });

    // --- Prevent double submit ---
    var form = document.getElementById('attendanceForm');
    form.addEventListener('submit', function() {
        var btn = document.getElementById('submitAttendanceBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-2-line me-2"></i> Menyimpan...';
    });
})();
</script>
@endsection