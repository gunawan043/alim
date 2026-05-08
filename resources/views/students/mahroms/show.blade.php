@extends('layouts.master')
@section('title') Detail Mahrom — {{ $mahrom->name ?? 'Mahrom' }} @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('css')
<style>
    .photo-frame {
        border: 3px solid var(--bs-border-color);
        border-radius: 12px;
        overflow: hidden;
        background: var(--bs-tertiary-bg);
    }
    .photo-frame img { width: 100%; height: 200px; object-fit: cover; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}">{{ $student->name ?? 'Santri' }}</a> @endslot
        @slot('li_4') <a href="{{ route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $student->id]) }}">Mahrom</a> @endslot
        @slot('title') Detail Mahrom @endslot
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

    <div class="row">
        {{-- Left Column: Mahrom Photo + Info --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    {{-- Photo --}}
                    @if($mahrom->photo_path)
                        <div class="photo-frame mb-3">
                            <img src="{{ asset('storage/' . $mahrom->photo_path) }}"
                                 alt="Foto {{ $mahrom->name }}"
                                 class="img-fluid">
                        </div>
                    @else
                        <div class="avatar-xxl mx-auto mb-3">
                            <span class="avatar-title rounded-circle fs-1 fw-bold bg-{{ $mahrom->is_primary ? 'warning' : 'secondary' }}-subtle text-{{ $mahrom->is_primary ? 'warning' : 'secondary' }}">
                                {{ strtoupper(substr($mahrom->name, 0, 2)) }}
                            </span>
                        </div>
                    @endif

                    <h5 class="mb-1">{{ $mahrom->name }}</h5>
                    <p class="text-muted mb-3">
                        {{ $mahrom->relationship_text ?? ucfirst(str_replace('_', ' ', $mahrom->relationship)) }}
                        dari {{ $student->name ?? 'Santri' }}
                    </p>

                    {{-- Status Badges --}}
                    <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
                        @if($mahrom->is_primary)
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-star-line me-1"></i>Mahrom Utama
                            </span>
                        @endif
                        @if($mahrom->is_active)
                            <span class="badge bg-success-subtle text-success">
                                <i class="ri-checkbox-circle-line me-1"></i>Aktif
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">
                                <i class="ri-close-circle-line me-1"></i>Nonaktif
                            </span>
                        @endif
                    </div>

                    {{-- Quick Info --}}
                    <div class="text-start border-top pt-3">
                        <div class="row g-2">
                            @if($mahrom->phone)
                            <div class="col-6">
                                <div class="text-muted small">Telepon</div>
                                <div class="fw-semibold">{{ $mahrom->phone }}</div>
                            </div>
                            @endif
                            @if($mahrom->id_number)
                            <div class="col-6">
                                <div class="text-muted small">NIK</div>
                                <div class="fw-semibold"><code>{{ $mahrom->id_number }}</code></div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="card-footer bg-transparent border-top">
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="{{ route('user.students.mahroms.edit', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $mahrom->id]) }}"
                           class="btn btn-success">
                            <i class="ri-edit-box-line me-1"></i> Edit
                        </a>
                        <a href="{{ route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                           class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            {{-- Mahrom Meta Info --}}
            <div class="card mt-3">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="ri-file-info-line me-2"></i>Info Tambahan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">ID Mahrom</label>
                        <div><code class="small">{{ $mahrom->id }}</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Hubungan</label>
                        <div class="fw-semibold">{{ $mahrom->relationship_text ?? ucfirst(str_replace('_', ' ', $mahrom->relationship)) }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Dibuat</label>
                        <div class="fw-semibold small">{{ $mahrom->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small">Terakhir Update</label>
                        <div class="fw-semibold small">{{ $mahrom->updated_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Full Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-user-settings-line me-2 text-primary"></i>Data Lengkap Mahrom</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">

                        {{-- Identitas Section --}}
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2">
                                <i class="ri-id-card-line me-1"></i>Identitas
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nama Lengkap</label>
                            <div class="fw-semibold">{{ $mahrom->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">NIK</label>
                            <div class="fw-semibold">{{ $mahrom->id_number ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Hubungan</label>
                            <div class="fw-semibold">{{ $mahrom->relationship_text ?? ucfirst(str_replace('_', ' ', $mahrom->relationship)) }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nomor Telepon</label>
                            <div class="fw-semibold">{{ $mahrom->phone ?? '—' }}</div>
                        </div>

                        {{-- Alamat Section --}}
                        @if($mahrom->address)
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2 mt-3">
                                <i class="ri-home-4-line me-1"></i>Alamat
                            </h6>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small">Alamat Lengkap</label>
                            <div class="fw-semibold">{{ $mahrom->address }}</div>
                        </div>
                        @endif

                        {{-- Student Relation Section --}}
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2 mt-3">
                                <i class="ri-user-follow-line me-1"></i>Data Santri
                            </h6>
                        </div>
                        @if(isset($student))
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nama Santri</label>
                            <div class="fw-semibold">
                                <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                                   class="text-decoration-none">
                                    {{ $student->name }}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">NISN</label>
                            <div class="fw-semibold">{{ $student->nisn ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Jenis Kelamin</label>
                            <div class="fw-semibold">
                                <span class="badge bg-{{ $student->gender === 'L' ? 'primary' : 'danger' }}-subtle text-{{ $student->gender === 'L' ? 'primary' : 'danger' }}">
                                    {{ $student->gender_text ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Asrama</label>
                            <div class="fw-semibold">{{ $student->dormitory?->name ?? '—' }}</div>
                        </div>
                        @endif

                        {{-- Status Section --}}
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2 mt-3">
                                <i class="ri-settings-3-line me-1"></i>Status
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Mahrom Utama</label>
                            <div>
                                @if($mahrom->is_primary)
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="ri-star-line me-1"></i>Ya — Utama
                                    </span>
                                    <div class="text-muted small mt-1">Mahrom utama menerima semua informasi seputar Santi.</div>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Bukan</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Status Aktif</label>
                            <div>
                                @if($mahrom->is_active)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="ri-checkbox-circle-line me-1"></i>Aktif — Dapat menjenguk
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="ri-close-circle-line me-1"></i>Nonaktif — Tidak dapat menjenguk
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Notes Section --}}
                        @if($mahrom->notes)
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2 mt-3">
                                <i class="ri-sticky-note-line me-1"></i>Catatan
                            </h6>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded">
                                <div class="fw-semibold">{{ $mahrom->notes }}</div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>

                {{-- Bottom Action Bar --}}
                <div class="card-footer bg-transparent border-top">
                    <div class="d-flex gap-2 justify-content-between align-items-center flex-wrap">
                        <a href="{{ route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                           class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.students.mahroms.edit', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $mahrom->id]) }}"
                               class="btn btn-success">
                                <i class="ri-edit-box-line me-1"></i> Edit Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Visitor History Card (if any) --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-history-line me-2 text-primary"></i>Riwayat Kunjungan</h5>
                </div>
                <div class="card-body">
                    @if(isset($visitHistory) && $visitHistory->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th>Tanggal</th>
                                        <th>Tujuan</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Check-in</th>
                                        <th class="text-center">Check-out</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($visitHistory as $vh)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>{{ $vh->expected_arrival?->format('d M Y') ?? '—' }}</td>
                                            <td>{{ $vh->purpose_text ?? '—' }}</td>
                                            <td class="text-center">{!! $vh->status_badge !!}</td>
                                            <td class="text-center">
                                                {{ $vh->actual_arrival_at?->format('H:i') ?? '—' }}
                                            </td>
                                            <td class="text-center">
                                                {{ $vh->actual_departure_at?->format('H:i') ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ri-user-location-line text-muted" style="font-size:3rem;"></i>
                            <p class="text-muted mt-2 mb-0">Belum ada riwayat kunjungan.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection