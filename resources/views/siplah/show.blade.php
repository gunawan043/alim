@extends('layouts.master')
@section('title', 'Detail Pengajuan SIPLah')

@section('content')
    @php
        $status = $proposal['status'] ?? 'draft';
        $canSubmit = $status === 'draft';
        $canApprove = ($canApprove ?? false) && in_array($status, ['submitted', 'draft'], true);
        $canSend = $status === 'approved';
    @endphp
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.siplah.index', ['userId'=>$userId]) }}">Pengajuan SIPLah</a> @endslot
        @slot('title') {{ $proposal['number'] ?? 'Detail' }} @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
                    <div><h5 class="card-title mb-0">{{ $proposal['program_name'] ?? 'Pengajuan SIPLah' }}</h5><div class="text-muted small font-monospace">{{ $proposal['number'] ?? '' }}</div></div>
                    <div class="text-end"><span class="badge bg-info-subtle text-info">{{ $proposal['status_label'] ?? $status }}</span><div class="small text-muted">BUMP: {{ $proposal['bump_status_label'] ?? '—' }}</div></div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><div class="text-muted small">Unit</div><div>{{ data_get($proposal,'business_unit.name','—') }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Prioritas</div><div>{{ ucfirst($proposal['priority'] ?? 'normal') }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Waktu Kebutuhan</div><div>{{ $proposal['needed_at'] ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Sumber Anggaran</div><div>{{ $proposal['budget_source'] ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Penanggung Jawab</div><div>{{ $proposal['requester_name'] ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">No. WhatsApp</div><div>{{ $proposal['requester_phone'] ?? '—' }}</div></div>
                    </div>
                    <h6 class="fw-semibold">Alasan / Tujuan</h6>
                    <p class="text-muted" style="white-space:pre-wrap">{{ $proposal['purpose'] ?? '—' }}</p>
                    @if($proposal['notes'] ?? null)<div class="alert alert-light border small">{{ $proposal['notes'] }}</div>@endif

                    <h6 class="fw-semibold mt-4">Rincian Barang</h6>
                    <div class="table-responsive"><table class="table table-sm align-middle"><thead class="table-light"><tr><th>Barang</th><th>Spesifikasi</th><th class="text-center">Qty</th><th class="text-end">Estimasi</th><th class="text-center">Disetujui</th><th class="text-center">Dipenuhi</th><th class="text-center">Backorder</th></tr></thead><tbody>
                        @forelse(($proposal['items'] ?? []) as $item)
                            <tr><td><div class="fw-medium">{{ $item['name'] }}</div><div class="small text-muted">{{ $item['category'] ?? '' }}</div></td><td class="small">{{ $item['specification'] ?? '—' }}</td><td class="text-center">{{ $item['qty'] }} {{ $item['unit'] }}</td><td class="text-end">Rp {{ number_format((float)($item['total'] ?? 0),0,',','.') }}</td><td class="text-center">{{ $item['qty_approved'] ?? '—' }}</td><td class="text-center">{{ $item['qty_fulfilled'] ?? '—' }}</td><td class="text-center">{{ $item['qty_backorder'] ?? '—' }}</td></tr>
                        @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada item.</td></tr>@endforelse
                    </tbody></table></div>

                    @if($proposal['attachment_url'] ?? null)
                        <a href="{{ $proposal['attachment_url'] }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="ri-attachment-2 me-1"></i>{{ $proposal['attachment_name'] ?? 'Dokumen Pendukung' }}</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Ringkasan Nilai</h5></div>
                <div class="card-body">
                    @foreach(['Estimasi Pengajuan'=>$proposal['estimated_total'] ?? 0,'Anggaran Disetujui'=>$proposal['approved_total'] ?? null,'Harga Final'=>$proposal['final_total'] ?? null,'Nilai Realisasi'=>$proposal['realized_total'] ?? null] as $label=>$value)
                        <div class="d-flex justify-content-between py-1"><span class="text-muted small">{{ $label }}</span><strong>Rp {{ number_format((float)($value ?? 0),0,',','.') }}</strong></div>
                    @endforeach
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Aksi</h5></div>
                <div class="card-body d-grid gap-2">
                    @if($canSubmit)
                        <form method="POST" action="{{ route('user.siplah.action',['userId'=>$userId,'number'=>$proposal['number'],'action'=>'submit']) }}">@csrf<button class="btn btn-primary w-100"><i class="ri-send-plane-line me-1"></i>Ajukan untuk Persetujuan</button></form>
                    @endif
                    @if($canApprove)
                        <form method="POST" action="{{ route('user.siplah.action',['userId'=>$userId,'number'=>$proposal['number'],'action'=>'approve']) }}">@csrf<button class="btn btn-success w-100"><i class="ri-check-line me-1"></i>Setujui</button></form>
                        <form method="POST" action="{{ route('user.siplah.action',['userId'=>$userId,'number'=>$proposal['number'],'action'=>'reject']) }}">@csrf<input type="hidden" name="reason" value="Ditolak oleh pejabat berwenang."><button class="btn btn-outline-danger w-100"><i class="ri-close-line me-1"></i>Tolak</button></form>
                    @endif
                    @if($canSend)
                        <form method="POST" action="{{ route('user.siplah.action',['userId'=>$userId,'number'=>$proposal['number'],'action'=>'send']) }}">@csrf<button class="btn btn-primary w-100"><i class="ri-send-plane-line me-1"></i>Kirim ke BUMP</button></form>
                    @endif
                    @if(in_array($status,['sent','processing','partially_fulfilled'],true))
                        <a href="{{ route('user.siplah.show',['userId'=>$userId,'number'=>$proposal['number']]) }}" class="btn btn-soft-primary w-100"><i class="ri-refresh-line me-1"></i>Perbarui Status dari BUMP</a>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Linimasa</h5></div>
                <div class="card-body">
                    @forelse(($proposal['logs'] ?? []) as $log)
                        <div class="d-flex gap-2 mb-3"><i class="ri-checkbox-circle-line text-primary"></i><div><div class="fw-semibold small">{{ $log['status_label'] ?? $log['status'] }}</div><div class="small text-muted">{{ $log['note'] ?? '' }}</div><div class="text-muted" style="font-size:.7rem">{{ $log['at'] ?? '' }}</div></div></div>
                    @empty<p class="text-muted small mb-0">Belum ada aktivitas.</p>@endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
