@extends('layouts.master')
@section('title') Pelanggaran Santri — Sistem @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sistem @endslot
        @slot('title') Pelanggaran Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100 border-start border-dark">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-dark-subtle rounded fs-2"><i class="ri-error-warning-line fs-24 text-dark"></i></span>
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
            <div class="card card-animate h-100 border-start border-info">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-information-line fs-24 text-info"></i></span>
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
            <div class="card card-animate h-100 border-start border-warning">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-alert-line fs-24 text-warning"></i></span>
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
            <div class="card card-animate h-100 border-start border-danger">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-goblet-line fs-24 text-danger"></i></span>
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
                            <h5 class="card-title mb-0">Daftar Semua Pelanggaran</h5>
                            <p class="text-muted mb-0">Tahun Ajaran {{ $activeYear?->name ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama / asrama ..."
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
                        <div class="col-md-3">
                            <select name="dormitory_id" class="form-control">
                                <option value="">Semua Asrama</option>
                                @foreach($dormitories as $d)
                                    <option value="{{ $d->id }}" {{ request('dormitory_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('violations.index') }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Asrama</th>
                                    <th>Kamar</th>
                                    <th>Kategori</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th class="text-center">Poin</th>
                                    <th>Tanggal</th>
                                    <th>Tindakan</th>
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
                                            @if($v->dormitory)
                                                <a href="{{ route('user.asrama.show', ['userId' => auth()->id(), 'asramaUuid' => $v->dormitory_id]) }}"
                                                   class="text-primary">{{ $v->dormitory->name }}</a>
                                            @else
                                                <span class="text-muted">—</span>
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
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon> <br>
                                            Belum ada data pelanggaran.
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
    if (!form) return;
    form.addEventListener('submit', function (e) {
        const search = form.querySelector('input[name="search"]').value.trim();
        const cat    = form.querySelector('select[name="violation_category"]').value;
        const dorm   = form.querySelector('select[name="dormitory_id"]').value;
        if (!search && !cat && !dorm) return; // let Laravel handle empty filters
    });
});
</script>
@endpush
