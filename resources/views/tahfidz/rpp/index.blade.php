@extends('layouts.master')
@section('title', 'RPP Tahfizh & Iqro')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')

    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Master @endslot @slot('title') RPP Tahfizh & Iqro’ @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Total RPP', $stats['total'], 'ri-file-list-3-line', 'primary', 'Rencana pembelajaran'],
            ['Tahfizh', $stats['tahfizh'], 'ri-book-read-line', 'success', 'RPP tahfizh'],
            ['Iqro’', $stats['iqro'], 'ri-book-open-line', 'info', 'RPP Iqro’'],
            ['Butir Pembelajaran', $stats['items'], 'ri-list-check-2', 'warning', 'Total butir pada filter'],
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
            <div><h5 class="card-title mb-0"><i class="ri-file-list-3-line text-primary me-1"></i>RPP Tahfizh & Iqro’</h5><p class="text-muted mb-0 small">Rencana pembelajaran per kelas, semester, dan bulan KBM.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}" class="btn btn-soft-info"><i class="ri-book-open-line me-1"></i>Muqorror</a>
                @if($canManage)<a href="{{ route('user.tahfidz.rpp.create', ['userId' => $userId]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Susun RPP</a>@endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small">Jenis</label><select name="type" class="form-select" onchange="this.form.submit()"><option value="">Semua</option><option value="tahfizh" @selected(request('type')==='tahfizh')>Tahfizh</option><option value="iqro" @selected(request('type')==='iqro')>Iqro’</option></select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Judul</th><th>Jenis</th><th>Kelas</th><th>Semester</th><th>Bulan KBM</th><th>Sekolah</th><th class="text-center">Butir</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td><div class="fw-medium">{{ $plan->title }}</div><div class="text-muted small">{{ $plan->created_at?->format('d M Y') }}</div></td>
                            <td><span class="badge {{ $plan->type === 'iqro' ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success' }}">{{ $plan->type === 'iqro' ? 'Iqro’' : 'Tahfizh' }}</span></td>
                            <td>{{ $plan->gradeLevel?->name ?? '—' }}</td>
                            <td>{{ ucfirst($plan->semester) }} · {{ $plan->academicYear?->name }}</td>
                            <td>{{ $plan->kbm_month ?? '—' }}</td>
                            <td>{{ $plan->school?->name ?? 'Departemen' }}</td>
                            <td class="text-center">{{ $plan->items_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('user.tahfidz.rpp.show', ['userId' => $userId, 'id' => $plan->id]) }}" class="btn btn-sm btn-outline-primary" title="Lihat"><i class="ri-eye-line"></i></a>
                                <a href="{{ route('user.tahfidz.rpp.print', ['userId' => $userId, 'id' => $plan->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="Cetak"><i class="ri-printer-line"></i></a>
                                @if($canManage)
                                    <a href="{{ route('user.tahfidz.rpp.edit', ['userId' => $userId, 'id' => $plan->id]) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri-edit-line"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-rpp" title="Hapus"
                                            data-action="{{ route('user.tahfidz.rpp.destroy', ['userId' => $userId, 'id' => $plan->id]) }}"
                                            data-name="{{ $plan->title }}"><i class="ri-delete-bin-line"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ri-file-list-3-line fs-3 d-block mb-2"></i>Belum ada RPP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <form id="deleteRppForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="deleteRppModal" title="Hapus RPP?" message="RPP <strong id='deleteRppName'>ini</strong> beserta butir pembelajarannya akan dihapus."
            submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteRppForm" />
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-rpp').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteRppForm').action = this.dataset.action;
                document.getElementById('deleteRppName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRppModal')).show();
            });
        });
    });
</script>
@endpush
