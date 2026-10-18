@extends('layouts.master')
@section('title', $group->name)
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah</a> @endslot @slot('li_2') Detail @endslot @slot('title') {{ $group->name }} @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa data.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
            <div><h5 class="card-title mb-1">{{ $group->name }}</h5><div class="text-muted small">{{ $group->school?->name }} · {{ $group->studyGroup?->name ?? 'Tanpa kelas' }} · {{ strtoupper($group->gender) }} · {{ $group->academicYear?->name }}</div><div class="small mt-1">Guru: <strong>{{ $group->teacher?->name ?? '—' }}</strong>@if($group->coordinator) · Koordinator: {{ $group->coordinator->name }}@endif</div></div>
            <div class="d-flex gap-2"><a href="{{ route('user.tahfidz.setoran.create', ['userId' => $userId, 'group_id' => $group->id]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Input Setoran</a>@if($canManage)<a href="{{ route('user.tahfidz.halaqah.edit', ['userId' => $userId, 'id' => $group->id]) }}" class="btn btn-warning"><i class="ri-edit-line me-1"></i>Edit</a>@endif<a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}" class="btn btn-light">Kembali</a></div>
        </div>
        <div class="card-body">
            @if($group->notes)<p class="text-muted">{{ $group->notes }}</p>@endif
            <div class="row g-3">
                <div class="col-lg-7">
                    <h6>Anggota Halaqah <span class="badge bg-primary-subtle text-primary">{{ $activeMembers->count() }}</span></h6>
                    <div class="table-responsive"><table class="table table-sm align-middle"><thead class="table-light"><tr><th>Nama</th><th>NIS</th><th>Progres</th><th>Status</th>@if($canManage)<th></th>@endif</tr></thead><tbody>
                        @forelse($group->members as $member)
                            @php
                                $rows = $juzProgress[$member->student_id] ?? collect();
                                $selesai = $rows->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count();
                                $avg = $rows->whereNotNull('percentage')->avg('percentage');
                                $recap = $recaps[$member->student_id] ?? null;
                            @endphp
                            <tr><td>{{ $member->student?->name ?? '—' }}</td><td>{{ $member->student?->nis ?? '—' }}</td><td><span class="badge bg-success-subtle text-success">{{ $selesai }} juz</span> <span class="text-muted small">{{ $avg ? number_format($avg, 1).'%' : '' }}</span>@if($recap)<div class="text-muted" style="font-size:10px;">Target {{ $recap->pencapaian_target_persen !== null ? number_format($recap->pencapaian_target_persen, 0).'%' : '—' }}</div>@endif</td><td><span class="badge {{ $member->status==='aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $member->status }}</span></td>
                            @if($canManage && $member->status==='aktif')<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-member" title="Keluarkan" data-action="{{ route('user.tahfidz.halaqah.members.remove', ['userId'=>$userId,'id'=>$group->id,'memberId'=>$member->id]) }}" data-name="{{ $member->student?->name }}"><i class="ri-user-unfollow-line"></i></button></td>@endif
                            </tr>
                        @empty<tr><td colspan="{{ $canManage ? 5 : 4 }}" class="text-center text-muted py-3">Belum ada anggota.</td></tr>@endforelse
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

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-focus-3-line text-primary me-1"></i>Target Hafalan Santri</h5><p class="text-muted mb-0 small">Target bulanan per santri; menjadi dasar persentase capaian pada rekap.</p></div>
            @if($canTarget)<button type="button" class="btn btn-success" onclick="openTargetModal()"><i class="ri-add-line me-1"></i>Tambah Target</button>@endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Santri</th><th>Semester</th><th>Bulan</th><th>Juz</th><th>Halaman</th><th>Hadits</th><th>Catatan</th>@if($canTarget)<th class="text-end">Aksi</th>@endif</tr></thead>
                <tbody>
                    @forelse($targets as $target)
                        @php $targetPayload = ['id'=>$target->id,'student_id'=>$target->student_id,'semester'=>$target->semester,'target_bulan'=>$target->target_bulan,'juz_start'=>$target->juz_start,'juz_end'=>$target->juz_end,'target_halaman'=>$target->target_halaman,'target_hadits'=>$target->target_hadits,'notes'=>$target->notes]; @endphp
                        <tr>
                            <td><div class="fw-medium">{{ $target->student?->name ?? '—' }}</div><div class="text-muted small">{{ $target->student?->nis }}</div></td>
                            <td>{{ ucfirst($target->semester) }}</td>
                            <td>{{ $target->target_bulan ?? '—' }}</td>
                            <td>{{ $target->juz_start ?? '—' }}{{ $target->juz_end ? ' s.d. '.$target->juz_end : '' }}</td>
                            <td>{{ $target->target_halaman ?? '—' }}</td>
                            <td>{{ $target->target_hadits ?? 0 }}</td>
                            <td class="small text-muted">{{ $target->notes ?? '—' }}</td>
                            @if($canTarget)
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-warning edit-target" title="Edit" data-target="{{ json_encode($targetPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openTargetEditModal(JSON.parse(this.dataset.target))"><i class="ri-edit-line"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-target" title="Hapus" data-action="{{ route('user.tahfidz.halaqah.targets.destroy', ['userId'=>$userId,'id'=>$group->id,'targetId'=>$target->id]) }}" data-name="{{ $target->student?->name }}"><i class="ri-delete-bin-line"></i></button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canTarget ? 8 : 7 }}" class="text-center text-muted py-4">Belum ada target. Tambahkan target bulanan santri.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-line-chart-line text-primary me-1"></i>Progres Juz & Rekap Capaian</h5><p class="text-muted mb-0 small">Dihitung otomatis dari setoran ziyadah yang sudah diverifikasi.</p></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Santri</th><th class="text-center">Juz Selesai</th><th>Halaman Ziyadah</th><th>Rata-rata Nilai</th><th>Target</th><th>Posisi Terakhir</th><th>Detail Juz</th></tr></thead>
                <tbody>
                    @forelse($activeMembers as $member)
                        @php
                            $rows = $juzProgress[$member->student_id] ?? collect();
                            $recap = $recaps[$member->student_id] ?? null;
                            $selesai = $rows->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count();
                        @endphp
                        <tr>
                            <td><div class="fw-medium">{{ $member->student?->name }}</div><div class="text-muted small">{{ $member->student?->nis }}</div></td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $selesai }}</span></td>
                            <td>{{ $recap?->total_halaman_ziyadah !== null ? number_format((float) $recap->total_halaman_ziyadah, 1) : '0' }}</td>
                            <td>{{ $recap?->rata_rata_nilai !== null ? number_format((float) $recap->rata_rata_nilai, 2) : '—' }}</td>
                            <td>{{ $recap?->pencapaian_target_persen !== null ? number_format((float) $recap->pencapaian_target_persen, 1).'%' : '—' }}</td>
                            <td>{{ $recap?->last_position_juz ? 'Juz '.$recap->last_position_juz : '—' }}</td>
                            <td>
                                @if($rows->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($rows as $row)
                                            <span class="badge {{ $row->status === 'selesai_ziyadah' || $row->status === 'sudah_tasmi' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}" title="{{ $row->halaman_completed }}/{{ $row->total_halaman_juz }} halaman">J{{ $row->juz_number }} · {{ number_format((float) $row->percentage, 0) }}%</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">Belum ada setoran terverifikasi.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada anggota aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canTarget && $activeMembers->isNotEmpty())
        <div class="modal fade" id="targetModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.halaqah.targets.store', ['userId'=>$userId,'id'=>$group->id]) }}" id="targetForm">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" id="targetMethod" disabled>
                        <div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="targetModalTitle">Tambah Target Hafalan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Santri <span class="text-danger">*</span></label><select name="student_id" id="target_student_id" class="form-select" required><option value="">—</option>@foreach($activeMembers as $member)<option value="{{ $member->student_id }}">{{ $member->student?->name }} ({{ $member->student?->nis }})</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Semester <span class="text-danger">*</span></label><select name="semester" id="target_semester" class="form-select" required><option value="ganjil" @selected(($group->academicYear?->semester ?? 'ganjil')==='ganjil')>Ganjil</option><option value="genap" @selected(($group->academicYear?->semester ?? '')==='genap')>Genap</option></select></div>
                            <div class="col-md-3"><label class="form-label">Bulan Ke- <span class="text-danger">*</span></label><select name="target_bulan" id="target_bulan" class="form-select" required>@for($i=1;$i<=12;$i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
                            <div class="col-md-3"><label class="form-label">Juz Awal</label><input type="number" name="juz_start" id="target_juz_start" min="1" max="30" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Juz Akhir</label><input type="number" name="juz_end" id="target_juz_end" min="1" max="30" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Target Halaman</label><input type="number" step="0.1" name="target_halaman" id="target_halaman" min="0" max="604" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Target Hadits</label><input type="number" name="target_hadits" id="target_hadits" min="0" max="1000" class="form-control" value="0"></div>
                            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="target_notes" rows="2" class="form-control"></textarea></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="targetSubmit"><i class="ri-save-line me-1"></i>Simpan Target</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canTarget)
        <form id="deleteTargetForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="deleteTargetModal" title="Hapus Target?" message="Target <strong id='deleteTargetName'>santri</strong> akan dihapus." submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteTargetForm" />
    @endif

    @if($canManage)
        <form id="removeMemberForm" method="POST" class="d-none">@csrf</form>
        <x-modal-confirm id="removeMemberModal" title="Keluarkan Santri?" message="<strong id='removeMemberName'>Santri</strong> akan dikeluarkan dari halaqah ini." submit-label="Ya, Keluarkan" submit-icon="ri-user-unfollow-line" submit-class="btn-danger" form-id="removeMemberForm" />
    @endif
@endsection

@push('scripts')
<script>
    @php
        $targetStoreUrl = route('user.tahfidz.halaqah.targets.store', ['userId'=>$userId,'id'=>$group->id]);
        $targetUpdateUrl = route('user.tahfidz.halaqah.targets.update', ['userId'=>$userId,'id'=>$group->id,'targetId'=>'__ID__']);
    @endphp
    const targetStoreUrl = @json($targetStoreUrl);
    const targetUpdateUrl = @json($targetUpdateUrl);
    function setTargetField(key, value) {
        const field = document.getElementById('target_' + key);
        if (field) field.value = value === null || value === undefined ? '' : value;
    }
    function openTargetModal() {
        const form = document.getElementById('targetForm');
        if (! form || ! window.bootstrap) return;
        form.reset();
        form.action = targetStoreUrl;
        document.getElementById('targetMethod').disabled = true;
        document.getElementById('targetModalTitle').textContent = 'Tambah Target Hafalan';
        document.getElementById('targetSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Target';
        document.getElementById('target_hadits').value = 0;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('targetModal')).show();
    }
    function openTargetEditModal(data) {
        const form = document.getElementById('targetForm');
        if (! form || ! data || ! window.bootstrap) return;
        form.action = targetUpdateUrl.replace('__ID__', data.id);
        document.getElementById('targetMethod').disabled = false;
        document.getElementById('targetModalTitle').textContent = 'Edit Target Hafalan';
        document.getElementById('targetSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Perubahan';
        ['student_id','semester','target_bulan','juz_start','juz_end','target_halaman','target_hadits','notes'].forEach(function (key) { setTargetField(key, data[key]); });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('targetModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any() && $canTarget && $activeMembers->isNotEmpty())
            openTargetModal();
        @endif
        document.querySelectorAll('.edit-target').forEach(function (button) {
            button.addEventListener('click', function () { openTargetEditModal(JSON.parse(this.dataset.target)); });
        });
        document.querySelectorAll('.delete-target').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteTargetForm').action = this.dataset.action;
                document.getElementById('deleteTargetName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteTargetModal')).show();
            });
        });
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
