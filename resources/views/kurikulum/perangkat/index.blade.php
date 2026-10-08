@extends('layouts.master')
@section('title', 'Perangkat Pembelajaran')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') Perangkat @endslot
        @slot('title') Perangkat Pembelajaran @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Perangkat Pembelajaran</h4>
            <p class="text-muted mb-0 small">Disiapkan dari ATP: kelas, mapel, guru, dan JP efektif yang sama — dengan desain Pembelajaran Mendalam.</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#perangkat-modal">
            <i class="ri-add-line align-bottom me-1"></i> Buat Perangkat
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
            <form method="GET" action="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}" class="row g-3 align-items-end">
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
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line align-bottom me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-booklet-line text-primary me-1"></i> Daftar Perangkat</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $perangkatList->count() }} perangkat</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Judul</th>
                        <th>Mapel</th>
                        <th>Kelas</th>
                        <th>Guru</th>
                        <th class="text-center">ATP</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width:150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($perangkatList as $perangkat)
                        <tr>
                            <td class="fw-medium">{{ $perangkat->judul }}</td>
                            <td>{{ $perangkat->subject?->name ?? '-' }}</td>
                            <td>{{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? '—' }}</td>
                            <td class="small">{{ $perangkat->teacher?->name ?? '—' }}</td>
                            <td class="text-center">
                                @if($perangkat->atp)
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $perangkat->atp->total_jp }} JP</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $perangkat->status === 'final' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                    {{ \App\Models\PerangkatPembelajaran::STATUS_OPTIONS[$perangkat->status] ?? $perangkat->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id]) }}" class="btn btn-sm btn-soft-primary" title="Buka">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <form action="{{ route('user.kurikulum.perangkat.destroy', ['userId' => $userId, 'id' => $perangkat->id]) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus perangkat ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-booklet-line fs-1 d-block mb-2"></i>
                                    Belum ada perangkat. Buat dari ATP yang sudah tersusun.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade zoomIn" id="perangkat-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.kurikulum.perangkat.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header bg-primary-subtle p-3">
                        <h5 class="modal-title"><i class="ri-booklet-line me-1"></i> Buat Perangkat dari ATP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($atpOptions->isEmpty())
                            <p class="text-muted small mb-0">
                                Belum ada ATP untuk mapel Anda.
                                <a href="{{ route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}" class="link-primary">Susun ATP</a> terlebih dahulu.
                            </p>
                        @else
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">ATP <span class="text-danger">*</span></label>
                                    <select name="atp_id" id="perangkat-atp" class="form-select" required onchange="applyAtpDefaults()">
                                        <option value="">-- Pilih ATP --</option>
                                        @foreach($atpOptions as $atp)
                                            <option value="{{ $atp->id }}"
                                                data-subject="{{ $atp->subject?->name }}"
                                                data-grade="{{ $atp->gradeLevel?->name }}"
                                                data-jp="{{ $atp->total_jp }}"
                                                {{ request('atp_id') === $atp->id ? 'selected' : '' }}>
                                                {{ $atp->subject?->name }} — {{ $atp->gradeLevel?->name ?? 'Semua Jenjang' }} ({{ $atp->total_jp }} JP)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Judul Perangkat <span class="text-danger">*</span></label>
                                    <input type="text" name="judul" id="perangkat-judul" class="form-control" required maxlength="255">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Kelas</label>
                                    <select name="study_group_id" class="form-select">
                                        <option value="">— Semua Kelas —</option>
                                        @foreach($studyGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Catatan</label>
                                    <textarea name="catatan" rows="2" class="form-control" placeholder="Opsional"></textarea>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        @if($atpOptions->isNotEmpty())
                            <button type="submit" class="btn btn-success"><i class="ri-save-line align-bottom me-1"></i> Buat</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function applyAtpDefaults() {
            var select = document.getElementById('perangkat-atp');
            var judul = document.getElementById('perangkat-judul');
            if (! select || ! judul) return;
            var opt = select.options[select.selectedIndex];
            if (opt && opt.value && ! judul.value) {
                judul.value = 'Perangkat ' + (opt.dataset.subject || '') + ' — ' + (opt.dataset.grade || 'Semua Jenjang');
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
            var select = document.getElementById('perangkat-atp');
            if (select && select.value) { applyAtpDefaults(); }
        });
    </script>
@endsection
