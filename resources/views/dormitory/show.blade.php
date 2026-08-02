@extends('layouts.master')
@section('title') {{ $dormitory->name }} @endsection
@section('css')
<style>
.card-animate { transition: all 0.3s ease; }
.card-animate:hover { transform: translateY(-2px); }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('title') {{ $dormitory->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Quick Actions --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="text-decoration-none">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-team-line text-primary"></i></span></div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Penghuni</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total_residents'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="text-decoration-none">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-info"></i></span></div>
                            <div>
                                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Occupancy Rate</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $stats['occupancy_rate'] }}<small class="fw-normal text-muted">%</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-hotel-line text-warning"></i></span></div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kamar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total_rooms'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-secondary-subtle rounded fs-2"><i class="ri-layout-column-line text-secondary"></i></span></div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Gedung</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total_wings'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nav Tabs --}}
    <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#info"><i class="ri-information-line me-1"></i> Informasi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#penghuni"><i class="ri-team-line me-1"></i> Penghuni</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#kamar"><i class="ri-hotel-line me-1"></i> Kamar & Gedung</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#izin"><i class="ri-file-list-3-line me-1"></i> Perizinan</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#pelanggaran"><i class="ri-spam-line me-1"></i> Pelanggaran</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#informasi"><i class="ri-megaphone-line me-1"></i> Informasi</a>
        </li>
    </ul>

    <div class="tab-content">
        {{-- TAB: INFO --}}
        <div class="tab-pane active" id="info">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-bottom-dashed">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="ri-hotel-line me-1"></i> Informasi Asrama</h5>
                                <a href="{{ route('user.asrama.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ri-edit-line"></i> Edit
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Kode Asrama</span>
                                        <span class="detail-value">{{ $dormitory->code }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Nama Asrama</span>
                                        <span class="detail-value fw-semibold">{{ $dormitory->name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Gender</span>
                                        <span class="badge bg-{{ $dormitory->gender === 'putra' ? 'primary' : 'danger' }}">
                                            {{ $dormitory->gender === 'putra' ? 'Putra' : 'Putri' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Status</span>
                                        <span class="badge bg-{{ $dormitory->is_active ? 'success' : 'secondary' }}">
                                            {{ $dormitory->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Kapasitas</span>
                                        <span class="detail-value">{{ number_format($dormitory->capacity) }} orang</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Tahun Ajaran</span>
                                        <span class="detail-value">{{ $activeYear?->name ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Alamat</span>
                                        <span class="detail-value">{{ $dormitory->address ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Telepon</span>
                                        <span class="detail-value">{{ $dormitory->phone ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Kepala Asrama</span>
                                        <span class="detail-value">{{ $dormitory->head?->name ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="detail-row">
                                        <span class="detail-label">Catatan</span>
                                        <span class="detail-value">{{ $dormitory->notes ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header border-bottom-dashed">
                            <h6 class="mb-0"><i class="ri-links-line me-1"></i> Menu Cepat</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="d-grid gap-2">
                                <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                                    <i class="ri-team-line me-2 text-primary"></i> Kelola Penghuni
                                </a>
                                <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                                    <i class="ri-user-add-line me-2 text-success"></i> Tempatkan Santri
                                </a>
                                <a href="{{ route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                                    <i class="ri-checkbox-circle-line me-2 text-info"></i> Catat Absensi
                                </a>
                                <a href="{{ route('user.asrama.permits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                                    <i class="ri-file-list-3-line me-2 text-warning"></i> Ajukan Izin
                                </a>
                                <a href="{{ route('user.asrama.violations.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                                    <i class="ri-spam-line me-2 text-danger"></i> Catat Pelanggaran
                                </a>
                                <a href="{{ route('user.asrama.posts.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                                    <i class="ri-megaphone-line me-2 text-secondary"></i> Posting Informasi
                                </a>
                                <a href="{{ route('user.asrama.visits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light text-start">
                                    <i class="ri-user-follow-line me-2 text-dark"></i> Ajukan Kunjungan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: PENGHUNI --}}
        <div class="tab-pane" id="penghuni">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ri-team-line me-1"></i> Penghuni Aktif ({{ $dormitory->residents()->where('is_active', true)->count() }} orang)</h5>
                        <a href="{{ route('user.asrama.residents.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-sm btn-primary">
                            <i class="ri-add-line"></i> Tempatkan
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Nama Santri</th>
                                    <th>Kelas</th>
                                    <th>Kamar</th>
                                    <th>Bed</th>
                                    <th>Tanggal Ditempatkan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $activeResidents = $dormitory->residents()->where('is_active', true)->with('student', 'room')->get(); @endphp
                                @forelse($activeResidents as $i => $r)
                                <tr>
                                    <td class="text-center">{{ $i+1 }}</td>
                                    <td>{{ $r->student?->name ?? '—' }}</td>
                                    <td>{{ $r->student?->currentClassHistory?->studyGroup?->name ?? '—' }}</td>
                                    <td>{{ $r->room?->code ?? '—' }}</td>
                                    <td class="text-center">{{ $r->bed_number ?? '—' }}</td>
                                    <td>{{ $r->check_in_date?->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('user.asrama.residents.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $r->id]) }}" class="btn btn-sm btn-light">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="ri-team-line fs-1 d-block mb-2"></i>
                                        Belum ada penghuni aktif.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: KAMAR & GEDUNG --}}
        <div class="tab-pane" id="kamar">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="mb-0"><i class="ri-hotel-line me-1"></i> Denah Asrama</h5>
                </div>
                <div class="card-body">
                    @if($dormitory->wings->count())
                        @foreach($dormitory->wings as $wing)
                        <div class="mb-4">
                            <h6 class="text-uppercase fw-semibold mb-3">
                                <i class="ri-layout-column-line me-1"></i> Gedung {{ $wing->name }} ({{ $wing->code }})
                                <span class="badge bg-secondary ms-2">{{ $wing->rooms->count() }} kamar</span>
                            </h6>
                            <div class="row g-2">
                                @forelse($wing->rooms as $room)
                                @php $occ = $room->residents->filter(fn($r) => $r->is_active)->count(); @endphp
                                <div class="col-md-3 col-6">
                                    <div class="card border {{ $occ >= $room->capacity ? 'border-danger' : 'border-success' }}">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-bold">{{ $room->code }}</span>
                                                    <br><small class="text-muted">{{ $room->name ?? $room->room_type }}</small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-{{ $occ >= $room->capacity ? 'danger' : 'success' }}">
                                                        {{ $occ }}/{{ $room->capacity }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="col-12 text-muted">Tidak ada kamar di gedung ini.</div>
                                @endforelse
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center py-4">
                            <i class="ri-hotel-line fs-1 d-block mb-2"></i>
                            Belum ada kamar & gedung. Tambahkan di form edit asrama.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- TAB: PERIZINAN --}}
        <div class="tab-pane" id="izin">
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('user.asrama.permits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-sm btn-primary">
                    <i class="ri-add-line"></i> Ajukan Izin
                </a>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Santri</th>
                                    <th>Jenis Izin</th>
                                    <th>Tujuan</th>
                                    <th>Penjemput</th>
                                    <th>Berangkat</th>
                                    <th>Kembali</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dormitory->permits()->latest()->limit(10)->get() as $i => $p)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $p->student?->name ?? '—' }}</td>
                                    <td><span class="badge bg-secondary">{{ $p->permit_type_text }}</span></td>
                                    <td>{{ $p->destination ?? '—' }}</td>
                                    <td>
                                        @if($p->companion_is_mahrom)
                                            <span class="text-success"><i class="ri-shield-check-line"></i> {{ $p->companion_name }}</span>
                                        @else
                                            {{ $p->companion_name ?? '—' }}
                                        @endif
                                    </td>
                                    <td>{{ $p->departure_datetime?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($p->actual_return_datetime)
                                            <span class="text-success">{{ $p->actual_return_datetime->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span class="text-muted">{{ $p->expected_return_datetime?->format('d/m/Y H:i') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $p->status === 'approved' ? 'success' : ($p->status === 'pending' ? 'warning' : 'secondary') }}">
                                            {{ $p->status_text }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada data izin.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: PELANGGARAN --}}
        <div class="tab-pane" id="pelanggaran">
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('user.asrama.violations.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-sm btn-danger">
                    <i class="ri-add-line"></i> Catat Pelanggaran
                </a>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Santri</th>
                                    <th>Kategori</th>
                                    <th>Jenis</th>
                                    <th>Poin</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                    <th>Notifikasi Wali</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dormitory->violations()->latest()->limit(10)->get() as $i => $v)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $v->student?->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $v->violation_category === 'berat' ? 'danger' : ($v->violation_category === 'sedang' ? 'warning' : 'info') }}">{{ ucfirst($v->violation_category) }}</span></td>
                                    <td>{{ $v->violation_type }}</td>
                                    <td class="text-center fw-bold">{{ $v->points }}</td>
                                    <td>{{ $v->violation_date?->format('d/m/Y') }}</td>
                                    <td>
                                        @if($v->action_taken)
                                            <span class="text-muted small">{{ Str::limit($v->action_taken, 30) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($v->parent_notified_at)
                                            <span class="badge bg-success"><i class="ri-check-line"></i> Terkirim</span>
                                        @else
                                            <form action="{{ route('user.asrama.violations.notify', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'violationUuid' => $v->id]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Kirim WA</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada data pelanggaran.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: INFORMASI --}}
        <div class="tab-pane" id="informasi">
            <div class="d-flex justify-content-end mb-3 gap-2">
                <a href="{{ route('user.asrama.broadcasts.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-sm btn-dark">
                    <i class="ri-broadcast-line"></i> Broadcast Darurat
                </a>
                <a href="{{ route('user.asrama.posts.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-sm btn-primary">
                    <i class="ri-add-line"></i> Posting Informasi
                </a>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Visibilitas</th>
                                    <th>Butuh Respon</th>
                                    <th>Tanggal</th>
                                    <th>Penulis</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dormitory->posts()->latest()->limit(10)->get() as $i => $p)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>
                                        @if($p->is_pinned)
                                            <i class="ri-pinned-line text-warning"></i>
                                        @endif
                                        {{ $p->title }}
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $p->category_text }}</span></td>
                                    <td>{{ $p->visibility_text }}</td>
                                    <td class="text-center">
                                        @if($p->needs_response)
                                            <i class="ri-checkbox-circle-line text-success"></i>
                                        @endif
                                    </td>
                                    <td>{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $p->creator?->name ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('user.asrama.posts.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'postUuid' => $p->id]) }}" class="btn btn-sm btn-light">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada informasi.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .detail-row { display: flex; gap: 1rem; border-bottom: 1px solid var(--bs-border-color); padding: 8px 0; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { font-weight: 600; color: var(--bs-secondary-color); min-width: 140px; flex-shrink: 0; }
    .detail-value { color: var(--bs-body-color); }
    </style>
@endsection
