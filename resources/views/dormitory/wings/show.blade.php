@extends('layouts.master')
@section('title') {{ $wing->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Gedung</a> @endslot
        @slot('title') {{ $wing->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        {{-- Info Gedung --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ $wing->name }}</h5></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width:160px;">Asrama</th>
                            <td>{{ $dormitory->name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kode</th>
                            <td><span class="badge bg-dark">{{ $wing->code }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Lantai</th>
                            <td>{{ $wing->floor ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kapasitas</th>
                            <td>{{ number_format($wing->capacity) }} orang</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Supervisor</th>
                            <td>{{ $wing->supervisor?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                @if($wing->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        @if($wing->notes)
                        <tr>
                            <th class="text-muted">Catatan</th>
                            <td>{{ $wing->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.asrama.wings.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $wing->id]) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i> Edit
                    </a>
                    <a href="{{ route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Kembali</a>
                </div>
            </div>
        </div>

        {{-- Kamar di gedung ini --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Kamar di Gedung Ini</h5>
                    <a href="{{ route('user.asrama.rooms.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}?wing_id={{ $wing->id }}" class="btn btn-primary btn-sm">
                        <i class="ri-add-line me-1"></i> Tambah Kamar
                    </a>
                </div>
                <div class="card-body p-0">
                    @php
                        $wingRooms = $wing->rooms->sortBy('code');
                    @endphp
                    @if($wingRooms->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="ri-door-open-line fs-1 d-block mb-2"></i>
                            Belum ada kamar di gedung ini.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="text-center" style="width:50px;">No</th>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th class="text-center">Lantai</th>
                                        <th class="text-center">Kapasitas</th>
                                        <th class="text-center">Tipe</th>
                                        <th class="text-center">Penghuni</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($wingRooms as $r)
                                        @php $activeCount = $r->residents->filter(fn($res) => $res->is_active)->count(); @endphp
                                        <tr>
                                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                            <td><span class="badge bg-dark">{{ $r->code }}</span></td>
                                            <td>
                                                <a href="{{ route('user.asrama.rooms.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}">
                                                    {{ $r->name ?? $r->code }}
                                                </a>
                                            </td>
                                            <td class="text-center">{{ $r->floor ?? '-' }}</td>
                                            <td class="text-center">{{ $r->capacity }}</td>
                                            <td class="text-center">
                                                @if($r->room_type)
                                                    <span class="badge bg-{{ $r->room_type === 'musyrif' ? 'warning' : 'info' }}-subtle">
                                                        {{ ucfirst($r->room_type) }}
                                                    </span>
                                                @else — @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $activeCount >= $r->capacity ? 'danger' : 'success' }}">
                                                    {{ $activeCount }}/{{ $r->capacity }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('user.asrama.rooms.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}"
                                                   class="btn btn-outline-primary btn-sm py-0 px-1" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
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
@endsection
