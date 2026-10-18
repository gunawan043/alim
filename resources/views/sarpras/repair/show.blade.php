@extends('layouts.master')
@section('title') Detail Permintaan Perbaikan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.repair.index') }}">Antrean Perbaikan</a> @endslot
        @slot('title') {{ $repair->request_number }}
    @endcomponent

    @php
        $statusLabel = ['submitted'=>'Diajukan','verification_pending'=>'Menunggu Verifikasi','verification_in_progress'=>'Verifikasi','additional_info'=>'Perlu Info','approval_pending'=>'Menunggu Persetujuan','execution_pending'=>'Menunggu Pelaksanaan','started'=>'Dikerjakan','completed'=>'Selesai','closed'=>'Ditutup','stopped'=>'Dihentikan','verification_rejected'=>'Ditolak','approval_rejected'=>'Ditolak'][$repair->status] ?? ucfirst(str_replace('_',' ', $repair->status));
        $statusTone = ['completed'=>'success','closed'=>'success','started'=>'info','execution_pending'=>'info','approval_pending'=>'warning','verification_pending'=>'warning','submitted'=>'warning','verification_rejected'=>'danger','approval_rejected'=>'danger'][$repair->status] ?? 'secondary';
        $priorityTone = ['high'=>'danger','normal'=>'warning','low'=>'secondary'][$repair->priority] ?? 'secondary';
    @endphp

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-2"><h5 class="card-title mb-0">Detail Permintaan</h5><div class="d-flex gap-2"><span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}">{{ $statusLabel }}</span><span class="badge bg-{{ $priorityTone }}-subtle text-{{ $priorityTone }}">Prioritas {{ ucfirst($repair->priority) }}</span></div></div>
                <div class="card-body">
                    <div class="table-responsive"><table class="table table-borderless mb-0"><tbody>
                        <tr><td class="text-muted" style="width:180px">No Permintaan</td><td><code>{{ $repair->request_number }}</code></td></tr>
                        <tr><td class="text-muted">Aset</td><td>@if($repair->asset)<a href="{{ route('sarpras.aset.show', ['id' => $repair->asset_id]) }}" class="fw-medium">{{ $repair->asset->asset_name }}</a><br><code class="small">{{ $repair->asset->asset_code }}</code>@else - @endif</td></tr>
                        <tr><td class="text-muted">Lokasi</td><td>{{ $repair->asset?->room?->building?->building_name ? $repair->asset->room->building->building_name.' — ' : '' }}{{ $repair->asset?->room?->room_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Pelapor</td><td>{{ $repair->reportedBy?->name ?? '-' }} · {{ $repair->created_at?->format('d/m/Y H:i') }}</td></tr>
                        <tr><td class="text-muted">Judul</td><td><strong>{{ $repair->title }}</strong></td></tr>
                        <tr><td class="text-muted">Deskripsi</td><td>{{ $repair->description }}</td></tr>
                        <tr><td class="text-muted">Petugas Ditugaskan</td><td>{{ $repair->assignedTo?->name ?? 'Belum ditugaskan' }}</td></tr>
                        @if($repair->result_description)<tr><td class="text-muted">Hasil</td><td>{{ $repair->result_description }}</td></tr>@endif
                    </tbody></table></div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1"></i>Work Order</h5>@if($canManage)<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#workOrderModal"><i class="ri-add-line me-1"></i>Jadwalkan Perbaikan</button>@endif</div>
                <div class="card-body p-0">
                    @if($repair->workOrders->isNotEmpty())
                        <div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>No WO</th><th>Petugas</th><th>Jadwal</th><th>Status</th><th class="text-end">Biaya</th></tr></thead><tbody>@foreach($repair->workOrders as $wo)<tr><td><code>{{ $wo->order_number }}</code><div class="small text-muted">{{ $wo->scope_of_work }}</div></td><td class="small">{{ $wo->assignee?->name ?? '-' }}</td><td class="small">{{ $wo->scheduled_date?->format('d/m/Y') ?? '-' }}</td><td><span class="badge bg-info-subtle text-info">{{ ucfirst(str_replace('_',' ', $wo->status)) }}</span></td><td class="text-end small">Rp {{ number_format($wo->total_cost,0,',','.') }}</td></tr>@endforeach</tbody></table></div>
                    @else
                        <div class="p-3"><x-empty-state title="Belum ada work order" message="URT belum menjadwalkan perbaikan untuk permintaan ini." /></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-primary">
                <div class="card-header bg-primary-subtle"><h5 class="card-title mb-0 text-primary"><i class="ri-flow-chart me-1"></i>Alur Perbaikan</h5></div>
                <div class="card-body"><ol class="small text-muted ps-3 mb-0"><li class="mb-2">Unit melaporkan kerusakan.</li><li class="mb-2">Sistem membuat repair request otomatis.</li><li class="mb-2">URT memverifikasi dan menugaskan petugas.</li><li class="mb-2">Work order dijadwalkan.</li><li class="mb-2">Petugas mencatat pekerjaan, biaya, dan sparepart.</li><li>Unit melihat hasil pada passport aset via QR.</li></ol></div>
            </div>
        </div>
    </div>

    @if($canManage)
        <div class="modal fade" id="workOrderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.repair.work-order', ['repairId' => $repair->id]) }}" class="modal-content">@csrf
                <div class="modal-header"><h5 class="modal-title"><i class="ri-calendar-check-line me-1"></i>Jadwalkan Perbaikan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Petugas <span class="text-danger">*</span></label><select name="assignee_id" class="form-select" required><option value="">— Pilih —</option>@foreach($technicians as $technician)<option value="{{ $technician->id }}">{{ $technician->name }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label">Tanggal Pelaksanaan <span class="text-danger">*</span></label><input type="date" name="scheduled_date" class="form-control" required min="{{ date('Y-m-d') }}"></div>
                    <div class="mb-3"><label class="form-label">Jenis Pekerjaan <span class="text-danger">*</span></label><select name="type" class="form-select" required><option value="internal">Internal URT</option><option value="vendor">Vendor / Pihak Ketiga</option></select></div>
                    <div class="mb-3"><label class="form-label">Lingkup Pekerjaan <span class="text-danger">*</span></label><textarea name="scope_of_work" class="form-control" rows="3" required placeholder="Contoh: servis AC, ganti kapasitor, cek freon…"></textarea></div>
                    <div><label class="form-label">Estimasi Biaya (Rp)</label><input type="number" name="estimated_cost" class="form-control" min="0" placeholder="0"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Buat Work Order</button></div>
            </form></div>
        </div>
    @endif
@endsection
