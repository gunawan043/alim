@extends('layouts.master')
@section('title') Data Mahrom — Semua Santri @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Santri</a> @endslot
        @slot('li_3') Data Mahrom @endslot
        @slot('title') Semua Mahrom @endslot
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

    {{-- Global Stats Header --}}
    @if(isset($stats))
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-parent-line text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Total Mahrom</p>
                            <h3 class="fw-bold mb-0">{{ $stats['total'] ?? $mahroms->total() }}</h3>
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
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-star-line text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Mahrom Utama</p>
                            <h3 class="fw-bold mb-0">{{ $stats['primary'] ?? 0 }}</h3>
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
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Mahrom Aktif</p>
                            <h3 class="fw-bold mb-0">{{ $stats['active'] ?? 0 }}</h3>
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
                                <i class="ri-user-line text-info"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">Santri dengan Mahrom</p>
                            <h3 class="fw-bold mb-0">{{ $stats['students_with_mahrom'] ?? 0 }}</h3>
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
                            <h5 class="card-title mb-0">Daftar Mahrom (Semua Santri)</h5>
                            <p class="text-muted mb-0">Kelola data orang tua / mahrom lintas seluruh santri.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.students.mahroms.globalCreate', ['userId' => $userId]) }}"
                               class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Mahrom
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Search / Filter --}}
                    <form method="GET" action="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}" class="row g-2 mb-3">
                        <div class="col-md-4">
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama / NIK / telepon...">
                        </div>
                        <div class="col-md-3">
                            <select name="relationship" class="form-select">
                                <option value="">— Semua Hubungan —</option>
                                @foreach(($relationships ?? []) as $key => $label)
                                    <option value="{{ $key }}" {{ request('relationship') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_active" class="form-select">
                                <option value="">— Status —</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Cari
                            </button>
                            <a href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}" class="btn btn-light">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Foto</th>
                                    <th>Nama</th>
                                    <th>Santri</th>
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
                                            <a href="{{ route('user.students.mahroms.globalEdit', ['userId' => $userId, 'mahromUuid' => $m->id]) }}"
                                               class="fw-semibold text-decoration-none">
                                                {{ $m->name }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($m->student)
                                                <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $m->student_id]) }}"
                                                   class="text-decoration-none">
                                                    {{ $m->student->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
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
                                            <a href="{{ route('user.students.mahroms.globalShow', ['userId' => $userId, 'mahromUuid' => $m->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('user.students.mahroms.globalEdit', ['userId' => $userId, 'mahromUuid' => $m->id]) }}"
                                               class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            <form method="POST"
                                                  action="{{ route('user.students.mahroms.globalDestroy', ['userId' => $userId, 'mahromUuid' => $m->id]) }}"
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
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="ri-parent-line fs-1 d-block mb-2"></i>
                                            Belum ada data mahrom.
                                            <br>
                                            <a href="{{ route('user.students.mahroms.globalCreate', ['userId' => $userId]) }}"
                                               class="btn btn-primary btn-sm mt-2">
                                                <i class="ri-add-line me-1"></i> Tambah Mahrom
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination (sama persis dengan halaman permits) --}}
                    <x-pagination :paginator="$mahroms" />

                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ri-information-line text-primary mt-1"></i>
                            <div>
                                <strong>Catatan:</strong>
                                <ul class="mb-0 ps-3 mt-1 small text-muted">
                                    <li>Halaman ini menampilkan seluruh mahrom dari semua santri.</li>
                                    <li>Mahrom adalah orang yang diperbolehkan menjenguk di dalam kamar.</li>
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