@extends('layouts.master')
@section('title') {{ $assignment->teacher?->name ?? 'Detail' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">Penugasan Mengajar</a> @endslot
        @slot('title') {{ $assignment->teacher?->name ?? '-' }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $assignment->subject?->name }} — {{ $assignment->studyGroup?->full_name }}</h5>
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
                                    <a href="{{ route('user.gtk.show', ['userId' => $userId, 'id' => $assignment->teacher_id]) }}">{{ $assignment->teacher->name }}</a>
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
                            <td>{{ $assignment->weekly_hours }} JP</td>
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
    </div>
@endsection