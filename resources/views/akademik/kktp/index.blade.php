@extends('layouts.master')
@section('title') KKM / KKTP @endsection

@section('css')
<style>
    .kkm-input { min-width:70px; text-align:center; font-weight:700; }
    .kktp-input { min-width:70px; text-align:center; }
    .table-kktp tbody tr:hover { background:#f8fafc; }
</style>
@endsection

@section('content')
@php
    $userId = request()->route('userId') ?? Auth::id();
    $selectedAy = $academicYears->firstWhere('id', $selectedAyId);
    $selectedGl = $gradeLevels->firstWhere('id', $selectedGlId);
@endphp

@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('title') KKM / KKTP @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card" id="kktpList">
            <div class="card-header border-bottom-dashed">
                <div class="row g-3 align-items-center">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">
                            <i class="ri-file-edit-line text-primary me-1"></i>
                            KKM / KKTP — Target Kompetensi Tuntas
                        </h5>
                    </div>
                    <div class="col-sm-auto">
                        <span class="badge bg-info-subtle text-info" style="font-size:11px;padding:4px 10px;">
                            <i class="ri-user-settings-line align-bottom me-1"></i>Mode TU / Waka
                        </span>
                    </div>
                </div>
            </div>

            {{-- Filter --}}
            <div class="card-header py-2 bg-light border-bottom">
                <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-muted fw-medium" style="font-size:12px;">
                        <i class="ri-filter-line me-1"></i>Filter:
                    </span>
                    <div>
                        <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:160px;">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:140px;">
                            <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Semester Ganjil</option>
                            <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Semester Genap</option>
                        </select>
                    </div>
                    <div>
                        <select name="grade_level_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:140px;">
                            <option value="">— Semua Tingkat —</option>
                            @foreach($gradeLevels as $gl)
                                <option value="{{ $gl->id }}" {{ $selectedGlId == $gl->id ? 'selected' : '' }}>{{ $gl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                @if($selectedAyId && $selectedGlId)
                    {{-- Sudah pilih tingkat: tampilkan tabel KKTP per mapel --}}
                    <form method="POST" action="{{ route('user.schools.kktp.store', ['userId' => $userId]) }}">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <input type="hidden" name="grade_level_id" value="{{ $selectedGlId }}">

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0 table-kktp">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th class="text-start" style="min-width:280px;">
                                            <i class="ri-book-line me-1"></i>Mata Pelajaran
                                        </th>
                                        <th style="min-width:120px;">
                                            <div style="font-size:12px;">KKTP<br><small class="fw-normal text-muted">Target per Mapel</small></div>
                                        </th>
                                        <th style="min-width:120px;">
                                            <div style="font-size:12px;">KKM<br><small class="fw-normal text-muted">Ketuntasan Minimal</small></div>
                                        </th>
                                        <th style="min-width:200px;">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($subjects as $subject)
                                        @php
                                            $existing = collect($kktpList)->firstWhere('subject_id', $subject->id);
                                        @endphp
                                        <tr>
                                            <td class="text-start">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-xs">
                                                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary" style="font-size:10px;">
                                                            {{ strtoupper(substr($subject->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <span class="fw-medium" style="font-size:13px;">{{ $subject->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center kktp-input"
                                                       name="kktp[{{ $subject->id }}][kktp_score]"
                                                       value="{{ $existing?->kktp_score ?? ($existing ? $existing->kkm_score : '') }}"
                                                       min="0" max="100" step="0.01" placeholder="0–100">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center kkm-input"
                                                       name="kktp[{{ $subject->id }}][kkm_score]"
                                                       value="{{ $existing?->kkm_score ?? 75 }}"
                                                       min="0" max="100" step="0.01" placeholder="75">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm"
                                                       name="kktp[{{ $subject->id }}][notes]"
                                                       value="{{ $existing?->notes ?? '' }}"
                                                       placeholder="Catatan (opsional)">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="ri-book-line me-1"></i>Belum ada mata pelajaran.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer border-top-dashed px-4 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted" style="font-size:12px;">
                                    <i class="ri-information-line me-1"></i>
                                    KKTP = Target Kompetensi Tuntas per mapel. KKM = Nilai minimal yang harus dicapai siswa.
                                </small>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="ri-save-line me-1"></i> Simpan KKM / KKTP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                @elseif($selectedAyId)
                    {{-- Belum pilih tingkat: info dan redirect --}}
                    <div class="text-center py-5 px-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-light rounded-circle">
                                <i class="ri-bar-chart-line fs-1 text-muted"></i>
                            </div>
                        </div>
                        <h6 class="text-muted">Pilih Tingkat di Filter</h6>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            Pilih <strong>Tingkat</strong> di atas untuk menampilkan KKM / KKTP per mata pelajaran.
                        </p>
                        @if($selectedGlId)
                            <a href="{{ route('user.schools.kktp.index', array_merge(['userId' => $userId], request()->query(), ['grade_level_id' => ''])) }}"
                               class="btn btn-secondary mt-3">
                                <i class="ri-reset-right-line me-1"></i> Reset Filter
                            </a>
                        @endif
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-light rounded-circle">
                                <i class="ri-calendar-line fs-1 text-muted"></i>
                            </div>
                        </div>
                        <h6 class="text-muted">Tidak Ada Tahun Ajaran Aktif</h6>
                        <p class="text-muted mb-0" style="font-size:13px;">Hubungi admin untuk mengaktifkan tahun ajaran.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection