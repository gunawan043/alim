@extends('layouts.master')
@section('title') Detail Gedung — {{ $gedung->building_name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') <a href="{{ route('sarpras.gedung.index') }}">Sarana Prasarana</a> @endslot
        @slot('li_3') <a href="{{ route('sarpras.gedung.index') }}">Gedung</a> @endslot
        @slot('title') {{ $gedung->building_name }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="mb-0">Detail Gedung</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('sarpras.gedung.edit', ['id' => $gedung->id]) }}" class="btn btn-sm btn-warning">
                                <i class="ri-pencil-line me-1"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted fw-medium" style="width:200px">Nama Gedung</td>
                                    <td>{{ $gedung->building_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Kode Gedung</td>
                                    <td><code>{{ $gedung->building_code ?? '-' }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Tipe</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $gedung->building_type)) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Satuan Pendidikan</td>
                                    <td>{{ $gedung->school?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Jumlah Lantai</td>
                                    <td>{{ $gedung->total_floors ? "{$gedung->total_floors} Lantai" : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Luas</td>
                                    <td>{{ $gedung->building_area ? number_format($gedung->building_area, 1) . ' m²' : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Tahun Dibangun</td>
                                    <td>{{ $gedung->build_year ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Tahun Renovasi</td>
                                    <td>{{ $gedung->renovation_year ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Kondisi Struktur</td>
                                    <td>
                                        @php
                                            $kondisiColor = [
                                                'baik' => 'success',
                                                'rusak_ringan' => 'warning',
                                                'rusak_sedang' => 'warning',
                                                'rusak_berat' => 'danger',
                                            ][$gedung->structure_condition] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $kondisiColor }}-subtle text-{{ $kondisiColor }}">
                                            {{ ucfirst(str_replace('_', ' ', $gedung->structure_condition)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Status Kepemilikan</td>
                                    <td>{{ $gedung->ownership_status ? ucfirst(str_replace('_', ' ', $gedung->ownership_status)) : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Nomor IMB</td>
                                    <td>{{ $gedung->imb_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Total Ruang</td>
                                    <td>{{ $gedung->rooms->count() }} ruang</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Status</td>
                                    <td>
                                        @if($gedung->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($gedung->notes)
                                <tr>
                                    <td class="text-muted fw-medium">Catatan</td>
                                    <td>{{ $gedung->notes }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Ruang di gedung ini --}}
            <div class="card mt-3">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="mb-0">Ruang di Gedung Ini</h5>
                            <p class="text-muted mb-0">{{ $gedung->rooms->count() }} ruang terdaftar</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('sarpras.ruang.create') }}?building_id={{ $gedung->id }}" class="btn btn-sm btn-success">
                                <i class="ri-add-line me-1"></i> Tambah Ruang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($gedung->rooms->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th>Nama Ruang</th>
                                        <th>Kode</th>
                                        <th>Tipe</th>
                                        <th>Lantai</th>
                                        <th>Kapasitas</th>
                                        <th>Kondisi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gedung->rooms as $r)
                                        <tr>
                                            <td>
                                                <a href="{{ route('sarpras.ruang.show', ['id' => $r->id]) }}" class="fw-medium link-primary">
                                                    {{ $r->room_name }}
                                                </a>
                                            </td>
                                            <td><code>{{ $r->room_code ?? '-' }}</code></td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $r->room_type)) }}</td>
                                            <td>{{ $r->floor ? "Lantai {$r->floor}" : '-' }}</td>
                                            <td>{{ $r->capacity ? "{$r->capacity} org" : '-' }}</td>
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
                                                <a href="{{ route('sarpras.ruang.show', ['id' => $r->id]) }}" class="btn btn-sm btn-soft-primary">
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
                                    <i class="ri-community-line fs-1 text-muted"></i>
                                </div>
                            </div>
                            <h6 class="text-muted">Belum ada ruang di gedung ini</h6>
                            <a href="{{ route('sarpras.ruang.create') }}?building_id={{ $gedung->id }}" class="btn btn-sm btn-success mt-2">
                                <i class="ri-add-line me-1"></i> Tambah Ruang
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
