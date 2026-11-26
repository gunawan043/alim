@extends('layouts.master')
@section('title', 'Target Hafalan Santri')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Target Hafalan Santri @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Target', $stats['total'], 'ri-focus-3-line', 'primary', 'Baris target'],
            ['Santri', $stats['students'], 'ri-user-heart-line', 'info', 'Memiliki target'],
            ['Halaqah', $stats['groups'], 'ri-team-line', 'success', 'Tercakup'],
            ['Rata Halaman', $stats['average_pages'] !== null ? number_format((float)$stats['average_pages'], 1) : '—', 'ri-book-read-line', 'warning', 'Per target'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ $value }}</h3></div></div><p class="text-muted mb-0 stat-label">{{ $note }}</p></div></div></div>
        @endforeach
    </div>

    @if($inputGroups->isNotEmpty())
        <div class="card border-primary-subtle">
            <div class="card-header border-bottom-dashed py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-information-line text-primary fs-5"></i>
                    <div>
                        <h6 class="mb-0">Jenjang yang berlaku</h6>
                        <p class="text-muted small mb-0">Muqorror dipilih dari jenjang satuan pendidikan, bukan dari rombel.</p>
                    </div>
                </div>
                @if($inputGroups->count() > 1)
                    <button class="btn btn-sm btn-outline-primary" onclick="openBulkModal()"><i class="ri-clipboard-line me-1"></i>Ubah Target Massal</button>
                @endif
            </div>
            <div class="card-body py-3">
                <div class="row g-2">
                    @foreach($inputGroups as $group)
                        @php $info = $planInfo[$group->id] ?? null; @endphp
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded p-2 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="fw-semibold text-truncate">{{ $group->name }}</div>
                                    <span class="badge bg-primary-subtle text-primary">{{ $info['jenjang'] ?? '—' }}</span>
                                </div>
                                @if($info['plan'] ?? null)
                                    <div class="small text-muted text-truncate" title="{{ $info['plan'] }}">{{ $info['plan'] }}</div>
                                    <div class="small text-muted">{{ $info['months'] }} bulan target</div>
                                @else
                                    <div class="small text-danger">Belum ada muqorror untuk jenjang ini.</div>
                                    <a href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}" class="small">Buka Muqorror</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex flex-wrap gap-3 mt-3 small text-muted">
                    <span><i class="ri-arrow-up-line text-info me-1"></i><strong>Alumni</strong> mulai juz 1 → 30</span>
                    <span><i class="ri-arrow-down-line text-warning me-1"></i><strong>Non-alumni</strong> mulai juz 30 → 1</span>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-focus-3-line text-primary me-1"></i>Target Hafalan</h5><p class="text-muted mb-0 small">Target bulanan per santri dan halaqah.</p></div><div class="d-flex gap-2"><a href="{{ route('user.tahfidz.progress.index', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-line-chart-line me-1"></i>Progres & Rekap</a>@if($inputGroups->isNotEmpty())<button class="btn btn-soft-success" onclick="openBulkModal()"><i class="ri-clipboard-line me-1"></i>Ubah Massal</button><button class="btn btn-primary" onclick="openTargetModal()"><i class="ri-add-line me-1"></i>Tambah Target</button>@endif</div></div>
        <div class="card-body border-bottom"><form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">Halaqah</label><select name="group_id" class="form-select" data-searchable onchange="this.form.submit()"><option value="">Semua</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(request('group_id')===$group->id)>{{ $group->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small">Semester</label><select name="semester" class="form-select" onchange="this.form.submit()"><option value="">Semua</option><option value="ganjil" @selected(request('semester')==='ganjil')>Ganjil</option><option value="genap" @selected(request('semester')==='genap')>Genap</option></select></div>
            <div class="col-md-4"><label class="form-label small">Santri</label><select name="student_id" class="form-select" data-searchable onchange="this.form.submit()"><option value="">Semua</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(request('student_id')===$student->id)>{{ $student->name }} ({{ $student->nis }})</option>@endforeach</select></div>
        </form></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 tahfidz-table"><thead class="table-light"><tr><th>Santri</th><th>Halaqah</th><th class="text-center">Semester</th><th class="text-center">Bulan</th><th class="text-center">Juz</th><th class="text-center">Halaman</th><th class="text-center">Hadits</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($targets as $target)
                @php $payload = ['id'=>$target->id,'tahfidz_group_id'=>$target->tahfidz_group_id,'student_id'=>$target->student_id,'semester'=>$target->semester,'target_bulan'=>$target->target_bulan,'juz_start'=>$target->juz_start,'juz_end'=>$target->juz_end,'target_halaman'=>$target->target_halaman,'target_hadits'=>$target->target_hadits,'notes'=>$target->notes]; @endphp
                <tr><td><a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$target->student_id]) }}" class="fw-medium text-decoration-none">{{ $target->student?->name }}</a><div class="text-muted small">{{ $target->student?->nis }}</div>@if($directions[$target->student_id] ?? null)<div class="text-muted" style="font-size:.68rem">{{ $directions[$target->student_id]['label'] }}</div>@endif</td><td>{{ $target->group?->name }}</td><td class="text-center">{{ ucfirst($target->semester) }}</td><td class="text-center">{{ $target->target_bulan }}</td><td class="text-center">{{ $target->juz_start ?? '—' }}{{ $target->juz_end ? '–'.$target->juz_end : '' }}</td><td class="text-center">{{ $target->target_halaman ?? '—' }}</td><td class="text-center">{{ $target->target_hadits ?? 0 }}</td><td class="small text-muted">{{ \Illuminate\Support\Str::limit($target->notes, 60) }}</td><td class="text-end">@if($inputGroups->contains('id', $target->tahfidz_group_id))<button class="btn btn-sm btn-outline-warning" onclick="openTargetEditModal(JSON.parse(this.dataset.target))" data-target="{{ json_encode($payload, JSON_HEX_APOS | JSON_HEX_QUOT) }}"><i class="ri-edit-line"></i></button><button class="btn btn-sm btn-outline-danger delete-target" data-action="{{ route('user.tahfidz.targets.destroy', ['userId'=>$userId,'id'=>$target->id]) }}" data-name="{{ $target->student?->name }}"><i class="ri-delete-bin-line"></i></button>@endif</td></tr>
            @empty<tr><td colspan="9" class="text-center text-muted py-5">Belum ada target.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    @if($inputGroups->isNotEmpty())
        <div class="modal fade" id="targetModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="POST" action="{{ route('user.tahfidz.targets.store', ['userId'=>$userId]) }}" id="targetForm">@csrf<input type="hidden" name="_method" value="PUT" id="targetMethod" disabled><div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="targetModalTitle"><i class="ri-focus-3-line me-1"></i>Tambah Target</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Halaqah <span class="text-danger">*</span></label><select name="tahfidz_group_id" id="targetGroup" class="form-select" required onchange="fillTargetStudents(this.value)">@foreach($inputGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Santri <span class="text-danger">*</span></label><select name="student_id" id="targetStudent" class="form-select" required><option value="">—</option></select><div id="targetDirection" class="form-text"></div></div>
            <div class="col-md-4"><label class="form-label">Semester <span class="text-danger">*</span></label><select name="semester" id="target_semester" class="form-select" required><option value="ganjil">Ganjil</option><option value="genap">Genap</option></select></div>
            <div class="col-md-4"><label class="form-label">Bulan Ke- <span class="text-danger">*</span></label><select name="target_bulan" id="target_bulan" class="form-select" required>@for($i=1;$i<=12;$i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
            <div class="col-md-4"><label class="form-label">Target Hadits</label><input type="number" name="target_hadits" id="target_hadits" min="0" max="1000" class="form-control" value="0"></div>
            <div class="col-md-4"><label class="form-label">Juz Awal</label><input type="number" name="juz_start" id="target_juz_start" min="1" max="30" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Juz Akhir</label><input type="number" name="juz_end" id="target_juz_end" min="1" max="30" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Target Halaman</label><input type="number" step="0.1" name="target_halaman" id="target_halaman" min="0" max="604" class="form-control"></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="target_notes" rows="2" class="form-control"></textarea></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="targetSubmit"><i class="ri-save-line me-1"></i>Simpan Target</button></div></form></div></div></div>
        <form id="deleteTargetForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="deleteTargetModal" title="Hapus Target?" message="Target <strong id='deleteTargetName'>santri</strong> akan dihapus." submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteTargetForm" />

        <div class="modal fade" id="bulkModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="POST" action="{{ route('user.tahfidz.targets.bulk', ['userId'=>$userId]) }}" id="bulkForm">@csrf<div class="modal-header bg-success-subtle"><h5 class="modal-title"><i class="ri-clipboard-line me-1"></i>Ubah Target Massal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
            <div class="alert alert-light border small mb-3">
                Atur sekaligus target beberapa santri. Kosongkan Juz Awal/Akhir agar sistem memakai juz berikutnya sesuai arah santri
                (<strong>alumni 1 → 30</strong>, <strong>non-alumni 30 → 1</strong>).
            </div>
            <div class="row g-3">
                <div class="col-md-7"><label class="form-label">Halaqah <span class="text-danger">*</span></label><select name="tahfidz_group_id" id="bulkGroup" class="form-select" required onchange="fillBulkStudents(this.value)">@foreach($inputGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select><div id="bulkPlanHint" class="form-text"></div></div>
                <div class="col-md-5"><label class="form-label">Semester <span class="text-danger">*</span></label><select name="semester" class="form-select" required><option value="ganjil">Ganjil</option><option value="genap">Genap</option></select></div>
                <div class="col-12"><div class="d-flex justify-content-between align-items-center mb-1"><label class="form-label mb-0">Santri <span class="text-danger">*</span></label><button type="button" class="btn btn-sm btn-outline-primary" onclick="bulkToggleAll()">Pilih semua</button></div><div id="bulkStudents" class="border rounded p-2" style="max-height:220px;overflow:auto"></div><div class="form-text">Hanya santri yang dicentang yang diubah.</div></div>
                <div class="col-md-3"><label class="form-label">Bulan Dari</label><select name="bulan_dari" class="form-select">@for($i=1;$i<=12;$i++)<option value="{{ $i }}" @selected($i===1)>{{ $i }}</option>@endfor</select></div>
                <div class="col-md-3"><label class="form-label">Bulan Sampai</label><select name="bulan_sampai" class="form-select">@for($i=1;$i<=12;$i++)<option value="{{ $i }}" @selected($i===6)>{{ $i }}</option>@endfor</select></div>
                <div class="col-md-3"><label class="form-label">Juz Awal</label><input type="number" name="juz_start" min="1" max="30" class="form-control" placeholder="Otomatis"></div>
                <div class="col-md-3"><label class="form-label">Juz Akhir</label><input type="number" name="juz_end" min="1" max="30" class="form-control" placeholder="= Juz Awal"></div>
                <div class="col-md-6"><label class="form-label">Target Halaman</label><input type="number" step="0.1" name="target_halaman" min="0" max="604" class="form-control" placeholder="Biarkan kosong bila tidak diubah"></div>
                <div class="col-md-6"><label class="form-label">Target Hadits</label><input type="number" name="target_hadits" min="0" max="1000" class="form-control" placeholder="Tidak diubah"></div>
                <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" rows="2" class="form-control" maxlength="2000" placeholder="Opsional — dipakai untuk semua santri terpilih"></textarea></div>
            </div>
        </div><div class="modal-footer bg-light-subtle"><span class="text-muted small me-auto" id="bulkCount">0 santri dipilih</span><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Terapkan ke Santri Terpilih</button></div></form></div></div></div>
    @endif
@endsection

@push('scripts')
<script>
    const targetMembers = @json($members);
    @php
        $targetStoreUrl = route('user.tahfidz.targets.store', ['userId'=>$userId]);
        $targetUpdateUrl = route('user.tahfidz.targets.update', ['userId'=>$userId,'id'=>'__ID__']);
    @endphp
    const targetDirections = @json($directions);
    function updateTargetDirection(studentId) {
        const el = document.getElementById('targetDirection');
        if (! el) return;
        const direction = targetDirections[studentId] || null;
        el.textContent = direction ? direction.label : '';
    }

    const targetStoreUrl = @json($targetStoreUrl);
    const targetUpdateUrl = @json($targetUpdateUrl);
    function fillTargetStudents(groupId, selected) {
        const select = document.getElementById('targetStudent');
        if (! select) return;
        select.innerHTML = '<option value="">—</option>';
        (targetMembers[groupId] || []).forEach(function (member) {
            const option = document.createElement('option');
            option.value = member.id;
            option.textContent = member.name + (member.nis ? ' (' + member.nis + ')' : '');
            if (selected && String(selected) === String(member.id)) option.selected = true;
            select.appendChild(option);
        });
    }
    function setTargetField(key, value) {
        const field = document.getElementById('target_' + key);
        if (field) field.value = value === null || value === undefined ? '' : value;
    }
    function openTargetModal() {
        const form = document.getElementById('targetForm');
        if (! form || ! window.bootstrap) return;
        form.reset(); form.action = targetStoreUrl;
        document.getElementById('targetMethod').disabled = true;
        document.getElementById('targetModalTitle').textContent = 'Tambah Target';
        document.getElementById('targetSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Target';
        const group = document.getElementById('targetGroup');
        fillTargetStudents(group.value);
        document.getElementById('target_hadits').value = 0;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('targetModal')).show();
    }
    function openTargetEditModal(data) {
        const form = document.getElementById('targetForm');
        if (! form || ! window.bootstrap) return;
        form.action = targetUpdateUrl.replace('__ID__', data.id);
        document.getElementById('targetMethod').disabled = false;
        document.getElementById('targetModalTitle').textContent = 'Edit Target';
        document.getElementById('targetSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Perubahan';
        document.getElementById('targetGroup').value = data.tahfidz_group_id;
        fillTargetStudents(data.tahfidz_group_id, data.student_id);
        ['semester','target_bulan','juz_start','juz_end','target_halaman','target_hadits','notes'].forEach(function (key) { setTargetField(key, data[key]); });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('targetModal')).show();
    }
    const targetPlanInfo = @json($planInfo);

    function fillBulkStudents(groupId) {
        const box = document.getElementById('bulkStudents');
        if (! box) return;
        box.innerHTML = '';
        const members = targetMembers[groupId] || [];
        if (! members.length) {
            box.innerHTML = '<div class="text-muted small py-2">Halaqah ini belum memiliki santri aktif.</div>';
            updateBulkCount();
            return;
        }
        members.forEach(function (member) {
            const direction = targetDirections[member.id] || null;
            const row = document.createElement('label');
            row.className = 'form-check d-flex align-items-center gap-2 py-1 mb-0';
            row.innerHTML = '<input class="form-check-input mt-0 bulk-student" type="checkbox" name="student_ids[]" value="' + member.id + '" checked>' +
                '<span class="form-check-label small">' + member.name + (member.nis ? ' <span class="text-muted">(' + member.nis + ')</span>' : '') +
                (direction ? ' <span class="text-muted">· ' + direction.label + '</span>' : '') + '</span>';
            box.appendChild(row);
        });
        box.querySelectorAll('.bulk-student').forEach(function (input) {
            input.addEventListener('change', updateBulkCount);
        });
        updateBulkCount();
        updateBulkPlanHint(groupId);
    }

    function updateBulkPlanHint(groupId) {
        const el = document.getElementById('bulkPlanHint');
        if (! el) return;
        const info = targetPlanInfo[groupId] || null;
        el.textContent = info && info.plan
            ? 'Muqorror jenjang ' + info.jenjang + ': ' + info.plan
            : (info ? 'Jenjang ' + info.jenjang + ' belum punya muqorror aktif.' : '');
    }

    function bulkToggleAll() {
        const inputs = document.querySelectorAll('.bulk-student');
        const allChecked = Array.prototype.every.call(inputs, function (input) { return input.checked; });
        inputs.forEach(function (input) { input.checked = ! allChecked; });
        updateBulkCount();
    }

    function updateBulkCount() {
        const el = document.getElementById('bulkCount');
        if (! el) return;
        el.textContent = document.querySelectorAll('.bulk-student:checked').length + ' santri dipilih';
    }

    function openBulkModal() {
        const modal = document.getElementById('bulkModal');
        if (! modal || ! window.bootstrap) return;
        fillBulkStudents(document.getElementById('bulkGroup').value);
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const group = document.getElementById('targetGroup');
        if (group) fillTargetStudents(group.value);
        document.getElementById('targetStudent')?.addEventListener('change', function () { updateTargetDirection(this.value); });
        @if($errors->any() && $inputGroups->isNotEmpty()) openTargetModal(); @endif
        document.querySelectorAll('.delete-target').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteTargetForm').action = this.dataset.action;
                document.getElementById('deleteTargetName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteTargetModal')).show();
            });
        });
    });
</script>
@endpush
