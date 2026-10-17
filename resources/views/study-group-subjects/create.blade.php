@extends('layouts.master')
@section('title') Assign Mata Pelajaran — {{ $studyGroup->full_name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2')
            <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}">
                {{ $studyGroup->full_name }}
            </a>
        @endslot
        @slot('li_3') <a href="{{ route('user.study-groups.subjects.create', ['userId' => $userId, 'id' => $studyGroup->id]) }}">Assign Mapel</a> @endslot
        @slot('title') Assign Mata Pelajaran @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Assign Mata Pelajaran ke Rombel</h5>
                </div>
                <div class="card-body">
                    @if($availableSubjects->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="ri-book-close-line fs-1 d-block mb-2"></i>
                            Tidak ada mata pelajaran yang tersedia untuk di-assign.
                            <p class="small mt-2">
                                Semua mapel di sekolah ini sudah ter-assign ke rombel ini untuk
                                <strong>{{ $academicYear?->name ?? '-' }}</strong>,
                                atau belum ada mapel yang dibuat di master data.
                            </p>
                            <a href="{{ route('user.subjects.create', ['userId' => $userId]) }}" class="btn btn-sm btn-primary">
                                <i class="ri-add-line me-1"></i> Buat Mata Pelajaran Baru
                            </a>
                        </div>
                    @else
                        <form action="{{ route('user.study-groups.subjects.store', ['userId' => $userId, 'id' => $studyGroup->id]) }}"
                              method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    @foreach($availableSubjects as $subject)
                                        <option value="{{ $subject->id }}">
                                            {{ $subject->code }} — {{ $subject->name }}
                                            ({{ ucfirst($subject->category) }}, {{ $subject->credit_hours }} JP)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-7 mb-3">
                                    <label class="form-label">Guru Pengampu</label>
                                    <select name="teacher_id" class="form-select">
                                        <option value="">-- Belum ada guru (bisa diisi nanti) --</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Jika dikosongkan, assignment tetap dibuat tetapi struktur akademik turunan
                                        (admin book, nilai) akan menunggu guru ditugaskan.
                                    </small>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">Jam per Minggu</label>
                                    <input type="number" name="weekly_hours" class="form-control"
                                           min="0.5" max="40" step="0.5" value="2">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Catatan opsional"></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}"
                                   class="btn btn-light">Batal</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Assign Mapel
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Info Rombel</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <th class="text-muted" style="width:130px">Rombel</th>
                            <td>{{ $studyGroup->full_name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Sekolah</th>
                            <td>{{ $studyGroup->school?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tahun Ajaran</th>
                            <td>
                                <span class="badge bg-info-subtle text-info">
                                    {{ $academicYear?->name ?? '-' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tingkat</th>
                            <td>{{ $studyGroup->gradeLevel?->name ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3 border-info">
                <div class="card-body">
                    <h6 class="text-info"><i class="ri-information-line me-1"></i> Apa yang terjadi saat assign?</h6>
                    <ul class="small mb-0 ps-3">
                        <li>Struktur <strong>admin book</strong> untuk guru & mapel di rombel dibuat otomatis</li>
                        <li>Konteks <strong>KKTP</strong> dicocokkan dan dilampirkan ke admin book</li>
                        <li>Slot <strong>nilai per siswa</strong> dibuat untuk seluruh anggota aktif rombel</li>
                        <li>Sistem <strong>presensi per mapel</strong> siap menerima sesi mengajar</li>
                        <li>Mapel otomatis muncul di pipeline <strong>raport</strong> siswa</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
