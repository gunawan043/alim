@extends('layouts.master')

@section('title', 'Kegiatan Malam')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') Operasional @endslot
        @slot('title') Pendampingan Kegiatan Malam @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Kegiatan Terjadwal', 'value' => number_format($activities->count()), 'icon' => 'ri-moon-line', 'color' => 'primary'],
            ['label' => 'Sudah Selesai', 'value' => number_format($activities->where('status', 'selesai')->count()), 'icon' => 'ri-checkbox-circle-line', 'color' => 'success'],
            ['label' => 'Masih Rencana', 'value' => number_format($activities->where('status', 'rencana')->count()), 'icon' => 'ri-time-line', 'color' => 'warning'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Kegiatan Malam</h5>
                            <p class="text-muted mb-0 small">Jadwal kegiatan malam dan petugas pendampingnya.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <form method="GET" class="d-flex gap-2"
                                      action="{{ route('user.night-activities.index', ['userId' => $userId]) }}">
                                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                                    <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line"></i></button>
                                </form>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#activityModal">
                                    <i class="ri-add-line me-1"></i> Tambah Kegiatan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($activities->isEmpty())
                        <x-empty-state icon="ri-moon-line" title="Belum ada kegiatan malam"
                                       message="Tambahkan kegiatan yang perlu pendampingan petugas." />
                    @else
                        <div class="table-container table-freeze">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 130px;">Tanggal</th>
                                        <th style="width: 110px;">Waktu</th>
                                        <th>Kegiatan</th>
                                        <th>Asrama</th>
                                        <th>Lokasi</th>
                                        <th>Pendamping</th>
                                        <th class="text-end" style="width: 80px;">Santri</th>
                                        <th class="text-center" style="width: 100px;">Status</th>
                                        <th class="text-end" style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activities as $activity)
                                        <tr>
                                            <td>{{ $activity->activity_date->format('d/m/Y') }}</td>
                                            <td>{{ substr($activity->start_time, 0, 5) }}{{ $activity->end_time ? ' – '.substr($activity->end_time, 0, 5) : '' }}</td>
                                            <td class="fw-semibold">{{ $activity->name }}</td>
                                            <td>{{ $activity->dormitory?->name ?? '-' }}</td>
                                            <td>{{ $activity->location ?: '-' }}</td>
                                            <td>{{ $activity->person_in_charge ?: '-' }}</td>
                                            <td class="text-end">{{ number_format($activity->student_count) }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $activity->statusColor() }}-subtle text-{{ $activity->statusColor() }}">
                                                    {{ $activity->statusLabel() }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-soft-secondary" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @foreach($statusOptions as $value => $label)
                                                            @continue($value === $activity->status)
                                                            <li>
                                                                <form method="POST"
                                                                      action="{{ route('user.night-activities.status', ['userId' => $userId, 'activityId' => $activity->id]) }}">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="{{ $value }}">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="ri-checkbox-blank-circle-line me-1"></i> Tandai {{ $label }}
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endforeach
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST"
                                                                  action="{{ route('user.night-activities.destroy', ['userId' => $userId, 'activityId' => $activity->id]) }}"
                                                                  data-confirm="Hapus kegiatan ini?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="ri-delete-bin-line me-1"></i> Hapus
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.night-activities.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-moon-line me-1"></i> Tambah Kegiatan Malam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Asrama <span class="text-danger">*</span></label>
                                <select name="dormitory_id" class="form-select" required>
                                    @foreach($dormitories as $dormitory)
                                        <option value="{{ $dormitory->id }}" @selected(old('dormitory_id') === $dormitory->id)>{{ $dormitory->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="activity_date" class="form-control" required value="{{ old('activity_date', $date) }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required maxlength="120"
                                       placeholder="Mis. muhadharah, malam bina iman" value="{{ old('name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', 'rencana') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required value="{{ old('start_time', '19:30') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Selesai</label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time', '21:00') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Santri</label>
                                <input type="number" name="student_count" class="form-control" min="0" max="2000" value="{{ old('student_count', 0) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="location" class="form-control" maxlength="120" value="{{ old('location') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pendamping</label>
                                <input type="text" name="person_in_charge" class="form-control" maxlength="120" value="{{ old('person_in_charge') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-control" maxlength="255" value="{{ old('notes') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
