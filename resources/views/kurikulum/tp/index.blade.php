@extends('layouts.master')
@section('title', 'Tujuan Pembelajaran')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();

        $totalTp = $tpList->count();
        $dalamAtp = $tpList->filter(fn ($tp) => in_array($tp->id, $usedTpIds, true))->count();
        $belumAtp = $totalTp - $dalamAtp;
        $mapelCount = $tpList->pluck('subject_id')->filter()->unique()->count();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') TP @endslot
        @slot('title') Tujuan Pembelajaran @endslot
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
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-list-check-2 text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total TP</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalTp) }}</h3>
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
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-route-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Sudah di ATP</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($dalamAtp) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>TP sudah masuk alur</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-error-warning-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Belum di ATP</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($belumAtp) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Perlu disusun ke ATP</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-book-2-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mata Pelajaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($mapelCount) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-price-tag-3-line me-1"></i>Mapel tercakup TP</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="tpList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar TP</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $totalTp }} TP</span>
                                <span class="text-muted small ms-2">TP diturunkan dari CP; buat, ubah, dan urutkan sebagai dasar ATP.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <form method="GET" action="{{ route('user.kurikulum.tp.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
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
                                            <option value="{{ $subject->id }}" {{ $subjectId === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="grade_level_id" class="form-select" style="width:140px" onchange="this.form.submit()">
                                        <option value="">— Semua Jenjang —</option>
                                        @foreach($gradeLevels as $grade)
                                            <option value="{{ $grade->id }}" {{ request('grade_level_id') === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('user.kurikulum.tp.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                </form>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tp-modal" onclick="openCreate()">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah TP
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
                        <span class="text-muted small ms-2 me-2">·</span>
                        <span class="text-muted small">{{ $dalamAtp }} sudah di ATP · {{ $belumAtp }} belum</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode / Mapel</th>
                                <th>Tujuan Pembelajaran</th>
                                <th>CP / Elemen</th>
                                <th class="text-center">Fase</th>
                                <th class="text-center">JP</th>
                                <th class="text-center" style="width:110px">Urutan</th>
                                <th class="text-end" style="width:110px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tpList as $tp)
                                @php
                                    $canManage = $isKurikulumTeam || in_array($tp->subject_id, $taughtSubjectIds, true);
                                    $inAtp = in_array($tp->id, $usedTpIds, true);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">{{ $tp->kode_tp }}</span>
                                        <div class="small text-muted mt-1">{{ $tp->subject?->name }}</div>
                                        <div class="small text-muted">{{ $tp->gradeLevel?->name ?? 'Semua Jenjang' }}</div>
                                    </td>
                                    <td class="small">{{ $tp->deskripsi }}</td>
                                    <td class="small text-muted">
                                        @if($tp->capaianPembelajaran)
                                            <div>{{ \Illuminate\Support\Str::limit($tp->capaianPembelajaran->deskripsi, 60) }}</div>
                                        @endif
                                        {{ $tp->elemen ?: '—' }}
                                    </td>
                                    <td class="text-center">{{ $tp->fase ?: '—' }}</td>
                                    <td class="text-center">{{ $tp->alokasi_waktu }}</td>
                                    <td class="text-center">
                                        @if($canManage)
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <form action="{{ route('user.kurikulum.tp.move', ['userId' => $userId, 'id' => $tp->id]) }}" method="POST">
                                                    @csrf <input type="hidden" name="direction" value="up">
                                                    <button class="btn btn-sm btn-soft-secondary" title="Naik"><i class="ri-arrow-up-line"></i></button>
                                                </form>
                                                <span class="fw-semibold">{{ $tp->urutan }}</span>
                                                <form action="{{ route('user.kurikulum.tp.move', ['userId' => $userId, 'id' => $tp->id]) }}" method="POST">
                                                    @csrf <input type="hidden" name="direction" value="down">
                                                    <button class="btn btn-sm btn-soft-secondary" title="Turun"><i class="ri-arrow-down-line"></i></button>
                                                </form>
                                            </div>
                                        @else
                                            {{ $tp->urutan }}
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($canManage)
                                            <button class="btn btn-sm btn-soft-warning"
                                                data-tp="{{ json_encode([
                                                    'id' => $tp->id,
                                                    'subject_id' => $tp->subject_id,
                                                    'grade_level_id' => $tp->grade_level_id,
                                                    'academic_year_id' => $tp->academic_year_id,
                                                    'semester' => $tp->semester,
                                                    'capaian_pembelajaran_id' => $tp->capaian_pembelajaran_id,
                                                    'kode_tp' => $tp->kode_tp,
                                                    'deskripsi' => $tp->deskripsi,
                                                    'elemen' => $tp->elemen,
                                                    'fase' => $tp->fase,
                                                    'alokasi_waktu' => $tp->alokasi_waktu,
                                                    'urutan' => $tp->urutan,
                                                    'is_active' => (bool) $tp->is_active,
                                                ]) }}"
                                                onclick="openEdit(this)" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </button>
                                            @if(! $inAtp)
                                                <form action="{{ route('user.kurikulum.tp.destroy', ['userId' => $userId, 'id' => $tp->id]) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Hapus TP ini?');">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary" title="Dipakai di ATP">ATP</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-list-check-2 fs-1 d-block mb-2"></i>
                                            Belum ada TP pada filter ini.
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

    <div class="modal fade zoomIn" id="tp-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" id="tp-form" action="{{ route('user.kurikulum.tp.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="tp-method" value="POST">
                    <div class="modal-header bg-primary-subtle p-3">
                        <h5 class="modal-title" id="tp-modal-title"><i class="ri-list-check-2 me-1"></i> Tambah TP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" id="tp-subject" class="form-select" required onchange="filterCpOptions()">
                                    <option value="">-- Pilih Mapel --</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenjang</label>
                                <select name="grade_level_id" id="tp-grade" class="form-select" onchange="applyGradeFase()">
                                    <option value="">— Semua Jenjang —</option>
                                    @foreach($gradeLevels as $grade)
                                        <option value="{{ $grade->id }}" data-fase="{{ $grade->fase }}">{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" id="tp-ay" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" id="tp-semester" class="form-select" required>
                                    <option value="ganjil">Ganjil</option>
                                    <option value="genap">Genap</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode TP <span class="text-danger">*</span></label>
                                <input type="text" name="kode_tp" id="tp-kode" class="form-control" maxlength="20" required placeholder="TP.01">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Diturunkan dari CP</label>
                                <select name="capaian_pembelajaran_id" id="tp-cp" class="form-select" onchange="applyCpDefaults()">
                                    <option value="">— Tanpa CP —</option>
                                </select>
                                <small class="text-muted">TP sebaiknya diturunkan dari CP mapel &amp; fase yang sesuai.</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi TP <span class="text-danger">*</span></label>
                                <textarea name="deskripsi" id="tp-deskripsi" rows="3" class="form-control" required
                                    placeholder="Contoh: Peserta didik mampu menganalisis ..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Elemen</label>
                                <input type="text" name="elemen" id="tp-elemen" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Fase</label>
                                <input type="text" name="fase" id="tp-fase" class="form-control" maxlength="5">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Alokasi JP</label>
                                <input type="number" name="alokasi_waktu" id="tp-jp" class="form-control" min="1" max="100" value="2">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Urutan</label>
                                <input type="number" name="urutan" id="tp-urutan" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" id="tp-active" class="form-check-input" value="1" checked>
                                    <label class="form-check-label" for="tp-active">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-save-line align-bottom me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var tpStoreUrl = @json(route('user.kurikulum.tp.store', ['userId' => $userId]));
        var tpUpdateUrl = @json($tpUpdateUrlTemplate);
        var cpOptions = @json($cpOptionsJson);

        function filterCpOptions(selectedId) {
            var subjectId = document.getElementById('tp-subject').value;
            var select = document.getElementById('tp-cp');
            select.innerHTML = '<option value="">— Tanpa CP —</option>';
            cpOptions.filter(function (cp) { return ! subjectId || cp.subject_id === subjectId; })
                .forEach(function (cp) {
                    var opt = document.createElement('option');
                    opt.value = cp.id;
                    opt.textContent = '[' + (cp.fase || '-') + '] ' + (cp.elemen ? cp.elemen + ' — ' : '') + cp.deskripsi;
                    opt.dataset.fase = cp.fase || '';
                    opt.dataset.elemen = cp.elemen || '';
                    if (selectedId && selectedId === cp.id) { opt.selected = true; }
                    select.appendChild(opt);
                });
        }

        function applyGradeFase() {
            var grade = document.getElementById('tp-grade');
            var fase = grade.options[grade.selectedIndex] ? grade.options[grade.selectedIndex].dataset.fase : '';
            if (fase) { document.getElementById('tp-fase').value = fase; }
        }

        function applyCpDefaults() {
            var cp = document.getElementById('tp-cp');
            var opt = cp.options[cp.selectedIndex];
            if (! opt || ! opt.value) { return; }
            if (opt.dataset.fase) { document.getElementById('tp-fase').value = opt.dataset.fase; }
            if (opt.dataset.elemen) { document.getElementById('tp-elemen').value = opt.dataset.elemen; }
        }

        function openCreate() {
            document.getElementById('tp-modal-title').innerHTML = '<i class="ri-list-check-2 me-1"></i> Tambah TP';
            document.getElementById('tp-form').action = tpStoreUrl;
            document.getElementById('tp-method').value = 'POST';
            document.getElementById('tp-subject').value = @json($subjectId ?? '');
            document.getElementById('tp-grade').value = '';
            document.getElementById('tp-ay').value = @json($academicYearId);
            document.getElementById('tp-semester').value = @json($semester);
            document.getElementById('tp-kode').value = '';
            document.getElementById('tp-deskripsi').value = '';
            document.getElementById('tp-elemen').value = '';
            document.getElementById('tp-fase').value = '';
            document.getElementById('tp-jp').value = 2;
            document.getElementById('tp-urutan').value = 0;
            document.getElementById('tp-active').checked = true;
            filterCpOptions();
        }

        function openEdit(btn) {
            var tp = JSON.parse(btn.dataset.tp);
            document.getElementById('tp-modal-title').innerHTML = '<i class="ri-pencil-line me-1"></i> Edit TP';
            document.getElementById('tp-form').action = tpUpdateUrl.replace('__ID__', tp.id);
            document.getElementById('tp-method').value = 'PUT';
            document.getElementById('tp-subject').value = tp.subject_id;
            document.getElementById('tp-grade').value = tp.grade_level_id || '';
            document.getElementById('tp-ay').value = tp.academic_year_id;
            document.getElementById('tp-semester').value = tp.semester;
            document.getElementById('tp-kode').value = tp.kode_tp;
            document.getElementById('tp-deskripsi').value = tp.deskripsi || '';
            document.getElementById('tp-elemen').value = tp.elemen || '';
            document.getElementById('tp-fase').value = tp.fase || '';
            document.getElementById('tp-jp').value = tp.alokasi_waktu || 2;
            document.getElementById('tp-urutan').value = tp.urutan || 0;
            document.getElementById('tp-active').checked = tp.is_active !== false;
            filterCpOptions(tp.capaian_pembelajaran_id);
            new bootstrap.Modal(document.getElementById('tp-modal')).show();
        }
    </script>
@endsection
