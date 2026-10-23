@extends('layouts.master')
@section('title') Penugasan GTK dari Pondok @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Personalia @endslot
        @slot('title') Penugasan GTK dari Pondok @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">Penugasan GTK Lintas Unit</h5>
                <p class="text-muted mb-0">Penempatan GTK dari pondok ke satuan pendidikan. Mapel, rombel, dan JP diatur oleh satuan pendidikan melalui SK.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPenugasanModal">
                <i class="ri-add-line me-1"></i> Tambah Penugasan
            </button>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama GTK...">
                </div>
                <div class="col-md-3">
                    <select name="school_id" class="form-select">
                        <option value="">Semua satuan pendidikan</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ request('school_id') === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Dicabut</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="ri-search-line me-1"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('user.cross-unit-assignments.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>GTK</th>
                            <th>Work Unit Asal</th>
                            <th>Ditugaskan di</th>
                            <th>Tahun Ajaran</th>
                            <th>Mapel Rekomendasi</th>
                            <th>SK / Tanggal</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $assignment->user?->name ?? '-' }}</div>
                                    <small class="text-muted">GTK Penugasan Pondok</small>
                                </td>
                                <td>{{ $assignment->homeWorkUnit?->name ?? '-' }}</td>
                                <td>{{ $assignment->school?->name ?? '-' }}</td>
                                <td>{{ $assignment->academicYear?->name ?? '-' }}</td>
                                <td>{{ $assignment->subject?->name ?? 'Ditentukan satuan pendidikan' }}</td>
                                <td>
                                    <div>{{ $assignment->decree_number ?: '-' }}</div>
                                    <small class="text-muted">
                                        {{ $assignment->start_date?->format('d M Y') ?? '-' }}
                                        @if($assignment->end_date) — {{ $assignment->end_date->format('d M Y') }} @endif
                                    </small>
                                </td>
                                <td>
                                    @if($assignment->status === 'active')
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Dicabut</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('user.cross-unit-assignments.toggle', ['userId' => $userId, 'id' => $assignment->id]) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $assignment->status === 'active' ? 'btn-soft-danger' : 'btn-soft-success' }}">
                                            {{ $assignment->status === 'active' ? 'Cabut' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada penugasan lintas unit.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $assignments->links() }}</div>
        </div>
    </div>

    <div class="modal fade" id="addPenugasanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.cross-unit-assignments.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Penugasan Pondok</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            Personalia hanya menetapkan GTK dan satuan pendidikan tujuan. Mapel, rombel, dan JP ditentukan satuan pendidikan di halaman SK.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">GTK <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">-- Pilih GTK --</option>
                                    @foreach($gtkUsers as $gtk)
                                        <option value="{{ $gtk->id }}">{{ $gtk->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Work Unit Asal <span class="text-danger">*</span></label>
                                <select name="home_work_unit_id" class="form-select" required>
                                    <option value="">-- Pilih Work Unit --</option>
                                    @foreach($workUnits as $workUnit)
                                        <option value="{{ $workUnit->id }}">{{ $workUnit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Satuan Pendidikan Tujuan <span class="text-danger">*</span></label>
                                <select name="school_id" class="form-select" required>
                                    <option value="">-- Pilih Satuan Pendidikan --</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    <option value="">-- Pilih Tahun Ajaran --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ \App\Models\AcademicYear::active()->value('id') === $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mapel Rekomendasi</label>
                                <select name="subject_id" class="form-select">
                                    <option value="">Ditentukan satuan pendidikan</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor SK Penugasan</label>
                                <input type="text" name="decree_number" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Penugasan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
