@extends('layouts.master')

@section('title', 'Ketersediaan Guru')

@push('css')
    @include('kurikulum._styles')
    <style>
        .availability-day .card-header { padding: .75rem 1rem; }
        .availability-slot { border: 1px solid #e5e7eb; border-radius: .5rem; padding: .55rem .7rem; margin-bottom: .45rem; }
        .availability-slot:last-child { margin-bottom: 0; }
        .availability-slot .form-check-input { margin-top: .15rem; }
    </style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">Jadwal KBM</a> @endslot
    @slot('li_2') Ketersediaan Guru @endslot
    @slot('title') Pengaturan Ketersediaan Guru @endslot
@endcomponent

<div class="alert alert-info small">
    <i class="ri-information-line me-1"></i>
    Tandai hari dan jam ketika guru <strong>tersedia</strong> untuk mengajar. Generator jadwal hanya akan
    menempatkan guru pada slot yang dicentang. Jika guru belum punya pengaturan, semua slot dianggap tersedia.
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

<div class="card mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <label class="form-label small text-muted mb-1">Pilih Guru</label>
                <form method="GET" action="{{ route('user.jadwal-kbm.availability', ['userId' => $userId]) }}">
                    <select name="teacher_id" class="form-select" data-searchable data-placeholder="Cari nama guru..." onchange="this.form.submit()" {{ $teachers->isEmpty() ? 'disabled' : '' }}>
                        @forelse($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $selectedTeacher?->id === $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @empty
                            <option>Tidak ada guru pada sekolah ini</option>
                        @endforelse
                    </select>
                </form>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="badge bg-primary-subtle text-primary">
                    {{ $configuredCount }} guru punya pengaturan
                </span>
                <span class="text-muted small ms-2">
                    <i class="ri-calendar-line me-1"></i>Slot mengikuti master Jam Pelajaran
                </span>
            </div>
        </div>
    </div>
</div>

@if(! $selectedTeacher)
    <div class="alert alert-warning">
        Belum ada guru yang dapat diatur pada sekolah ini. Tambahkan data kepegawaian guru terlebih dahulu.
    </div>
@else
    <form method="POST" action="{{ route('user.jadwal-kbm.availability.store', ['userId' => $userId]) }}">
        @csrf
        <input type="hidden" name="teacher_id" value="{{ $selectedTeacher->id }}">

        <div class="card">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="ri-user-star-line text-primary me-1"></i>{{ $selectedTeacher->name }}
                    </h5>
                    <small class="text-muted">
                        @if($availability)
                            Pembatasan aktif. Hanya slot dicentang yang boleh dipakai generator.
                        @else
                            Belum ada pembatasan — semua slot mengajar tersedia.
                        @endif
                    </small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">
                        <i class="ri-arrow-left-line me-1"></i>Kembali
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ri-save-line me-1"></i>Simpan Ketersediaan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="availabilitySelectAll(true)">
                        <i class="ri-checkbox-multiple-line me-1"></i>Semua Tersedia
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="availabilitySelectAll(false)">
                        <i class="ri-checkbox-blank-line me-1"></i>Kosongkan
                    </button>
                    <span class="text-muted small">Centang jam yang tersedia untuk guru ini.</span>
                </div>

                <div class="row g-3">
                    @foreach($days as $dayNumber => $dayName)
                        @php
                            $slots = collect($masterDays[$dayNumber] ?? [])->reject(fn ($slot) => $slot['is_break'])->values();
                            $checked = $selectedSlots[$dayNumber] ?? [];
                        @endphp
                        <div class="col-xl-4 col-md-6 availability-day">
                            <div class="card border h-100 mb-0">
                                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                    <strong>{{ $dayName }}</strong>
                                    @if($slots->isNotEmpty())
                                        <button type="button" class="btn btn-sm btn-ghost-secondary p-0"
                                            onclick="availabilityToggleDay({{ $dayNumber }})" title="Pilih / kosongkan hari ini">
                                            <i class="ri-checkbox-multiple-line fs-16"></i>
                                        </button>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @forelse($slots as $slot)
                                        <div class="availability-slot">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input availability-checkbox day-{{ $dayNumber }}"
                                                    type="checkbox"
                                                    name="available[{{ $dayNumber }}][]"
                                                    value="{{ $slot['slot'] }}"
                                                    id="slot-{{ $dayNumber }}-{{ $slot['slot'] }}"
                                                    {{ in_array((int) $slot['slot'], array_map('intval', $checked), true) ? 'checked' : '' }}>
                                                <label class="form-check-label small w-100" for="slot-{{ $dayNumber }}-{{ $slot['slot'] }}">
                                                    <span class="fw-medium">Jam {{ $slot['slot'] }}</span>
                                                    <span class="text-muted ms-1">
                                                        {{ substr($slot['start'], 0, 5) }}–{{ substr($slot['end'], 0, 5) }}
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-muted small">Belum ada slot untuk hari ini.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($availability)
                    <hr>
                    <button type="submit" name="reset" value="1" class="btn btn-sm btn-soft-danger"
                        onclick="return confirm('Hapus pembatasan guru ini? Guru akan kembali tersedia pada semua slot mengajar.')">
                        <i class="ri-delete-bin-line me-1"></i>Hapus Pembatasan
                    </button>
                @endif
            </div>
        </div>
    </form>
@endif
@endsection

@push('scripts')
<script>
function availabilityToggleDay(day) {
    const boxes = document.querySelectorAll('.availability-checkbox.day-' + day);
    const allChecked = Array.from(boxes).every(function (box) { return box.checked; });
    boxes.forEach(function (box) { box.checked = ! allChecked; });
}

function availabilitySelectAll(checked) {
    document.querySelectorAll('.availability-checkbox').forEach(function (box) {
        box.checked = checked;
    });
}
</script>
@endpush
