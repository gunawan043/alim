@extends('layouts.master')
@section('title') Cetak Rapor — {{ $studyGroup->name ?? '' }} @endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? Auth::id();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">Nilai Kelas</a> @endslot
        @slot('title') Cetak Rapor @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">
                                <i class="ri-file-paper-2-line text-primary me-1"></i>
                                Cetak Rapor Santri
                            </h5>
                            <p class="text-muted mb-0" style="font-size:12px;">
                                {{ $studyGroup->school?->name ?? '' }} — {{ $studyGroup->name }} — TA {{ $selectedAy?->name ?? '' }} Semester {{ ucfirst($selectedSem) }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" style="font-size:12px;">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Semester</label>
                            <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="ganjil" {{ $selectedSem == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSem == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Cari Nama</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Nama Santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                    </form>

                    {{-- Student List --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>NIS</th>
                                    <th>Nama Santri</th>
                                    <th style="width:120px;text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNum = 0; @endphp
                                @forelse($students as $history)
                                    @php
                                        $student = $history->student;
                                        if (request('search') && stripos($student->name, request('search')) === false)
                                            continue;
                                    @endphp
                                    @php $rowNum++; @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $rowNum }}</td>
                                        <td class="text-center">{{ $student->nis ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary text-white" style="font-size:10px;">
                                                        {{ strtoupper(substr($student->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <span class="fw-semibold">{{ $student->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.schools.nilai-kelas.rapor.cetak', [
                                                'userId' => $userId,
                                                'studyGroupId' => $studyGroup->id,
                                                'studentId' => $student->id,
                                                'academic_year_id' => $selectedAyId,
                                                'semester' => $selectedSem,
                                            ]) }}"
                                               class="btn btn-primary btn-sm">
                                                <i class="ri-download-2-line me-1"></i> Unduh PDF
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="ri-group-line me-1"></i>Tidak ada Santri.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
