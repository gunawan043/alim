@extends('layouts.master')

@push('styles')
<style>
    .mutabaah-modal .modal-dialog { max-height: calc(100vh - 1rem); max-height: calc(100dvh - 1rem); }
    .mutabaah-modal .modal-content { display: flex; flex-direction: column; max-height: calc(100vh - 1rem); max-height: calc(100dvh - 1rem); overflow: hidden; }
    .mutabaah-modal .modal-body { overflow-y: auto !important; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; flex: 1 1 auto; }
    .mutabaah-modal .modal-footer { flex-shrink: 0; }
    @media (max-width: 767.98px) {
        .mutabaah-modal .modal-dialog { margin: .5rem; max-height: calc(100vh - 1rem); max-height: calc(100dvh - 1rem); }
        .mutabaah-modal .modal-body { max-height: calc(100vh - 170px) !important; max-height: calc(100dvh - 170px) !important; overflow-y: auto !important; padding: 1rem; }
    }
</style>
@endpush
@section('title', 'Waraqat al-Mutaba’ah')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $monthValue = $month->format('Y-m');
        $symbol = fn ($value) => match ($value) {
            'berjamaah' => 'B', 'sendiri' => 'S', 'qadha' => 'Q', 'tidak' => 'T', 'uzur' => 'U', default => '–',
        };
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Waraqat al-Mutaba’ah @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa data mutaba’ah.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Catatan Bulan Ini', $stats['total'], 'ri-calendar-check-line', 'primary', 'Harian yang sudah diisi'],
            ['Sholat 5 Waktu Jamaah', $stats['jamaah'], 'ri-mosque-line', 'success', 'Lengkap dalam sehari'],
            ['Halaman Tilawah', $stats['tilawah'], 'ri-book-read-line', 'info', 'Total tilawah mandiri'],
            ['Halaman Tikror', $stats['tikror'], 'ri-repeat-line', 'warning', 'Total murajaah mandiri'],
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
            <div><h5 class="card-title mb-0"><i class="ri-calendar-check-line text-primary me-1"></i>Waraqat al-Mutaba’ah</h5><p class="text-muted mb-0 small">Pantau ibadah harian, tilawah, dan tikror santri.</p></div>
            <div class="d-flex gap-2">
                @if($inputGroups->isNotEmpty())<button type="button" class="btn btn-primary" onclick="openMutabaahModal()"><i class="ri-add-line me-1"></i>Input Mutaba’ah</button>@endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label small">Bulan</label><input type="month" name="month" value="{{ $monthValue }}" class="form-control" onchange="this.form.submit()"></div>
                <div class="col-md-4"><label class="form-label small">Halaqah</label><select name="group_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(request('group_id')===$group->id)>{{ $group->name }} — {{ $group->school?->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small">Santri</label><select name="student_id" class="form-select mutabaah-filter-student" onchange="this.form.submit()"><option value="">Semua</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(request('student_id')===$student->id)>{{ $student->name }} ({{ $student->nis }})</option>@endforeach</select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Halaqah</th><th class="text-center" title="Subuh, Dzuhur, Ashar, Maghrib, Isya">Sholat</th><th class="text-center">Sunnah</th><th class="text-center">Tilawah</th><th class="text-center">Tikror</th><th>Pencatat</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($records as $record)
                        @php
                            $editPayload = [
                                'id' => $record->id, 'tahfidz_group_id' => $record->tahfidz_group_id,
                                'student_id' => $record->student_id, 'record_date' => $record->record_date?->format('Y-m-d'),
                                'sholat_subuh' => $record->sholat_subuh, 'sholat_dzuhur' => $record->sholat_dzuhur,
                                'sholat_ashar' => $record->sholat_ashar, 'sholat_maghrib' => $record->sholat_maghrib,
                                'sholat_isya' => $record->sholat_isya, 'sholat_tahajud' => $record->sholat_tahajud,
                                'sholat_dhuha' => $record->sholat_dhuha, 'puasa_sunnah' => $record->puasa_sunnah,
                                'sedekah' => $record->sedekah, 'tilawah_halaman' => $record->tilawah_halaman,
                                'tikror_mandiri_halaman' => $record->tikror_mandiri_halaman,
                                'wirid_pagi' => $record->wirid_pagi, 'wirid_sore' => $record->wirid_sore,
                                'catatan_musyrif' => $record->catatan_musyrif,
                            ];
                        @endphp
                        <tr>
                            <td>{{ $record->record_date?->format('d/m/Y') }}</td>
                            <td><div class="fw-medium">{{ $record->student?->name }}</div><div class="text-muted small">{{ $record->student?->nis }}</div></td>
                            <td>{{ $record->group?->name }}</td>
                            <td class="text-center text-nowrap">
                                @foreach(['sholat_subuh','sholat_dzuhur','sholat_ashar','sholat_maghrib','sholat_isya'] as $field)
                                    <span class="badge {{ $record->{$field} === 'berjamaah' ? 'bg-success-subtle text-success' : ($record->{$field} ? 'bg-warning-subtle text-warning' : 'bg-light text-muted') }}" title="{{ str_replace('_',' ', $field) }}: {{ $record->{$field} ?? 'belum' }}">{{ $symbol($record->{$field}) }}</span>
                                @endforeach
                            </td>
                            <td class="text-center small">{{ $record->sholat_tahajud ? 'T' : '–' }} · {{ $record->sholat_dhuha ? 'D' : '–' }} · {{ $record->puasa_sunnah ? 'P' : '–' }} · {{ $record->sedekah ? 'S' : '–' }}</td>
                            <td class="text-center">{{ $record->tilawah_halaman }}</td>
                            <td class="text-center">{{ $record->tikror_mandiri_halaman }}</td>
                            <td class="small">{{ $record->recordedBy?->name }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('user.tahfidz.mutabaah.print', ['userId'=>$userId,'studentId'=>$record->student_id,'month'=>$monthValue,'group_id'=>$record->tahfidz_group_id]) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="Cetak Waraqat"><i class="ri-printer-line"></i></a>
                                @if($inputGroups->contains('id', $record->tahfidz_group_id))
                                    <button type="button" class="btn btn-sm btn-outline-warning edit-mutabaah" title="Edit" data-record="{{ json_encode($editPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openMutabaahEditModal(JSON.parse(this.dataset.record))"><i class="ri-edit-line"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-mutabaah" title="Hapus" data-action="{{ route('user.tahfidz.mutabaah.destroy', ['userId'=>$userId,'id'=>$record->id]) }}" data-name="{{ $record->student?->name }}"><i class="ri-delete-bin-line"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5"><i class="ri-calendar-check-line fs-3 d-block mb-2"></i>Belum ada catatan mutaba’ah pada bulan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())<div class="card-body border-top">{{ $records->links() }}</div>@endif
    </div>

    @if($inputGroups->isNotEmpty())
        <div class="modal fade mutabaah-modal" id="mutabaahModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.mutabaah.store', ['userId'=>$userId]) }}" id="mutabaahForm">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" id="mutabaahMethod" disabled>
                        <div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="mutabaahModalTitle"><i class="ri-calendar-check-line me-1"></i>Input Mutaba’ah Harian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><div class="row g-3">
                            <div class="col-md-5"><label class="form-label">Halaqah <span class="text-danger">*</span></label><select name="tahfidz_group_id" id="mutabaahGroup" class="form-select" required onchange="fillMutabaahStudents(this.value)">@foreach($inputGroups as $g)<option value="{{ $g->id }}">{{ $g->name }} — {{ $g->school?->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Santri <span class="text-danger">*</span></label><select name="student_id" id="mutabaahStudent" class="form-select" required><option value="">—</option></select><div class="form-text">Ketik nama atau NIS untuk mencari.</div></div>
                            <div class="col-md-3"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="record_date" id="mutabaah_record_date" class="form-control" value="{{ old('record_date', now()->toDateString()) }}" required></div>
                            @foreach(['sholat_subuh'=>'Subuh','sholat_dzuhur'=>'Dzuhur','sholat_ashar'=>'Ashar','sholat_maghrib'=>'Maghrib','sholat_isya'=>'Isya'] as $field=>$label)
                                <div class="col-md-2"><label class="form-label small">{{ $label }}</label><select name="{{ $field }}" id="mutabaah_{{ $field }}" class="form-select"><option value="">—</option>@foreach(['berjamaah'=>'Berjamaah','sendiri'=>'Sendiri','qadha'=>'Qadha','tidak'=>'Tidak','uzur'=>'Uzur'] as $val=>$text)<option value="{{ $val }}" @selected(old($field)===$val)>{{ $text }}</option>@endforeach</select></div>
                            @endforeach
                            <div class="col-md-2"><label class="form-label small">Tahajud</label><div class="form-check form-switch mt-2"><input type="checkbox" name="sholat_tahajud" value="1" id="mutabaah_sholat_tahajud" class="form-check-input"></div></div>
                            <div class="col-md-2"><label class="form-label small">Dhuha</label><div class="form-check form-switch mt-2"><input type="checkbox" name="sholat_dhuha" value="1" id="mutabaah_sholat_dhuha" class="form-check-input"></div></div>
                            <div class="col-md-2"><label class="form-label small">Puasa Sunnah</label><div class="form-check form-switch mt-2"><input type="checkbox" name="puasa_sunnah" value="1" id="mutabaah_puasa_sunnah" class="form-check-input"></div></div>
                            <div class="col-md-2"><label class="form-label small">Sedekah</label><div class="form-check form-switch mt-2"><input type="checkbox" name="sedekah" value="1" id="mutabaah_sedekah" class="form-check-input"></div></div>
                            <div class="col-md-2"><label class="form-label small">Wirid Pagi</label><div class="form-check form-switch mt-2"><input type="checkbox" name="wirid_pagi" value="1" id="mutabaah_wirid_pagi" class="form-check-input"></div></div>
                            <div class="col-md-2"><label class="form-label small">Wirid Sore</label><div class="form-check form-switch mt-2"><input type="checkbox" name="wirid_sore" value="1" id="mutabaah_wirid_sore" class="form-check-input"></div></div>
                            <div class="col-md-3"><label class="form-label">Tilawah (halaman)</label><input type="number" name="tilawah_halaman" id="mutabaah_tilawah_halaman" min="0" max="604" class="form-control" value="{{ old('tilawah_halaman', 0) }}"></div>
                            <div class="col-md-3"><label class="form-label">Tikror (halaman)</label><input type="number" name="tikror_mandiri_halaman" id="mutabaah_tikror_mandiri_halaman" min="0" max="604" class="form-control" value="{{ old('tikror_mandiri_halaman', 0) }}"></div>
                            <div class="col-12"><label class="form-label">Catatan Musyrif</label><textarea name="catatan_musyrif" id="mutabaah_catatan_musyrif" rows="2" class="form-control">{{ old('catatan_musyrif') }}</textarea></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="mutabaahSubmit"><i class="ri-save-line me-1"></i>Simpan Mutaba’ah</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <form id="deleteMutabaahForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
    <x-modal-confirm id="deleteMutabaahModal" title="Hapus Mutaba’ah?" message="Catatan <strong id='deleteMutabaahName'>santri</strong> akan dihapus." submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteMutabaahForm" />
@endsection
@push('scripts')
<script>
    @php
        $mutabaahStoreUrl = route('user.tahfidz.mutabaah.store', ['userId'=>$userId]);
        $mutabaahUpdateUrl = route('user.tahfidz.mutabaah.update', ['userId'=>$userId,'id'=>'__ID__']);
    @endphp
    const mutabaahMembers = @json($membersByGroup);
    const mutabaahStoreUrl = @json($mutabaahStoreUrl);
    const mutabaahUpdateUrl = @json($mutabaahUpdateUrl);
    function fillMutabaahStudents(groupId, selected) {
        const select = document.getElementById('mutabaahStudent');
        if (! select) return;
        const members = mutabaahMembers[groupId] || [];
        select.innerHTML = '<option value="">—</option>';
        members.forEach(function (member) {
            const option = document.createElement('option');
            option.value = member.id;
            option.textContent = member.name + (member.nis ? ' (' + member.nis + ')' : '');
            if (selected && String(selected) === String(member.id)) option.selected = true;
            select.appendChild(option);
        });

        if (window.jQuery && jQuery.fn.select2) {
            const $select = jQuery(select);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                width: '100%',
                placeholder: 'Cari nama santri...',
                allowClear: true,
            });
        }
    }
    function setMutabaahField(key, value) {
        const field = document.getElementById('mutabaah_' + key);
        if (! field) return;
        if (field.type === 'checkbox') {
            field.checked = Number(value) === 1;
        } else {
            field.value = value === null || value === undefined ? '' : value;
        }
    }
    function openMutabaahModal() {
        const form = document.getElementById('mutabaahForm');
        if (! form || ! window.bootstrap) return;
        form.reset();
        form.action = mutabaahStoreUrl;
        document.getElementById('mutabaahMethod').disabled = true;
        document.getElementById('mutabaahModalTitle').innerHTML = '<i class="ri-calendar-check-line me-1"></i>Input Mutaba’ah Harian';
        document.getElementById('mutabaahSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Mutaba’ah';
        const group = document.getElementById('mutabaahGroup');
        fillMutabaahStudents(group.value);
        document.getElementById('mutabaah_tilawah_halaman').value = 0;
        document.getElementById('mutabaah_tikror_mandiri_halaman').value = 0;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('mutabaahModal')).show();
    }
    function openMutabaahEditModal(data) {
        const form = document.getElementById('mutabaahForm');
        if (! form || ! data || ! window.bootstrap) return;
        form.action = mutabaahUpdateUrl.replace('__ID__', data.id);
        document.getElementById('mutabaahMethod').disabled = false;
        document.getElementById('mutabaahModalTitle').innerHTML = '<i class="ri-edit-line me-1"></i>Edit Mutaba’ah Harian';
        document.getElementById('mutabaahSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Perubahan';
        document.getElementById('mutabaahGroup').value = data.tahfidz_group_id;
        fillMutabaahStudents(data.tahfidz_group_id, data.student_id);
        ['record_date','sholat_subuh','sholat_dzuhur','sholat_ashar','sholat_maghrib','sholat_isya','sholat_tahajud','sholat_dhuha','puasa_sunnah','sedekah','tilawah_halaman','tikror_mandiri_halaman','wirid_pagi','wirid_sore','catatan_musyrif'].forEach(function (key) { setMutabaahField(key, data[key]); });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('mutabaahModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.mutabaah-filter-student').select2({
                width: '100%',
                placeholder: 'Cari nama santri...',
                allowClear: true,
            });
        }

        const group = document.getElementById('mutabaahGroup');
        if (group) fillMutabaahStudents(group.value, @json(old('student_id')));
        @if($errors->any() && $inputGroups->isNotEmpty())
            openMutabaahModal();
        @endif

        const mutabaahModal = document.getElementById('mutabaahModal');
        function fitMutabaahModal() {
            if (! mutabaahModal) return;
            const body = mutabaahModal.querySelector('.modal-body');
            const header = mutabaahModal.querySelector('.modal-header');
            const footer = mutabaahModal.querySelector('.modal-footer');
            if (! body) return;
            const available = (window.visualViewport?.height || window.innerHeight) - (header?.offsetHeight || 70) - (footer?.offsetHeight || 70) - 24;
            body.style.maxHeight = Math.max(220, available) + 'px';
            body.style.overflowY = 'auto';
        }
        mutabaahModal?.addEventListener('shown.bs.modal', fitMutabaahModal);
        window.addEventListener('resize', fitMutabaahModal);
        window.visualViewport?.addEventListener('resize', fitMutabaahModal);

        document.querySelectorAll('.edit-mutabaah').forEach(function (button) {
            button.addEventListener('click', function () { openMutabaahEditModal(JSON.parse(this.dataset.record)); });
        });
        document.querySelectorAll('.delete-mutabaah').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteMutabaahForm').action = this.dataset.action;
                document.getElementById('deleteMutabaahName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteMutabaahModal')).show();
            });
        });
    });
</script>
@endpush
