<div class="d-flex align-items-start gap-2 py-2 border-bottom agenda-row">
    <div class="form-check pt-1">
        <input type="hidden" name="items[{{ $item->id }}][status]" value="belum">
        <input class="form-check-input agenda-done" type="checkbox" data-update-url="{{ route('sarpras.pertamanan.agenda.items.update', ['itemId' => $item->id]) }}" name="items[{{ $item->id }}][status]" value="selesai" id="agenda_done_{{ $item->id }}" {{ $item->status === 'selesai' ? 'checked' : '' }}>
        <label class="visually-hidden" for="agenda_done_{{ $item->id }}">Selesai</label>
    </div>
    <div class="flex-grow-1 min-w-0">
        <label for="agenda_done_{{ $item->id }}" class="fw-medium d-block mb-1" style="cursor:pointer">{{ $item->title }}</label>
        <input name="items[{{ $item->id }}][notes]" class="form-control form-control-sm" value="{{ $item->notes }}" placeholder="Keterangan (opsional)">
    </div>
    @if($canManageAgenda)
        <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 delete-agenda-item" data-bs-toggle="modal" data-bs-target="#deleteAgendaItemModal" data-action="{{ route('sarpras.pertamanan.agenda.items.destroy', ['itemId' => $item->id]) }}" title="Hapus item"><i class="ri-delete-bin-line"></i></button>
    @endif
</div>
