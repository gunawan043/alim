@extends('layouts.master')
@section('title', 'Tindak Lanjut Tahfidz')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Monitoring @endslot @slot('title') Tindak Lanjut Tahfidz @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Berjalan', $stats['open'], 'ri-task-line', 'primary', 'Belum selesai'],
            ['Terlambat', $stats['overdue'], 'ri-alarm-warning-line', 'danger', 'Melewati batas waktu'],
            ['Selesai Bulan Ini', $stats['done'], 'ri-check-double-line', 'success', 'Tuntas'],
            ['Total', $stats['total'], 'ri-list-check-2', 'info', 'Sesuai filter'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                    <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h3></div>
                </div>
                <p class="text-muted mb-0 stat-label">{{ $note }}</p>
            </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-task-line text-primary me-1"></i>Tindak Lanjut Santri</h5><p class="text-muted mb-0 small">Rencana bimbingan, panggilan wali, dan perbaikan target.</p></div>
            <a href="{{ route('user.tahfidz.interventions.index', array_merge(request()->query(), ['userId'=>$userId,'export'=>1])) }}" class="btn btn-soft-success"><i class="ri-download-2-line me-1"></i>Export Excel</a>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small">Halaqah</label><select name="group_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(request('group_id')===$group->id)>{{ $group->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small">Jenis</label><select name="type" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach(['panggilan_wali'=>'Panggilan Wali','bimbingan_tambahan'=>'Bimbingan Tambahan','target_ulang'=>'Penyesuaian Target','motivasi'=>'Motivasi','lainnya'=>'Lainnya'] as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select" onchange="this.form.submit()"><option value="">Semua</option><option value="open" @selected(request('status')==='open')>Berjalan</option><option value="selesai" @selected(request('status')==='selesai')>Selesai</option><option value="batal" @selected(request('status')==='batal')>Dibatalkan</option></select></div>
                <div class="col-md-2"><div class="form-check mt-3"><input type="checkbox" name="overdue" value="1" class="form-check-input" id="overdueFilter" @checked(request()->boolean('overdue')) onchange="this.form.submit()"><label class="form-check-label small" for="overdueFilter">Terlambat</label></div></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Santri</th><th>Halaqah</th><th>Jenis</th><th>Catatan</th><th>Batas Waktu</th><th>PJ</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($interventions as $intervention)
                        @php $overdue = $intervention->status === 'open' && $intervention->due_date && $intervention->due_date->lt(today()); @endphp
                        <tr class="{{ $overdue ? 'table-warning' : '' }}">
                            <td><a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$intervention->student_id]) }}" class="fw-medium text-decoration-none">{{ $intervention->student?->name }}</a><div class="text-muted small">{{ $intervention->student?->nis }}</div></td>
                            <td>{{ $intervention->group?->name }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $intervention->type_label }}</span></td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($intervention->notes, 80) }}</td>
                            <td>{{ $intervention->due_date?->format('d/m/Y') ?? '—' }}@if($overdue)<div class="text-danger small">Terlambat</div>@endif</td>
                            <td class="small">{{ $intervention->assignedTo?->name ?? '—' }}</td>
                            <td><span class="badge {{ $intervention->status==='open' ? ($overdue ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') : 'bg-success-subtle text-success' }}">{{ $intervention->status_label }}</span></td>
                            <td class="text-end">@if($intervention->status==='open')<form method="POST" action="{{ route('user.tahfidz.interventions.complete', ['userId'=>$userId,'interventionId'=>$intervention->id]) }}">@csrf<button class="btn btn-sm btn-outline-success" title="Tandai selesai"><i class="ri-check-line"></i></button></form>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ri-task-line fs-3 d-block mb-2"></i>Belum ada tindak lanjut.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($interventions->hasPages())<div class="card-body border-top">{{ $interventions->links() }}</div>@endif
    </div>
@endsection
