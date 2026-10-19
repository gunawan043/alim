<div class="border rounded-3 p-3 mb-2 flex-grow-1">
    <div class="fw-medium mb-2">{{ $item->title }}</div>
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Status</label><select name="items[{{ $item->id }}][status]" class="form-select form-select-sm">@foreach(\App\Models\PertamananAgendaItem::STATUS_OPTIONS as $value=>$label)<option value="{{ $value }}" {{ $item->status === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Petugas</label><select name="items[{{ $item->id }}][officer_id]" class="form-select form-select-sm"><option value="">—</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ (string) ($item->officer_id ?: $agenda->officer_id) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></div>
        <div class="col-12 col-md-5"><label class="form-label small text-muted mb-1">Keterangan</label><input name="items[{{ $item->id }}][notes]" class="form-control form-control-sm" value="{{ $item->notes }}" placeholder="Keterangan"></div>
        @if($canManageAgenda)<div class="col-12 col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger delete-agenda-item" data-bs-toggle="modal" data-bs-target="#deleteAgendaItemModal" data-action="{{ route('sarpras.pertamanan.agenda.items.destroy', ['itemId' => $item->id]) }}"><i class="ri-delete-bin-line"></i></button></div>@endif
    </div>
</div>
