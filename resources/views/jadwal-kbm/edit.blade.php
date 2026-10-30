@extends('layouts.master')

@section('title', 'Edit Jadwal KBM — ' . ($studyGroup->full_name ?? $studyGroup->name))

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">Jadwal KBM</a> @endslot
    @slot('li_2') <a href="{{ route('user.jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">{{ $studyGroup->full_name ?? $studyGroup->name }}</a> @endslot
    @slot('li_3') Edit @endslot
    @slot('title') Edit Jadwal — {{ $studyGroup->full_name ?? $studyGroup->name }} @endslot
@endcomponent

@if(session('conflict_warnings'))
    <div class="alert alert-warning">
        <h6 class="fw-semibold mb-2"><i class="ri-error-warning-line me-1"></i>Beberapa entri dilewati karena konflik:</h6>
        <ul class="mb-0 small">
            @foreach(session('conflict_warnings') as $conflict)
                <li>{{ $conflict['reason'] }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('user.jadwal-kbm.update', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-edit-line text-primary me-1"></i>Edit Jadwal Manual</h5>
            <p class="text-muted mb-0 small">Perubahan manual tetap divalidasi bentrok guru/rombel.</p>
        </div>
        <div class="card-body">
            <div class="alert alert-info small">
                <i class="ri-information-line me-1"></i>
                Perubahan hari/jam mengikuti master slot sekolah. Entri yang bentrok antar guru/rombel atau jatuh di jam istirahat
                akan ditolak otomatis (tidak disimpan) dan dilaporkan setelah simpan.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:110px">Hari</th>
                            <th style="width:110px">Jam ke-</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th style="width:130px">Ruang</th>
                            <th style="width:120px">Waktu</th>
                            <th style="width:52px" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwals as $jadwal)
                            <tr>
                                <td>
                                    <select name="entries[{{ $loop->index }}][day_of_week]" class="form-select form-select-sm">
                                        @foreach($days as $number => $name)
                                            <option value="{{ $number }}" {{ (int) $jadwal->day_of_week === $number ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="entries[{{ $loop->index }}][id]" value="{{ $jadwal->id }}">
                                </td>
                                <td>
                                    <select name="entries[{{ $loop->index }}][slot_index]" class="form-select form-select-sm">
                                        @for($slot = 1; $slot <= $maxSlots; $slot++)
                                            <option value="{{ $slot }}" {{ (int) $jadwal->slot_index === $slot ? 'selected' : '' }}>Jam {{ $slot }}</option>
                                        @endfor
                                    </select>
                                </td>
                                <td>
                                    <select name="entries[{{ $loop->index }}][subject_id]" class="form-select form-select-sm" required>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ $jadwal->subject_id == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="entries[{{ $loop->index }}][teacher_id]" class="form-select form-select-sm" data-searchable data-placeholder="Cari guru...">
                                        <option value="">— Kosong —</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" {{ $jadwal->teacher_id == $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="entries[{{ $loop->index }}][room]" class="form-control form-control-sm"
                                           value="{{ $jadwal->room }}" maxlength="50">
                                </td>
                                <td class="text-muted small">{{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}</td>
                                <td class="text-center">
                                    <button type="submit" form="delete-entry-{{ $jadwal->id }}" class="btn btn-sm btn-soft-danger"
                                        title="Hapus entri">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada jadwal.
                                    <a href="{{ route('user.jadwal-kbm.generate', ['userId' => $userId]) }}">Generate dulu</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('user.jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}" class="btn btn-light">
                <i class="ri-arrow-left-line me-1"></i>Kembali
            </a>
            <button type="submit" class="btn btn-primary" {{ $jadwals->isEmpty() ? 'disabled' : '' }}>
                <i class="ri-save-line me-1"></i>Simpan Perubahan
            </button>
        </div>
    </div>
</form>

@foreach($jadwals as $jadwal)
    <form id="delete-entry-{{ $jadwal->id }}" method="POST"
        action="{{ route('user.jadwal-kbm.entries.destroy', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'entryId' => $jadwal->id]) }}"
        class="d-none" onsubmit="return confirm('Hapus entri jadwal ini?')">
        @csrf
        @method('DELETE')
    </form>
@endforeach

@if($activeAy)
    <div class="card mt-3">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-add-circle-line text-success me-1"></i>Tambah Entri Jadwal Manual</h5>
            <p class="text-muted mb-0 small">Gunakan untuk menambah pelajaran yang belum ada. Slot tetap mengikuti ketersediaan guru dan anti-bentrok.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('user.jadwal-kbm.entries.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small">Hari</label>
                        <select name="day_of_week" class="form-select form-select-sm" required>
                            @foreach($days as $number => $name)
                                <option value="{{ $number }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Jam ke-</label>
                        <select name="slot_index" class="form-select form-select-sm" required>
                            @for($slot = 1; $slot <= $maxSlots; $slot++)
                                <option value="{{ $slot }}">Jam {{ $slot }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Mata Pelajaran</label>
                        <select name="subject_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih mapel —</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Guru</label>
                        <select name="teacher_id" class="form-select form-select-sm" data-searchable data-placeholder="Cari guru...">
                            <option value="">— Kosong —</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ruang</label>
                        <input type="text" name="room" class="form-control form-control-sm" value="{{ $studyGroup->room }}" maxlength="50">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="ri-add-line me-1"></i>Tambah Entri
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
