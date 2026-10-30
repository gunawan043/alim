@extends('layouts.master')

@section('title') Kegiatan Rutin Asrama @endsection
@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
    $base = ['userId' => $userId, 'asramaUuid' => $dormitory->id];
    $dayLabels = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Ahad'];
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.kegiatan.index', $base) }}">Kegiatan</a> @endslot
        @slot('title') Kegiatan Rutin @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> Terjadi kesalahan pada formulir:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        {{-- ============================================================
             KOLOM KIRI: FORM TAMBAH
        ============================================================ --}}
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-add-line me-2 text-primary"></i>Tambah Kegiatan Rutin</h5>
                    <p class="text-muted mb-0 small">Jadwal harian/malam tertentu. Sesi presensi dibentuk otomatis untuk 14 hari pertama, lalu setiap hari oleh scheduler.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.asrama.kegiatan.schedules.store', $base) }}" id="scheduleForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted mb-1">Nama Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Contoh: Kajian Malam">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fs-12 text-muted mb-1">Pengulangan <span class="text-danger">*</span></label>
                                <select name="recurrence" id="recurrence" class="form-select" required>
                                    <option value="daily" {{ old('recurrence', 'daily') === 'daily' ? 'selected' : '' }}>Setiap Hari</option>
                                    <option value="weekly" {{ old('recurrence') === 'weekly' ? 'selected' : '' }}>Malam Tertentu</option>
                                </select>
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="scheduleActive" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="scheduleActive">Aktif</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 d-none" id="weeklyDays">
                            <label class="form-label fs-12 text-muted mb-1">Pilih Hari</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($dayLabels as $num => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="days_of_week[]" value="{{ $num }}"
                                               id="day{{ $num }}" {{ in_array($num, old('days_of_week', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="day{{ $num }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fs-12 text-muted mb-1">Mulai Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-12 text-muted mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-12 text-muted mb-1">Jam Mulai</label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-12 text-muted mb-1">Jam Selesai</label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted mb-1">Lokasi</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Aula / Masjid / Kamar">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted mb-1">Target Peserta</label>
                            <select name="target_type" id="targetType" class="form-select">
                                <option value="all" {{ old('target_type', 'all') === 'all' ? 'selected' : '' }}>Semua penghuni asrama</option>
                                <option value="class" {{ old('target_type') === 'class' ? 'selected' : '' }}>Kelas tertentu</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="targetClasses">
                            <label class="form-label fs-12 text-muted mb-1">Pilih Kelas</label>
                            <div class="border rounded p-2" style="max-height:180px;overflow:auto">
                                @forelse($studyGroups as $group)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_study_group_ids[]" value="{{ $group->id }}"
                                               id="group{{ $group->id }}" {{ in_array($group->id, old('target_study_group_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="group{{ $group->id }}">{{ $group->name }}</label>
                                    </div>
                                @empty
                                    <div class="text-muted small">Belum ada kelas pada tahun ajaran aktif.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted mb-1">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-save-line me-1"></i> Simpan Kegiatan Rutin
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================================
             KOLOM KANAN: DAFTAR
        ============================================================ --}}
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-list-check me-1 text-primary"></i>Daftar Kegiatan Rutin</h5>
                            <p class="text-muted mb-0 small">{{ $dormitory->name ?? 'Asrama' }} &mdash; jadwal berulang &amp; target peserta.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.kegiatan.index', $base) }}"
                                   class="btn btn-outline-secondary btn-sm" title="Kembali ke Daftar Kegiatan">
                                    <i class="ri-arrow-go-back-line me-1"></i> Kembali
                                </a>
                                <form method="POST"
                                      action="{{ route('user.asrama.kegiatan.schedules.generate', $base) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Bentuk sesi kegiatan untuk 30 hari ke depan?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-flashlight-line me-1"></i> Generate 30 Hari
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $schedules->count() }}</strong> jadwal rutin
                        </small>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Nama</th>
                                    <th>Jadwal</th>
                                    <th>Periode</th>
                                    <th>Target</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules ?? [] as $i => $schedule)
                                    <tr>
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $schedule->name }}</td>
                                        <td class="small">
                                            @if($schedule->recurrence === 'daily')
                                                Setiap hari
                                            @else
                                                {{ collect($schedule->days_of_week ?? [])->map(fn ($d) => $dayLabels[$d] ?? $d)->implode(', ') }}
                                            @endif
                                            @if($schedule->start_time)
                                                <div class="text-muted">{{ substr($schedule->start_time, 0, 5) }}@if($schedule->end_time)–{{ substr($schedule->end_time, 0, 5) }}@endif</div>
                                            @endif
                                        </td>
                                        <td class="small">
                                            {{ $schedule->start_date?->format('d/m/Y') }}
                                            <br><span class="text-muted">{{ $schedule->end_date?->format('d/m/Y') ?? 'seterusnya' }}</span>
                                        </td>
                                        <td class="small">
                                            {{ $schedule->target_type === 'class' ? 'Kelas tertentu' : 'Semua penghuni' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $schedule->is_active ? 'success' : 'secondary' }}-subtle text-{{ $schedule->is_active ? 'success' : 'secondary' }}">
                                                <i class="ri-{{ $schedule->is_active ? 'checkbox-circle-line' : 'pause-circle-line' }} me-1"></i>
                                                {{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#editSchedule{{ $schedule->id }}"
                                                        title="Edit">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <form method="POST"
                                                      action="{{ route('user.asrama.kegiatan.schedules.destroy', $base + ['scheduleId' => $schedule->id]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Hapus jadwal rutin ini? Sesi yang sudah terbentuk tetap tersimpan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="editSchedule{{ $schedule->id }}">
                                        <td colspan="7" class="bg-light-subtle">
                                            <form method="POST"
                                                  action="{{ route('user.asrama.kegiatan.schedules.update', $base + ['scheduleId' => $schedule->id]) }}"
                                                  class="row g-2">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-md-4">
                                                    <label class="form-label fs-12 text-muted mb-1">Nama</label>
                                                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $schedule->name }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fs-12 text-muted mb-1">Pengulangan</label>
                                                    <select name="recurrence" class="form-select form-select-sm">
                                                        <option value="daily" {{ $schedule->recurrence === 'daily' ? 'selected' : '' }}>Harian</option>
                                                        <option value="weekly" {{ $schedule->recurrence === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fs-12 text-muted mb-1">Mulai</label>
                                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $schedule->start_date?->format('Y-m-d') }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fs-12 text-muted mb-1">Selesai</label>
                                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $schedule->end_date?->format('Y-m-d') }}">
                                                </div>
                                                <div class="col-md-2 d-flex align-items-end">
                                                    <div class="form-check form-switch">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $schedule->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label small">Aktif</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fs-12 text-muted mb-1">Lokasi</label>
                                                    <input type="text" name="location" class="form-control form-control-sm" value="{{ $schedule->location }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label fs-12 text-muted mb-1">Jam Mulai</label>
                                                    <input type="time" name="start_time" class="form-control form-control-sm" value="{{ $schedule->start_time }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label fs-12 text-muted mb-1">Jam Selesai</label>
                                                    <input type="time" name="end_time" class="form-control form-control-sm" value="{{ $schedule->end_time }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fs-12 text-muted mb-1">Target</label>
                                                    <select name="target_type" class="form-select form-select-sm">
                                                        <option value="all" {{ $schedule->target_type === 'all' ? 'selected' : '' }}>Semua</option>
                                                        <option value="class" {{ $schedule->target_type === 'class' ? 'selected' : '' }}>Kelas</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label fs-12 text-muted mb-1">Hari (mingguan)</label>
                                                    <select name="days_of_week[]" class="form-select form-select-sm" multiple size="3">
                                                        @foreach($dayLabels as $num => $label)
                                                            <option value="{{ $num }}" {{ in_array($num, $schedule->days_of_week ?? []) ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fs-12 text-muted mb-1">Kelas (jika target kelas)</label>
                                                    <div class="d-flex flex-wrap gap-3">
                                                        @foreach($studyGroups as $group)
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="target_study_group_ids[]" value="{{ $group->id }}"
                                                                       id="editGroup{{ $schedule->id }}{{ $group->id }}" {{ in_array($group->id, $schedule->target_study_group_ids ?? []) ? 'checked' : '' }}>
                                                                <label class="form-check-label small" for="editGroup{{ $schedule->id }}{{ $group->id }}">{{ $group->name }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="ri-save-line me-1"></i> Simpan Perubahan
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="7" title="Belum ada kegiatan rutin"
                                        message="Buat jadwal seperti Kajian Malam agar sesi presensi terbentuk otomatis setiap malam." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    (function () {
        var recurrence = document.getElementById('recurrence');
        var weekly = document.getElementById('weeklyDays');
        function syncWeekly() {
            if (weekly) weekly.classList.toggle('d-none', recurrence.value !== 'weekly');
        }
        recurrence?.addEventListener('change', syncWeekly);
        syncWeekly();

        var targetType = document.getElementById('targetType');
        var targetClasses = document.getElementById('targetClasses');
        function syncTarget() {
            if (targetClasses) targetClasses.classList.toggle('d-none', targetType.value !== 'class');
        }
        targetType?.addEventListener('change', syncTarget);
        syncTarget();
    })();
</script>
@endsection