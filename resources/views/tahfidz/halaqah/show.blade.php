@extends('layouts.master')
@section('title', $group->name)
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $juzSelesai = $activeMembers->sum(fn ($member) => ($juzProgress[$member->student_id] ?? collect())->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count());
        $targetAverage = $recaps->pluck('pencapaian_target_persen')->filter(fn ($value) => $value !== null)->avg();
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah</a> @endslot @slot('li_2') Detail @endslot @slot('title') {{ $group->name }} @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
            <div><h5 class="card-title mb-1">{{ $group->name }}</h5><div class="text-muted small">{{ $group->school?->name }} · {{ $group->studyGroup?->name ?? 'Tanpa kelas' }} · {{ strtoupper($group->gender) }} · {{ $group->academicYear?->name }}</div><div class="small mt-1">Guru: <strong>{{ $group->teacher?->name ?? '—' }}</strong>@if($group->coordinator) · Koordinator: {{ $group->coordinator->name }}@endif</div></div>
            <div class="d-flex flex-wrap gap-2 tahfidz-page-actions">
                <a href="{{ route('user.tahfidz.setoran.create', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-primary"><i class="ri-add-line me-1"></i>Input Setoran</a>
                <a href="{{ route('user.tahfidz.progress.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-soft-info"><i class="ri-line-chart-line me-1"></i>Progres & Rekap</a>
                <a href="{{ route('user.tahfidz.targets.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-soft-warning"><i class="ri-focus-3-line me-1"></i>Target</a>
                @if($canManage)<a href="{{ route('user.tahfidz.halaqah.edit', ['userId'=>$userId,'id'=>$group->id]) }}" class="btn btn-warning"><i class="ri-edit-line me-1"></i>Edit</a>@endif
                <a href="{{ route('user.tahfidz.halaqah.index', ['userId'=>$userId]) }}" class="btn btn-light">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            @if($group->notes)<p class="text-muted small">{{ $group->notes }}</p>@endif
            <div class="row g-3">
                <div class="col-xl-3 col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Anggota Aktif</div><div class="fs-4 fw-bold">{{ $group->active_members_count }}</div></div></div>
                <div class="col-xl-3 col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Total Setoran</div><div class="fs-4 fw-bold">{{ $group->setorans_count }}</div></div></div>
                <div class="col-xl-3 col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Juz Selesai</div><div class="fs-4 fw-bold text-success">{{ $juzSelesai }}</div></div></div>
                <div class="col-xl-3 col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Rata Capaian Target</div><div class="fs-4 fw-bold">{{ $targetAverage !== null ? number_format((float)$targetAverage, 1).'%' : '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h6 class="card-title mb-0"><i class="ri-team-line text-primary me-1"></i>Anggota Halaqah</h6><span class="badge bg-primary-subtle text-primary">{{ $activeMembers->count() }}</span></div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0 tahfidz-table"><thead class="table-light"><tr><th>Nama</th><th>NIS</th><th>Progres</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($group->members as $member)
                        @php $rows = $juzProgress[$member->student_id] ?? collect(); $selesai = $rows->whereIn('status', ['selesai_ziyadah','sudah_tasmi'])->count(); $avg = $rows->whereNotNull('percentage')->avg('percentage'); @endphp
                        <tr><td class="fw-medium">{{ $member->student?->name ?? '—' }}</td><td>{{ $member->student?->nis ?? '—' }}</td><td><span class="badge bg-success-subtle text-success">{{ $selesai }} juz</span> <span class="text-muted small">{{ $avg ? number_format($avg, 1).'%' : '' }}</span></td><td><span class="badge {{ $member->status==='aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $member->status }}</span></td><td class="text-end text-nowrap"><a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$member->student_id]) }}" class="btn btn-sm btn-outline-primary" title="Detail Tahfidz"><i class="ri-eye-line"></i></a>@if($canManage && $member->status==='aktif')<button type="button" class="btn btn-sm btn-outline-danger remove-member" data-action="{{ route('user.tahfidz.halaqah.members.remove', ['userId'=>$userId,'id'=>$group->id,'memberId'=>$member->id]) }}" data-name="{{ $member->student?->name }}" title="Keluarkan"><i class="ri-user-unfollow-line"></i></button>@endif</td></tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada anggota.</td></tr>@endforelse
                </tbody></table></div>
                @if($canManage)
                    <div class="card-body border-top"><form method="POST" action="{{ route('user.tahfidz.halaqah.members.store', ['userId'=>$userId,'id'=>$group->id]) }}" class="row g-2 align-items-end">@csrf<div class="col-md-9"><label class="form-label small">Tambah Santri</label><select name="student_ids[]" class="form-select" multiple size="5">@foreach($availableStudents as $student)<option value="{{ $student->id }}">{{ $student->name }} ({{ $student->nis }})</option>@endforeach</select><div class="form-text">Ctrl/Cmd untuk memilih beberapa santri.</div></div><div class="col-md-3 d-grid"><button class="btn btn-outline-primary"><i class="ri-user-add-line me-1"></i>Tambah</button></div></form></div>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h6 class="card-title mb-0"><i class="ri-file-add-line text-primary me-1"></i>Setoran Terakhir</h6><a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-sm btn-soft-info">Semua</a></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Jenis</th><th>Nilai</th></tr></thead><tbody>
                    @forelse($group->setorans as $setoran)<tr><td>{{ $setoran->setoran_date?->format('d/m') }}</td><td>{{ $setoran->student?->name }}</td><td class="small">{{ $setoran->setoran_type }}</td><td>{{ $setoran->nilai_setoran ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-3">Belum ada setoran.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>

    @if($canManage)
        <form id="removeMemberForm" method="POST" class="d-none">@csrf</form>
        <x-modal-confirm id="removeMemberModal" title="Keluarkan Santri?" message="<strong id='removeMemberName'>Santri</strong> akan dikeluarkan dari halaqah ini." submit-label="Ya, Keluarkan" submit-icon="ri-user-unfollow-line" submit-class="btn-danger" form-id="removeMemberForm" />
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.remove-member').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('removeMemberForm').action = this.dataset.action;
                document.getElementById('removeMemberName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('removeMemberModal')).show();
            });
        });
    });
</script>
@endpush
