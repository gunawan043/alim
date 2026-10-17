@extends('layouts.master')
@section('title') Kelulusan Massal @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') Kelulusan Massal @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($studyGroup)
        {{-- MODE: dari rombel --}}
        <div class="alert alert-info">
            <i class="ri-information-line me-1"></i>
            <strong>{{ $studyGroup->full_name }}</strong> — Santri di rombel ini akan ditandai lulus.
        </div>
    @else
        <form method="GET" class="card mb-3">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="ri-graduation-cap-line me-1"></i>Filter Santri Akan Lulus</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tingkat Masuk</label>
                        <select name="entry_grade_level" class="form-control">
                            <option value="">— Pilih Tingkat —</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $selectedGrade == $i ? 'selected' : '' }}>Kelas {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tahun Masuk</label>
                        <input type="number" name="graduation_year" class="form-control"
                            value="{{ $selectedYear }}" min="1900" max="2100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sekolah</label>
                        <select name="school_id" class="form-control">
                            @if($schoolContextId)
                                <option value="{{ $schoolContextId }}">{{ $schools->find($schoolContextId)?->name ?? '' }}</option>
                            @else
                                <option value="">Semua Sekolah</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ $selectedSchoolId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i>Tampilkan</button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    @if($totalMatch > 0)
        <form method="POST" id="graduationForm" action="{{ route('user.bulk-graduation.store', ['userId' => $userId]) }}">
            @csrf
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Daftar Santri Akan Lulus ({{ $totalMatch }} orang)</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check-all">
                            <label class="form-check-label fw-semibold" for="check-all">Pilih Semua</label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light text-muted sticky-top" style="top:0">
                                <tr>
                                    <th style="width:40px"></th>
                                    <th>Nama</th>
                                    <th>NISN</th>
                                    <th>JK</th>
                                    <th>Tempat, Tgl Lahir</th>
                                    <th>Sekolah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $s)
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input student-check" type="checkbox" name="student_ids[]" value="{{ $s->id }}" checked>
                                        </td>
                                        <td class="fw-semibold">{{ $s->name }}</td>
                                        <td><code>{{ $s->nisn }}</code></td>
                                        <td>
                                            @if($s->gender === 'L')
                                                <span class="badge bg-primary-subtle text-primary">L</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">P</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $s->birth_place ?: '-' }}, {{ $s->birth_date?->format('d/m/Y') ?? '-' }}</small></td>
                                        <td><small class="text-muted">{{ $s->school?->name ?? '-' }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light"><h5 class="mb-0">Data Kelulusan</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tahun Lulus <span class="text-danger">*</span></label>
                            <input type="number" name="graduation_year" class="form-control"
                                value="{{ old('graduation_year', $selectedYear) }}" min="1900" max="2100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Lulus</label>
                            <input type="date" name="graduation_date" class="form-control"
                                value="{{ old('graduation_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No. Ijazah</label>
                            <input type="text" name="graduation_certificate_number" class="form-control"
                                value="{{ old('graduation_certificate_number') }}" placeholder="Opsional">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nama Sekolah</label>
                            <input type="text" name="graduation_school_name" class="form-control"
                                value="{{ old('graduation_school_name') }}" placeholder="Opsional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <textarea name="reason" class="form-control" rows="2" placeholder="Keterangan opsional">{{ old('reason') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('user.students.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmModal">
                        <i class="ri-graduation-cap-line me-1"></i>Tandai Lulus ({{ $totalMatch }})
                    </button>
                </div>
            </div>
        </form>

        {{-- Modal Konfirmasi --}}
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmModalLabel">
                            <i class="ri-checkbox-circle-line me-1 text-success"></i>Konfirmasi Kelulusan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <strong>{{ $totalMatch }}</strong> santri akan ditandai <strong>LULUS</strong>.
                        Tindakan ini tidak dapat dibatalkan.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" form="graduationForm" class="btn btn-success">
                            <i class="ri-checkbox-circle-line me-1"></i>Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @elseif(request()->has('entry_grade_level'))
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="ri-search-line fs-1 text-muted"></i>
                <h5 class="mt-2">Tidak ada santri yang cocok</h5>
                <p>Coba ubah filter tingkat dan tahun masuk.</p>
            </div>
        </div>
    @endif
@endsection

@section('script')
<script>
document.getElementById('check-all')?.addEventListener('change', function() {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = this.checked);
});
</script>
@endsection
