@extends('layouts.master')
@section('title') {{ $subject->name }} @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}">Mata Pelajaran</a> @endslot
        @slot('title') {{ $subject->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ri-book-open-line text-primary me-1"></i>{{ $subject->name }}
                        <span class="badge bg-dark-subtle text-dark ms-1">{{ $subject->code ?? '—' }}</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('user.subjects.sync-grades', ['userId' => $userId, 'id' => $subject->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success"
                                    onclick="return confirm('Terapkan mapel ini ke seluruh tingkat aktif? JP memakai default mapel.')">
                                <i class="ri-stack-line me-1"></i> Terapkan ke Semua Tingkat
                            </button>
                        </form>
                        <a href="{{ route('user.subjects.edit', ['userId' => $userId, 'id' => $subject->id]) }}" class="btn btn-sm btn-warning">
                            <i class="ri-pencil-line me-1"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width:180px;">Sekolah</th>
                            <td>
                                @if($subject->school)
                                    <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $subject->school_id]) }}">{{ $subject->school->name }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kode</th>
                            <td><span class="badge bg-dark-subtle text-dark">{{ $subject->code }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Nama</th>
                            <td>{{ $subject->name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kategori</th>
                            <td>
                                @if($subject->category === 'nasional')
                                    <span class="badge bg-info-subtle text-info">Nasional</span>
                                @elseif($subject->category === 'lokal')
                                    <span class="badge bg-warning-subtle text-warning">Lokal</span>
                                @else
                                    <span class="badge bg-purple-subtle text-purple">Muatan Lokal</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Jam Pelajaran / Minggu</th>
                            <td class="fw-semibold">{{ $subject->credit_hours }} JP</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Deskripsi</th>
                            <td>{{ $subject->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                @if($subject->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Dibuat</th>
                            <td>{{ $subject->created_at->translatedFormat('d F Y, H:i') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Diperbarui</th>
                            <td>{{ $subject->updated_at->translatedFormat('d F Y, H:i') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.subjects.edit', ['userId' => $userId, 'id' => $subject->id]) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i> Edit
                    </a>
                    <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="btn btn-light">Kembali</a>
                </div>
            </div>

            {{-- JP PER TINGKAT: sumber yang sama dengan Peta Kurikulum. --}}
            <div class="card mt-3">
                <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0">
                        <i class="ri-stack-line text-primary me-1"></i>JP per Tingkat
                    </h5>
                    <span class="text-muted small">
                        {{ $academicYear?->name ?? 'Tahun ajaran aktif' }} &middot; 1 mapel boleh berbeda JP antar tingkat
                    </span>
                </div>
                <div class="card-body">
                    @if($gradeHours->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="ri-stack-line fs-1 d-block mb-2"></i>
                            Mapel ini belum dipasang di tingkat mana pun.
                            <br>
                            <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary mt-2">
                                Buka Data Kelas
                            </a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('user.subjects.grade-hours.save', ['userId' => $userId, 'id' => $subject->id]) }}">
                            @csrf
                            @if($academicYear)
                                <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                            @endif

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tingkat</th>
                                            <th class="text-center" style="width:120px">JP / Minggu</th>
                                            <th class="text-center" style="width:110px">JP Efektif</th>
                                            <th class="text-center" style="width:90px">Rombel</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($gradeHours as $row)
                                            <tr>
                                                <td class="fw-medium">
                                                    {{ $row['grade'] }}
                                                    @if($row['fase'])
                                                        <span class="badge bg-info-subtle text-info ms-1">{{ $row['fase'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                           name="jp[{{ $row['grade_level_id'] }}]"
                                                           value="{{ $row['hours'] }}"
                                                           min="0" max="40" step="1"
                                                           class="form-control form-control-sm text-center"
                                                           style="width:80px;margin:0 auto">
                                                </td>
                                                <td class="text-center text-muted">{{ $row['jp_efektif'] }}</td>
                                                <td class="text-center text-muted">{{ $row['rombel'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                <span class="text-muted small">
                                    <i class="ri-information-line me-1"></i>
                                    Perubahan menyinkron otomatis ke rombel dan pembagian tugas guru.
                                </span>
                                @if($canEditGradeHours)
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-save-line me-1"></i>Simpan JP per Tingkat
                                    </button>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Hanya lihat</span>
                                @endif
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-links-line text-primary me-1"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-list-check me-2"></i> Daftar Mata Pelajaran
                        </a>
                        <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-stack-line me-2"></i> Data Kelas
                        </a>
                        <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId, 'subject_id' => $subject->id]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-user-star-line me-2"></i> Plotting Guru Mapel Ini
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
