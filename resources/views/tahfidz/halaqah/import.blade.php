@extends('layouts.master')
@section('title', 'Impor Halaqah')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $isPreview = isset($plan);
    @endphp
    @include('tahfidz._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah</a> @endslot
        @slot('li_2') Impor
        @slot('title') Impor Halaqah
    @endcomponent

    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('warning'))<div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @unless($isPreview)
        {{-- Langkah 1: unggah berkas --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-1"><i class="ri-upload-2-line me-1"></i>Unggah Berkas</h5>
                    <p class="text-muted small mb-0">Data belum disimpan. Anda akan melihat pratinjau terlebih dahulu.</p>
                </div>
                <a href="{{ route('user.tahfidz.halaqah.import.template', ['userId' => $userId, 'school_id' => $schoolId, 'academic_year_id' => $academicYearId]) }}"
                   class="btn btn-outline-success btn-sm">
                    <i class="ri-file-excel-2-line me-1"></i>Unduh template
                </a>
            </div>
            <form method="POST" action="{{ route('user.tahfidz.halaqah.import.preview', ['userId' => $userId]) }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                            @if($canChooseSchool)
                                <select name="school_id" class="form-select" required>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" @selected((string) $schoolId === (string) $school->id)>{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ $schools->firstWhere('id', $schoolId)->name ?? '—' }}" readonly disabled>
                                <input type="hidden" name="school_id" value="{{ $schoolId }}">
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select name="academic_year_id" class="form-select" required>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected((string) $academicYearId === (string) $year->id)>{{ $year->name }} — {{ ucfirst($year->semester) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Berkas <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">Maksimal 10 MB. Format .xlsx, .xls, atau .csv.</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    <button class="btn btn-success"><i class="ri-eye-line me-1"></i>Pratinjau</button>
                </div>
            </form>
        </div>
    @else
        {{-- Langkah 2: pratinjau --}}
        <div class="row g-3 mb-3">
            @foreach([
                ['Halaqah', $plan['totals']['halaqah'], 'ri-team-line', 'primary'],
                ['Baru', $plan['totals']['baru'], 'ri-add-circle-line', 'success'],
                ['Diperbarui', $plan['totals']['diperbarui'], 'ri-refresh-line', 'info'],
                ['Anggota', $plan['totals']['anggota'], 'ri-user-heart-line', 'warning'],
                ['Bermasalah', $plan['totals']['error'], 'ri-error-warning-line', 'danger'],
            ] as [$label, $value, $icon, $color])
                <div class="col-xl col-md-4">
                    <div class="card">
                        <div class="card-body py-3 d-flex align-items-center gap-2">
                            <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">{{ $label }}</p>
                                <h4 class="fw-bold mb-0">{{ number_format($value) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($plan['errors'] !== [])
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-1 text-danger"><i class="ri-error-warning-line me-1"></i>Baris yang tidak akan disimpan</h5>
                    <p class="text-muted small mb-0">Baris ini dilewati; baris lain tetap diproses. Perbaiki lalu unggah ulang bila perlu.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;" class="text-center">Baris</th>
                                <th style="width:220px;">Halaqah</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plan['errors'] as $error)
                                <tr>
                                    <td class="text-center text-muted">{{ $error['line'] }}</td>
                                    <td>{{ $error['halaqah'] }}</td>
                                    <td class="text-danger">{{ $error['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-1"><i class="ri-checkbox-circle-line me-1"></i>Halaqah yang akan disimpan</h5>
                <p class="text-muted small mb-0">
                    {{ $school->name ?? '' }} —
                    Data lama yang tidak tercantum di berkas <strong>tidak dihapus</strong>, dan anggota lama tidak dikeluarkan.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Halaqah</th>
                            <th style="width:140px;">Kelas</th>
                            <th>Guru</th>
                            <th style="width:110px;" class="text-center">Anggota</th>
                            <th style="width:120px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plan['groups'] as $group)
                            <tr>
                                <td class="fw-semibold">{{ $group['name'] }}</td>
                                <td>{{ $group['kelas_raw'] ?: '—' }}</td>
                                <td>{{ $group['guru_raw'] ?: '—' }}</td>
                                <td class="text-center">{{ count($group['member_ids']) }}</td>
                                <td>
                                    @if($group['action'] === 'update')
                                        <span class="badge bg-info-subtle text-info">Diperbarui</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">Baru</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Tidak ada halaqah valid pada berkas ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('user.tahfidz.halaqah.import.form', ['userId' => $userId, 'academic_year_id' => $academicYearId]) }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Unggah berkas lain
                </a>
                <form method="POST" action="{{ route('user.tahfidz.halaqah.import.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                    <button class="btn btn-success" @disabled($plan['groups'] === [])>
                        <i class="ri-save-line me-1"></i>Simpan {{ count($plan['groups']) }} halaqah
                    </button>
                </form>
            </div>
        </div>
    @endunless
@endsection
