@extends('layouts.master')
@section('title') {{ $assignment->subject?->code ?? '?' }} — {{ $studyGroup->full_name }} @endsection

@section('content')
@php
    $userId = $userId ?? request()->route('userId') ?? auth()->id();
    $subject = $assignment->subject;
@endphp
@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2')
        <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombongan Belajar</a>
    @endslot
    @slot('li_3')
        <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}">
            {{ $studyGroup->full_name }}
        </a>
    @endslot
    @slot('title') Detail Assignment @endslot
@endcomponent

<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}"
           class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali ke Rombel
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-4">
                            <i class="bx bx-book-open"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Detail Assignment</h5>
                        <p class="text-muted mb-0" style="font-size:0.8rem;">
                            {{ $subject?->code ?? '–' }} — {{ $subject?->name ?? 'Deleted Subject' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Informasi Mata Pelajaran</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <th class="text-muted" style="width:140px;">Kode</th>
                                <td>{{ $subject?->code ?? '–' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Nama</th>
                                <td>{{ $subject?->name ?? '–' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Kategori</th>
                                <td>{{ ucfirst($subject?->category ?? '–') }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">SKS / Minggu</th>
                                <td>{{ $subject?->credit_hours ?? '–' }} JP</td>
                            </tr>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Informasi Assignment</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <th class="text-muted" style="width:140px;">Guru Pengampu</th>
                                <td>
                                    @if($assignment->teacher)
                                        <span class="text-primary">{{ $assignment->teacher->name }}</span>
                                    @else
                                        <span class="text-muted">Belum ditugaskan</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Jam Mengajar</th>
                                <td>{{ number_format($assignment->weekly_hours, 1) }} JP/minggu</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Status</th>
                                <td>
                                    @if($assignment->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Catatan</th>
                                <td>{{ $assignment->notes ?: '–' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($assignment->notes)
                    <hr>
                    <div>
                        <h6 class="text-muted mb-2">Catatan</h6>
                        <p class="mb-0 text-muted">{{ $assignment->notes }}</p>
                    </div>
                @endif

                <hr>
                <div class="d-flex gap-2">
                    <a href="{{ route('user.study-groups.subjects.edit', ['userId' => $userId, 'id' => $studyGroup->id, 'assignmentId' => $assignment->id]) }}"
                       class="btn btn-primary btn-sm">
                        <i class="ri-pencil-line me-1"></i> Edit Assignment
                    </a>
                    <button type="button" class="btn btn-danger btn-sm"
                            onclick="document.getElementById('del-form').submit()">
                        <i class="ri-delete-bin-line me-1"></i> Unassign
                    </button>
                </div>

                <form id="del-form" action="{{ route('user.study-groups.subjects.destroy', ['userId' => $userId, 'id' => $studyGroup->id, 'assignmentId' => $assignment->id]) }}"
                      method="POST" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Info Rombel</h6></div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <th class="text-muted" style="width:120px">Rombel</th>
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
                                {{ $studyGroup->academicYear?->name ?? '-' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Tingkat</th>
                        <td>{{ $studyGroup->gradeLevel?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Kurikulum</th>
                        <td>{{ ucfirst($studyGroup->curriculum_type ?? '-') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Shift</th>
                        <td>{{ ucfirst($studyGroup->shift ?? '-') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Meta Informasi</h6></div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <th class="text-muted">Dibuat</th>
                        <td style="font-size:13px;">{{ $assignment->created_at?->format('d M Y H:i') ?? '–' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Diubah</th>
                        <td style="font-size:13px;">{{ $assignment->updated_at?->format('d M Y H:i') ?? '–' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
