@extends('layouts.master')
@section('title', 'Alur Tujuan Pembelajaran')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') ATP @endslot
        @slot('title') Alur Tujuan Pembelajaran @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Alur Tujuan Pembelajaran (ATP)</h4>
            <p class="text-muted mb-0 small">Susunan TP sistematis per mapel &amp; jenjang, dibandingkan dengan JP efektif dari Pekan Efektif.</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#atp-modal">
            <i class="ri-add-line align-bottom me-1"></i> Susun ATP
        </button>
    </div>

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

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('user.kurikulum.atp.index', ['userId' => $userId]) }}" class="row g-3 align-items-end">
                <div class="col-xxl-3 col-sm-6">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-6">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <label class="form-label">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select">
                        <option value="">— Semua Mapel —</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-6">
                    <label class="form-label">Jenjang</label>
                    <select name="grade_level_id" class="form-select">
                        <option value="">— Semua —</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}" {{ request('grade_level_id') === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="ri-search-line align-bottom me-1"></i> Filter</button>
                    <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId]) }}" class="btn btn-light"><i class="ri-refresh-line align-bottom"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-route-line text-primary me-1"></i> Daftar ATP</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $atpList->count() }} ATP</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
