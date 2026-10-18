@extends('layouts.master')
@section('title', $group->name)
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah</a> @endslot @slot('li_2') Detail @endslot @slot('title') {{ $group->name }} @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
            <div><h5 class="card-title mb-1">{{ $group->name }}</h5><div class="text-muted small">{{ $group->school?->name }} · {{ $group->studyGroup?->name ?? 'Tanpa kelas' }} · {{ strtoupper($group->gender) }} · {{ $group->academicYear?->name }}</div><div class="small mt-1">Guru: <strong>{{ $group->teacher?->name ?? '—' }}</strong>@if($group->coordinator) · Koordinator: {{ $group->coordinator->name }}@endif</div></div>
            <div class="d-flex gap-2"><a href="{{ route('user.tahfidz.setoran.create', ['userId' => $userId, 'group_id' => $group->id]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Input Setoran</a>@if($canManage)<a href="{{ route('user.tahfidz.halaqah.edit', ['userId' => $userId, 'id' => $group->id]) }}" class="btn btn-warning"><i class="ri-edit-line me-1"></i>Edit</a>@endif<a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}" class="btn btn-light">Kembali</a></div>
        </div>
        <div class="card-body">
            @if($group->notes)<p class="text-muted">{{ $group->notes }}</p>@endif
            <div class="row g-3">
                <div class="col-lg-7">
                    <h6>Anggota Halaqah <span class="badge bg-primary-subtle text-primary">{{ $group->members->where('status','aktif')->count() }}</span></h6>
                    <div class="table-responsive"><table class="table table-sm align-middle"><thead class="table-light"><tr><th>Nama</th><th>NIS</th><th>Status</th>@if($canManage)<th></th>@endif</tr></thead><tbody>
                        @forelse($group->members as $member)
                            <tr><td>{{ $member->student?->name ?? '—' }}</td><td>{{ $member->student?->nis ?? '—' }}</td><td><span class="badge {{ $member->status==='aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $member->status }}</span></td>
                            @if($canManage && $member->status==='aktif')<td class="text-end"><form method="POST" action="{{ route('user.tahfidz.halaqah.members.remove', ['userId'=>$userId,'id'=>$group->id,'memberId'=>$member->id]) }}" onsubmit="return confirm('Keluarkan santri ini dari halaqah?')">@csrf<button class="btn btn-sm btn-outline-danger"><i class="ri-user-unfollow-line"></i></button></form></td>@endif
                            </tr>
                        @empty<tr><td colspan="{{ $canManage ? 4 : 3 }}" class="text-center text-muted py-3">Belum ada anggota.</td></tr>@endforelse
                    </tbody></table></div>
                    @if($canManage)
                        <form method="POST" action="{{ route('user.tahfidz.halaqah.members.store', ['userId'=>$userId,'id'=>$group->id]) }}" class="row g-2 align-items-end mt-2">@csrf
                            <div class="col-md-9"><label class="form-label small">Tambah Santri</label><select name="student_ids[]" class="form-select" multiple size="5">@foreach($availableStudents as $student)<option value="{{ $student->id }}">{{ $student->name }} ({{ $student->nis }})</option>@endforeach</select><div class="form-text">Ctrl/Cmd untuk memilih beberapa santri.</div></div>
                            <div class="col-md-3 d-grid"><button class="btn btn-outline-success"><i class="ri-user-add-line me-1"></i>Tambah</button></div>
                        </form>
                    @endif
                </div>
                <div class="col-lg-5">
                    <h6>Setoran Terakhir</h6>
                    <div class="table-responsive"><table class="table table-sm align-middle"><thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Jenis</th><th>Nilai</th><th>Verifikasi</th></tr></thead><tbody>
                        @forelse($group->setorans as $setoran)
                            <tr><td>{{ $setoran->setoran_date?->format('d/m') }}</td><td>{{ $setoran->student?->name }}</td><td>{{ $setoran->setoran_type }}</td><td>{{ $setoran->nilai_setoran ?? '—' }}</td><td><span class="badge {{ $setoran->verification_status==='verified' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $setoran->verification_status }}</span></td></tr>
                        @empty<tr><td colspan="5" class="text-center text-muted py-3">Belum ada setoran.</td></tr>@endforelse
                    </tbody></table></div>
                    <a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-sm btn-outline-primary">Lihat semua setoran</a>
                </div>
            </div>
        </div>
    </div>
@endsection
