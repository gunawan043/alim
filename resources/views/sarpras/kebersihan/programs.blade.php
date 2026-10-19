@extends('layouts.master')
@section('title') Program Kebersihan Tahunan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.kebersihan.index') }}">Kebersihan</a> @endslot
        @slot('title') Program Tahunan
    @endcomponent

    @php $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des']; @endphp

    <div class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-2">
        <form method="GET" action="{{ route('sarpras.kebersihan.programs') }}" class="d-flex gap-2 align-items-end">@if(request('area_type'))<input type="hidden" name="area_type" value="{{ request('area_type') }}">@endif<div><label class="form-label small fw-semibold">Tahun Program</label><input type="number" name="year" class="form-control" value="{{ $year }}" min="2000" max="2100"></div><button class="btn btn-primary">Tampilkan</button></form>
        <div class="d-flex gap-2"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Kembali</a>@if($canManage)<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProgramModal"><i class="ri-add-line me-1"></i>Tambah Program</button>@endif</div>
    </div></div>

    <div class="alert alert-light border small"><strong>Keterangan:</strong> <span class="badge bg-success-subtle text-success">✓</span> sudah terlaksana · <span class="badge bg-primary-subtle text-primary">○</span> direncanakan · <span class="text-muted">·</span> tidak dijadwalkan. Koordinator mencentang bulan pelaksanaan.</div>
    @if($canManage)<div id="programAutoSaveStatus" class="small text-muted mb-2"><i class="ri-cloud-done-line me-1"></i>Klik bulan untuk menyimpan otomatis.</div>@endif

    <div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-calendar-todo-line me-2 text-primary"></i>Program Kebersihan {{ $year }}</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0" style="min-width:1100px">
        <thead class="table-light"><tr><th style="min-width:260px">Program</th><th>Kategori</th><th>Frekuensi</th><th>Petugas</th>@foreach($months as $m)<th class="text-center" style="width:48px">{{ $m }}</th>@endforeach<th class="text-end" style="width:110px">Aksi</th></tr></thead>
        <tbody>
        @forelse($programs as $program)
            <tr>
                <td><div class="fw-medium">{{ $program->title }}</div><small class="text-muted">{{ $program->area?->name ?? $program->building?->building_name ?? '-' }}</small></td>
                <td class="small">{{ ucfirst(str_replace('_',' ', $program->category)) }}</td>
                <td class="small">{{ ucfirst($program->frequency) }}</td>
                <td class="small">{{ $program->assignee?->name ?? '-' }}</td>
                @foreach($months as $m => $label)
                    @php
                        $planned = in_array($m, $program->months ?? [], true);
                        $check = $program->checks->firstWhere('month', $m);
                        $done = (bool) ($check?->is_done);
                    @endphp
                    <td class="text-center p-1">
                        @if($canManage)
                            <form method="POST" class="program-check-form" action="{{ route('sarpras.kebersihan.program.check', ['programId' => $program->id]) }}">
                                @csrf
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="month" value="{{ $m }}">
                                <input type="hidden" name="is_done" value="{{ $done ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm p-1 {{ $done ? 'btn-success' : ($planned ? 'btn-outline-primary' : 'btn-light text-muted') }}" data-planned="{{ $planned ? '1' : '0' }}" title="{{ $done ? 'Batalkan checklist' : 'Tandai terlaksana' }}" style="min-width:30px">{{ $done ? '✓' : ($planned ? '○' : '·') }}</button>
                            </form>
                        @else
                            <span class="badge {{ $done ? 'bg-success-subtle text-success' : ($planned ? 'bg-primary-subtle text-primary' : 'bg-light text-muted') }}">{{ $done ? '✓' : ($planned ? '○' : '·') }}</span>
                        @endif
                    </td>
                @endforeach
                <td class="text-end"><div class="d-inline-flex gap-1"><button type="button" class="btn btn-sm btn-outline-warning edit-program" data-bs-toggle="modal" data-bs-target="#editProgramModal" data-action="{{ route('sarpras.kebersihan.program.update', ['programId' => $program->id]) }}" data-title="{{ $program->title }}" data-category="{{ $program->category }}" data-area="{{ $program->area_id }}" data-building="{{ $program->building_id }}" data-frequency="{{ $program->frequency }}" data-assignee="{{ $program->assigned_to }}" data-standard="{{ $program->standard }}" data-method="{{ $program->method }}" data-notes="{{ $program->notes }}" data-months="{{ json_encode($program->months ?? []) }}"><i class="ri-pencil-line"></i></button><form method="POST" action="{{ route('sarpras.kebersihan.program.destroy', ['programId' => $program->id]) }}" data-confirm="Hapus program {{ $program->title }}?"><button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form></div></td>
            </tr>
        @empty
            <x-empty-state colspan="18" title="Belum ada program" message="Tambahkan program kebersihan tahunan." />
        @endforelse
        </tbody>
    </table></div></div></div>

    @if($canManage)
        <div class="modal fade" id="addProgramModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('sarpras.kebersihan.program.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Program Tahunan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
            <div class="col-md-8"><label class="form-label">Nama Program <span class="text-danger">*</span></label><input name="title" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Kategori <span class="text-danger">*</span></label><select name="category" class="form-select" required>@foreach(App\Models\CleaningProgram::CATEGORY_OPTIONS as $category)<option value="{{ $category }}">{{ ucfirst(str_replace('_',' ', $category)) }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Area</label><select name="area_id" class="form-select"><option value="">— Tidak ada —</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" class="form-select"><option value="">— Tidak ada —</option>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Frekuensi <span class="text-danger">*</span></label><select name="frequency" class="form-select" required>@foreach(App\Models\CleaningTask::FREQUENCY_OPTIONS as $frequency)<option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Petugas</label><select name="assigned_to" class="form-select"><option value="">— Pilih —</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">Bulan Rencana</label><div class="d-flex flex-wrap gap-2">@foreach($months as $m=>$label)<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="months[]" value="{{ $m }}"><span class="form-check-label">{{ $label }}</span></label>@endforeach</div></div>
            <div class="col-md-6"><label class="form-label">Standar</label><input name="standard" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Metode</label><input name="method" class="form-control" placeholder="Visual / Tes fungsi"></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div></div><div class="modal-footer"><button class="btn btn-primary">Simpan Program</button></div></form></div></div>
        <div class="modal fade" id="editProgramModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" id="editProgramForm" class="modal-content">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit Program Tahunan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
            <div class="col-md-8"><label class="form-label">Nama Program <span class="text-danger">*</span></label><input name="title" id="editProgramTitle" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Kategori <span class="text-danger">*</span></label><select name="category" id="editProgramCategory" class="form-select" required>@foreach(App\Models\CleaningProgram::CATEGORY_OPTIONS as $category)<option value="{{ $category }}">{{ ucfirst(str_replace('_',' ', $category)) }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Area</label><select name="area_id" id="editProgramArea" class="form-select"><option value="">— Tidak ada —</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" id="editProgramBuilding" class="form-select"><option value="">— Tidak ada —</option>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Frekuensi <span class="text-danger">*</span></label><select name="frequency" id="editProgramFrequency" class="form-select" required>@foreach(App\Models\CleaningTask::FREQUENCY_OPTIONS as $frequency)<option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Petugas</label><select name="assigned_to" id="editProgramAssignee" class="form-select"><option value="">— Pilih —</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">Bulan Rencana</label><div class="d-flex flex-wrap gap-2">@foreach($months as $m=>$label)<label class="form-check form-check-inline"><input class="form-check-input edit-month" type="checkbox" name="months[]" value="{{ $m }}"><span class="form-check-label">{{ $label }}</span></label>@endforeach</div></div>
            <div class="col-md-6"><label class="form-label">Standar</label><input name="standard" id="editProgramStandard" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Metode</label><input name="method" id="editProgramMethod" class="form-control"></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="editProgramNotes" class="form-control" rows="2"></textarea></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div></form></div></div>

    @endif
@endsection

@push('script')
<script>
    (function () {
        const form = document.getElementById('editProgramForm');
        document.querySelectorAll('.edit-program').forEach(function (button) {
            button.addEventListener('click', function () {
                form.action = button.dataset.action;
                document.getElementById('editProgramTitle').value = button.dataset.title || '';
                document.getElementById('editProgramCategory').value = button.dataset.category || '';
                document.getElementById('editProgramArea').value = button.dataset.area || '';
                document.getElementById('editProgramBuilding').value = button.dataset.building || '';
                document.getElementById('editProgramFrequency').value = button.dataset.frequency || 'bulanan';
                document.getElementById('editProgramAssignee').value = button.dataset.assignee || '';
                document.getElementById('editProgramStandard').value = button.dataset.standard || '';
                document.getElementById('editProgramMethod').value = button.dataset.method || '';
                document.getElementById('editProgramNotes').value = button.dataset.notes || '';
                const months = JSON.parse(button.dataset.months || '[]').map(String);
                document.querySelectorAll('.edit-month').forEach(function (checkbox) {
                    checkbox.checked = months.includes(checkbox.value);
                });
            });
        });
    })();
</script>
@endpush
@push('script')
<script>
    (function () {
        const statusEl = document.getElementById('programAutoSaveStatus');
        document.querySelectorAll('.program-check-form').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                const button = form.querySelector('button[type="submit"]');
                if (!button || button.disabled) return;
                const token = form.querySelector('input[name="_token"]')?.value || '';
                button.disabled = true;
                if (statusEl) statusEl.innerHTML = '<i class="ri-loader-4-line me-1"></i>Menyimpan…';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: new FormData(form)
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Gagal menyimpan.');

                    const done = !!data.is_done;
                    const planned = button.dataset.planned === '1';
                    const doneInput = form.querySelector('input[name="is_done"]');
                    if (doneInput) doneInput.value = done ? '0' : '1';
                    button.textContent = done ? '✓' : (planned ? '○' : '·');
                    button.classList.toggle('btn-success', done);
                    button.classList.toggle('btn-outline-primary', !done && planned);
                    button.classList.toggle('btn-light', !done && !planned);
                    button.classList.toggle('text-muted', !done && !planned);
                    button.title = done ? 'Batalkan checklist' : 'Tandai terlaksana';
                    if (statusEl) statusEl.innerHTML = '<i class="ri-cloud-done-line me-1"></i>Tersimpan ' + (data.saved_at || '');
                } catch (error) {
                    if (statusEl) statusEl.innerHTML = '<i class="ri-error-warning-line me-1"></i>Gagal menyimpan: ' + error.message;
                } finally {
                    button.disabled = false;
                }
            });
        });
    })();
</script>
@endpush
