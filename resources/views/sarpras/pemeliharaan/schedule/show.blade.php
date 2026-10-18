@extends('layouts.master')
@section('title') Detail Jadwal Pemeliharaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}">Jadwal Pemeliharaan</a> @endslot
        @slot('title') Detail Jadwal
    @endcomponent

    @php
        $isOverdue = $schedule->is_active && $schedule->next_maintenance_date && $schedule->next_maintenance_date->isPast();
        $isSoon = $schedule->is_active && $schedule->next_maintenance_date && $schedule->next_maintenance_date->diffInDays(now()) <= 7;
        $approvalTone = ['pending'=>'warning','approved'=>'success','reschedule_requested'=>'info','rejected'=>'danger'][$schedule->unit_approval_status] ?? 'secondary';
    @endphp

    <div class="d-flex gap-2 flex-wrap mb-3">
        @if($isOverdue)<span class="badge bg-danger fs-6"><i class="ri-error-warning-line me-1"></i>TERLAMBAT</span>@elseif($isSoon)<span class="badge bg-warning fs-6"><i class="ri-time-line me-1"></i>MENDEKATI</span>@else<span class="badge bg-success fs-6"><i class="ri-check-line me-1"></i>TERJADWAL</span>@endif
        <span class="badge bg-{{ $approvalTone }}-subtle text-{{ $approvalTone }} fs-6">{{ $schedule->approval_label }}</span>
        @if(! $schedule->is_active)<span class="badge bg-secondary fs-6">NONAKTIF</span>@endif
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0">Detail Jadwal Pemeliharaan</h5>
                    <div class="d-flex gap-1 flex-wrap">
                        @if($canManage)
                            <a href="{{ route('sarpras.pemeliharaan.schedule.edit', $schedule->id) }}" class="btn btn-warning btn-sm"><i class="ri-pencil-line me-1"></i>Edit</a>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteScheduleModal"><i class="ri-delete-bin-line"></i></button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr><td class="text-muted fw-medium" style="width:210px">Jenis Perawatan</td><td><strong>{{ $schedule->maintenance_type }}</strong></td></tr>
                                <tr><td class="text-muted fw-medium">Satuan Kerja</td><td>{{ $schedule->workUnit?->name ?? '-' }}</td></tr>
                                <tr><td class="text-muted fw-medium">Target</td><td>@if($schedule->asset)<span class="badge bg-primary-subtle text-primary me-1">Aset</span><a href="{{ route('sarpras.aset.show', $schedule->asset_id) }}" class="fw-medium">{{ $schedule->asset->asset_name }}</a><br><code class="small text-muted">{{ $schedule->asset->asset_code }}</code>@elseif($schedule->room)<span class="badge bg-info-subtle text-info me-1">Ruang</span>{{ $schedule->room->room_name }}@if($schedule->room->building)<small class="text-muted"> — {{ $schedule->room->building->building_name }}</small>@endif @elseif($schedule->building)<span class="badge bg-secondary-subtle text-secondary me-1">Gedung</span>{{ $schedule->building->building_name }}@endif</td></tr>
                                <tr><td class="text-muted fw-medium">Frekuensi</td><td>{{ ucfirst(str_replace('_',' ', $schedule->frequency)) }}</td></tr>
                                <tr><td class="text-muted fw-medium">Tanggal Terakhir</td><td>{{ $schedule->last_maintenance_date?->format('d/m/Y') ?? 'Belum pernah' }}</td></tr>
                                <tr><td class="text-muted fw-medium">Jadwal Berikutnya</td><td><strong class="{{ $isOverdue ? 'text-danger' : '' }}">{{ $schedule->next_maintenance_date?->format('d/m/Y') }}</strong> @if($schedule->next_maintenance_date)<span class="text-muted small">({{ $schedule->next_maintenance_date->diffForHumans() }})</span>@endif</td></tr>
                                <tr><td class="text-muted fw-medium">Penanggung Jawab</td><td>{{ $schedule->responsibleUser?->name ?? '-' }}</td></tr>
                                <tr><td class="text-muted fw-medium">Vendor / Penyedia Jasa</td><td>{{ $schedule->vendor_name ?? '-' }}</td></tr>
                                <tr><td class="text-muted fw-medium">Estimasi Biaya</td><td>{{ $schedule->estimated_cost ? 'Rp '.number_format($schedule->estimated_cost,0,',','.') : '-' }}</td></tr>
                                <tr><td class="text-muted fw-medium">Pengingat</td><td>{{ $schedule->reminder_days_before ? $schedule->reminder_days_before.' hari sebelumnya' : '-' }}</td></tr>
                                <tr><td class="text-muted fw-medium">Persetujuan Satuan Kerja</td><td><span class="badge bg-{{ $approvalTone }}-subtle text-{{ $approvalTone }}">{{ $schedule->approval_label }}</span>@if($schedule->unitApprover)<div class="small text-muted mt-1">Oleh {{ $schedule->unitApprover->name }} · {{ $schedule->unit_approved_at?->format('d/m/Y H:i') }}</div>@endif @if($schedule->unit_proposed_date)<div class="small text-info mt-1">Usulan tanggal: {{ $schedule->unit_proposed_date->format('d/m/Y') }}</div>@endif @if($schedule->unit_approval_note)<div class="small text-muted mt-1">Catatan: {{ $schedule->unit_approval_note }}</div>@endif</td></tr>
                                @if($schedule->notes)<tr><td class="text-muted fw-medium">Catatan URT</td><td>{{ $schedule->notes }}</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($canApprove && in_array($schedule->unit_approval_status, ['pending', 'reschedule_requested'], true))
                <div class="card mt-3 border-warning">
                    <div class="card-header bg-warning-subtle"><h5 class="card-title mb-0 text-warning"><i class="ri-checkbox-circle-line me-1"></i>Tindakan Satuan Kerja</h5></div>
                    <div class="card-body">
                        <p class="text-muted small">Satuan kerja meninjau jadwal yang disusun URT sebelum pekerjaan dijadwalkan.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveScheduleModal"><i class="ri-check-line me-1"></i>Setujui Jadwal</button>
                            <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#rescheduleScheduleModal"><i class="ri-calendar-2-line me-1"></i>Minta Jadwal Ulang</button>
                            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectScheduleModal"><i class="ri-close-line me-1"></i>Tolak</button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mt-3 border-info">
                <div class="card-header bg-info-subtle d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0 text-info"><i class="ri-clipboard-check-line me-1"></i>Checklist Pemeliharaan</h5>
                    <a href="{{ route('sarpras.checklist.start', $schedule->id) }}" class="btn btn-info btn-sm text-white"><i class="ri-play-line me-1"></i>Mulai Checklist</a>
                </div>
                <div class="card-body p-0">
                    @forelse($checklistInstances as $checklist)
                        @php $tone = ['in_progress'=>'warning','completed'=>'success','failed'=>'danger','cancelled'=>'secondary'][$checklist->status] ?? 'secondary'; @endphp
                        <a href="{{ route('sarpras.checklist.instance', $checklist->id) }}" class="d-flex justify-content-between align-items-center border-bottom px-3 py-2 text-decoration-none text-dark">
                            <div><div class="small fw-medium">{{ $checklist->template?->name ?? '-' }}</div><div class="text-muted" style="font-size:.7rem">{{ $checklist->created_at?->format('d/m/Y H:i') }} · {{ $checklist->executor?->name ?? '-' }}</div></div>
                            <div class="text-end"><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst(str_replace('_',' ', $checklist->status)) }}</span><div class="text-muted" style="font-size:.7rem">{{ $checklist->failed_items_count }} temuan</div></div>
                        </a>
                    @empty
                        <div class="p-3 text-center text-muted small"><i class="ri-clipboard-line fs-3 d-block mb-1"></i>Belum ada checklist untuk jadwal ini.</div>
                    @endforelse
                </div>
            </div>

            @if($canManage)
                <div class="card mt-3 border-primary">
                    <div class="card-header bg-primary-subtle"><h5 class="card-title mb-0 text-primary"><i class="ri-tools-line me-1"></i>Catat Perawatan</h5></div>
                    <div class="card-body"><p class="text-muted small">Catat perawatan agar jadwal berikutnya, biaya, dan suku cadang tercatat pada passport aset.</p><a href="{{ route('sarpras.pemeliharaan.log.create') }}?schedule_id={{ $schedule->id }}" class="btn btn-primary"><i class="ri-add-line me-1"></i>Catat Perawatan Baru</a></div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-history-line me-1"></i>Riwayat Perawatan</h5></div>
                <div class="card-body p-0">
                    @if($schedule->logs && $schedule->logs->isNotEmpty())
                        <div class="list-group list-group-flush">
                            @foreach($schedule->logs->sortByDesc('maintenance_date') as $log)
                                <a href="{{ route('sarpras.pemeliharaan.log.show', $log->id) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start"><div><small class="fw-medium">{{ $log->maintenance_type }}</small><br><small class="text-muted">{{ $log->maintenance_date?->format('d/m/Y') }}</small></div><div class="text-end">@if($log->actual_cost)<small class="text-muted">Rp {{ number_format($log->actual_cost,0,',','.') }}</small><br>@endif<span class="badge bg-{{ $log->condition_after === 'baik' ? 'success' : 'warning' }}-subtle small">{{ ucfirst(str_replace('_',' ', $log->condition_after ?? '-')) }}</span></div></div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4"><i class="ri-inbox-archive-line fs-2 text-muted"></i><p class="text-muted small mb-0">Belum ada riwayat perawatan.</p></div>
                    @endif
                </div>
                <div class="card-footer bg-transparent border-top"><a href="{{ route('sarpras.pemeliharaan.log.index') }}?schedule_id={{ $schedule->id }}" class="btn btn-outline-secondary btn-sm w-100">Lihat Semua Riwayat</a></div>
            </div>
        </div>
    </div>

    @if($canManage)
        <x-modal-confirm id="deleteScheduleModal" icon="https://cdn.lordicon.com/gsqxdxog.json" title="Hapus Jadwal?" submitLabel="Ya, Hapus" submitIcon="ri-delete-bin-line" submitClass="btn-danger" formId="deleteScheduleForm"><p class="text-center text-muted mb-0">Jadwal pemeliharaan ini akan dihapus.</p></x-modal-confirm>
        <form id="deleteScheduleForm" method="POST" action="{{ route('sarpras.pemeliharaan.schedule.destroy', $schedule->id) }}" class="d-none">@csrf @method('DELETE')</form>
    @endif

    @if($canApprove && in_array($schedule->unit_approval_status, ['pending', 'reschedule_requested'], true))
        <x-modal-confirm id="approveScheduleModal" icon="https://cdn.lordicon.com/lupuorrc.json" title="Setujui Jadwal?" submitLabel="Ya, Setujui" submitIcon="ri-check-line" submitClass="btn-success" formId="approveScheduleForm">
            <p class="text-center text-muted">Jadwal akan disetujui untuk satuan kerja {{ $schedule->workUnit?->name }}.</p>
            <textarea name="unit_approval_note" form="approveScheduleForm" class="form-control" rows="2" placeholder="Catatan (opsional)…"></textarea>
        </x-modal-confirm>
        <form id="approveScheduleForm" method="POST" action="{{ route('sarpras.pemeliharaan.schedule.approve', $schedule->id) }}" class="d-none">@csrf</form>

        <x-modal-confirm id="rescheduleScheduleModal" icon="https://cdn.lordicon.com/lupuorrc.json" title="Minta Jadwal Ulang" submitLabel="Kirim Usulan" submitIcon="ri-calendar-2-line" submitClass="btn-info" formId="rescheduleScheduleForm">
            <p class="text-center text-muted">Ajukan tanggal baru untuk ditinjau URT.</p>
            <label class="form-label">Tanggal Usulan <span class="text-danger">*</span></label>
            <input type="date" name="unit_proposed_date" form="rescheduleScheduleForm" class="form-control mb-2" required min="{{ date('Y-m-d') }}">
            <label class="form-label">Alasan <span class="text-danger">*</span></label>
            <textarea name="unit_approval_note" form="rescheduleScheduleForm" class="form-control" rows="2" required placeholder="Alasan jadwal ulang…"></textarea>
        </x-modal-confirm>
        <form id="rescheduleScheduleForm" method="POST" action="{{ route('sarpras.pemeliharaan.schedule.reschedule', $schedule->id) }}" class="d-none">@csrf</form>

        <x-modal-confirm id="rejectScheduleModal" icon="https://cdn.lordicon.com/gsqxdxog.json" title="Tolak Jadwal?" submitLabel="Ya, Tolak" submitIcon="ri-close-line" submitClass="btn-danger" formId="rejectScheduleForm">
            <p class="text-center text-muted">Jadwal akan ditolak dan URT perlu menyusun ulang.</p>
            <label class="form-label">Alasan <span class="text-danger">*</span></label>
            <textarea name="unit_approval_note" form="rejectScheduleForm" class="form-control" rows="2" required placeholder="Alasan penolakan…"></textarea>
        </x-modal-confirm>
        <form id="rejectScheduleForm" method="POST" action="{{ route('sarpras.pemeliharaan.schedule.reject', $schedule->id) }}" class="d-none">@csrf</form>
    @endif
@endsection
