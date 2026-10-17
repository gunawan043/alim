@extends('layouts.master')

@section('title') Presensi {{ $activity->name }} @endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $activityUrl = ['userId' => $userId, 'asramaUuid' => $dormitory->id];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.kegiatan.index', $activityUrl) }}">Kegiatan</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.kegiatan.show', $activityUrl + ['activityId' => $activity->id]) }}">{{ $activity->name }}</a> @endslot
        @slot('title') Presensi Kegiatan @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-1"><i class="ri-user-follow-line me-2 text-primary"></i>Presensi — {{ $activity->name }}</h5>
            <p class="text-muted small mb-0">
                {{ $activity->activity_date?->translatedFormat('l, d F Y') }}
                @if($activity->start_time) · {{ substr($activity->start_time, 0, 5) }}@if($activity->end_time)–{{ substr($activity->end_time, 0, 5) }}@endif @endif
                · {{ $residentsByRoom->flatten()->count() }} penghuni aktif
                @if($existingCount > 0) · {{ $existingCount }} sudah tercatat @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-success btn-sm" id="markAllPresent">
                <i class="ri-check-double-line me-1"></i>Tandai Semua Hadir
            </button>
            <a href="{{ route('user.asrama.kegiatan.show', $activityUrl + ['activityId' => $activity->id]) }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-go-back-line me-1"></i>Kembali
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('user.asrama.kegiatan.attendance.store', $activityUrl + ['activityId' => $activity->id]) }}" id="activityAttendanceForm">
        @csrf

        @forelse($residentsByRoom as $roomName => $residents)
            <div class="card mb-3">
                <div class="card-header bg-primary-subtle border-primary-subtle d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-primary"><i class="ri-home-4-line me-2"></i>{{ $roomName }}</h6>
                    <label class="small text-primary mb-0">
                        <input type="checkbox" class="form-check-input me-1 room-check-all" data-room="{{ $loop->index }}"> Pilih semua kamar
                    </label>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px" class="text-center">✓</th>
                                    <th style="width:45px">No</th>
                                    <th>Nama Santri</th>
                                    <th style="width:80px">Bed</th>
                                    <th style="width:150px">Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($residents as $idx => $resident)
                                    @php
                                        $row = $existing[$resident->id] ?? null;
                                        $checked = (bool) $row;
                                        $status = $row?->status ?? 'hadir';
                                    @endphp
                                    <tr class="{{ $checked ? 'table-success-subtle' : '' }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input resident-checkbox"
                                                   name="attendances[{{ $resident->id }}][selected]" value="1"
                                                   data-room="{{ $loop->parent->index }}"
                                                   {{ $checked ? 'checked' : '' }}>
                                        </td>
                                        <td class="text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $resident->student?->name ?? '-' }}</div>
                                            <div class="small text-muted">{{ $resident->student?->nisn ?? '' }}</div>
                                        </td>
                                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">#{{ $resident->bed_number }}</span></td>
                                        <td>
                                            <select name="attendances[{{ $resident->id }}][status]" class="form-select form-select-sm activity-status">
                                                @foreach(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'] as $value => $label)
                                                    <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="attendances[{{ $resident->id }}][notes]" class="form-control form-control-sm"
                                                   value="{{ $row?->notes }}" placeholder="Catatan opsional…">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center py-5 text-muted">
                <i class="ri-user-search-line fs-1 d-block mb-2"></i>
                Belum ada penghuni aktif di asrama ini.
            </div></div>
        @endforelse

        @if($residentsByRoom->flatten()->isNotEmpty())
            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('user.asrama.kegiatan.show', $activityUrl + ['activityId' => $activity->id]) }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary" id="submitActivityAttendance">
                    <i class="ri-save-line me-1"></i>Simpan Presensi
                </button>
            </div>
        @endif
    </form>
@endsection

@section('script')
<script>
    (function () {
        'use strict';

        document.querySelectorAll('.room-check-all').forEach(function (master) {
            master.addEventListener('change', function () {
                var room = master.dataset.room;
                document.querySelectorAll('.resident-checkbox[data-room="' + room + '"]').forEach(function (cb) {
                    cb.checked = master.checked;
                    if (master.checked) {
                        cb.closest('tr').classList.add('table-success-subtle');
                    } else {
                        cb.closest('tr').classList.remove('table-success-subtle');
                    }
                });
            });
        });

        document.querySelectorAll('.resident-checkbox').forEach(function (cb) {
            cb.addEventListener('change', function () {
                cb.closest('tr').classList.toggle('table-success-subtle', cb.checked);
            });
        });

        document.getElementById('markAllPresent')?.addEventListener('click', function () {
            document.querySelectorAll('.resident-checkbox').forEach(function (cb) {
                cb.checked = true;
                cb.closest('tr').classList.add('table-success-subtle');
            });
            document.querySelectorAll('.activity-status').forEach(function (select) {
                select.value = 'hadir';
            });
        });

        document.getElementById('activityAttendanceForm')?.addEventListener('submit', function () {
            var btn = document.getElementById('submitActivityAttendance');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="ri-loader-2-line me-1"></i>Menyimpan…';
            }
        });
    })();
</script>
@endsection
