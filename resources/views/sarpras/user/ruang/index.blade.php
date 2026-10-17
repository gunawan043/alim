@extends('layouts.master')
@section('title') Daftar Ruang @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('title') Ruang @endslot
@endcomponent

<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0">Daftar Ruang</h6>
                <a href="{{ route('sarpras.user.ruang.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success">
                    <i class="ri-add-line me-1"></i>Tambah Ruang
                </a>
            </div>
            <div class="card-body p-0">
                @if($rooms->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Nama Ruang</th>
                                <th>Kode</th>
                                <th>Jenis</th>
                                <th>Gedung</th>
                                <th>Lantai</th>
                                <th>Kapasitas</th>
                                <th>Kondisi</th>
                                <th>Aset</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rooms as $r)
                            <tr>
                                <td>
                                    <a href="{{ route('sarpras.user.ruang.show', ['userId' => $userId, 'id' => $r->id]) }}" class="fw-semibold link-primary">
                                        {{ $r->room_name }}
                                    </a>
                                </td>
                                <td><code class="small">{{ $r->room_code ?? '-' }}</code></td>
                                <td>{{ ucfirst(str_replace('_',' ',$r->room_type)) }}</td>
                                <td>{{ $r->building?->building_name ?? '-' }}</td>
                                <td class="text-center">{{ $r->floor ? 'L ' . $r->floor : '-' }}</td>
                                <td class="text-center">{{ $r->capacity ?? '-' }}</td>
                                <td>
                                    @php $c = ['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger'][$r->condition] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $c }}-subtle text-{{ $c }}" style="font-size:10px;">
                                        {{ ucfirst(str_replace('_',' ',$r->condition)) }}
                                    </span>
                                </td>
                                <td class="text-center"><span class="badge bg-light text-dark">{{ $r->assets->count() }}</span></td>
                                <td>
                                    <a href="{{ route('sarpras.user.ruang.show', ['userId' => $userId, 'id' => $r->id]) }}" class="btn btn-sm btn-soft-primary py-0 px-1">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <form action="{{ route('sarpras.user.ruang.destroy', ['userId' => $userId, 'id' => $r->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus ruang ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger py-0 px-1" {{ $r->assets->count() > 0 ? 'disabled title=Ruang memiliki aset' : '' }}>
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-light text-muted rounded-circle fs-1"><i class="ri-door-open-line"></i></span></div>
                    <h6 class="text-muted">Belum ada ruang</h6>
                    <a href="{{ route('sarpras.user.ruang.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success mt-2">
                        <i class="ri-add-line me-1"></i>Tambah Ruang
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="ri-door-open-line text-primary me-2"></i>Statistik</h6></div>
            <div class="card-body p-2">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small text-muted">Total Ruang</span>
                    <strong>{{ $rooms->count() }}</strong>
                </div>
                @foreach(['kelas','laboratorium','perpustakaan','aula','gudang','wc'] as $t)
                    @php $count = $rooms->where('room_type', $t)->count(); @endphp
                    @if($count > 0)
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="small text-muted text-capitalize">{{ str_replace('_',' ',$t) }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="{{ route('sarpras.user.ruang.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
                <i class="ri-add-line me-1"></i>Tambah Ruang
            </a>
        </div>
    </div>
</div>
@endsection