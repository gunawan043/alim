@extends('layouts.master')

@section('title') Mapping Dapodik @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') Mapping Dapodik @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-title bg-primary-subtle text-primary rounded fs-4" style="width:44px;height:44px;">
                        <i class="ri-team-line"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Santri Aktif</p>
                        <h4 class="mb-0">{{ number_format($totalStudents) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-title bg-success-subtle text-success rounded fs-4" style="width:44px;height:44px;">
                        <i class="ri-link-m"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Sudah Dipetakan</p>
                        <h4 class="mb-0">{{ number_format($mappedCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-title bg-warning-subtle text-warning rounded fs-4" style="width:44px;height:44px;">
                        <i class="ri-link-unlink-m"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Belum Dipetakan</p>
                        <h4 class="mb-0">{{ number_format(max(0, $totalStudents - $mappedCount)) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-title bg-info-subtle text-info rounded fs-4" style="width:44px;height:44px;">
                        <i class="ri-progress-5-line"></i>
                    </span>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0 small">Progres</p>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="mb-0">{{ $totalStudents > 0 ? round(($mappedCount / $totalStudents) * 100) : 0 }}%</h4>
                            <div class="progress flex-grow-1" style="height:6px;">
                                <div class="progress-bar bg-success" style="width: {{ $totalStudents > 0 ? round(($mappedCount / $totalStudents) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter & aksi massal --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" @selected($yearId === $ay->id)>{{ $ay->name }} · {{ ucfirst($ay->semester) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Cari Santri</label>
                    <input type="search" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nama / NISN / NIS">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Rombel Operasional</label>
                    <select name="study_group_id" class="form-select form-select-sm">
                        <option value="">Semua rombel</option>
                        @foreach($studyGroups as $group)
                            <option value="{{ $group->id }}" @selected((string) $studyGroupId === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="mapping_status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="mapped" @selected($mappingStatus === 'mapped')>Sudah dipetakan</option>
                        <option value="unmapped" @selected($mappingStatus === 'unmapped')>Belum dipetakan</option>
                    </select>
                </div>
                <div class="col-lg-1 col-md-12 d-flex gap-1">
                    <button class="btn btn-primary btn-sm w-100" type="submit" title="Terapkan filter">
                        <i class="ri-filter-3-line"></i>
                    </button>
                    <a href="{{ route('user.students.dapodik-mapping', ['userId' => $userId]) }}"
                       class="btn btn-light btn-sm" title="Reset filter">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </form>

            <hr class="my-3">

            <div class="row g-2 align-items-end">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="mb-0"><i class="ri-magic-line me-1 text-primary"></i>Isi Cepat Rombel Dapodik</h6>
                            <p class="text-muted mb-0 small">
                                Isi kolom di bawah lalu terapkan ke semua baris pada halaman ini. Nilai yang sudah ada akan ditimpa.
                            </p>
                        </div>
                        <a href="{{ route('user.students.dapodik-export', ['userId' => $userId, 'academic_year_id' => $yearId]) }}"
                           class="btn btn-sm btn-soft-success">
                            <i class="ri-file-excel-2-line me-1"></i>Export CSV
                        </a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <input type="text" id="bulkNpsn" class="form-control form-control-sm" placeholder="NPSN Dapodik (mis. {{ $school?->npsn }})">
                </div>
                <div class="col-lg-3 col-md-6">
                    <input type="text" id="bulkRombel" class="form-control form-control-sm" placeholder="Nama rombel Dapodik">
                </div>
                <div class="col-lg-2 col-md-6">
                    <input type="text" id="bulkCode" class="form-control form-control-sm" placeholder="Kode rombel">
                </div>
                <div class="col-lg-2 col-md-6">
                    <input type="text" id="bulkLevel" class="form-control form-control-sm" placeholder="Tingkat">
                </div>
                <div class="col-lg-2 col-md-12">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100" id="applyBulkMapping">
                        <i class="ri-arrow-down-double-line me-1"></i>Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('user.students.dapodik-mapping.save', ['userId' => $userId]) }}">
        @csrf
        <input type="hidden" name="academic_year_id" value="{{ $yearId }}">

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0">Daftar Santri</h5>
                    <p class="text-muted mb-0 small">
                        Menampilkan {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }}
                        dari {{ number_format($students->total()) }} santri.
                    </p>
                </div>
                <span class="badge bg-primary-subtle text-primary">
                    {{ $year?->name ?? '-' }} · {{ ucfirst($year?->semester ?? '-') }}
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 68vh; overflow:auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light" style="position:sticky;top:0;z-index:2;">
                            <tr>
                                <th style="min-width:220px;">Santri</th>
                                <th style="min-width:130px;">Rombel Operasional</th>
                                <th style="min-width:110px;">Unit</th>
                                <th style="min-width:150px;">NPSN Dapodik</th>
                                <th style="min-width:190px;">Rombel Dapodik</th>
                                <th style="min-width:120px;">Kode</th>
                                <th style="min-width:90px;">Tingkat</th>
                                <th style="width:90px;" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php
                                    $history = $student->classHistories->first();
                                    $group = $history?->studyGroup;
                                    $mapping = $mappings->get($student->id);
                                    $isMapped = $mapping && filled($mapping->dapodik_rombel_name);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $student->name }}</div>
                                        <div class="small text-muted">{{ $student->nisn ?: $student->nis ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @if($group)
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $group->name }}</span>
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 ms-1 js-copy-group"
                                                    data-group="{{ $group->name }}"
                                                    title="Salin nama rombel operasional">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $group?->school?->name ?? '—' }}</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm js-npsn"
                                               name="mappings[{{ $student->id }}][dapodik_school_npsn]"
                                               value="{{ $mapping?->dapodik_school_npsn ?? $school?->npsn }}"
                                               placeholder="NPSN">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm js-rombel"
                                               name="mappings[{{ $student->id }}][dapodik_rombel_name]"
                                               value="{{ $mapping?->dapodik_rombel_name }}"
                                               placeholder="Nama rombel Dapodik">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm js-code"
                                               name="mappings[{{ $student->id }}][dapodik_rombel_code]"
                                               value="{{ $mapping?->dapodik_rombel_code }}"
                                               placeholder="Kode">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm js-level"
                                               name="mappings[{{ $student->id }}][dapodik_level]"
                                               value="{{ $mapping?->dapodik_level }}"
                                               placeholder="Tingkat">
                                    </td>
                                    <td class="text-center">
                                        @if($isMapped)
                                            <span class="badge bg-success-subtle text-success">Terpetakan</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Belum</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                                        Tidak ada santri yang cocok dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($students->isNotEmpty())
                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-muted">
                        <i class="ri-information-line me-1"></i>
                        Perubahan hanya tersimpan setelah tombol <strong>Simpan Mapping</strong> ditekan.
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-3-line me-1"></i>Simpan Mapping
                    </button>
                </div>
            @endif
        </div>
    </form>

    @if($students->hasPages())
        <div class="mt-3">
            {{ $students->links() }}
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const applyBulk = document.getElementById('applyBulkMapping');

            if (applyBulk) {
                applyBulk.addEventListener('click', function () {
                    const npsn = document.getElementById('bulkNpsn').value.trim();
                    const rombel = document.getElementById('bulkRombel').value.trim();
                    const code = document.getElementById('bulkCode').value.trim();
                    const level = document.getElementById('bulkLevel').value.trim();

                    if (!npsn && !rombel && !code && !level) {
                        window.alert('Isi minimal satu kolom untuk diterapkan.');
                        return;
                    }

                    if (!window.confirm('Terapkan nilai ini ke semua baris pada halaman ini? Nilai yang sudah ada akan ditimpa.')) {
                        return;
                    }

                    document.querySelectorAll('.js-npsn').forEach(el => { if (npsn) el.value = npsn; });
                    document.querySelectorAll('.js-rombel').forEach(el => { if (rombel) el.value = rombel; });
                    document.querySelectorAll('.js-code').forEach(el => { if (code) el.value = code; });
                    document.querySelectorAll('.js-level').forEach(el => { if (level) el.value = level; });
                });
            }

            document.querySelectorAll('.js-copy-group').forEach(function (button) {
                button.addEventListener('click', function () {
                    const row = button.closest('tr');
                    const target = row ? row.querySelector('.js-rombel') : null;

                    if (target) {
                        target.value = button.dataset.group || '';
                        target.focus();
                    }
                });
            });
        });
    </script>
@endpush
