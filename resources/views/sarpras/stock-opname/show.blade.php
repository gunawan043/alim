@extends('layouts.master')
@section('title') Stock Opname — {{ $session->title }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.stock-opname.index') }}">Stock Opname</a> @endslot
        @slot('title') {{ $session->title }}
    @endcomponent

    @php
        $statusTone = ['planned'=>'warning','in_progress'=>'info','closed'=>'success','cancelled'=>'secondary'][$session->status] ?? 'secondary';
        $statusLabel = ['planned'=>'Direncanakan','in_progress'=>'Berjalan','closed'=>'Selesai','cancelled'=>'Dibatalkan'][$session->status] ?? ucfirst($session->status);
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap"><h4 class="mb-0">{{ $session->title }}</h4><span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}">{{ $statusLabel }}</span></div>
                    <div class="text-muted small mt-1"><code>{{ $session->session_code }}</code> · {{ $session->workUnit?->name ?? '-' }} · {{ $session->scheduled_date?->format('d/m/Y') }}</div>
                    @if($session->description)<p class="text-muted mb-0 mt-2 small">{{ $session->description }}</p>@endif
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if($session->status === 'planned')
                        <form method="POST" action="{{ route('sarpras.stock-opname.start', $session->id) }}">@csrf<button class="btn btn-primary"><i class="ri-play-line me-1"></i>Mulai Opname</button></form>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelSessionModal"><i class="ri-close-circle-line me-1"></i>Batalkan</button>
                    @elseif($session->status === 'in_progress')
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#closeSessionModal"><i class="ri-check-double-line me-1"></i>Tutup Sesi</button>
                    @endif
                </div>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-6"><div class="small text-muted mb-1">Cakupan</div><div class="fw-medium">{{ $session->room?->room_name ?? 'Seluruh ruang unit' }}@if($session->category) · {{ $session->category->name }}@endif</div></div>
                <div class="col-md-6"><div class="small text-muted mb-1">Petugas</div><div class="d-flex flex-wrap gap-1">@forelse($session->officers as $officer)<span class="badge bg-light text-dark border">{{ $officer->user?->name }}@if($officer->role === 'lead') (ketua)@endif</span>@empty<span class="text-muted">-</span>@endforelse</div></div>
            </div>
            @if($session->status === 'closed' && $session->summary_notes)<div class="alert alert-success mt-3 mb-0"><i class="ri-check-double-line me-1"></i>{{ $session->summary_notes }}</div>@endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Total', $stats['total'], 'primary', 'ri-archive-line'],
            ['Ditemukan', $stats['found'], 'success', 'ri-check-line'],
            ['Hilang', $stats['missing'], 'danger', 'ri-question-line'],
            ['Rusak', $stats['damaged'], 'warning', 'ri-tools-line'],
            ['Pindah Ruang', $stats['moved'], 'info', 'ri-arrow-left-right-line'],
            ['Belum Dicek', $stats['pending'], 'secondary', 'ri-time-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-2">
                <div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-2">
                    <span class="avatar-xs"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }}"><i class="{{ $icon }}"></i></span></span>
                    <div><div class="text-muted" style="font-size:.7rem">{{ $label }}</div><div class="fs-5 fw-bold">{{ number_format($value) }}</div></div>
                </div></div>
            </div>
        @endforeach
    </div>

    @if($session->status === 'in_progress')
        <div class="card mb-3 border-info">
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.stock-opname.scan', $session->id) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8"><label class="form-label small fw-semibold">Scan QR / Kode Aset</label><input type="text" name="code" class="form-control" placeholder="Scan QR label unit aset atau ketik kode…" autofocus required></div>
                    <div class="col-md-4"><button class="btn btn-info w-100 text-white"><i class="ri-scan-2-line me-1"></i>Tandai Ditemukan</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-list-check-3 me-2 text-primary"></i>Aset dalam Sesi</h5>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Cari aset…">
                <select name="observation" class="form-select form-select-sm"><option value="">Semua temuan</option>@foreach(['pending'=>'Belum dicek','found'=>'Ditemukan','missing'=>'Hilang','damaged'=>'Rusak','moved'=>'Pindah ruang'] as $key=>$label)<option value="{{ $key }}" {{ request('observation') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                <button class="btn btn-sm btn-primary"><i class="ri-search-line"></i></button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Aset</th><th>Lokasi Tercatat</th><th>Kondisi Tercatat</th><th>Temuan</th><th>Petugas</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse($items as $i => $item)
                        @php
                            $tone = ['pending'=>'secondary','found'=>'success','missing'=>'danger','damaged'=>'warning','moved'=>'info'][$item->observed_status] ?? 'secondary';
                            $label = ['pending'=>'Belum dicek','found'=>'Ditemukan','missing'=>'Hilang','damaged'=>'Rusak','moved'=>'Pindah ruang'][$item->observed_status] ?? ucfirst($item->observed_status);
                        @endphp
                        <tr>
                            <td>{{ $items->firstItem() + $i }}</td>
                            <td><div class="fw-medium">{{ $item->asset?->asset_name ?? '-' }}</div><div class="small text-muted"><code>{{ $item->asset?->asset_code }}</code> · {{ $item->asset?->category?->name }}</div></td>
                            <td class="small">{{ $item->asset?->room?->room_name ?? '-' }}@if($item->asset?->room?->building)<div class="text-muted">{{ $item->asset->room->building->building_name }}</div>@endif</td>
                            <td class="small">{{ ucfirst(str_replace('_',' ', $item->expected_condition ?? '-')) }}</td>
                            <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $label }}</span>@if($item->observed_condition)<div class="small text-muted mt-1">{{ ucfirst(str_replace('_',' ', $item->observed_condition)) }}</div>@endif</td>
                            <td class="small">{{ $item->recorder?->name ?? '-' }}@if($item->scanned_at)<div class="text-muted">{{ $item->scanned_at->format('d/m H:i') }}</div>@endif</td>
                            <td class="text-end">
                                @if($session->status === 'in_progress')
                                    <button type="button" class="btn btn-sm btn-outline-primary record-item" data-bs-toggle="modal" data-bs-target="#recordItemModal"
                                        data-action="{{ route('sarpras.stock-opname.record', ['id' => $session->id, 'itemId' => $item->id]) }}"
                                        data-name="{{ $item->asset?->asset_name }}"
                                        data-status="{{ $item->observed_status === 'pending' ? 'found' : $item->observed_status }}"
                                        data-condition="{{ $item->observed_condition ?? $item->expected_condition }}"
                                        data-room="{{ $item->observed_room_id ?? $item->asset?->room_id }}"
                                        data-notes="{{ $item->notes }}"><i class="ri-edit-line"></i> Catat</button>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" title="Tidak ada aset pada filter ini" message="Ubah filter atau pastikan sesi memiliki aset." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($items->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $items])</div>@endif
    </div>

    @if($session->status === 'in_progress')
        <div class="modal fade" id="recordItemModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" id="recordItemForm" class="modal-content">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-clipboard-line me-1"></i>Catat Temuan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p class="fw-medium mb-3" id="recordItemName">Aset</p>
                        <div class="mb-3"><label class="form-label">Temuan <span class="text-danger">*</span></label><select name="observed_status" id="recordStatus" class="form-select" required>@foreach(['found'=>'Ditemukan','missing'=>'Hilang','damaged'=>'Rusak','moved'=>'Pindah ruang'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="form-label">Kondisi Fisik</label><select name="observed_condition" id="recordCondition" class="form-select"><option value="">— Sama dengan tercatat —</option>@foreach(\App\Models\Asset::CONDITION_OPTIONS as $condition)<option value="{{ $condition }}">{{ ucfirst(str_replace('_',' ', $condition)) }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="form-label">Lokasi Ditemukan</label><select name="observed_room_id" id="recordRoom" class="form-select"><option value="">— Tidak berubah —</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->building?->building_name ? $room->building->building_name.' — ' : '' }}{{ $room->room_name }}</option>@endforeach</select></div>
                        <div><label class="form-label">Catatan</label><textarea name="notes" id="recordNotes" class="form-control" rows="2" placeholder="Keterangan temuan…"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Temuan</button></div>
                </form>
            </div>
        </div>
    @endif

    <x-modal-confirm id="closeSessionModal" icon="https://cdn.lordicon.com/lupuorrc.json" title="Tutup Sesi Opname?" submitLabel="Ya, Tutup Sesi" submitIcon="ri-check-double-line" submitClass="btn-success" formId="closeSessionForm">
        <p class="text-center text-muted">Pastikan seluruh aset sudah dicek. Aset yang belum dicek tetap tercatat sebagai belum dicek.</p>
        <textarea name="summary_notes" form="closeSessionForm" class="form-control" rows="3" placeholder="Catatan penutup / tindak lanjut…"></textarea>
    </x-modal-confirm>
    <form id="closeSessionForm" method="POST" action="{{ route('sarpras.stock-opname.close', $session->id) }}" class="d-none">@csrf</form>

    <x-modal-confirm id="cancelSessionModal" icon="https://cdn.lordicon.com/gsqxdxog.json" title="Batalkan Sesi?" submitLabel="Ya, Batalkan" submitIcon="ri-close-line" submitClass="btn-danger" formId="cancelSessionForm">
        <p class="text-center text-muted mb-0">Sesi yang dibatalkan tidak dapat digunakan lagi.</p>
    </x-modal-confirm>
    <form id="cancelSessionForm" method="POST" action="{{ route('sarpras.stock-opname.cancel', $session->id) }}" class="d-none">@csrf</form>
@endsection

@push('script')
<script>
    document.querySelectorAll('.record-item').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = document.getElementById('recordItemForm');
            if (!form) return;
            form.action = button.dataset.action;
            document.getElementById('recordItemName').textContent = button.dataset.name;
            document.getElementById('recordStatus').value = button.dataset.status || 'found';
            document.getElementById('recordCondition').value = button.dataset.condition || '';
            document.getElementById('recordRoom').value = button.dataset.room || '';
            document.getElementById('recordNotes').value = button.dataset.notes || '';
        });
    });
</script>
@endpush
