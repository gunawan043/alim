@extends('layouts.master')
@section('title') Mahrom — {{ $student->name ?? 'Santri' }} @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}">{{ $student->name ?? 'Santri' }}</a> @endslot
        @slot('title') Mahrom @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Student Summary Header --}}
    @if(isset($student))
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border border-primary-subtle">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            @if($student->photo_path)
                                <img src="{{ asset('storage/' . $student->photo_path) }}" alt="{{ $student->name }}"
                                     class="rounded-circle object-fit-cover" width="40" height="40">
                            @else
                                <span class="avatar-title rounded-circle fs-2 fw-bold bg-{{ $student->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $student->gender === 'P' ? 'danger' : 'primary' }}">
                                    {{ strtoupper(substr($student->name, 0, 2)) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Santri</p>
                            <h5 class="fw-bold mb-0">{{ $student->name }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-shield-star-line text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Total Mahrom</p>
                            <h3 class="fw-bold mb-0">{{ $mahroms->total() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-star-line text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Mahrom Utama</p>
                            <h3 class="fw-bold mb-0">{{ $primaryCount ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-checkbox-circle-line text-info"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Mahrom Aktif</p>
                            <h3 class="fw-bold mb-0">{{ $activeCount ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Mahrom</h5>
                            <p class="text-muted mb-0">Orang tua / mahrom yang dapat menjenguk santri.</p>
                        </div>
                        <div class="col-sm-auto">
                            @php $maxMahrom = config('alim.max_mahrom', 4); @endphp
                            @if($mahroms->total() >= $maxMahrom)
                                <div class="alert alert-warning py-2 px-3 mb-0 d-inline-flex align-items-center gap-1">
                                    <i class="ri-error-warning-line"></i>
                                    Batas maksimal {{ $maxMahrom }} mahrom tercapai.
                                </div>
                            @else
                                <a href="{{ route('user.students.mahroms.create', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                                   class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah Mahrom
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Foto</th>
                                    <th>Nama</th>
                                    <th>Hubungan</th>
                                    <th>NIK</th>
                                    <th>Telepon</th>
                                    <th class="text-center">Utama?</th>
                                    <th class="text-center">Aktif?</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mahroms as $i => $m)
                                    <tr>
                                        <td class="text-center">{{ ($mahroms->currentPage() - 1) * $mahroms->perPage() + $i + 1 }}</td>
                                        <td class="text-center">
                                            @if($m->photo_path)
                                                <img src="{{ asset('storage/' . $m->photo_path) }}"
                                                     alt="Foto {{ $m->name }}"
                                                     class="rounded object-fit-cover"
                                                     style="width:44px;height:44px;">
                                            @else
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-secondary-subtle text-secondary fs-6">
                                                        {{ strtoupper(substr($m->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('user.students.mahroms.show', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $m->id]) }}"
                                               class="fw-semibold text-decoration-none">
                                                {{ $m->name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $m->is_primary ? 'primary' : 'secondary' }}-subtle text-{{ $m->is_primary ? 'primary' : 'secondary' }}">
                                                {{ $m->relationship_text ?? ucfirst(str_replace('_', ' ', $m->relationship)) }}
                                            </span>
                                        </td>
                                        <td><code>{{ $m->id_number ?? '—' }}</code></td>
                                        <td>{{ $m->phone ?? '—' }}</td>
                                        <td class="text-center">
                                            @if($m->is_primary)
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-star-line me-1"></i>Ya
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($m->is_active)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Aktif
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.students.mahroms.show', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $m->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('user.students.mahroms.edit', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $m->id]) }}"
                                               class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            <form method="POST"
                                                  action="{{ route('user.students.mahroms.destroy', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $m->id]) }}"
                                                  class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" title="Hapus">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="ri-parent-line fs-1 d-block mb-2"></i>
                                            Belum ada data mahrom.
                                            <br>
                                            <a href="{{ route('user.students.mahroms.create', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                                               class="btn btn-primary btn-sm mt-2">
                                                <i class="ri-add-line me-1"></i> Tambah Mahrom
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($mahroms->hasPages())
                        {{ $mahroms->withQueryString()->links() }}
                    @endif

                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ri-information-line text-primary mt-1"></i>
                            <div>
                                <strong>Catatan:</strong>
                                <ul class="mb-0 ps-3 mt-1 small text-muted">
                                    <li>Mahrom adalah orang yang diperbolehkan menjengukdi dalam kamar.</li>
                                    <li>Batas maksimal {{ $maxMahrom ?? 4 }} mahrom per Santri.</li>
                                    <li>Mahrom utama akan menjadi kontak utama untuk informasi Santri.</li>
                                    <li>Mahrom nonaktif tidak dapat menjenguk.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection