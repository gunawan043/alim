@extends('layouts.master')

@section('title', 'Jadwal KBM')

@push('css')
<style>
    .sg-badge { font-size: .72rem; }
    .table-freeze th, .table-freeze td { vertical-align: middle; }
</style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Jadwal KBM @endslot
    @slot('title') Jadwal Kegiatan Belajar @endslot
@endcomponent

<div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
    <a href="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
        <i class="ri-timer-line me-1"></i> Jam Pelajaran
    </a>
    <a href="{{ route('user.jadwal-kbm.generate', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
        <i class="ri-magic-line me-1"></i> Generate Jadwal
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@include('jadwal-kbm._report')

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-calendar-2-line text-primary me-1"></i> Daftar Jadwal per Rombel</h5>
        @if($activeAy)
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                Tahun Ajaran: {{ $activeAy->name }} ({{ ucfirst($activeAy->semester ?? '-') }})
            </span>
        @else
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Tahun ajaran aktif belum ditetapkan</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:48px" class="text-center">No</th>
                    <th>Rombel</th>
                    <th>Tingkat</th>
                    <th>Wali Kelas</th>
                    <th class="text-center">Slot Jadwal</th>
                    <th class="text-end" style="width:210px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studyGroups as $sg)
                    @php
                        $group = $jadwals[$sg->id] ?? collect();
                        $count = $group->count();
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="fw-medium">{{ $sg->full_name ?? $sg->name }}</td>
                        <td>{{ $sg->gradeLevel->name ?? '-' }}</td>
                        <td>{{ $sg->homeroomTeacher?->name ?? '-' }}</td>
                        <td class="text-center">
                            @if($count > 0)
                                <span class="badge bg-success sg-badge">{{ $count }} slot</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary sg-badge">Belum ada jadwal</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($count > 0)
                                <a href="{{ route('user.jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                   class="btn btn-sm btn-outline-primary" title="Lihat jadwal">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('user.jadwal-kbm.edit', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                   class="btn btn-sm btn-outline-warning" title="Edit manual">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <a href="{{ route('user.jadwal-kbm.cetak', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Cetak" target="_blank">
                                    <i class="ri-printer-line"></i>
                                </a>
                            @else
                                <form method="POST"
                                      action="{{ route('user.jadwal-kbm.generate.execute', ['userId' => $userId, 'studyGroupId' => $sg->id]) }}"
                                      class="d-inline js-generate-single">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            data-name="{{ $sg->full_name ?? $sg->name }}">
                                        <i class="ri-magic-line me-1"></i>Generate
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="ri-calendar-close-line" style="font-size:2.5rem;opacity:.4"></i></div>
                            <h6 class="fw-semibold">Belum ada rombel aktif</h6>
                            <p class="text-muted small mb-0">Tambahkan rombongan belajar terlebih dahulu.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-generate-single').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        var name = form.querySelector('button[data-name]')?.dataset.name || 'rombel ini';
        if (! confirm('Generate jadwal untuk ' + name + ' berdasarkan SK guru aktif?')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
