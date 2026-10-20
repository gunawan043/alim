@extends('layouts.master')
@section('title', 'Penilaian Projek')
@section('css')
    @include('kurikulum._styles')
@endsection
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $statusColors = ['draft' => 'secondary', 'active' => 'primary', 'completed' => 'success'];
        $assessedCount = $assessments->count();
        $averageValue = $average ? number_format((float) $average, 2) : null;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.proyek.index', ['userId' => $userId]) }}">Projek {{ $labels['kokurikuler_label'] ?? 'P5 / P5RA' }}</a> @endslot
        @slot('title') {{ $project->title }} @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa penilaian.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-start g-3">
                <div class="col-md">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary">{{ $project->type_label }}</span>
                        <span class="badge bg-{{ $statusColors[$project->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$project->status] ?? 'secondary' }}">{{ $project->status_label }}</span>
                        @if($profile)<span class="badge bg-success-subtle text-success">{{ $profile->name }}</span>@endif
                    </div>
                    <h5 class="card-title mb-1">{{ $project->title }}</h5>
                    <p class="text-muted mb-0 small">{{ $project->theme ?: 'Tema belum diisi' }} · {{ $project->academicYear?->name }} · Semester {{ ucfirst($project->semester) }}</p>
                    <p class="text-muted mb-0 small mt-1">{{ $project->description ?: 'Belum ada deskripsi projek.' }}</p>
                </div>
                <div class="col-md-auto d-flex flex-wrap gap-2">
                    <a href="{{ route('user.kurikulum.proyek.print', ['userId' => $userId, 'id' => $project->id]) }}" target="_blank" class="btn btn-soft-secondary btn-sm"><i class="ri-printer-line me-1"></i>Cetak Laporan</a>
                    <a href="{{ route('user.kurikulum.proyek.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach($dimensions as $key => $label)
                    <span class="badge bg-light text-dark border">{{ $label }}</span>
                @endforeach
                @if($project->start_date || $project->end_date)
                    <span class="badge bg-secondary-subtle text-secondary">{{ $project->start_date?->format('d/m/Y') ?? '—' }} – {{ $project->end_date?->format('d/m/Y') ?? '—' }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row tahfidz-stat g-2 mb-3">
        @foreach([
            ['Dinilai', $assessedCount, 'ri-user-check-line', 'primary', 'Santri sudah dinilai'],
            ['Rata-rata', $averageValue ?? '—', 'ri-bar-chart-2-line', 'success', 'Rata-rata nilai projek'],
            ['Dimensi', count($dimensions), 'ri-focus-3-line', 'info', 'Dimensi/nilai dinilai'],
            ['Aktivitas', count($project->activities ?? []), 'ri-list-check-2', 'warning', 'Rangkaian kegiatan'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div><div><div class="text-muted small text-uppercase">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div><div class="text-muted small">{{ $note }}</div></div></div></div></div></div>
        @endforeach
    </div>

    <div class="row g-3">
        @if($canManage)
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-star-line text-primary me-1"></i>Input Penilaian</h5><p class="text-muted mb-0 small">Skor 1–4 per dimensi. Nilai akhir dihitung otomatis.</p></div>
                    <div class="card-body">
                        @if(empty($dimensions))
                            <div class="alert alert-warning mb-0"><i class="ri-error-warning-line me-1"></i>Belum ada dimensi/nilai pada projek. Edit projek dan pilih dimensi terlebih dahulu.</div>
                        @else
                            <form method="POST" action="{{ route('user.kurikulum.proyek.assess', ['userId' => $userId, 'id' => $project->id]) }}" id="assessmentForm">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Santri <span class="text-danger">*</span></label>
                                    <select name="student_id" id="assessmentStudent" class="form-select" required>
                                        <option value="">— Pilih Santri —</option>
                                        @foreach($students as $student)
                                            <option value="{{ $student->id }}">{{ $student->nis ? $student->nis.' — ' : '' }}{{ $student->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @foreach($dimensions as $key => $label)
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">{{ $label }}</label>
                                        <select name="scores[{{ $key }}]" id="score-{{ $key }}" class="form-select form-select-sm assessment-score">
                                            <option value="">— Belum dinilai —</option>
                                            @foreach($rubric as $score => $rubricLabel)
                                                <option value="{{ $score }}">{{ $score }} — {{ $rubricLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                                <div class="mb-3">
                                    <label class="form-label">Catatan</label>
                                    <textarea name="notes" id="assessmentNotes" rows="2" class="form-control" maxlength="2000"></textarea>
                                </div>
                                <button class="btn btn-primary w-100"><i class="ri-save-line me-1"></i>Simpan Penilaian</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $canManage ? 'col-xl-8' : 'col-12' }}">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div><h5 class="card-title mb-0"><i class="ri-file-list-3-line text-primary me-1"></i>Rekap Penilaian</h5><p class="text-muted mb-0 small">{{ $assessedCount }} santri dinilai · rata-rata {{ $averageValue ?? '—' }}</p></div>
                    @if($canManage)<span class="badge bg-light text-dark border">Klik ikon pensil untuk mengubah</span>@endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Santri</th>
                                @foreach($dimensions as $label)<th class="text-center small">{{ $label }}</th>@endforeach
                                <th class="text-center">Nilai</th>
                                <th>Predikat</th>
                                <th>Catatan</th>
                                @if($canManage)<th class="text-end">Aksi</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assessments as $assessment)
                                @php
                                    $editPayload = ['student_id' => $assessment->student_id, 'scores' => $assessment->scores ?? [], 'notes' => $assessment->notes];
                                @endphp
                                <tr>
                                    <td><div class="fw-medium">{{ $assessment->student?->name ?? '—' }}</div><div class="text-muted small">{{ $assessment->student?->nis }}</div></td>
                                    @foreach($dimensions as $key => $label)
                                        <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ data_get($assessment->scores, $key, '—') }}</span></td>
                                    @endforeach
                                    <td class="text-center fw-bold">{{ number_format((float) $assessment->average, 2) }}</td>
                                    <td><span class="badge {{ (float) $assessment->average >= 3.5 ? 'bg-success-subtle text-success' : ((float) $assessment->average >= 2.5 ? 'bg-info-subtle text-info' : ((float) $assessment->average >= 1.5 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger')) }}">{{ $assessment->predicate ?? '—' }}</span></td>
                                    <td class="small text-muted">{{ $assessment->notes ?: '—' }}</td>
                                    @if($canManage)
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-soft-warning" data-assessment="{{ json_encode($editPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="editAssessment(this)" title="Edit"><i class="ri-pencil-line"></i></button>
                                                <form method="POST" action="{{ route('user.kurikulum.proyek.assessment.destroy', ['userId' => $userId, 'id' => $project->id, 'assessmentId' => $assessment->id]) }}" onsubmit="return confirm('Hapus penilaian ini?');">
                                                    @csrf @method('DELETE')<button class="btn btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ 5 + count($dimensions) + ($canManage ? 1 : 0) }}" class="text-center text-muted py-5"><i class="ri-user-star-line fs-1 d-block mb-2"></i>Belum ada penilaian. @if($canManage) Gunakan form di samping. @endif</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($dimensionAverages->filter(fn ($value) => $value !== null)->isNotEmpty())
                    <div class="card-footer bg-light-subtle">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="text-muted small"><i class="ri-bar-chart-line me-1"></i>Rata-rata per dimensi:</span>
                            @foreach($dimensionAverages as $key => $value)
                                <span class="badge bg-light text-dark border">{{ $dimensions[$key] ?? $key }}: {{ $value ?? '—' }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function editAssessment(button) {
            var data = JSON.parse(button.dataset.assessment);
            var student = document.getElementById('assessmentStudent');
            if (student) student.value = data.student_id || '';
            Object.keys(data.scores || {}).forEach(function (key) {
                var input = document.getElementById('score-' + key);
                if (input) input.value = data.scores[key];
            });
            var notes = document.getElementById('assessmentNotes');
            if (notes) notes.value = data.notes || '';
            document.getElementById('assessmentForm')?.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    </script>
@endsection
