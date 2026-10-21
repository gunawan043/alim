@extends('layouts.master')
@section('title', 'Pemeriksaan Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Pemeriksaan Berkala @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-add-circle-line me-1 text-primary"></i>Form Pemeriksaan</h5></div><div class="card-body">
            <form method="POST" action="{{ route('lab.inspection.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
                <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Tanggal Jadwal</label><input type="date" name="scheduled_date" class="form-control" value="{{ today()->toDateString() }}"></div><div class="col-6"><label class="form-label">Tanggal Pelaksanaan</label><input type="date" name="performed_date" class="form-control" value="{{ today()->toDateString() }}"></div></div>
                <div class="table-responsive"><table class="table table-sm align-middle" id="inspectionRows"><thead><tr><th>Nama Alat</th><th style="width:70px">Jml</th><th style="width:95px">Kondisi</th><th>Tindak Lanjut</th></tr></thead><tbody>
                @for($i=0;$i<3;$i++)<tr><td><select name="items[{{ $i }}][asset_id]" class="form-select form-select-sm lab-asset"><option value="">— manual —</option>@foreach($assets as $asset)<option value="{{ $asset->id }}" data-name="{{ $asset->asset_name }}" data-qty="{{ $asset->quantity }}">{{ $asset->asset_name }}</option>@endforeach</select><input name="items[{{ $i }}][item_name]" class="form-control form-control-sm mt-1 lab-name" placeholder="Nama alat" required></td><td><input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm lab-qty" min="1" value="1" required></td><td><select name="items[{{ $i }}][condition_code]" class="form-select form-select-sm"><option value="B">B — Baik</option><option value="RR">RR — Rusak Ringan</option><option value="RB">RB — Rusak Berat</option></select></td><td><input name="items[{{ $i }}][follow_up]" class="form-control form-control-sm" placeholder="Perbaikan/kalibrasi/penggantian"><input type="hidden" name="items[{{ $i }}][follow_up_type]" value="perbaikan"></td></tr>@endfor
                </tbody></table></div>
                <button type="button" class="btn btn-sm btn-outline-primary mb-2" onclick="addInspectionRow()"><i class="ri-add-line"></i> Tambah Baris</button>
                <div class="mb-2"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Simpan Pemeriksaan</button>
            </form>
        </div></div>
    </div>
    <div class="col-xl-8">
        <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Riwayat Pemeriksaan</h5><span class="badge bg-info-subtle text-info">{{ $inspections instanceof \Illuminate\Pagination\LengthAwarePaginator ? $inspections->total() : $inspections->count() }} pemeriksaan</span></div><div class="card-body p-0"><div class="table-container"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Inspektur</th><th>Item</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($inspections as $row)<tr><td>{{ $row->performed_date?->format('d/m/Y') ?? $row->scheduled_date?->format('d/m/Y') ?? '-' }}</td><td>{{ $row->inspector?->name ?? '-' }}</td><td>{{ $row->items->count() }} item</td><td><span class="badge bg-{{ $row->status === 'completed' || $row->status === 'closed' ? 'success' : 'warning' }}-subtle text-{{ $row->status === 'completed' || $row->status === 'closed' ? 'success' : 'warning' }}">{{ $row->status }}</span></td><td><a href="{{ route('lab.inspection.print', ['userId' => $userId, 'inspection' => $row->id]) }}" target="_blank" class="btn btn-sm btn-soft-secondary"><i class="ri-printer-line"></i></a></td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada pemeriksaan.</td></tr>@endforelse
        </tbody></table></div></div>@if($inspections instanceof \Illuminate\Pagination\LengthAwarePaginator && $inspections->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $inspections])</div>@endif</div>
    </div>
</div>
@endsection
@section('script')
<script>
let inspectionIndex = 3;
function addInspectionRow() {
    const tbody = document.querySelector('#inspectionRows tbody');
    const options = `@foreach($assets as $asset)<option value="{{ $asset->id }}" data-name="{{ $asset->asset_name }}" data-qty="{{ $asset->quantity }}">{{ $asset->asset_name }}</option>@endforeach`;
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><select name="items[${inspectionIndex}][asset_id]" class="form-select form-select-sm lab-asset"><option value="">— manual —</option>${options}</select><input name="items[${inspectionIndex}][item_name]" class="form-control form-control-sm mt-1 lab-name" placeholder="Nama alat" required></td><td><input type="number" name="items[${inspectionIndex}][quantity]" class="form-control form-control-sm lab-qty" min="1" value="1" required></td><td><select name="items[${inspectionIndex}][condition_code]" class="form-select form-select-sm"><option value="B">B — Baik</option><option value="RR">RR — Rusak Ringan</option><option value="RB">RB — Rusak Berat</option></select></td><td><input name="items[${inspectionIndex}][follow_up]" class="form-control form-control-sm" placeholder="Perbaikan/kalibrasi/penggantian"><input type="hidden" name="items[${inspectionIndex}][follow_up_type]" value="perbaikan"></td>`;
    tbody.appendChild(tr); inspectionIndex++;
}
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('lab-asset')) return;
    const row = e.target.closest('tr'); const option = e.target.selectedOptions[0];
    if (option?.dataset.name) row.querySelector('.lab-name').value = option.dataset.name;
    if (option?.dataset.qty) row.querySelector('.lab-qty').value = option.dataset.qty;
});
</script>
@endsection
