@extends('layouts.master')
@section('title', 'RPP Tahfizh & Iqro')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Tahfidz @endslot
        @slot('li_2') Master @endslot
        @slot('title') RPP Tahfizh & Iqro’ @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-file-list-3-line text-primary me-1"></i>RPP Tahfizh & Iqro’</h5><p class="text-muted mb-0 small">Rencana pembelajaran per kelas, semester, dan bulan KBM.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}" class="btn btn-soft-info"><i class="ri-book-open-line me-1"></i>Muqorror</a>
                <a href="{{ route('user.tahfidz.rpp.create', ['userId' => $userId]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Susun RPP</a>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small">Jenis</label><select name="type" class="form-select" onchange="this.form.submit()"><option value="">Semua</option><option value="tahfizh" @selected(request('type')==='tahfizh')>Tahfizh</option><option value="iqro" @selected(request('type')==='iqro')>Iqro’</option></select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                                <a href="{{ route('user.tahfidz.rpp.show', ['userId' => $userId, 'id' => $plan->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a>
                                <a href="{{ route('user.tahfidz.rpp.print', ['userId' => $userId, 'id' => $plan->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="ri-printer-line"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada RPP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
