@extends('layouts.master')
@section('title', 'Halaqah Tahfizh')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Halaqah Tahfizh @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-team-line text-primary me-1"></i>Halaqah Tahfizh</h5><p class="text-muted mb-0 small">Satu kelas dapat memiliki beberapa halaqah.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId]) }}" class="btn btn-soft-info"><i class="ri-file-add-line me-1"></i>Setoran</a>
                @if($canManage)<a href="{{ route('user.tahfidz.halaqah.create', ['userId' => $userId]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Buat Halaqah</a>@endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                @if($schools->isNotEmpty())<div class="col-md-4"><label class="form-label small">Sekolah</label><select name="school_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($schools as $school)<option value="{{ $school->id }}" @selected(request('school_id')===$school->id)>{{ $school->name }}</option>@endforeach</select></div>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Halaqah</th><th>Sekolah</th><th>Kelas</th><th>Guru / Koordinator</th><th class="text-center">Anggota</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td><div class="fw-medium">{{ $group->name }}</div><div class="text-muted small">{{ strtoupper($group->gender) }} · {{ $group->code ?? '—' }}</div></td>
                            <td>{{ $group->school?->name ?? '—' }}</td>
                            <td>{{ $group->studyGroup?->name ?? '—' }}</td>
                            <td>{{ $group->teacher?->name ?? '—' }}<div class="text-muted small">{{ $group->coordinator?->name ? 'Koor: '.$group->coordinator->name : '' }}</div></td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $group->members_count }}</span></td>
                            <td>{!! $group->is_active ? '<span class="badge bg-success-subtle text-success">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>' !!}</td>
                            <td class="text-end"><a href="{{ route('user.tahfidz.halaqah.show', ['userId' => $userId, 'id' => $group->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada halaqah.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
