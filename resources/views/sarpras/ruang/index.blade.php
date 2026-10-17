@extends('layouts.master')
@section('title') Sarana Prasarana — Ruang @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

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

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Ruang</h5>
                            <p class="text-muted mb-0">Kelola ruang pada satuan pendidikan.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('sarpras.ruang.create') }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Ruang
                            </a>
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
                                                           data-id="{{ $r->id }}" data-name="{{ $r->room_name }}">
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
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-ruang').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: 'Ruang "' + name + '" akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/sarpras/ruang/' + id;
                        ['_token','_method'].forEach(function(n, i) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = n;
                            inp.value = i === 0 ? '{{ csrf_token() }}' : 'DELETE';
                            form.appendChild(inp);
                        });
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection