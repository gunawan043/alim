@extends('layouts.master')
@section('title', 'Alur Tujuan Pembelajaran')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();

        $totalAtp = $atpList->count();
        $terbit = $atpList->where('status', 'published')->count();
        $draft = $totalAtp - $terbit;
        $totalTp = (int) $atpList->sum('items_count');
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') ATP @endslot
        @slot('title') Alur Tujuan Pembelajaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-route-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total ATP</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalAtp) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Pada filter ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-check-double-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Terbit</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($terbit) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-send-plane-line me-1"></i>ATP berstatus terbit</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-draft-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Draft</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($draft) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-edit-line me-1"></i>Masih disusun</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-list-check-2 text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">TP Teralur</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalTp) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-links-line me-1"></i>Total TP dalam ATP</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="atpList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar ATP</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $totalAtp }} ATP</span>
                                <span class="text-muted small ms-2">Susunan TP sistematis per mapel &amp; jenjang, dibandingkan dengan JP efektif.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.kurikulum.atp.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                    <select name="academic_year_id" class="form-select" style="width:150px" onchange="this.form.submit()">
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="semester" class="form-select" style="width:110px" onchange="this.form.submit()">
                                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                                    </select>
                                    <select name="subject_id" class="form-select" style="width:180px" onchange="this.form.submit()">
                                        <option value="">— Semua Mapel —</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="grade_level_id" class="form-select" style="width:140px" onchange="this.form.submit()">
                                        <option value="">— Semua Jenjang —</option>
                                        @foreach($gradeLevels as $grade)
                                            <option value="{{ $grade->id }}" {{ request('grade_level_id') === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#atp-modal">
                                    <i class="ri-add-line align-bottom me-1"></i> Susun ATP
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'ganjil']) }}" class="filter-badge {{ $semester === 'ganjil' ? 'active' : '' }}">Ganjil</a>
                        <a href="{{ request()->fullUrlWithQuery(['semester' => 'genap']) }}" class="filter-badge {{ $semester === 'genap' ? 'active' : '' }}">Genap</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mapel</th>
                                <th>Jenjang / Fase</th>
                                <th>Guru Penyusun</th>
                                <th class="text-center">Jumlah TP</th>
                                <th class="text-center">Total JP</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:150px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($atpList as $atp)
                                <tr>
                                    <td class="fw-medium">{{ $atp->subject?->name ?? '-' }}</td>
                                    <td>
                                        {{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }}
                                        @if($atp->fase) <span class="badge bg-info-subtle text-info ms-1">{{ $atp->fase }}</span> @endif
                                    </td>
                                    <td class="small">{{ $atp->teacher?->name ?? '—' }}</td>
                                    <td class="text-center">{{ $atp->items_count }}</td>
                                    <td class="text-center fw-semibold">{{ $atp->total_jp }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $atp->status === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                            {{ \App\Models\AlurTujuanPembelajaran::STATUS_OPTIONS[$atp->status] ?? $atp->status }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $atp->id]) }}" class="btn btn-sm btn-soft-primary" title="Buka">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <form action="{{ route('user.kurikulum.atp.destroy', ['userId' => $userId, 'id' => $atp->id]) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus ATP ini beserta susunan TP-nya?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-route-line fs-1 d-block mb-2"></i>
                                            Belum ada ATP pada filter ini. Klik <strong>Susun ATP</strong>.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade zoomIn" id="atp-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.kurikulum.atp.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header bg-primary-subtle p-3">
                        <h5 class="modal-title"><i class="ri-route-line me-1"></i> Susun ATP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenjang</label>
                                <select name="grade_level_id" class="form-select">
                                    <option value="">— Semua Jenjang —</option>
                                    @foreach($gradeLevels as $grade)
                                        <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="catatan" rows="2" class="form-control" placeholder="Opsional"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-save-line align-bottom me-1"></i> Buat ATP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
