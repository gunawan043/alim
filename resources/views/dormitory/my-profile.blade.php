@extends('layouts.master')
@section('title') Profil Asrama @endsection
@section('css')
<style>
    .dormitory-badge-lg { font-size: 0.9rem; padding: 0.4em 0.8em; }
    .info-card-profile { border: 1px solid #f0f0f0; border-radius: 10px; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('title') Profil Asrama @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header: Logo + Name + Badges --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-auto">
                    @if($dormitory->logo_path)
                        <img src="{{ $dormitory->logo_path }}" alt="{{ $dormitory->name }}" class="rounded-circle" width="90" height="90" style="object-fit:cover;border:3px solid #dee2e6">
                    @else
                        <div class="avatar-xl bg-{{ $dormitory->gender === 'putra' ? 'primary' : ($dormitory->gender === 'putri' ? 'danger' : 'info') }}-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:90px;height:90px">
                            <span class="fs-2 fw-bold text-{{ $dormitory->gender === 'putra' ? 'primary' : ($dormitory->gender === 'putri' ? 'danger' : 'info') }}">
                                {{ strtoupper($dormitory->gender === 'putra' ? 'P' : ($dormitory->gender === 'putri' ? 'Pi' : 'M')) }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="col">
                    <div class="d-flex align-items-start flex-wrap gap-2 mb-2">
                        <h3 class="mb-0 me-2">{{ $dormitory->name }}</h3>
                        @if(!$dormitory->is_active)
                            <span class="badge bg-danger-subtle text-danger dormitory-badge-lg">Nonaktif</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-{{ $dormitory->gender === 'putra' ? 'primary' : ($dormitory->gender === 'putri' ? 'danger' : 'info') }}-subtle text-{{ $dormitory->gender === 'putra' ? 'primary' : ($dormitory->gender === 'putri' ? 'danger' : 'info') }} dormitory-badge-lg">
                            <i class="ri-user-line me-1"></i>{{ ucfirst($dormitory->gender) }}
                        </span>
                    </div>
                    <div class="mt-2 text-muted small">
                        @if($dormitory->code)
                            <span class="me-3"><i class="ri-hash-line me-1"></i>Kode: {{ $dormitory->code }}</span>
                        @endif
                        @if($dormitory->head)
                            <span class="me-3"><i class="ri-user-star-line me-1"></i>Kepala Asrama: {{ $dormitory->head->name }}</span>
                        @endif
                        @if($dormitory->school)
                            <span><i class="ri-school-line me-1"></i>{{ $dormitory->school->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-auto">
                    @if($dormitory->capacity)
                        <span class="badge bg-primary-subtle text-primary dormitory-badge-lg">
                            Kapasitas {{ number_format($dormitory->capacity) }} penghuni
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- LEFT COLUMN --}}
        <div class="col-lg-8">
            {{-- Identitas Asrama --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-hotel-line me-2 text-primary"></i>Identitas Asrama</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="table-light w-40">Nama Asrama</th>
                                <td>{{ $dormitory->name }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Kode</th>
                                <td>{{ $dormitory->code ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Gender</th>
                                <td>{{ ucfirst($dormitory->gender) }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Status</th>
                                <td>
                                    <span class="badge bg-{{ $dormitory->is_active ? 'success' : 'secondary' }}-subtle text-{{ $dormitory->is_active ? 'success' : 'secondary' }}">
                                        {{ $dormitory->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="table-light">Kapasitas</th>
                                <td>{{ $dormitory->capacity ? number_format($dormitory->capacity) . ' penghuni' : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Jumlah Kamar</th>
                                <td>{{ number_format($dormitory->total_rooms ?? 0) }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Jumlah Blok (Wing)</th>
                                <td>{{ number_format($dormitory->total_wings ?? 0) }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Jumlah Penghuni Aktif</th>
                                <td>
                                    <span class="fw-semibold">{{ number_format($dormitory->total_residents) }}</span>
                                    @if($dormitory->capacity)
                                        / {{ number_format($dormitory->capacity) }}
                                        <span class="text-muted ms-1">({{ round($dormitory->total_residents / max(1, $dormitory->capacity) * 100, 1) }}%)</span>
                                    @endif
                                </td>
                            </tr>
                            @if($dormitory->notes)
                                <tr>
                                    <th class="table-light">Catatan</th>
                                    <td>{{ $dormitory->notes }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kepala Asrama --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-user-star-line me-2 text-warning"></i>Kepala Asrama</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="table-light w-40">Nama</th>
                                <td>
                                    @if($dormitory->head)
                                        {{ $dormitory->head->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-light">No. Handphone</th>
                                <td>{{ $dormitory->head->no_hp ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kontak & Alamat --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-map-pin-line me-2 text-danger"></i>Kontak & Alamat</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="table-light">Alamat</th>
                                <td>{{ $dormitory->address ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">No. Telepon</th>
                                <td>{{ $dormitory->phone ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-lg-4">
            {{-- Badge / Gender --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-user-line me-2 text-info"></i>Informasi Singkat</h5>
                </div>
                <div class="card-body text-center">
                    @if($dormitory->logo_path)
                        <img src="{{ $dormitory->logo_path }}" alt="Logo Asrama" class="img-fluid rounded-circle mb-2" style="max-height:160px">
                    @else
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width:120px;height:120px">
                            <i class="ri-hotel-line text-{{ $dormitory->gender === 'putra' ? 'primary' : ($dormitory->gender === 'putri' ? 'danger' : 'info') }} fs-2"></i>
                        </div>
                        <small class="text-muted d-block mb-2">Logo belum diupload</small>
                    @endif
                    <hr>
                    <table class="table table-sm table-borderless mb-0 text-start">
                        <tr><th class="text-muted small">Kode</th><td class="small">{{ $dormitory->code ?? '-' }}</td></tr>
                        <tr><th class="text-muted small">Gender</th><td class="small">{{ ucfirst($dormitory->gender) }}</td></tr>
                        <tr><th class="text-muted small">Status</th><td class="small">
                            <span class="badge bg-{{ $dormitory->is_active ? 'success' : 'secondary' }}-subtle text-{{ $dormitory->is_active ? 'success' : 'secondary' }}">{{ $dormitory->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td></tr>
                        @if($dormitory->school)
                        <tr><th class="text-muted small">Sekolah</th><td class="small">{{ $dormitory->school->name }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-flashlight-line me-2 text-success"></i>Menu Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                            <i class="ri-user-follow-line me-2 text-primary"></i> Data Penghuni
                            <span class="float-end badge bg-primary">{{ number_format($dormitory->total_residents) }}</span>
                        </a>
                        <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                            <i class="ri-door-open-line me-2 text-info"></i> Daftar Kamar
                            <span class="float-end badge bg-info">{{ number_format($dormitory->rooms_count ?? 0) }}</span>
                        </a>
                        <a href="{{ route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                            <i class="ri-stack-line me-2 text-warning"></i> Lantai Blok
                            <span class="float-end badge bg-warning">{{ number_format($dormitory->wings_count ?? 0) }}</span>
                        </a>
                        <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                            <i class="ri-pass-valid-line me-2 text-success"></i> Perizinan
                        </a>
                        <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                            <i class="ri-calendar-check-line me-2 text-danger"></i> Absensi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}" class="btn btn-light">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>
@endsection
