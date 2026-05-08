@extends('layouts.master')
@section('title') Detail Ruang — {{ $ruang->room_name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') <a href="{{ route('user.sarpras.gedung.index', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('li_3') <a href="{{ route('user.sarpras.ruang.index', ['userId' => $userId]) }}">Ruang</a> @endslot
        @slot('title') {{ $ruang->room_name }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            {{-- Info Card --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="mb-0">Detail Ruang</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="hstack gap-2 justify-content-end">
                                <a href="{{ route('user.sarpras.aset.import', ['userId' => $userId, 'room_id' => $ruang->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ri-upload-cloud-line me-1"></i> Import Aset
                                </a>
                                <a href="{{ route('user.sarpras.aset.create', ['userId' => $userId, 'room_id' => $ruang->id]) }}" class="btn btn-sm btn-success">
                                    <i class="ri-add-line me-1"></i> Tambah Aset
                                </a>
                                <a href="{{ route('user.sarpras.ruang.edit', ['userId' => $userId, 'id' => $ruang->id]) }}" class="btn btn-sm btn-warning">
                                    <i class="ri-pencil-line me-1"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted fw-medium" style="width:180px">Nama Ruang</td>
                                    <td>{{ $ruang->room_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Kode Ruang</td>
                                    <td><code>{{ $ruang->room_code ?? '-' }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Tipe</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $ruang->room_type)) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Satuan Pendidikan</td>
                                    <td>{{ $ruang->school?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Gedung</td>
                                    <td>
                                        @if($ruang->building)
                                            <a href="{{ route('user.sarpras.gedung.show', ['userId' => $userId, 'id' => $ruang->building_id]) }}">
                                                {{ $ruang->building->building_name }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($ruang->studyGroup)
                                <tr>
                                    <td class="text-muted fw-medium">Rombongan Belajar</td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info me-1">
                                            <i class="ri-user-line me-1"></i>Ruang Kelas
                                        </span>
                                        {{ $ruang->studyGroup->full_name }}
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="text-muted fw-medium">Lantai</td>
                                    <td>{{ $ruang->floor ? "Lantai {$ruang->floor}" : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Luas</td>
                                    <td>{{ $ruang->room_area ? number_format($ruang->room_area, 1) . ' m²' : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Kapasitas</td>
                                    <td>{{ $ruang->capacity ? $ruang->capacity . ' orang' : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Kondisi</td>
                                    <td>
                                        @php
                                            $kondisiColor = [
                                                'baik' => 'success',
                                                'rusak_ringan' => 'warning',
                                                'rusak_sedang' => 'warning',
                                                'rusak_berat' => 'danger',
                                            ][$ruang->condition] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $kondisiColor }}-subtle text-{{ $kondisiColor }}">
                                            {{ ucfirst(str_replace('_', ' ', $ruang->condition)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Status</td>
                                    <td>
                                        @if($ruang->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Dapat Dipinjam</td>
                                    <td>
                                        @if($ruang->is_bookable)
                                            <span class="badge bg-info-subtle text-info">Ya</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Tidak</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($ruang->facilities)
                                <tr>
                                    <td class="text-muted fw-medium">Fasilitas</td>
                                    <td>{{ $ruang->facilities }}</td>
                                </tr>
                                @endif
                                @if($ruang->notes)
                                <tr>
                                    <td class="text-muted fw-medium">Catatan</td>
                                    <td>{{ $ruang->notes }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Aset Card --}}
            <div class="card mt-3">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="mb-0">Aset / Inventaris</h5>
                            <p class="text-muted mb-0">{{ $ruang->assets->count() }} item terdaftar</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.sarpras.aset.create', ['userId' => $userId]) }}?room_id={{ $ruang->id }}" class="btn btn-sm btn-success">
                                <i class="ri-add-line me-1"></i> Tambah Aset
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($ruang->assets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th>Nama Aset</th>
                                        <th>Kode</th>
                                        <th>Kategori</th>
                                        <th>Kondisi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ruang->assets as $aset)
                                        <tr>
                                            <td>
                                                <a href="{{ route('user.sarpras.aset.show', ['userId' => $userId, 'id' => $aset->id]) }}" class="fw-medium link-primary">
                                                    {{ $aset->asset_name }}
                                                </a>
                                            </td>
                                            <td><code>{{ $aset->asset_code ?? '-' }}</code></td>
                                            <td>{{ $aset->category?->name ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $asetColor = [
                                                        'baik' => 'success',
                                                        'rusak_ringan' => 'warning',
                                                        'rusak_sedang' => 'warning',
                                                        'rusak_berat' => 'danger',
                                                        'hilang' => 'secondary',
                                                        'dihapus' => 'secondary',
                                                    ][$aset->condition] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $asetColor }}-subtle text-{{ $asetColor }}">
                                                    {{ ucfirst(str_replace('_', ' ', $aset->condition)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @php $statusColor = ['tersedia'=>'success','dipinjam'=>'info','dalam_perbaikan'=>'warning','dihapus'=>'secondary'][$aset->status] ?? 'secondary'; @endphp
                                                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                    {{ ucfirst(str_replace('_', ' ', $aset->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('user.sarpras.aset.show', ['userId' => $userId, 'id' => $aset->id]) }}" class="btn btn-sm btn-soft-primary">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="avatar-lg mx-auto mb-3">
                                <div class="avatar-title bg-light rounded-circle">
                                    <i class="ri-archive-line fs-1 text-muted"></i>
                                </div>
                            </div>
                            <h6 class="text-muted">Belum ada aset di ruang ini</h6>
                            <a href="{{ route('user.sarpras.aset.create', ['userId' => $userId]) }}?room_id={{ $ruang->id }}" class="btn btn-sm btn-success mt-2">
                                <i class="ri-add-line me-1"></i> Tambah Aset
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Informasi Lainnya</h5></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <div class="text-muted small">Total Aset</div>
                            <div class="fw-medium">{{ $ruang->assets->count() }} item</div>
                        </li>
                        <li class="mb-2">
                            <div class="text-muted small">Aset Aktif</div>
                            <div class="fw-medium">{{ $ruang->assets->where('is_active', true)->count() }} item</div>
                        </li>
                        <li class="mb-2">
                            <div class="text-muted small">Nilai Total</div>
                            <div class="fw-medium">
                                {{ $ruang->assets->sum('current_value') ? 'Rp ' . number_format($ruang->assets->sum('current_value'), 0, ',', '.') : '-' }}
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
