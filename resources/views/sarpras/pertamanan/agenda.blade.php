@extends('layouts.master')
@section('title') Agenda Harian Pertamanan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Agenda Harian Pertamanan
    @endcomponent

    @php
        $totalItems = $items->count();
        $doneItems = $items->where('status', 'selesai')->count();
        $pendingItems = $totalItems - $doneItems;
    @endphp

    <x-sarpras-stats :items="[
        ['label'=>'Total Agenda','value'=>$totalItems,'tone'=>'primary','icon'=>'ri-calendar-task-line'],
        ['label'=>'Selesai','value'=>$doneItems,'tone'=>'success','icon'=>'ri-check-double-line'],
        ['label'=>'Belum Selesai','value'=>$pendingItems,'tone'=>'warning','icon'=>'ri-time-line'],
        ['label'=>'Progres','value'=>($totalItems ? round(($doneItems/$totalItems)*100) : 0).'%','tone'=>'info','icon'=>'ri-bar-chart-line'],
    ]" />

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h4 class="mb-1">Agenda Harian Pertamanan</h4><div class="text-muted small">TA. {{ $agenda->academic_year }} · {{ $date->translatedFormat('l, d F Y') }}</div></div>
                <div class="d-flex align-items-center gap-1">
                    <a href="{{ route('sarpras.pertamanan.agenda.index', ['date' => $previousDate->toDateString()]) }}" class="btn btn-outline-secondary btn-sm" title="Hari sebelumnya"><i class="ri-arrow-left-s-line"></i></a>
                    <form method="GET" action="{{ route('sarpras.pertamanan.agenda.index') }}" class="d-flex gap-1"><input type="date" name="date" class="form-control form-control-sm" value="{{ $date->toDateString() }}"><button class="btn btn-primary btn-sm">Tampilkan</button></form>
                    <a href="{{ route('sarpras.pertamanan.agenda.index', ['date' => $nextDate->toDateString()]) }}" class="btn btn-outline-secondary btn-sm" title="Hari berikutnya"><i class="ri-arrow-right-s-line"></i></a>
                    <a href="{{ route('sarpras.pertamanan.agenda.index') }}" class="btn btn-light btn-sm" title="Hari ini"><i class="ri-today-line"></i></a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(!$canManageAgenda)<div class="alert alert-info d-flex align-items-center gap-2"><i class="ri-information-line fs-5"></i><span>Centang pekerjaan yang sudah selesai. Penambahan/penghapusan item dilakukan koordinator.</span></div>@endif

    <form method="POST" action="{{ route('sarpras.pertamanan.agenda.save') }}">
        @csrf
        <input type="hidden" name="agenda_date" value="{{ $date->toDateString() }}">

        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar-sm"><span class="avatar-title rounded-circle bg-primary-subtle text-primary"><i class="ri-user-star-line"></i></span></span>
                    <div><div class="small text-muted">Petugas Hari Ini</div><select name="officer_id" class="form-select form-select-sm mt-1" style="min-width:250px"><option value="">— Pilih petugas —</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ (string) $agenda->officer_id === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></div>
                </div>
                <div class="d-flex gap-2">@if($canManageAgenda)<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgendaItemModal" data-section="pemeliharaan"><i class="ri-add-line me-1"></i>Tambah Agenda</button>@endif<button class="btn btn-success" data-confirm="Simpan seluruh agenda harian ini? Perubahan checklist sudah tersimpan otomatis."><i class="ri-save-line me-1"></i>Simpan</button></div>
            </div>
        </div>

        @foreach(\App\Models\PertamananAgenda::SECTIONS as $sectionKey => $sectionLabel)
            @php
                $sectionItems = $items->where('section', $sectionKey);
                $sectionDone = $sectionItems->where('status', 'selesai')->count();
                $sectionTotal = $sectionItems->count();
                $sectionPercent = $sectionTotal ? round(($sectionDone / $sectionTotal) * 100) : 0;
                $blocks = $sectionItems->groupBy(fn ($item) => $item->group_title ?: 'single-'.$item->id);
                $number = 1;
            @endphp
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2"><span class="badge bg-{{ $sectionKey === 'pemeliharaan' ? 'primary' : ($sectionKey === 'pembibitan' ? 'success' : 'danger') }}-subtle text-{{ $sectionKey === 'pemeliharaan' ? 'primary' : ($sectionKey === 'pembibitan' ? 'success' : 'danger') }}">{{ strtoupper(substr($sectionLabel, 0, 1)) }}</span><div><h5 class="card-title mb-0">{{ substr($sectionLabel, 3) }}</h5><small class="text-muted">{{ $sectionDone }}/{{ $sectionTotal }} selesai</small></div></div>
                    <div class="d-flex align-items-center gap-2"><div class="progress" style="width:120px;height:7px"><div class="progress-bar bg-success" style="width:{{ $sectionPercent }}%"></div></div>@if($canManageAgenda)<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAgendaItemModal" data-section="{{ $sectionKey }}"><i class="ri-add-line me-1"></i>Tambah</button>@endif</div>
                </div>
                <div class="card-body py-2">
                    @forelse($blocks as $blockKey => $blockItems)
                        @php $first = $blockItems->first(); @endphp
                        @if($first->group_title)
                            <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                                <div class="fw-semibold"><span class="text-muted me-2">{{ $number++ }}.</span>{{ $first->group_title }}</div>
                                @if($canManageAgenda)<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAgendaItemModal" data-section="{{ $sectionKey }}" data-group="{{ $first->group_title }}"><i class="ri-add-line"></i><span class="d-none d-md-inline ms-1">Sub-item</span></button>@endif
                            </div>
                            <div class="ps-3 border-start border-2 ms-1">
                                @foreach($blockItems as $item)
                                    @include('sarpras.pertamanan.partials.agenda-item', ['item' => $item, 'users' => $users, 'agenda' => $agenda, 'canManageAgenda' => $canManageAgenda])
                                @endforeach
                            </div>
                        @else
                            @include('sarpras.pertamanan.partials.agenda-item', ['item' => $first, 'users' => $users, 'agenda' => $agenda, 'canManageAgenda' => $canManageAgenda])
                        @endif
                    @empty
                        <div class="text-center py-3 text-muted"><i class="ri-inbox-line fs-3 d-block mb-1"></i>Belum ada agenda.@if($canManageAgenda) <button type="button" class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#addAgendaItemModal" data-section="{{ $sectionKey }}">Tambah item</button>@endif</div>
                    @endforelse
                </div>
            </div>
        @endforeach

        <div class="card mb-3" style="position:sticky;bottom:0;z-index:5;box-shadow:0 -8px 24px rgba(0,0,0,.08)"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2"><div class="small text-muted"><i class="ri-checkbox-circle-line me-1 text-success"></i>Centang pekerjaan yang sudah selesai.</div><button class="btn btn-success px-4" data-confirm="Simpan seluruh agenda harian ini? Perubahan checklist sudah tersimpan otomatis."><i class="ri-save-line me-1"></i>Simpan Agenda</button></div></div>
    </form>

    @if($canManageAgenda)
        <button type="button" class="btn btn-primary rounded-circle shadow position-fixed" style="width:58px;height:58px;right:22px;bottom:88px;z-index:1050" data-bs-toggle="modal" data-bs-target="#addAgendaItemModal" data-section="pemeliharaan" title="Tambah Agenda"><i class="ri-add-line fs-4"></i></button>
    @endif

    <div class="modal fade" id="addAgendaItemModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.pertamanan.agenda.items.store') }}" class="modal-content">@csrf<input type="hidden" name="agenda_date" value="{{ $date->toDateString() }}"><input type="hidden" name="section" id="agendaSection" value="pemeliharaan"><div class="modal-header"><h5 class="modal-title">Tambah Item Agenda</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Bagian</label><select class="form-select" id="agendaSectionSelect">@foreach(\App\Models\PertamananAgenda::SECTIONS as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label">Kelompok Pekerjaan <span class="text-muted">(opsional)</span></label><input name="group_title" id="agendaGroup" class="form-control" placeholder="Contoh: Menyiram taman"></div><div class="mb-3"><label class="form-label">Uraian / Lokasi <span class="text-danger">*</span></label><input name="title" class="form-control" required></div><div class="mb-3"><label class="form-label">Status Awal</label><select name="status" class="form-select"><option value="belum">Belum</option><option value="selesai">Selesai</option></select></div><div><label class="form-label">Keterangan</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-success">Tambah Item</button></div></form></div></div>

    <div class="modal fade" id="deleteAgendaItemModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" id="deleteAgendaItemForm" class="modal-content">@csrf @method('DELETE')<div class="modal-header"><h5 class="modal-title">Hapus Item Agenda?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted mb-0">Item akan dihapus dari agenda harian ini.</p></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hapus</button></div></form></div></div>
@endsection

@push('script')
<script>
    (function () {
        const sectionInput = document.getElementById('agendaSection');
        const sectionSelect = document.getElementById('agendaSectionSelect');
        const groupInput = document.getElementById('agendaGroup');
        document.querySelectorAll('[data-bs-target="#addAgendaItemModal"]').forEach(function (button) {
            button.addEventListener('click', function () {
                sectionInput.value = button.dataset.section || 'pemeliharaan';
                sectionSelect.value = sectionInput.value;
                groupInput.value = button.dataset.group || '';
            });
        });
        sectionSelect.addEventListener('change', function () { sectionInput.value = this.value; });
        document.getElementById('deleteAgendaItemModal').addEventListener('show.bs.modal', function (event) {
            document.getElementById('deleteAgendaItemForm').action = event.relatedTarget.dataset.action;
        });
    })();
</script>
@endpush

@push('script')
<script>
    (function () {
        const statusEl = document.getElementById('agendaAutoSaveStatus');
        const officer = () => document.querySelector('select[name="officer_id"]')?.value || '';
        document.querySelectorAll('.agenda-done').forEach(function (checkbox) {
            checkbox.addEventListener('change', async function () {
                const row = checkbox.closest('.agenda-row');
                const notes = row?.querySelector('input[name$="[notes]"]')?.value || '';
                const form = new FormData();
                form.append('_token', '{{ csrf_token() }}');
                form.append('status', checkbox.checked ? 'selesai' : 'belum');
                form.append('notes', notes);
                form.append('officer_id', officer());
                if (statusEl) statusEl.textContent = 'Menyimpan…';
                try {
                    const response = await fetch(checkbox.dataset.updateUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: form });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Gagal menyimpan.');
                    if (statusEl) statusEl.textContent = '✓ Tersimpan ' + (data.saved_at || '');
                    row.classList.toggle('text-decoration-line-through', checkbox.checked);
                } catch (error) {
                    checkbox.checked = !checkbox.checked;
                    if (statusEl) statusEl.textContent = 'Gagal menyimpan: ' + error.message;
                }
            });
        });
    })();
</script>
@endpush
