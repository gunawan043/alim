@extends('layouts.master')
@section('title') Detail Ruang — {{ $ruang->room_name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('li_2') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}#tab-ruang">Ruang</a> @endslot
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
                                <a href="{{ route('sarpras.user.aset.import.room', ['userId' => $userId, 'roomId' => $ruang->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ri-upload-cloud-line me-1"></i> Import Aset
                                </a>
                                <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}#tab-aset" class="btn btn-sm btn-success">
                                    <i class="ri-add-line me-1"></i> Tambah Aset
                                </a>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditRuang">
                                    <i class="ri-pencil-line me-1"></i> Edit
                                </button>
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
                                            {{ $ruang->building->building_name }}
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
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahAset">
                                <i class="ri-add-line me-1"></i> Tambah Aset
                            </button>
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
                                                <a href="{{ route('sarpras.aset.show', ['id' => $aset->id]) }}" class="fw-medium link-primary">
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
                                                <a href="{{ route('sarpras.aset.show', ['id' => $aset->id]) }}" class="btn btn-sm btn-soft-primary">
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
                            <button class="btn btn-sm btn-success mt-2" data-bs-toggle="modal" data-bs-target="#modalTambahAset">
                                <i class="ri-add-line me-1"></i> Tambah Aset
                            </button>
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
</div>

{{-- Modal: Tambah Aset ke Ruang Ini --}}
<div class="modal fade" id="modalTambahAset" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-archive-line text-primary me-2"></i>Tambah Aset ke {{ $ruang->room_name }}</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('sarpras.user.aset.store', ['userId' => $userId]) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="room_id" value="{{ $ruang->id }}">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
                            <input type="text" name="asset_name" class="form-control" required placeholder="Contoh: Meja Guru MDF">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="asset_category_id" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                @foreach(\App\Models\AssetCategory::where('is_active', true)->orderBy('name')->get() as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Merk / Brand</label>
                            <input type="text" name="brand" class="form-control" placeholder="Contoh: Yamaha">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Model / Tipe</label>
                            <input type="text" name="model" class="form-control" placeholder="Contoh: P-45B">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kondisi <span class="text-danger">*</span></label>
                            <select name="condition" class="form-select" required>
                                @foreach(\App\Models\Asset::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tanggal Perolehan</label>
                            <input type="date" name="acquisition_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Harga Perolehan (Rp)</label>
                            <input type="number" name="acquisition_price" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection