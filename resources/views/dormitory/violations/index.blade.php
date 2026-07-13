@extends('layouts.master')
@section('title') Pelanggaran Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Pelanggaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-dark-subtle">
                                <i class="ri-error-warning-line fs-24 text-dark"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Pelanggaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-info-subtle">
                                <i class="ri-information-line fs-24 text-info"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Ringan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['ringan'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-warning-subtle">
                                <i class="ri-alert-line fs-24 text-warning"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Sedang</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['sedang'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-danger-subtle">
                                <i class="ri-goblet-line fs-24 text-danger"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Berat</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['berat'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Pelanggaran</h5>
                            <p class="text-muted mb-0">{{ $dormitory->name }} — Tahun Ajaran {{ $activeYear->name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.violations.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Catat Pelanggaran
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama pelanggaran / jenis..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="violation_category" class="form-control">
                                <option value="">Semua Kategori</option>
                                <option value="ringan"  {{ request('violation_category') == 'ringan' ? 'selected' : '' }}>Ringan</option>
                                <option value="sedang"  {{ request('violation_category') == 'sedang' ? 'selected' : '' }}>Sedang</option>
                                <option value="berat"   {{ request('violation_category') == 'berat' ? 'selected' : '' }}>Berat</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Dari</label>
                            <input type="date" name="start_date" id="filter_start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Sampai</label>
                            <input type="date" name="end_date" id="filter_end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line"></i> Filter</button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Kamar</th>
                                    <th>Kategori</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th class="text-center">Poin</th>
                                    <th>Tanggal</th>
                                    <th>Tindakan</th>
                                    <th>Notifikasi Wali</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($violations as $i => $v)
                                    <tr>
                                        <td class="text-center">{{ $violations->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $v->student?->name ?? '—' }}</div>
                                            @if($v->student?->nisn)
                                                <div class="text-muted small">NISN: {{ $v->student->nisn }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($v->room)
                                                {{ $v->room->name }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($v->violation_category === 'ringan')
                                                <span class="badge bg-info-subtle text-info">Ringan</span>
                                            @elseif($v->violation_category === 'sedang')
                                                <span class="badge bg-warning-subtle text-warning">Sedang</span>
                                            @elseif($v->violation_category === 'berat')
                                                <span class="badge bg-danger-subtle text-danger">Berat</span>
                                            @else
                                                <span class="badge bg-secondary-subtle">{{ $v->violation_category }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $v->violation_type ?: '—' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-danger">{{ $v->points }}</span>
                                        </td>
                                        <td>
                                            @if($v->violation_date)
                                                {{ $v->violation_date->format('d/m/Y') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ Str::limit($v->action_taken, 35) }}</span>
                                        </td>
                                        <td>
                                            @if($v->parent_notified_at)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>
                                                    Terkirim
                                                    <br><small class="text-muted">{{ $v->parent_notified_at->format('d/m/Y H:i') }}</small>
                                                </span>
                                            @else
                                                <form method="POST"
                                                      action="{{ route('user.asrama.violations.notify', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'violationUuid' => $v->id]) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning"
                                                            onclick="return confirm('Kirim notifikasi ke wali murid?')"
                                                            title="Kirim Notifikasi Wali">
                                                        <i class="ri-notification-3-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.violations.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'violationUuid' => $v->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-5">
                                            <i class="ri-checkbox-indeterminate-line fs-1 d-block mb-2 text-muted"></i>
                                            Belum ada data pelanggaran.
                                            <br>
                                            <a href="{{ route('user.asrama.violations.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-sm btn-primary mt-2">
                                                <i class="ri-add-line me-1"></i> Catat Pelanggaran Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">Menampilkan {{ $violations->firstItem() ?? 0 }} - {{ $violations->lastItem() ?? 0 }} dari {{ $violations->total() }} data</div>
                        <div>{{ $violations->withQueryString()->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[method="GET"]');
    const startInput = document.getElementById('filter_start_date');
    const endInput = document.getElementById('filter_end_date');

    if (!form || !startInput || !endInput) return;

    // Auto-update end_date min when start_date changes
    startInput.addEventListener('change', function () {
        if (this.value) {
            endInput.min = this.value;
            if (endInput.value && endInput.value < this.value) {
                endInput.value = '';
                Toastify({ text: 'Tanggal akhir diubah agar tidak lebih kecil dari tanggal mulai.', duration: 3000, gravity: 'top', position: 'right', backgroundColor: '#ffc107', stopOnFocus: true }).showToast();
            }
        } else {
            endInput.removeAttribute('min');
        }
    });

    // Validate on submit
    form.addEventListener('submit', function (e) {
        if (startInput.value && endInput.value && endInput.value < startInput.value) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Tanggal tidak valid', text: 'Tanggal akhir tidak boleh lebih kecil dari tanggal mulai.', confirmButtonColor: '#405189' });
        }
    });

    // Initialize min on load
    if (startInput.value) {
        endInput.min = startInput.value;
    }
});
</script>
@endpush