@extends('layouts.master')
@section('title') {{ $decree->decree_number }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">Surat Keputusan</a> @endslot
        @slot('title') {{ $decree->decree_number }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info SK --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $decree->title }}</h5>
                    <div class="d-flex gap-2">
                        @if($decree->status === 'active')
                            <span class="badge bg-success-subtle text-success">Aktif</span>
                        @elseif($decree->status === 'archived')
                            <span class="badge bg-secondary-subtle text-secondary">Arsip</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Draft</span>
                        @endif
                        <a href="{{ route('user.institution-decrees.edit', ['userId' => $userId, 'id' => $decree->id]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-pencil-line"></i> Edit SK
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0" style="max-width:700px">
                        <tr>
                            <th class="text-muted" style="width:200px;">Nomor SK</th>
                            <td><code>{{ $decree->decree_number }}</code></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Jenis SK</th>
                            <td>{{ $decree->decree_type }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tahun Ajaran</th>
                            <td>{{ $decree->academicYear?->name ?? '-' }}@if($decree->academicYear?->semester_text) ({{ $decree->academicYear->semester_text }})@endif</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Sekolah</th>
                            <td>{{ $decree->school?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tanggal Dikeluarkan</th>
                            <td>{{ ($decree->issued_date?->translatedFormat('d F Y')) ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tanggal Efektif</th>
                            <td>{{ ($decree->effective_date?->translatedFormat('d F Y')) ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Penandatangan</th>
                            <td>
                                @if($decree->signer)
                                    {{ $decree->signer->name }} — <span class="text-muted">{{ $decree->signed_position }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Deskripsi</th>
                            <td>{{ $decree->description ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Matriks Pembagian Tugas Mengajar --}}
    @if($decree->decree_type === 'SK Pembagian Tugas')
    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom-dashed">
                    <h5 class="mb-0">Matriks Pembagian Tugas Mengajar</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}"
                           class="btn btn-sm btn-outline-info">
                            <i class="ri-user-settings-line me-1"></i> Tugas Tambahan
                        </a>
                        <a href="{{ route('user.institution-decrees.print', ['userId' => $userId, 'id' => $decree->id]) }}"
                           class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="ri-printer-line me-1"></i> Cetak SK
                        </a>
                        <a href="{{ route('user.teaching-assignments.edit-matrix', ['userId' => $userId, 'decree_id' => $decree->id]) }}"
                           class="btn btn-sm btn-success">
                            <i class="ri-edit-2-line me-1"></i> Edit Isi Tabel
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">

                    @php
                        // Group study groups by grade level
                        $byGrade = $studyGroups->groupBy(fn($sg) => $sg->gradeLevel->level ?? 0);
                        $sortedGrades = $byGrade->sortKeys();

                        // Per-teacher data
                        $teacherRows = [];
                        foreach ($teachers as $teacher) {
                            $tAssignments = $decree->teachingAssignments->where('teacher_id', $teacher->id)->where('status', 'active');
                            if ($tAssignments->isEmpty()) continue;

                            // Group by subject
                            $bySubject = $tAssignments->groupBy('subject_id');
                            foreach ($bySubject as $subjectId => $subjectAssignments) {
                                $subject = $subjects->get($subjectId);
                                $rowHours = [];
                                $totalHours = 0;
                                foreach ($studyGroups as $sg) {
                                    $h = $matrix[$teacher->id][$subjectId][$sg->id] ?? null;
                                    $rowHours[$sg->id] = $h;
                                    if ($h) $totalHours += $h;
                                }
                                $teacherRows[] = [
                                    'teacher' => $teacher,
                                    'subject' => $subject,
                                    'hours' => $rowHours,
                                    'total' => $totalHours,
                                    'is_first_subject' => $subjectId === $bySubject->keys()->first(),
                                ];
                            }
                        }
                    @endphp

                    @if($studyGroups->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="ri-file-list-3-line fs-1 mb-2 d-block"></i>
                            <p>Tidak ada data kelas untuk sekolah ini.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0" style="min-width:800px; font-size:12px;">
                                <thead class="table-light text-center align-middle">
                                    <tr>
                                        {{-- Colspan helper: study group columns per grade level --}}
                                        <th rowspan="3" class="text-center align-middle" style="width:40px; min-width:40px;">No</th>
                                        <th rowspan="3" class="text-center align-middle" style="min-width:180px;">Nama /<br>Mapel / Tugas Tambahan</th>

                                        @foreach($sortedGrades as $level => $groups)
                                            @php $gradeName = $groups->first()->gradeLevel->name ?? "Kelas $level"; @endphp
                                            <th colspan="{{ $groups->count() }}" class="text-center fw-semibold border-start border-end border-dark">
                                                {{ $gradeName }}
                                            </th>
                                        @endforeach

                                        <th rowspan="3" class="text-center align-middle bg-success-subtle" style="width:70px;">Sebaran<br>Jam</th>
                                        <th rowspan="3" class="text-center align-middle bg-info-subtle" style="width:70px;">Tugas<br>Lain2</th>
                                        <th rowspan="3" class="text-center align-middle bg-primary-subtle" style="width:70px;">Jml.<br>Jam</th>
                                    </tr>
                                    <tr>
                                        @foreach($sortedGrades as $level => $groups)
                                            @foreach($groups as $sg)
                                                <th class="text-center fw-normal" style="min-width:44px;">{{ $sg->name }}</th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $teacherCount = 0;
                                        $lastTeacherId = null;
                                    @endphp
                                    @forelse($teacherRows as $row)
                                        @php
                                            $isFirst = $row['teacher']->id !== $lastTeacherId;
                                            if ($isFirst) $teacherCount++;
                                            $isCoordinator = $row['subject'] && $decree->teachingAssignments
                                                ->where('teacher_id', $row['teacher']->id)
                                                ->where('subject_id', $row['subject']->id)
                                                ->first()?->is_coordinator;
                                            $lastTeacherId = $row['teacher']->id;
                                        @endphp
                                        <tr class="{{ $isFirst ? 'border-top border-dark' : '' }}">
                                            {{-- No --}}
                                            <td class="text-center text-muted" style="width:40px;">
                                                @if($isFirst)
                                                    {{ $teacherCount }}
                                                @endif
                                            </td>

                                            {{-- Nama + Mapel --}}
                                            <td style="{{ $isFirst ? 'font-weight:600; background:#f8f9fa;' : '' }}">
                                                @if($isFirst)
                                                    {{ $row['teacher']->name }}
                                                    <br>
                                                    <span class="badge bg-secondary-subtle text-secondary mt-1" style="font-size:10px;">
                                                        {{ $row['teacher']->getRoleNames()->first() ?? 'GTK' }}
                                                    </span>
                                                @endif
                                                <div class="{{ $isFirst ? 'mt-1' : '' }}" style="{{ $isFirst ? '' : 'padding-left:16px;' }}">
                                                    <span class="{{ $isCoordinator ? 'fw-semibold' : '' }}">
                                                        {{ $row['subject']?->name ?? '-' }}
                                                    </span>
                                                    @if($isCoordinator)
                                                        <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:10px;">Koordinator</span>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- Jam per kelas --}}
                                            @foreach($sortedGrades as $level => $groups)
                                                @foreach($groups as $sg)
                                                    <td class="text-center">
                                                        @if(isset($row['hours'][$sg->id]) && $row['hours'][$sg->id])
                                                            {{ $row['hours'][$sg->id] }}
                                                        @else
                                                            <span class="text-light">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            @endforeach

                                            {{-- Sebaran Jam --}}
                                            <td class="text-center fw-semibold bg-success-subtle">
                                                {{ $row['total'] }}
                                            </td>

                                            {{-- Tugas Lain2 + Total --}}
                                            @php
                                                $guruTasks = $otherTeacherTasks->get($row['teacher']->id, collect());
                                                $totalTaskHours = $guruTasks->sum('weekly_hours');
                                            @endphp
                                            <td class="text-center bg-info-subtle" style="font-size:11px;">
                                                @forelse($guruTasks as $ott)
                                                    <div class="mb-1">
                                                        {{ $ott->task_name }}
                                                        <span class="fw-bold d-block">{{ $ott->weekly_hours }} JP</span>
                                                    </div>
                                                @empty
                                                    <span class="text-muted">—</span>
                                                @endforelse
                                            </td>

                                            {{-- Total (Sebaran + Tugas Lain) --}}
                                            <td class="text-center fw-bold bg-primary-subtle">
                                                {{ $row['total'] + $totalTaskHours }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 2 + $studyGroups->count() + 3 }}" class="text-center py-4 text-muted">
                                                <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                                                Belum ada data penugasan. Silakan klik "Edit Isi Tabel" untuk menambahkan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="ri-information-line me-1"></i>
                        Klik tombol "Edit Isi Tabel" untuk mengisi atau mengedit data penugasan mengajar.
                        Hubungi admin untuk mengelola Tugas Tambahan.
                    </small>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection
