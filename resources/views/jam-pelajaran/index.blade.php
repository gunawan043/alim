{{-- Master Jam Pelajaran — sumber slot untuk Generator Jadwal KBM --}}
@extends('layouts.master')

@section('title', 'Jam Pelajaran')

@push('css')
<style>
    .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    .stat-value { font-size: 1.3rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .5px; margin: 0; }
    .stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .slot-time { font-family: 'SF Mono', Monaco, monospace; font-size: .78rem; white-space: nowrap; }
    .nav-tabs-custom .nav-link { font-size: .82rem; font-weight: 500; color: #64748b; border: none; border-bottom: 2px solid transparent; padding: 8px 14px; }
    .nav-tabs-custom .nav-link.active { color: #6366f1; border-bottom-color: #6366f1; background: transparent; }
    .table-slots th, .table-slots td { vertical-align: middle; }
</style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">Jadwal Pelajaran</a> @endslot
    @slot('li_2') Jam Pelajaran @endslot
    @slot('title') Pengaturan Jam Pelajaran @endslot
@endcomponent

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="text-muted small mb-0">
        Master slot waktu per hari — jam mulai, jam selesai, dan jam istirahat.
        Generator <strong>Jadwal Pelajaran</strong> memakai slot ini saat menyusun jadwal.
    </p>
    @if($canManage && $schoolId)
        <button type="button" class="btn btn-primary btn-sm" onclick="openSlotModal()">
            <i class="ri-add-line me-1"></i>Tambah Jam
        </button>
    @endif
</div>

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
        <i class="ri-close-circle-line me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($schools->isNotEmpty())
    <div class="card border mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-0 small">Pilih Sekolah</label>
                    <select name="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">— Pilih sekolah —</option>
                        @foreach($schools as $schoolOption)
                            <option value="{{ $schoolOption->id }}" {{ $schoolId === $schoolOption->id ? 'selected' : '' }}>{{ $schoolOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>
@endif

@if(! $schoolId)
    <div class="alert alert-info">
        <i class="ri-information-line me-1"></i>
        Konteks sekolah belum terpilih. Pilih sekolah terlebih dahulu untuk mengatur jam pelajaran.
    </div>
@else
    {{-- Statistik ringkas --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="stat-icon bg-primary-subtle text-primary"><i class="ri-timer-line"></i></span>
                    <div>
                        <p class="stat-label text-muted">Total Slot</p>
                        <div class="stat-value">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="stat-icon bg-warning-subtle text-warning"><i class="ri-cup-line"></i></span>
                    <div>
                        <p class="stat-label text-muted">Jam Istirahat</p>
                        <div class="stat-value">{{ $stats['istirahat'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="stat-icon bg-success-subtle text-success"><i class="ri-calendar-check-line"></i></span>
                    <div>
                        <p class="stat-label text-muted">Hari Terdefinisi</p>
                        <div class="stat-value">{{ $stats['hari'] }} / 6</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                @foreach($days as $dayNumber => $dayName)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" href="#day-{{ $dayNumber }}" role="tab">
                            {{ $dayName }}
                            @if(isset($slotsByDay[$dayNumber]))
                                <span class="badge bg-primary-subtle text-primary ms-1">{{ $slotsByDay[$dayNumber]->count() }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                @foreach($days as $dayNumber => $dayName)
                    @php $daySlots = $slotsByDay[$dayNumber] ?? collect(); @endphp
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="day-{{ $dayNumber }}" role="tabpanel">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <h6 class="mb-0 fw-semibold">{{ $dayName }}</h6>
                            <div class="d-flex gap-2">
                                @if($canManage && $daySlots->isEmpty())
                                    <form method="POST" action="{{ route('user.jam-pelajaran.template', ['userId' => $userId]) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="day_of_week" value="{{ $dayNumber }}">
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Buat 6 slot default">
                                            <i class="ri-magic-line me-1"></i>Terapkan Template
                                        </button>
                                    </form>
                                @endif
                                @if($canManage)
                                    <button type="button" class="btn btn-sm btn-primary" onclick="openSlotModal(null, {{ $dayNumber }})">
                                        <i class="ri-add-line me-1"></i>Tambah Jam
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-slots mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:80px" class="text-center">Jam ke-</th>
                                        <th>Label</th>
                                        <th style="width:120px">Mulai</th>
                                        <th style="width:120px">Selesai</th>
                                        <th style="width:110px" class="text-center">Istirahat</th>
                                        <th style="width:100px" class="text-center">Status</th>
                                        @if($canManage)
                                            <th style="width:120px" class="text-end">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($daySlots as $slot)
                                        <tr class="{{ $slot->is_break ? 'table-warning' : '' }}">
                                            <td class="text-center fw-semibold">{{ $slot->slot_number }}</td>
                                            <td>{{ $slot->label }}</td>
                                            <td class="slot-time">{{ substr($slot->time_start, 0, 5) }}</td>
                                            <td class="slot-time">{{ substr($slot->time_end, 0, 5) }}</td>
                                            <td class="text-center">
                                                @if($slot->is_break)
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Istirahat</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">KBM</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($slot->is_active)
                                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                                @endif
                                            </td>
                                            @if($canManage)
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Edit"
                                                            onclick='openSlotModal(@json($slot))'>
                                                        <i class="ri-edit-line"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('user.jam-pelajaran.destroy', ['userId' => $userId, 'slotId' => $slot->id]) }}"
                                                          class="d-inline js-delete-slot">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"
                                                                data-label="{{ $slot->label }}">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canManage ? 7 : 6 }}" class="text-center py-4 text-muted">
                                                <i class="ri-timer-line me-1"></i>Belum ada jam pelajaran untuk {{ $dayName }}.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer bg-white py-2">
            <span class="small text-muted">
                <i class="ri-information-line me-1"></i>
                Slot <strong>Istirahat</strong> tidak akan dipakai generator jadwal. Rentang waktu antar slot tidak boleh tumpang tindih.
            </span>
        </div>
    </div>
@endif

{{-- Modal Tambah/Edit --}}
@if($canManage && $schoolId)
    <div class="modal fade" id="slotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="slotForm" action="{{ route('user.jam-pelajaran.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="slotMethod" value="PUT" disabled>
                    <div class="modal-header">
                        <h5 class="modal-title" id="slotModalTitle">Tambah Jam Pelajaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Hari <span class="text-danger">*</span></label>
                                <select name="day_of_week" id="slotDay" class="form-select" required>
                                    @foreach($days as $dayNumber => $dayName)
                                        <option value="{{ $dayNumber }}">{{ $dayName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Jam ke- <span class="text-danger">*</span></label>
                                <input type="number" name="slot_number" id="slotNumber" class="form-control" min="1" max="30" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Label <span class="text-danger">*</span></label>
                                <input type="text" name="label" id="slotLabel" class="form-control" maxlength="30" placeholder="Contoh: Jam 1 / Istirahat / Sholat Dzuhur" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="time_start" id="slotStart" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Selesai <span class="text-danger">*</span></label>
                                <input type="time" name="time_end" id="slotEnd" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_break" value="1" id="slotIsBreak">
                                    <label class="form-check-label" for="slotIsBreak">Jam istirahat</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="slotIsActive" checked>
                                    <label class="form-check-label" for="slotIsActive">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="slotSubmit">
                            <i class="ri-save-line me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
const slotStoreUrl = @json(route('user.jam-pelajaran.store', ['userId' => $userId]));
const slotUpdateUrlTemplate = @json(route('user.jam-pelajaran.update', ['userId' => $userId, 'slotId' => '__SLOT__']));
const dayMaxSlots = @json($slotsByDay->map(fn ($slots) => (int) $slots->max('slot_number')));

function openSlotModal(slot = null, dayNumber = null) {
    const modalEl = document.getElementById('slotModal');
    if (! modalEl) return;

    const form = document.getElementById('slotForm');
    const method = document.getElementById('slotMethod');
    const title = document.getElementById('slotModalTitle');

    if (slot) {
        title.textContent = 'Edit Jam Pelajaran';
        form.action = slotUpdateUrlTemplate.replace('__SLOT__', slot.id);
        method.disabled = false;
        method.value = 'PUT';
        document.getElementById('slotDay').value = slot.day_of_week;
        document.getElementById('slotNumber').value = slot.slot_number;
        document.getElementById('slotLabel').value = slot.label;
        document.getElementById('slotStart').value = (slot.time_start || '').substring(0, 5);
        document.getElementById('slotEnd').value = (slot.time_end || '').substring(0, 5);
        document.getElementById('slotIsBreak').checked = !! Number(slot.is_break);
        document.getElementById('slotIsActive').checked = !! Number(slot.is_active);
    } else {
        title.textContent = 'Tambah Jam Pelajaran';
        form.action = slotStoreUrl;
        method.disabled = true;
        method.value = 'PUT';
        form.reset();
        document.getElementById('slotIsActive').checked = true;
        if (dayNumber) document.getElementById('slotDay').value = dayNumber;
        document.getElementById('slotNumber').value = (dayMaxSlots[document.getElementById('slotDay').value] ?? 0) + 1;
    }

    new bootstrap.Modal(modalEl).show();
}

document.querySelectorAll('.js-delete-slot').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        const label = form.querySelector('button[data-label]')?.dataset.label || 'jam ini';
        if (! confirm('Hapus "' + label + '"?')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
