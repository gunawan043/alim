@extends('layouts.master')
@section('title') Sarana Prasarana — Ruang @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') <a href="{{ route('sarpras.gedung.index') }}">Sarana Prasarana</a> @endslot
        @slot('title') Ruang
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('import_errors') && count(session('import_errors')))
        <div class="alert alert-warning alert-dismissible fade show"><div class="fw-semibold mb-1">Baris yang dilewati:</div><ul class="mb-0 small">@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <x-sarpras-stats :items="[
        ['label'=>'Total Ruang','value'=>$stats['total'],'tone'=>'primary','icon'=>'ri-door-open-line'],
        ['label'=>'Ruang Aktif','value'=>$stats['active'],'tone'=>'success','icon'=>'ri-checkbox-circle-line'],
        ['label'=>'Bisa Dibooking','value'=>$stats['bookable'],'tone'=>'info','icon'=>'ri-calendar-check-line'],
        ['label'=>'Total Kapasitas','value'=>$stats['capacity'],'tone'=>'warning','icon'=>'ri-group-line'],
    ]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Ruang</h5>
                            <p class="text-muted mb-0">Kelola ruang pada satuan pendidikan.</p>
                        </div>
                        <div class="col-sm-auto d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importRuangModal"><i class="ri-upload-cloud-2-line me-1"></i>Import Ruang</button>
                            <a href="{{ route('sarpras.ruang.create') }}" class="btn btn-success"><i class="ri-add-line align-bottom me-1"></i> Tambah Ruang</a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="room_type" class="form-control">
                                <option value="">Semua Tipe</option>
                                @foreach(App\Models\AssetRoom::ROOM_TYPE_OPTIONS as $t)
                                    <option value="{{ $t }}" {{ request('room_type') == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="condition" class="form-control">
                                <option value="">Semua Kondisi</option>
                                @foreach(App\Models\AssetRoom::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}" {{ request('condition') == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode ruang..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('sarpras.ruang.index') }}" class="btn btn-light w-100">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Ruang</th>
                                    <th>Kode</th>
                                    <th>Tipe</th>
                                    <th>Gedung</th>
                                    <th>Lantai</th>
                                    <th>Luas (m²)</th>
                                    <th>Kapasitas</th>
                                    <th>Kondisi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ruangs as $r)
                                    <tr>
                                        <td>{{ $loop->iteration + ($ruangs->currentPage() - 1) * $ruangs->perPage() }}</td>
                                        <td>
                                            <a href="{{ route('sarpras.ruang.show', ['id' => $r->id]) }}"
                                               class="fw-medium link-primary">{{ $r->room_name }}</a>
                                            @if($r->study_group_id)
                                                <span class="badge bg-info-subtle text-info ms-1" title="Ruang Kelas — Rombongan Belajar">
                                                    <i class="ri-user-line"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td><code>{{ $r->room_code ?? '-' }}</code></td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $r->room_type)) }}</td>
                                        <td>
                                            @if($r->building)
                                                <a href="{{ route('sarpras.gedung.show', ['id' => $r->building_id]) }}">
                                                    {{ $r->building->building_name }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $r->floor ? "Lantai {$r->floor}" : '-' }}</td>
                                        <td>{{ $r->room_area ? number_format($r->room_area, 1) . ' m²' : '-' }}</td>
                                        <td>{{ $r->capacity ? $r->capacity . ' org' : '-' }}</td>
                                        <td>
                                            @php
                                                $kondisiColor = [
                                                    'baik' => 'success',
                                                    'rusak_ringan' => 'warning',
                                                    'rusak_sedang' => 'warning',
                                                    'rusak_berat' => 'danger',
                                                ][$r->condition] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $kondisiColor }}-subtle text-{{ $kondisiColor }}">
                                                {{ ucfirst(str_replace('_', ' ', $r->condition)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($r->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('sarpras.ruang.show', ['id' => $r->id]) }}">
                                                            <i class="ri-eye-line me-2"></i>Detail
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('sarpras.aset.index', ['room_id' => $r->id]) }}">
                                                            <i class="ri-archive-line me-2"></i>Daftar Aset
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('sarpras.ruang.edit', ['id' => $r->id]) }}">
                                                            <i class="ri-pencil-line me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-ruang" href="javascript:void(0)"
                                                           data-id="{{ $r->id }}" data-action="{{ route('sarpras.ruang.destroy', ['id' => $r->id]) }}" data-name="{{ $r->room_name }}">
                                                            <i class="ri-delete-bin-line me-2"></i>Hapus
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-5">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-community-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-muted">Belum ada data ruang</h5>
                                            <p class="text-muted">Tambah ruang untuk memulai pencatatan sarana prasarana.</p>
                                            <a href="{{ route('sarpras.ruang.create') }}" class="btn btn-success">
                                                <i class="ri-add-line me-1"></i>Tambah Ruang
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('shared._pagination', ['paginator' => $ruangs])
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="importRuangModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.ruang.import') }}" enctype="multipart/form-data" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title"><i class="ri-upload-cloud-2-line me-1"></i>Import Data Ruang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-light border small"><i class="ri-information-line me-1"></i>Gunakan template agar nama kolom sesuai. Data dengan kode ruang yang sama akan diperbarui.</div><div class="mb-3"><label class="form-label">File Excel / CSV <span class="text-danger">*</span></label><input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required></div><a href="{{ route('sarpras.data-exchange.template', ['dataset' => 'ruang']) }}" class="btn btn-sm btn-outline-secondary"><i class="ri-file-download-line me-1"></i>Unduh Template Ruang</a></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="ri-upload-2-line me-1"></i>Import Sekarang</button></div></form></div></div>

    <x-modal-confirm id="deleteRuangModal" icon="https://cdn.lordicon.com/gsqxdxog.json" title="Hapus Ruang?" submitLabel="Ya, Hapus" submitIcon="ri-delete-bin-line" submitClass="btn-danger" formId="deleteRuangForm">
        <p class="text-center text-muted mb-0" id="deleteRuangMessage">Ruang akan dihapus.</p>
    </x-modal-confirm>
    <form id="deleteRuangForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
@endsection

@push('script')
<script>
    document.querySelectorAll('.delete-ruang').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = document.getElementById('deleteRuangForm');
            if (!form) return;
            form.action = button.dataset.action;
            document.getElementById('deleteRuangMessage').textContent = 'Ruang "' + button.dataset.name + '" akan dihapus permanen.';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRuangModal')).show();
        });
    });
</script>
@endpush