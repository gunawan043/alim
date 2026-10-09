@extends('layouts.master')
@section('title') {{ $assignment->teacher?->name ?? 'Detail' }} @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">Plotting Guru</a> @endslot
        @slot('title') {{ $assignment->teacher?->name ?? '-' }} @endslot
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
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ri-user-star-line text-primary me-1"></i>{{ $assignment->subject?->name }} — {{ $assignment->studyGroup?->full_name }}
                    </h5>
                    <span class="badge bg-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}">
                        {{ $assignment->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width:200px;">Guru</th>
                            <td>
                                @if($assignment->teacher)
                                    <a href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $assignment->teacher_id]) }}">{{ $assignment->teacher->name }}</a>
                                @else
                                    -
                                @endif
                                @if($assignment->is_coordinator)
                                    <span class="badge bg-primary-subtle text-primary ms-2">Koordinator Mapel</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Mata Pelajaran</th>
                            <td>{{ $assignment->subject?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kelas (Rombel)</th>
                            <td>{{ $assignment->studyGroup?->full_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Sekolah</th>
                            <td>{{ $assignment->school?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tahun Ajaran</th>
                            <td>{{ $assignment->academicYear?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Peran</th>
                            <td>
                                @if($assignment->role === 'guru_mapel')
                                    <span class="badge bg-info-subtle text-info">Guru Mata Pelajaran</span>
                                @elseif($assignment->role === 'guru_pendamping')
                                    <span class="badge bg-warning-subtle text-warning">Guru Pendamping</span>
                                @elseif($assignment->role === 'guru_praktik')
                                    <span class="badge bg-purple-subtle text-purple">Guru Praktik</span>
                                @elseif($assignment->role === 'ustadz_pengasuh')
                                    <span class="badge bg-secondary-subtle text-secondary">Ustadz Pengasuh</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Jam Pelajaran / Minggu</th>
                            <td class="fw-semibold">{{ $assignment->weekly_hours }} JP</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Surat Keputusan</th>
                            <td>
                                @if($assignment->decree)
                                    <a href="{{ route('user.institution-decrees.show', ['userId' => $userId, 'id' => $assignment->decree_id]) }}">
                                        <code>{{ $assignment->decree->decree_number }}</code> — {{ $assignment->decree->title }}
                                    </a>
                                @else
                                    <span class="text-muted">Tanpa SK</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Dibuat</th>
                            <td>{{ $assignment->created_at->translatedFormat('d F Y, H:i') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.teaching-assignments.edit', ['userId' => $userId, 'id' => $assignment->id]) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i> Edit
                    </a>
                    <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}" class="btn btn-light">Kembali</a>
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
                        <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId, 'teacher_id' => $assignment->teacher_id]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-user-line me-2"></i> Semua Tugas Guru Ini
                        </a>
                        <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId, 'study_group_id' => $assignment->study_group_id]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-group-line me-2"></i> Plotting Rombel Ini
                        </a>
                        <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-calendar-schedule-line me-2"></i> Jadwal Pelajaran
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
