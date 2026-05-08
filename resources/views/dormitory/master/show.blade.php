@extends('layouts.master')
@section('title') Detail Asrama @endsection
@section('css')
<style>
.asrama-badge-lg { font-size: 0.9rem; padding: 0.4em 0.8em; }
.doc-preview { border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; background: #f8f9fa; min-height: 140px; display: flex; align-items: center; justify-content: center; }
.doc-preview img { max-height: 120px; object-fit: contain; }
.doc-preview-placeholder { color: #adb5bd; font-size: 2rem; }
.info-card-profile { border: 1px solid #f0f0f0; border-radius: 10px; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pengelolaan @endslot
        @slot('li_2') <a href="{{ route('user.dormitory-master.index', ['userId' => $userId]) }}">Asrama</a> @endslot
        @slot('title') {{ $dormitory->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header: Logo + Name + Badges --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-auto">
                    @if($dormitory->logo_path)
                        <img src="{{ asset('storage/' . $dormitory->logo_path) }}" alt="{{ $dormitory->name }}" class="rounded-circle" width="90" height="90" style="object-fit:cover;border:3px solid #dee2e6">
                    @else
                        <div class="avatar-xl bg-{{ $dormitory->gender === 'putra' ? 'primary' : 'danger' }}-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:90px;height:90px">
                            <span class="fs-2 fw-bold text-{{ $dormitory->gender === 'putra' ? 'primary' : 'danger' }}">
                                {{ strtoupper(substr($dormitory->name, 0, 2)) }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="col">
                    <div class="d-flex align-items-start flex-wrap gap-2 mb-2">
                        <h3 class="mb-0 me-2">{{ $dormitory->name }}</h3>
                        @if(!$dormitory->is_active)
                            <span class="badge bg-danger-subtle text-danger asrama-badge-lg">Nonaktif</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-{{ $dormitory->gender === 'putra' ? 'primary' : 'danger' }}-subtle text-{{ $dormitory->gender === 'putra' ? 'primary' : 'danger' }} asrama-badge-lg">
                            <i class="ri-user-{{ $dormitory->gender === 'putra' ? 'male' : 'female' }}-line me-1"></i>{{ $dormitory->gender === 'putra' ? 'Putra' : 'Putri' }}
                        </span>
                        @if($dormitory->workUnit)
                            <span class="badge bg-secondary-subtle text-secondary asrama-badge-lg">
                                <i class="ri-hotel-line me-1"></i>{{ str_replace('Pengasuhan', 'Asrama', $dormitory->workUnit->name) }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-2 text-muted small">
                        @if($dormitory->code)
                            <span class="me-3"><i class="ri-shield-star-line me-1"></i>Kode: {{ $dormitory->code }}</span>
                        @endif
                        @if($dormitory->head?->name)
                            <span><i class="ri-user-star-line me-1"></i>Kepala: {{ $dormitory->head->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    @if(auth()->user()->hasRole(['Super Admin', 'Administrator']))
                    <a href="{{ route('user.dormitory-master.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-soft-secondary">
                        <i class="ri-pencil-line me-1"></i> Edit
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- LEFT COLUMN --}}
        <div class="col-lg-8">

            {{-- Stat Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border card-shadow h-100">
                        <div class="card-body py-3 text-center">
                            <div class="fs-3 fw-bold text-primary">{{ $stats['total_residents'] }}</div>
                            <div class="small text-muted">Penghuni Aktif</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border card-shadow h-100">
                        <div class="card-body py-3 text-center">
                            <div class="fs-3 fw-bold text-success">{{ $stats['occupancy_rate'] }}%</div>
                            <div class="small text-muted">Okupansi ({{ $stats['total_residents'] }}/{{ $stats['total_capacity'] }})</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border card-shadow h-100">
                        <div class="card-body py-3 text-center">
                            <div class="fs-3 fw-bold text-info">{{ $stats['total_rooms'] }}</div>
                            <div class="small text-muted">Kamar</div>
                        </div>
                    </div>
                </div>
            </div>

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
                                <th class="table-light">Unit Asrama</th>
                                <td>{{ str_replace('Pengasuhan', 'Asrama', $dormitory->workUnit?->name) ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Gender</th>
                                <td>{{ $dormitory->gender === 'putra' ? 'Putra' : 'Putri' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Kapasitas</th>
                                <td>{{ number_format($dormitory->capacity) }} orang</td>
                            </tr>
                            <tr>
                                <th class="table-light">Sekolah Terkait</th>
                                <td>{{ $dormitory->school?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Kepala Asrama</th>
                                <td>{{ $dormitory->head?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Status</th>
                                <td>
                                    @if($dormitory->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
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

            {{-- Kontak & Alamat --}}
            <div class="card info-card-profile">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-map-pin-line me-2 text-danger"></i>Kontak & Alamat</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="table-light w-40">Alamat</th>
                                <td>{{ $dormitory->address ?? '-' }}</td>
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

            {{-- Logo --}}
            <div class="card info-card-profile">
                <div class="card-header"><h5 class="mb-0"><i class="ri-image-line me-2 text-info"></i>Logo</h5></div>
                <div class="card-body text-center">
                    @if($dormitory->logo_path)
                        <img src="{{ asset('storage/' . $dormitory->logo_path) }}" alt="Logo" class="img-fluid rounded" style="max-height:160px">
                    @else
                        <div class="doc-preview mb-2">
                            <div class="doc-preview-placeholder"><i class="ri-image-add-line"></i></div>
                        </div>
                        <small class="text-muted">Logo belum diupload</small>
                    @endif
                </div>
            </div>

            {{-- Ringkasan --}}
            <div class="card info-card-profile">
                <div class="card-header"><h5 class="mb-0"><i class="ri-bar-chart-box-line me-2 text-success"></i>Ringkasan</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="text-muted">Total Gedung</th><td class="text-end fw-semibold">{{ $stats['total_wings'] }}</td></tr>
                        <tr><th class="text-muted">Total Kamar</th><td class="text-end fw-semibold">{{ $stats['total_rooms'] }}</td></tr>
                        <tr><th class="text-muted">Penghuni Aktif</th><td class="text-end fw-semibold">{{ $stats['total_residents'] }}</td></tr>
                        <tr><th class="text-muted">Kapasitas</th><td class="text-end fw-semibold">{{ number_format($stats['total_capacity']) }}</td></tr>
                        <tr><th class="text-muted">Okupansi</th>
                            <td class="text-end">
                                <span class="badge bg-{{ $stats['occupancy_rate'] >= 100 ? 'danger' : ($stats['occupancy_rate'] >= 80 ? 'warning' : 'success') }}">
                                    {{ $stats['occupancy_rate'] }}%
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('user.dormitory-master.index', ['userId' => $userId]) }}" class="btn btn-light">
            <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
        </a>
        @if(auth()->user()->hasRole(['Super Admin', 'Administrator']))
        <button class="btn btn-light text-danger float-end delete-asrama" data-id="{{ $dormitory->id }}" data-name="{{ $dormitory->name }}">
            <i class="ri-delete-bin-line me-1"></i> Hapus Asrama
        </button>
        @endif
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade zoomIn" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus Asrama?</h4>
                    <p class="text-muted">Asrama <strong id="deleteAsramaName"></strong> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteForm" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Ya, Hapus!</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-asrama').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('deleteAsramaName').textContent = this.dataset.name;
            document.getElementById('deleteForm').action = '/' + '{{ $userId }}' + '/dormitory-master/' + this.dataset.id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });
});
</script>
@endsection
