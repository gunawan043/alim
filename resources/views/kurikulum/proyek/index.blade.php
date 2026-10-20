@extends('layouts.master')
@section('title', 'Projek P5 / P5RA')
@section('css')
    @include('kurikulum._styles')
@endsection
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $statusColors = ['draft' => 'secondary', 'active' => 'primary', 'completed' => 'success'];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') Kokurikuler @endslot
        @slot('title') Projek {{ $labels['kokurikuler_label'] ?? 'P5 / P5RA' }} @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa projek.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @include('kurikulum.partials.profile-scope', ['curriculumProfile' => $profile ?? null, 'profileScope' => $profileScope ?? 'active'])

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-seedling-line me-1 text-primary"></i>Projek Kokurikuler {{ $labels['kokurikuler_label'] ?? 'P5 / P5RA' }}</h5>
                    <p class="text-muted mb-0 small">Rencanakan projek lintas disiplin, pilih dimensi/nilai yang disasar, dan pantau status pelaksanaannya.</p>
                </div>
                <div class="col-md-auto d-flex flex-wrap align-items-center gap-2">
                    @if($profile)
                        <span class="badge bg-success-subtle text-success p-2">{{ $profile->name }}</span>
                    @endif
                    <a href="{{ route('user.kurikulum.proyek.bank.index', ['userId' => $userId]) }}" class="btn btn-soft-info btn-sm"><i class="ri-archive-line me-1"></i>Bank Tema &amp; Aktivitas</a>
                    @if($canManage)
                        <button class="btn btn-primary btn-sm" onclick="openProjectCreate()"><i class="ri-add-line me-1"></i>Tambah Projek</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row tahfidz-stat g-2 mb-3">
        @foreach([['Total Projek', $stats['total'], 'ri-folder-3-line', 'primary'], ['Berjalan', $stats['active'], 'ri-play-circle-line', 'info'], ['Selesai', $stats['completed'], 'ri-checkbox-circle-line', 'success'], ['Draft', $stats['draft'], 'ri-draft-line', 'warning']] as [$label, $value, $icon, $color])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div><div><div class="text-muted small text-uppercase">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div></div></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <h5 class="card-title mb-0">Daftar Projek Kokurikuler</h5>
                    <p class="text-muted mb-0 small">{{ $academicYear?->name ?? 'Tahun ajaran' }} · Semester {{ ucfirst($semester) }}</p>
                </div>
                <div class="col-md-7">
                    <form method="GET" action="{{ route('user.kurikulum.proyek.index', ['userId' => $userId]) }}" class="d-flex flex-wrap justify-content-md-end gap-2">
                        <select name="academic_year_id" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                            @foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }}</option>@endforeach
                        </select>
                        <select name="semester" class="form-select form-select-sm" style="width:100px" onchange="this.form.submit()">
                            <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                            <option value="genap" @selected($semester === 'genap')>Genap</option>
                        </select>
                        <select name="type" class="form-select form-select-sm" style="width:110px" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            @foreach($projectTypes as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ strtoupper($value) }}</option>@endforeach
                        </select>
                        <select name="status" class="form-select form-select-sm" style="width:115px" onchange="this.form.submit()">
                            <option value="">Semua status</option>
                            @foreach(\App\Models\CurriculumProject::STATUS_OPTIONS as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach
                        </select>
                        <a href="{{ route('user.kurikulum.proyek.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm" title="Reset"><i class="ri-refresh-line"></i></a>
                    </form>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Projek</th><th>Jenis</th><th>Dimensi / Nilai</th><th>Kelas</th><th>Pelaksanaan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($projects as $project)
                        @php
                            $gradeNames = $gradeLevels->whereIn('id', $project->grade_level_ids ?? [])->pluck('name')->implode(', ');
                            $dimensionNames = collect($project->dimensions ?? [])->map(fn ($key) => $profileValues[$key] ?? $key)->implode(', ');
                            $editPayload = [
                                'id' => $project->id,
                                'academic_year_id' => $project->academic_year_id,
                                'semester' => $project->semester,
                                'type' => $project->type,
                                'title' => $project->title,
                                'theme' => $project->theme,
                                'description' => $project->description,
                                'dimensions' => $project->dimensions ?? [],
                                'grade_level_ids' => $project->grade_level_ids ?? [],
                                'start_date' => $project->start_date?->format('Y-m-d'),
                                'end_date' => $project->end_date?->format('Y-m-d'),
                                'status' => $project->status,
                                'notes' => $project->notes,
                                'activities' => $project->activities ?? [],
                            ];
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $project->title }}
                                    @include('kurikulum.partials.profile-badge', ['model' => $project, 'class' => 'ms-1'])
                                </div>
                                <div class="text-muted small">{{ $project->theme ?: 'Tema belum diisi' }}</div>
                            </td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $project->type_label }}</span></td>
                            <td class="small">{{ $dimensionNames ?: '—' }}</td>
                            <td class="small">{{ $gradeNames ?: 'Semua kelas' }}</td>
                            <td class="small">
                                @if($project->start_date || $project->end_date)
                                    {{ $project->start_date?->format('d/m/Y') ?? '—' }} – {{ $project->end_date?->format('d/m/Y') ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge bg-{{ $statusColors[$project->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$project->status] ?? 'secondary' }}">{{ $project->status_label }}</span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('user.kurikulum.proyek.show', ['userId' => $userId, 'id' => $project->id]) }}" class="btn btn-soft-primary" title="Nilai & laporan"><i class="ri-bar-chart-box-line"></i></a>
                                    @if($canManage)
                                        <button class="btn btn-soft-warning" data-project="{{ json_encode($editPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openProjectEdit(this)" title="Edit"><i class="ri-pencil-line"></i></button>
                                        <form method="POST" action="{{ route('user.kurikulum.proyek.destroy', ['userId' => $userId, 'id' => $project->id]) }}" onsubmit="return confirm('Hapus projek ini?');">
                                            @csrf @method('DELETE')<button class="btn btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Lihat</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-seedling-line fs-1 d-block mb-2"></i>Belum ada projek pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" id="projectForm" action="{{ route('user.kurikulum.proyek.store', ['userId' => $userId]) }}">
                        @csrf
                        <input type="hidden" name="_method" id="projectMethod" value="POST">
                        <div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="projectModalTitle"><i class="ri-seedling-line me-1"></i>Tambah Projek Kokurikuler</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="type" id="projectType" class="form-select" required>@foreach($projectTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                                <div class="col-md-3"><label class="form-label">Tahun Ajaran</label><input type="text" class="form-control" value="{{ $academicYear?->name ?? '—' }}" disabled><input type="hidden" name="academic_year_id" id="projectAcademicYear" value="{{ $academicYearId }}"></div>
                                <div class="col-md-3"><label class="form-label">Semester</label><select name="semester" id="projectSemester" class="form-select"><option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option><option value="genap" @selected($semester === 'genap')>Genap</option></select></div>
                                <div class="col-md-3"><label class="form-label">Status</label><select name="status" id="projectStatus" class="form-select">@foreach(\App\Models\CurriculumProject::STATUS_OPTIONS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                                <div class="col-md-8"><label class="form-label">Judul Projek <span class="text-danger">*</span></label><input name="title" id="projectTitle" class="form-control" required maxlength="191" placeholder="Mis. Kampung Cinta Lingkungan"></div>
                                <div class="col-md-4"><label class="form-label">Tema</label><input name="theme" id="projectTheme" class="form-control" maxlength="191" list="themeBank" placeholder="Mis. Gaya Hidup Berkelanjutan"></div>
                                <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" id="projectDescription" rows="2" class="form-control" maxlength="5000" placeholder="Tujuan dan gambaran projek..."></textarea></div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ $labels['values_label'] ?? 'Dimensi / Nilai' }}</label>
                                    <div class="border rounded p-2" style="max-height:180px;overflow:auto">
                                        @forelse($profileValues as $key => $label)
                                            <div class="form-check"><input class="form-check-input project-dimension" type="checkbox" name="dimensions[]" value="{{ $key }}" id="dim-{{ $key }}"><label class="form-check-label" for="dim-{{ $key }}">{{ $label }}</label></div>
                                        @empty
                                            <div class="text-muted small">Profil belum memiliki daftar nilai/dimensi.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kelas Sasaran</label>
                                    <select name="grade_level_ids[]" id="projectGrades" class="form-select" multiple size="6">
                                        @foreach($gradeLevels as $grade)<option value="{{ $grade->id }}">{{ $grade->name }}</option>@endforeach
                                    </select>
                                    <div class="form-text">Kosongkan untuk semua kelas.</div>
                                </div>
                                <div class="col-md-3"><label class="form-label">Tanggal Mulai</label><input type="date" name="start_date" id="projectStart" class="form-control"></div>
                                <div class="col-md-3"><label class="form-label">Tanggal Selesai</label><input type="date" name="end_date" id="projectEnd" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label">Catatan</label><input name="notes" id="projectNotes" class="form-control" maxlength="2000"></div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2"><label class="form-label mb-0">Aktivitas Projek</label><button type="button" class="btn btn-sm btn-soft-primary" onclick="addActivity()"><i class="ri-add-line me-1"></i>Tambah Aktivitas</button></div>
                                    <div id="projectActivities"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Projek</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <datalist id="themeBank">
        @foreach($bankThemes as $theme)
            <option value="{{ $theme->title }}">{{ $theme->description }}</option>
        @endforeach
    </datalist>
    <datalist id="activityBank">
        @foreach($bankActivities as $activity)
            <option value="{{ $activity->title }}">{{ $activity->description }}</option>
        @endforeach
    </datalist>
@endsection

@section('script')
    <script>
        var projectStoreUrl = @json(route('user.kurikulum.proyek.store', ['userId' => $userId]));
        var projectUpdateUrl = @json(route('user.kurikulum.proyek.update', ['userId' => $userId, 'id' => '__ID__']));

        function addActivity(activity) {
            activity = activity || {};
            var wrap = document.getElementById('projectActivities');
            var row = document.createElement('div');
            row.className = 'row g-2 align-items-end mb-2 project-activity';
            row.innerHTML = '<div class="col-md-4"><input class="form-control form-control-sm activity-title" list="activityBank" placeholder="Nama aktivitas" value="' + escapeHtml(activity.judul || '') + '"></div>'
                + '<div class="col-md-5"><input class="form-control form-control-sm activity-desc" placeholder="Deskripsi singkat" value="' + escapeHtml(activity.deskripsi || '') + '"></div>'
                + '<div class="col-md-2"><input type="date" class="form-control form-control-sm activity-date" value="' + escapeHtml(activity.tanggal || '') + '"></div>'
                + '<div class="col-md-1"><button type="button" class="btn btn-sm btn-soft-danger w-100" onclick="this.closest(\'.project-activity\').remove();reindexActivities()"><i class="ri-delete-bin-line"></i></button></div>';
            wrap.appendChild(row);
            reindexActivities();
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (char) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
            });
        }

        function reindexActivities() {
            document.querySelectorAll('#projectActivities .project-activity').forEach(function (row, index) {
                row.querySelector('.activity-title').name = 'activities[' + index + '][judul]';
                row.querySelector('.activity-desc').name = 'activities[' + index + '][deskripsi]';
                row.querySelector('.activity-date').name = 'activities[' + index + '][tanggal]';
            });
        }

        function clearActivities() {
            document.getElementById('projectActivities').innerHTML = '';
        }

        function openProjectCreate() {
            document.getElementById('projectModalTitle').innerHTML = '<i class="ri-seedling-line me-1"></i>Tambah Projek Kokurikuler';
            document.getElementById('projectForm').action = projectStoreUrl;
            document.getElementById('projectMethod').value = 'POST';
            document.getElementById('projectTitle').value = '';
            document.getElementById('projectTheme').value = '';
            document.getElementById('projectDescription').value = '';
            document.getElementById('projectStatus').value = 'draft';
            document.getElementById('projectStart').value = '';
            document.getElementById('projectEnd').value = '';
            document.getElementById('projectNotes').value = '';
            document.getElementById('projectGrades').selectedIndex = -1;
            document.querySelectorAll('.project-dimension').forEach(function (el) { el.checked = false; });
            clearActivities();
            addActivity();
            new bootstrap.Modal(document.getElementById('projectModal')).show();
        }

        function openProjectEdit(button) {
            var project = JSON.parse(button.dataset.project);
            document.getElementById('projectModalTitle').innerHTML = '<i class="ri-pencil-line me-1"></i>Edit Projek Kokurikuler';
            document.getElementById('projectForm').action = projectUpdateUrl.replace('__ID__', project.id);
            document.getElementById('projectMethod').value = 'PUT';
            document.getElementById('projectType').value = project.type || 'p5';
            document.getElementById('projectSemester').value = project.semester || 'ganjil';
            document.getElementById('projectStatus').value = project.status || 'draft';
            document.getElementById('projectTitle').value = project.title || '';
            document.getElementById('projectTheme').value = project.theme || '';
            document.getElementById('projectDescription').value = project.description || '';
            document.getElementById('projectStart').value = project.start_date || '';
            document.getElementById('projectEnd').value = project.end_date || '';
            document.getElementById('projectNotes').value = project.notes || '';
            document.querySelectorAll('.project-dimension').forEach(function (el) { el.checked = (project.dimensions || []).indexOf(el.value) !== -1; });
            Array.from(document.getElementById('projectGrades').options).forEach(function (option) { option.selected = (project.grade_level_ids || []).indexOf(option.value) !== -1; });
            clearActivities();
            (project.activities || []).forEach(function (activity) { addActivity(activity); });
            if (!(project.activities || []).length) addActivity();
            new bootstrap.Modal(document.getElementById('projectModal')).show();
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (document.querySelectorAll('#projectActivities .project-activity').length === 0) addActivity();
        });
    </script>
@endsection
