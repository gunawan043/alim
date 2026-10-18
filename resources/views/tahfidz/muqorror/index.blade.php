@extends('layouts.master')
@section('title', 'Muqorror Tahfizh')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')

    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Master @endslot @slot('title') Muqorror Tahfizh @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Total Muqorror', $stats['total'], 'ri-book-open-line', 'primary', 'Master pada filter ini'],
            ['Aktif', $stats['active'], 'ri-checkbox-circle-line', 'success', 'Berlaku untuk sekolah'],
            ['Bulan Tersusun', $stats['months'], 'ri-calendar-line', 'info', 'Total rincian bulanan'],
            ['Sekolah Spesifik', $stats['schools'], 'ri-building-line', 'warning', 'Sisanya master departemen'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                    <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h3></div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>{{ $note }}</p>
            </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-book-open-line text-primary me-1"></i>Muqorror Tahfizh</h5><p class="text-muted mb-0 small">Target hafalan bulanan per paket, jenjang, dan semester.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId]) }}" class="btn btn-soft-info"><i class="ri-file-list-3-line me-1"></i>RPP Tahfizh</a>
                @if($canManage)<a href="{{ route('user.tahfidz.muqorror.create', ['userId' => $userId]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Susun Muqorror</a>@endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small">Semester</label><select name="semester" class="form-select" onchange="this.form.submit()"><option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option><option value="genap" @selected($semester === 'genap')>Genap</option></select></div>
                <div class="col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach(['sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA'] as $key => $label)<option value="{{ $key }}" @selected(request('jenjang') === $key)>{{ $label }}</option>@endforeach</select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Judul</th><th>Paket</th><th>Jenjang / Kelas</th><th>Semester</th><th class="text-center">Bulan</th><th>Sekolah</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td><div class="fw-medium">{{ $plan->title }}</div><div class="text-muted small">Dibuat {{ $plan->created_at?->format('d M Y') }}</div></td>
                            <td>{{ $plan->package?->name ?? '—' }}</td>
                            <td>{{ strtoupper($plan->jenjang ?? '—') }} {{ $plan->gradeLevel?->name ? '· '.$plan->gradeLevel->name : '' }}</td>
                            <td>{{ ucfirst($plan->semester) }} · {{ $plan->academicYear?->name }}</td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $plan->months_count }} bulan</span></td>
                            <td>{{ $plan->school?->name ?? 'Departemen / semua sekolah' }}</td>
                            <td class="text-end">
                                <a href="{{ route('user.tahfidz.muqorror.show', ['userId' => $userId, 'id' => $plan->id]) }}" class="btn btn-sm btn-outline-primary" title="Lihat"><i class="ri-eye-line"></i></a>
                                <a href="{{ route('user.tahfidz.muqorror.print', ['userId' => $userId, 'id' => $plan->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="Cetak"><i class="ri-printer-line"></i></a>
                                @if($canManage)
                                    <a href="{{ route('user.tahfidz.muqorror.edit', ['userId' => $userId, 'id' => $plan->id]) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri-edit-line"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-muqorror" title="Hapus"
                                            data-action="{{ route('user.tahfidz.muqorror.destroy', ['userId' => $userId, 'id' => $plan->id]) }}"
                                            data-name="{{ $plan->title }}"><i class="ri-delete-bin-line"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-book-open-line fs-3 d-block mb-2"></i>Belum ada muqorror. Susun master pertama dari Departemen Tahfidz.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <form id="deleteMuqorrorForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="deleteMuqorrorModal" title="Hapus Muqorror?" message="Muqorror <strong id='deleteMuqorrorName'>ini</strong> beserta rincian bulanannya akan dihapus."
            submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteMuqorrorForm" />
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-muqorror').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteMuqorrorForm').action = this.dataset.action;
                document.getElementById('deleteMuqorrorName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteMuqorrorModal')).show();
            });
        });
    });
</script>
@endpush
