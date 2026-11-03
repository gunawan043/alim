@extends('layouts.master')

@section('title', 'Piket Kesiswaan')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Kesiswaan @endslot
        @slot('li_2') Kedisiplinan @endslot
        @slot('title') Piket Kesiswaan @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Piket Hari Ini', 'value' => number_format($todayDuties->count()), 'icon' => 'ri-user-star-line', 'color' => 'primary'],
            ['label' => 'Jadwal Periode Ini', 'value' => number_format($duties->count()), 'icon' => 'ri-calendar-check-line', 'color' => 'success'],
            ['label' => 'Petugas Terlibat', 'value' => number_format($duties->pluck('user_id')->unique()->count()), 'icon' => 'ri-team-line', 'color' => 'info'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Jadwal Piket</h5>
                            <p class="text-muted mb-0 small">Penugasan petugas kesiswaan per tanggal dan shift.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <form method="GET" class="d-flex gap-2 align-items-center"
                                      action="{{ route('user.kesiswaan.duties.index', ['userId' => $userId]) }}">
                                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                                    <span class="text-muted">s/d</span>
                                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                                    <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line"></i></button>
                                </form>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#dutyModal">
                                    <i class="ri-add-line me-1"></i> Tambah Piket
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($duties->isEmpty())
                        <x-empty-state icon="ri-calendar-line" title="Belum ada jadwal piket"
                                       message="Tambahkan penugasan piket untuk periode ini." />
                    @else
                        <div class="table-container table-freeze">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 130px;">Tanggal</th>
                                        <th style="width: 100px;">Shift</th>
                                        <th>Petugas</th>
                                        <th>Lokasi</th>
                                        <th>Catatan</th>
                                        <th class="text-end" style="width: 70px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($duties as $duty)
                                        <tr class="{{ $duty->duty_date->isToday() ? 'table-primary' : '' }}">
                                            <td>
                                                <div class="fw-semibold">{{ $duty->duty_date->translatedFormat('d M Y') }}</div>
                                                <div class="text-muted small">{{ $duty->duty_date->translatedFormat('l') }}</div>
                                            </td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $duty->shiftLabel() }}</span></td>
                                            <td class="fw-semibold">{{ $duty->user?->name ?? '-' }}</td>
                                            <td>{{ $duty->location ?: '-' }}</td>
                                            <td class="text-muted small">{{ $duty->notes ?: '-' }}</td>
                                            <td class="text-end">
                                                <form method="POST"
                                                      action="{{ route('user.kesiswaan.duties.destroy', ['userId' => $userId, 'dutyId' => $duty->id]) }}"
                                                      data-confirm="Hapus jadwal piket ini?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
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

    <div class="modal fade" id="dutyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.kesiswaan.duties.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-calendar-check-line me-1"></i> Tambah Jadwal Piket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Petugas <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">— Pilih petugas —</option>
                                    @foreach($staff as $person)
                                        <option value="{{ $person->id }}" @selected(old('user_id') === $person->id)>{{ $person->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="duty_date" class="form-control" required value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Shift <span class="text-danger">*</span></label>
                                <select name="shift" class="form-select" required>
                                    @foreach($shifts as $value => $label)
                                        <option value="{{ $value }}" @selected(old('shift') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="location" class="form-control" maxlength="120"
                                       placeholder="Mis. gerbang utama" value="{{ old('location') }}">
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
