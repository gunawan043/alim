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
        $pendingItems = $items->where('status', 'belum')->count();
        $postponedItems = $items->where('status', 'tunda')->count();
    @endphp

    <x-sarpras-stats :items="[
        ['label'=>'Total Agenda','value'=>$totalItems,'tone'=>'primary','icon'=>'ri-calendar-task-line'],
        ['label'=>'Selesai','value'=>$doneItems,'tone'=>'success','icon'=>'ri-check-double-line'],
        ['label'=>'Belum','value'=>$pendingItems,'tone'=>'warning','icon'=>'ri-time-line'],
        ['label'=>'Ditunda','value'=>$postponedItems,'tone'=>'danger','icon'=>'ri-pause-circle-line'],
    ]" />

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h4 class="mb-1">Agenda Harian Pertamanan</h4>
                <div class="text-muted small">TA. {{ $agenda->academic_year }} · {{ $date->translatedFormat('l, d F Y') }}</div>
            </div>
            <form method="GET" action="{{ route('sarpras.pertamanan.agenda.index') }}" class="d-flex gap-2 align-items-end">
                <div><label class="form-label small fw-semibold">Tanggal Agenda</label><input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}"></div>
                <button class="btn btn-primary"><i class="ri-eye-line me-1"></i>Tampilkan</button>
                <a href="{{ route('sarpras.pertamanan.agenda.index') }}" class="btn btn-light"><i class="ri-today-line"></i></a>
            </form>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <form method="POST" action="{{ route('sarpras.pertamanan.agenda.save') }}">
        @csrf
        <input type="hidden" name="agenda_date" value="{{ $date->toDateString() }}">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2"><span class="small text-muted">Petugas Agenda</span><select name="officer_id" class="form-select form-select-sm" style="min-width:240px"><option value="">— Pilih —</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ (string) $agenda->officer_id === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></div>
                <div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAgendaItemModal" data-section="pemeliharaan"><i class="ri-add-line me-1"></i>Tambah Item</button><button class="btn btn-sm btn-success"><i class="ri-save-line me-1"></i>Simpan Agenda</button></div>
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered align-middle mb-0">
                <thead class="table-light"><tr><th style="width:55px">No</th><th>Uraian Pekerjaan</th><th style="width:150px">Status</th><th style="width:200px">Petugas</th><th style="width:24%">Keterangan</th></tr></thead>
                <tbody>
                @foreach(\App\Models\PertamananAgenda::SECTIONS as $sectionKey => $sectionLabel)
                    <tr class="table-primary"><td class="fw-bold">{{ strtoupper(substr($sectionLabel, 0, 1)) }}</td><td colspan="4" class="fw-bold">{{ $sectionLabel }}</td></tr>
                    @php
                        $sectionItems = $items->where('section', $sectionKey);
                        $blocks = $sectionItems->groupBy(fn ($item) => $item->group_title ?: 'single-'.$item->id);
                        $number = 1;
                    @endphp
                    @forelse($blocks as $blockKey => $blockItems)
                        @php $first = $blockItems->first(); @endphp
                        @if($first->group_title)
                            <tr class="table-light"><td class="fw-semibold">{{ $number++ }}</td><td class="fw-semibold">{{ $first->group_title }}@if($canAdd ?? true)<button type="button" class="btn btn-sm btn-link p-0 ms-2 add-group-item" data-bs-toggle="modal" data-bs-target="#addAgendaItemModal" data-section="{{ $sectionKey }}" data-group="{{ $first->group_title }}" title="Tambah sub-item"><i class="ri-add-circle-line"></i></button>@endif</td><td colspan="3"></td></tr>
                            @foreach($blockItems as $item)
                                <tr>
                                    <td></td>
                                    <td class="ps-4">{{ $item->title }}</td>
                                    <td><select name="items[{{ $item->id }}][status]" class="form-select form-select-sm">@foreach(\App\Models\PertamananAgendaItem::STATUS_OPTIONS as $value=>$label)<option value="{{ $value }}" {{ $item->status === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></td>
                                    <td><select name="items[{{ $item->id }}][officer_id]" class="form-select form-select-sm"><option value="">—</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ (string) ($item->officer_id ?: $agenda->officer_id) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></td>
                                    <td><div class="d-flex gap-1"><input name="items[{{ $item->id }}][notes]" class="form-control form-control-sm" value="{{ $item->notes }}" placeholder="Keterangan"><button type="button" class="btn btn-sm btn-outline-danger delete-agenda-item" data-bs-toggle="modal" data-bs-target="#deleteAgendaItemModal" data-action="{{ route('sarpras.pertamanan.agenda.items.destroy', ['itemId' => $item->id]) }}" title="Hapus item"><i class="ri-delete-bin-line"></i></button></div></td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>{{ $number++ }}</td>
                                <td>{{ $first->title }}</td>
                                <td><select name="items[{{ $first->id }}][status]" class="form-select form-select-sm">@foreach(\App\Models\PertamananAgendaItem::STATUS_OPTIONS as $value=>$label)<option value="{{ $value }}" {{ $first->status === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></td>
                                <td><select name="items[{{ $first->id }}][officer_id]" class="form-select form-select-sm"><option value="">—</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ (string) ($first->officer_id ?: $agenda->officer_id) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></td>
                                <td><div class="d-flex gap-1"><input name="items[{{ $first->id }}][notes]" class="form-control form-control-sm" value="{{ $first->notes }}" placeholder="Keterangan"><button type="button" class="btn btn-sm btn-outline-danger delete-agenda-item" data-bs-toggle="modal" data-bs-target="#deleteAgendaItemModal" data-action="{{ route('sarpras.pertamanan.agenda.items.destroy', ['itemId' => $first->id]) }}" title="Hapus item"><i class="ri-delete-bin-line"></i></button></div></td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada agenda. <button type="button" class="btn btn-sm btn-link add-group-item" data-bs-toggle="modal" data-bs-target="#addAgendaItemModal" data-section="{{ $sectionKey }}">Tambah item</button></td></tr>
                    @endforelse
                @endforeach
                </tbody>
            </table></div></div>
            <div class="card-footer d-flex justify-content-between align-items-center"><div class="small text-muted"><i class="ri-information-line me-1"></i>Status dan keterangan dapat diisi langsung pada tabel.</div><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Agenda</button></div>
        </div>
    </form>

    <div class="modal fade" id="addAgendaItemModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.pertamanan.agenda.items.store') }}" class="modal-content">@csrf<input type="hidden" name="agenda_date" value="{{ $date->toDateString() }}"><input type="hidden" name="section" id="agendaSection" value="pemeliharaan"><div class="modal-header"><h5 class="modal-title">Tambah Item Agenda</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Bagian</label><select class="form-select" id="agendaSectionSelect">@foreach(\App\Models\PertamananAgenda::SECTIONS as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label">Kelompok Pekerjaan <span class="text-muted">(opsional)</span></label><input name="group_title" id="agendaGroup" class="form-control" placeholder="Contoh: Menyiram taman"></div><div class="mb-3"><label class="form-label">Uraian / Lokasi <span class="text-danger">*</span></label><input name="title" class="form-control" required placeholder="Contoh: Taman depan SD"></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(\App\Models\PertamananAgendaItem::STATUS_OPTIONS as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Petugas</label><select name="officer_id" class="form-select"><option value="">—</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div></div><div class="mt-3"><label class="form-label">Keterangan</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-success">Tambah Item</button></div></form></div></div>

    <div class="modal fade" id="deleteAgendaItemModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" id="deleteAgendaItemForm" class="modal-content">@csrf @method('DELETE')<div class="modal-header"><h5 class="modal-title">Hapus Item Agenda?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted mb-0">Item akan dihapus dari agenda harian ini.</p></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hapus</button></div></form></div></div>
@endsection

@push('script')
<script>
    (function () {
        const sectionInput = document.getElementById('agendaSection');
        const sectionSelect = document.getElementById('agendaSectionSelect');
        const groupInput = document.getElementById('agendaGroup');
        new bootstrap.Modal(document.getElementById('addAgendaItemModal'));

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
