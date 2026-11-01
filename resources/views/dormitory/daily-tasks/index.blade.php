@extends('layouts.master')
@section('title', 'Tugas Harian Asrama')
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Tugas Harian @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa input:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $shiftMeta = \App\Support\DormitoryShift::options()[$selectedShift] ?? null;
        $progress = $stats['shift_tasks'] > 0 ? round(($stats['shift_done'] / $stats['shift_tasks']) * 100) : 0;
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Tugas Aktif</p>
                    <h3 class="stat-value mb-0">{{ $stats['active_tasks'] }}</h3>
                    <p class="stat-note mb-0">Seluruh shift</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Checklist Shift Ini</p>
                    <h3 class="stat-value mb-0">{{ $stats['shift_done'] }}/{{ $stats['shift_tasks'] }}</h3>
                    <p class="stat-note mb-0">{{ $shiftMeta['label'] ?? ucfirst($selectedShift) }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Selesai Hari Ini</p>
                    <h3 class="stat-value mb-0">{{ $stats['day_done'] }}</h3>
                    <p class="stat-note mb-0">Dari {{ $stats['day_total'] }} tugas aktif</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Akses Anda</p>
                    <h3 class="stat-value mb-0" style="font-size:1.1rem;">{{ $canManage ? 'Kepala/Wakil Asrama' : 'Staf Asrama' }}</h3>
                    <p class="stat-note mb-0">{{ $canManage ? 'Dapat membuat & mengisi tugas' : 'Dapat mengisi checklist' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Tanggal</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Shift</label>
                    <select name="shift" class="form-select form-select-sm">
                        @foreach(\App\Support\DormitoryShift::options() as $value => $shift)
                            <option value="{{ $value }}" @selected($selectedShift === $value)>
                                {{ $shift['label'] }} ({{ $shift['time'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Tampilkan</button>
                    <a href="{{ route('user.asrama.daily-tasks.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light btn-sm">Hari Ini</a>
                    @if($canManage)
                        <a href="{{ route('user.asrama.daily-tasks.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-success btn-sm ms-auto">
                            <i class="ri-add-line me-1"></i>Buat Tugas
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($canManage)
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-list-settings-line me-1 text-primary"></i>Daftar Tugas Harian</h5>
                    <p class="text-muted mb-0 small">Dibuat Kepala/Wakil Asrama, diisi staf asrama pada shift masing-masing.</p>
                </div>
                <a href="{{ route('user.asrama.daily-tasks.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-primary btn-sm">
                    <i class="ri-add-line me-1"></i>Tambah Tugas
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Shift</th>
                                <th>Tugas</th>
                                <th>Keterangan</th>
                                <th class="text-center">Urutan</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allTasks as $task)
                                <tr class="{{ $task->is_active ? '' : 'opacity-50' }}">
                                    <td>
                                        <span class="badge bg-{{ $task->shift === 'pagi' ? 'warning' : ($task->shift === 'siang' ? 'info' : 'secondary') }}-subtle text-{{ $task->shift === 'pagi' ? 'warning' : ($task->shift === 'siang' ? 'info' : 'secondary') }}">
                                            {{ \App\Support\DormitoryShift::label($task->shift) }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold">{{ $task->name }}</td>
                                    <td class="text-muted small">{{ $task->description ? \Illuminate\Support\Str::limit($task->description, 80) : '—' }}</td>
                                    <td class="text-center">{{ $task->sort_order }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $task->is_active ? 'success' : 'secondary' }}-subtle text-{{ $task->is_active ? 'success' : 'secondary' }}">
                                            {{ $task->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('user.asrama.daily-tasks.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'taskId' => $task->id]) }}"
                                               class="btn btn-sm btn-outline-primary" title="Edit"><i class="ri-edit-line"></i></a>
                                            <form method="POST" action="{{ route('user.asrama.daily-tasks.toggle', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'taskId' => $task->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-warning" title="Aktif/Nonaktif"><i class="ri-toggle-line"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('user.asrama.daily-tasks.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'taskId' => $task->id]) }}"
                                                  onsubmit="return confirm('Hapus tugas ini? Riwayat checklist tetap tersimpan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada tugas harian. Klik <strong>Tambah Tugas</strong> untuk membuat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="ri-checkbox-multiple-line me-1 text-success"></i>
                        Checklist {{ $shiftMeta['label'] ?? ucfirst($selectedShift) }}
                        <span class="text-muted fw-normal">— {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</span>
                    </h5>
                    <p class="text-muted mb-0 small">{{ $shiftMeta['time'] ?? '' }} · Dicentang oleh staf asrama yang bertugas.</p>
                </div>
                <span class="badge bg-success-subtle text-success fs-6">{{ $progress }}% selesai</span>
            </div>
        </div>
        <div class="card-body">
            <div class="progress mb-3" style="height:8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"
                     aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            @if($checklistTasks->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ri-list-check-2 fs-1 d-block mb-2"></i>
                    Belum ada tugas aktif untuk shift ini.
                    @if($canManage)
                        <div class="mt-2">
                            <a href="{{ route('user.asrama.daily-tasks.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-primary btn-sm">
                                <i class="ri-add-line me-1"></i>Buat Tugas
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('user.asrama.daily-tasks.checklist', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    @csrf
                    <input type="hidden" name="task_date" value="{{ $selectedDate }}">
                    <input type="hidden" name="shift" value="{{ $selectedShift }}">

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px;" class="text-center">Selesai</th>
                                    <th>Tugas</th>
                                    <th style="width:32%;">Catatan</th>
                                    <th style="width:150px;">Diperbarui</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($checklistTasks as $task)
                                    @php $log = $logs->get($task->id); @endphp
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input" name="done[{{ $task->id }}]" value="1"
                                                   id="done-{{ $task->id }}" @checked($log?->is_done)>
                                        </td>
                                        <td>
                                            <label for="done-{{ $task->id }}" class="fw-semibold mb-1" style="cursor:pointer;">{{ $task->name }}</label>
                                            @if($task->description)
                                                <div class="text-muted small">{{ $task->description }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="text" name="notes[{{ $task->id }}]" class="form-control form-control-sm"
                                                   maxlength="500" value="{{ old('notes.'.$task->id, $log?->notes) }}"
                                                   placeholder="Opsional">
                                        </td>
                                        <td class="small text-muted">
                                            @if($log?->checked_at)
                                                {{ $log->checked_at->format('d/m/Y H:i') }}
                                                <div>{{ $log->checkedBy?->name ?? '—' }}</div>
                                            @else
                                                Belum diisi
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Checklist</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
