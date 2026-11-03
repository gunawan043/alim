@extends('layouts.master')

@section('title', 'Log Kendaraan')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2') Pos Gerbang @endslot
        @slot('title') Log Kendaraan @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Kendaraan di Dalam', 'value' => number_format($stats['inside']), 'icon' => 'ri-roadster-line', 'color' => 'primary'],
            ['label' => 'Masuk Hari Ini', 'value' => number_format($stats['today']), 'icon' => 'ri-login-box-line', 'color' => 'success'],
            ['label' => 'Keluar Hari Ini', 'value' => number_format($stats['out_today']), 'icon' => 'ri-logout-box-line', 'color' => 'warning'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Kendaraan</h5>
                            <p class="text-muted mb-0 small">Kendaraan yang masuk dan keluar area pesantren.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <form method="GET" class="d-flex gap-2" action="{{ route('user.keamanan.vehicles.index', ['userId' => $userId]) }}">
                                    <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                                           placeholder="Cari nopol atau pengemudi..." style="width: 240px;">
                                    <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                                </form>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#vehicleModal">
                                    <i class="ri-add-line me-1"></i> Catat Kendaraan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>
                        <a href="{{ route('user.keamanan.vehicles.index', ['userId' => $userId]) }}"
                           class="filter-badge {{ ! request('status') ? 'active' : '' }}">Semua</a>
                        <a href="{{ route('user.keamanan.vehicles.index', ['userId' => $userId, 'status' => 'di_dalam']) }}"
                           class="filter-badge {{ request('status') === 'di_dalam' ? 'active' : '' }}">Di Dalam</a>
                        <a href="{{ route('user.keamanan.vehicles.index', ['userId' => $userId, 'status' => 'keluar']) }}"
                           class="filter-badge {{ request('status') === 'keluar' ? 'active' : '' }}">Sudah Keluar</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead class="table-light">
                                <tr>
                                    <th>Nomor Polisi</th>
                                    <th>Kode</th>
                                    <th>Jenis</th>
                                    <th>Pengemudi</th>
                                    <th>Keperluan</th>
                                    <th>Penumpang</th>
                                    <th>Pos</th>
                                    <th>Masuk</th>
                                    <th>Keluar</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vehicles as $vehicle)
                                    <tr>
                                        <td><span class="badge bg-dark-subtle text-dark fs-6">{{ $vehicle->plate_number }}</span></td>
                                        <td><span class="small text-muted">{{ $vehicle->code }}</span></td>
                                        <td>{{ \App\Models\Security\SecurityVehicleLog::VEHICLE_TYPES[$vehicle->vehicle_type] ?? $vehicle->vehicle_type }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $vehicle->driver_name }}</div>
                                            <div class="small text-muted">{{ $vehicle->driver_phone ?: '—' }}</div>
                                        </td>
                                        <td>{{ $vehicle->purpose }}</td>
                                        <td>{{ $vehicle->passenger_count }}</td>
                                        <td>{{ $vehicle->post->name ?? '—' }}</td>
                                        <td>{{ optional($vehicle->check_in_at)->format('d/m H:i') }}</td>
                                        <td>{{ optional($vehicle->check_out_at)->format('d/m H:i') ?? '—' }}</td>
                                        <td>
                                            @if($vehicle->status === 'di_dalam')
                                                <span class="badge bg-success-subtle text-success">Di dalam</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Keluar</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($vehicle->status === 'di_dalam')
                                                <form method="POST" action="{{ route('user.keamanan.vehicles.checkout', ['userId' => $userId, 'vehicle' => $vehicle->id]) }}"
                                                      data-confirm="Catat kendaraan {{ $vehicle->plate_number }} sudah keluar?">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-soft-danger">
                                                        <i class="ri-logout-box-line me-1"></i> Keluar
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Selesai</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="11" title="Belum ada kendaraan tercatat"
                                        message="Catat kendaraan yang masuk area pesantren.">
                                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#vehicleModal">
                                            <i class="ri-add-line me-1"></i> Catat Kendaraan
                                        </button>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($vehicles->hasPages())
                        @include('shared._pagination', ['paginator' => $vehicles])
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-roadster-line me-2 text-primary"></i> Catat Kendaraan Masuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.keamanan.vehicles.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor Polisi <span class="text-danger">*</span></label>
                                <input type="text" name="plate_number" class="form-control text-uppercase" value="{{ old('plate_number') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                <select name="vehicle_type" class="form-select" required>
                                    @foreach(\App\Models\Security\SecurityVehicleLog::VEHICLE_TYPES as $value => $label)
                                        <option value="{{ $value }}" {{ old('vehicle_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Pengemudi <span class="text-danger">*</span></label>
                                <input type="text" name="driver_name" class="form-control" value="{{ old('driver_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No HP Pengemudi</label>
                                <input type="text" name="driver_phone" class="form-control" value="{{ old('driver_phone') }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Keperluan <span class="text-danger">*</span></label>
                                <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Penumpang</label>
                                <input type="number" name="passenger_count" class="form-control" min="1" value="{{ old('passenger_count', 1) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Pos Jaga</label>
                                <select name="post_id" class="form-select">
                                    <option value="">— Otomatis —</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}" {{ old('post_id') === $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
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
