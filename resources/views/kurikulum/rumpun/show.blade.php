@extends('layouts.master')
@section('title', 'Kelola Rumpun '.$group->name)

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.rumpun.index', ['userId' => $userId]) }}">Master Rumpun</a> @endslot
        @slot('title') {{ $group->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-price-tag-3-line text-primary me-1"></i>Keanggotaan Mata Pelajaran</h5>
                    <p class="text-muted mb-0 small">Master data resmi: penetapan di sini yang dipakai sistem (bukan pola nama otomatis).</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.kurikulum.rumpun.subjects.attach', ['userId' => $userId, 'groupId' => $group->id]) }}" class="row g-2 align-items-end mb-4">
                        @csrf
                        @method('PUT')
                        <div class="col-md-9">
                            <label class="form-label small">Tambah mapel ke rumpun {{ $group->name }}</label>
                            <select name="subject_ids[]" class="form-select" multiple size="5">
                                @foreach($availableSubjects as $subject)
                                    <option value="{{ $subject->id }}">
                                        {{ $subject->name }} — {{ $subject->school?->name ?? 'Institusi' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success w-100" {{ $availableSubjects->isEmpty() ? 'disabled' : '' }}>
                                <i class="ri-add-line me-1"></i>Petakan
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mata Pelajaran</th>
                                    <th>Satuan Pendidikan</th>
                                    <th class="text-center">Kode</th>
                                    <th class="text-end" style="width:90px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mappedSubjects as $subject)
                                    <tr>
                                        <td class="fw-medium">{{ $subject->name }}</td>
                                        <td class="text-muted small">{{ $subject->school?->name ?? 'Institusi' }}</td>
                                        <td class="text-center"><span class="badge bg-dark-subtle text-dark">{{ $subject->code ?? '—' }}</span></td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('user.kurikulum.rumpun.subjects.detach', ['userId' => $userId, 'groupId' => $group->id, 'subjectId' => $subject->id]) }}"
                                                  onsubmit="return confirm('Keluarkan {{ addslashes($subject->name) }} dari rumpun ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-soft-danger" title="Keluarkan"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Belum ada mapel dipetakan ke rumpun ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-team-line text-info me-1"></i>Guru Pengampu (Keanggotaan Turunan)</h5>
                    <p class="text-muted mb-0 small">Diturunkan dari tugas mengajar aktif pada mapel yang dipetakan — bukan penetapan manual.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead class="table-light">
                                <tr><th>Guru</th><th>Satuan Pendidikan</th></tr>
                            </thead>
                            <tbody>
                                @forelse($members as $member)
                                    <tr>
                                        <td>{{ $member->name }}</td>
                                        <td class="text-muted small">{{ $member->employment?->school?->name ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-4">Belum ada guru pengampu pada rumpun ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-user-star-line text-success me-1"></i>Koordinator Rumpun</h5>
                    <p class="text-muted mb-0 small">Ditetapkan melalui tugas tambahan: <strong>{{ $group->coordinator_task_name ?: '—' }}</strong></p>
                </div>
                <div class="card-body">
                    @if($schoolId && $activeYear && !$candidates->isEmpty())
                        <form method="POST" action="{{ route('user.kurikulum.rumpun.coordinator.assign', ['userId' => $userId, 'groupId' => $group->id]) }}" class="d-flex gap-2 mb-3">
                            @csrf
                            <select name="user_id" class="form-select form-select-sm" required>
                                <option value="">— Pilih guru —</option>
                                @foreach($candidates as $candidate)
                                    <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-success text-nowrap"><i class="ri-user-add-line me-1"></i>Tetapkan</button>
                        </form>
                    @else
                        <p class="text-muted small">Penetapan koordinator memerlukan konteks satuan pendidikan & tahun ajaran aktif.</p>
                    @endif

                    @forelse($coordinators as $coordinator)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ $coordinator['name'] ?? '—' }}</div>
                                    <div class="small text-muted">{{ $coordinator['school'] ?? '—' }} · {{ $coordinator['task_name'] }}</div>
                                    <div class="small text-muted">
                                        Sumber: {{ $coordinator['source'] === 'other_teacher_tasks' ? 'Tugas tambahan' : 'SK (legacy)' }}
                                    </div>
                                </div>
                                @if($coordinator['can_deactivate'])
                                    <form method="POST" action="{{ route('user.kurikulum.rumpun.coordinator.deactivate', ['userId' => $userId, 'groupId' => $group->id, 'taskId' => $coordinator['task_id']]) }}"
                                          onsubmit="return confirm('Nonaktifkan penetapan koordinator ini?');">
                                        @csrf
                                        @method('PUT')
                                        <button class="btn btn-sm btn-soft-danger" title="Nonaktifkan"><i class="ri-close-line"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada koordinator aktif untuk rumpun ini.</p>
                    @endforelse

                    <div class="alert alert-warning small mt-3 mb-0">
                        <i class="ri-shield-line me-1"></i>
                        Koordinator adalah <strong>reviewer utama</strong> pada rumpunnya, tetapi <strong>bukan approver final</strong>
                        dan tidak otomatis memperoleh kunci jawaban atau akses penuh lintas satuan pendidikan.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
