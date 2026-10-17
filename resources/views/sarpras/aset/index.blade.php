@extends('layouts.master')
@section('title') Aset / Inventaris @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Aset / Inventaris @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">Daftar Aset / Inventaris</h5>
                    </div>
                    <div class="col-sm-auto">
                        <div class="hstack gap-2">
                            <a href="{{ route('sarpras.aset.import') }}" class="btn btn-outline-primary">
                                <i class="ri-upload-cloud-line align-bottom me-1"></i> Import
                            </a>
                            <a href="{{ route('sarpras.aset.create') }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-2">
                        <select name="room_id" class="form-control">
                            <option value="">Semua Ruang</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="condition" class="form-control">
                            <option value="">Semua Kondisi</option>
                            @foreach(App\Models\Asset::CONDITION_OPTIONS as $c)
                                <option value="{{ $c }}" {{ request('condition') == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            @foreach(App\Models\Asset::STATUS_OPTIONS as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, kode, merk..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('sarpras.aset.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-nowrap align-middle">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Nama Aset</th>
                                <th>Kode</th>
                                <th>Kategori</th>
                                <th>Ruang</th>
                                <th>Merk/Model</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                                <th>Nilai (Rp)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($asets as $a)
                            <tr>
                                <td>{{ $loop->iteration + ($asets->currentPage() - 1) * $asets->perPage() }}</td>
                                <td>
                                    <a href="{{ route('sarpras.aset.show', ['id' => $a->id]) }}" class="fw-medium link-primary">{{ $a->asset_name }}</a>
                                </td>
                                <td><code>{{ $a->asset_code ?? '-' }}</code></td>
                                <td>{{ $a->category?->name ?? '-' }}</td>
                                <td>{{ $a->room?->room_name ?? '-' }}</td>
                                <td>{{ $a->brand ? "{$a->brand} {$a->model}" : '-' }}</td>
                                <td>
                                    @php $kc=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary','dihapus'=>'secondary']; @endphp
                                    <span class="badge bg-{{ $kc[$a->condition] ?? 'secondary' }}-subtle text-{{ $kc[$a->condition] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_',' ',$a->condition)) }}
                                    </span>
                                </td>
                                <td>
                                    @php $sc=['tersedia'=>'success','dipinjam'=>'info','dalam_perbaikan'=>'warning','dihapus'=>'secondary']; @endphp
                                    <span class="badge bg-{{ $sc[$a->status] ?? 'secondary' }}-subtle text-{{ $sc[$a->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_',' ',$a->status)) }}
                                    </span>
                                </td>
                                <td>{{ $a->current_value ? 'Rp ' . number_format($a->current_value, 0, ',', '.') : '-' }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ route('sarpras.aset.show', ['id' => $a->id]) }}"><i class="ri-eye-line me-2"></i>Detail</a></li>
                                            <li><a class="dropdown-item" href="{{ route('sarpras.aset.edit', ['id' => $a->id]) }}"><i class="ri-pencil-line me-2"></i>Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger delete-aset" href="javascript:void(0)" data-id="{{ $a->id }}" data-name="{{ $a->asset_name }}">
                                                    <i class="ri-delete-bin-line me-2"></i>Hapus
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="avatar-lg mx-auto mb-3"><div class="avatar-title bg-light rounded-circle"><i class="ri-archive-line fs-1 text-muted"></i></div></div>
                                    <h5 class="text-muted">Belum ada data aset</h5>
                                    <a href="{{ route('sarpras.aset.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Tambah Aset</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $asets])
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.querySelectorAll('.delete-aset').forEach(function(btn) {
    btn.addEventListener('click', function() {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: 'Aset "' + this.dataset.name + '" akan dihapus.',
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
                form.action = '/sarpras/aset/' + btn.dataset.id;
                ['_token','_method'].forEach(function(n, i) {
                    var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = n;
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
