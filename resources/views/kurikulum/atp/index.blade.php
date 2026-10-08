@extends('layouts.master')
@section('title') Alur Tujuan Pembelajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') ATP @endslot
        @slot('title') Alur Tujuan Pembelajaran @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Alur Tujuan Pembelajaran (ATP)</h4>
                <p class="text-muted small mb-0">Susunan TP sistematis per mapel &amp; jenjang, dibandingkan dengan JP efektif dari Pekan Efektif.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#atp-modal">
                <i class="ri-add-line me-1"></i> Susun ATP
            </button>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.kurikulum.atp.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Mapel</label>
                    <select name="subject_id" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Jenjang</label>
                    <select name="grade_level_id" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}" {{ request('grade_level_id') === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100"><i class="ri-filter-3-line me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mapel</th>
                            <th>Jenjang / Fase</th>
                            <th>Guru Penyusun</th>
                            <th class="text-center">Jumlah TP</th>
                            <th class="text-center">Total JP</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
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
                                <td>
                                    <span class="badge {{ $atp->status === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                        {{ \App\Models\AlurTujuanPembelajaran::STATUS_OPTIONS[$atp->status] ?? $atp->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $atp->id]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ri-eye-line"></i> Buka
                                    </a>
                                    <form action="{{ route('user.kurikulum.atp.destroy', ['userId' => $userId, 'id' => $atp->id]) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus ATP ini beserta susunan TP-nya?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada ATP pada filter ini. Klik <strong>Susun ATP</strong>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="atp-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.kurikulum.atp.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Susun ATP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Buat ATP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
