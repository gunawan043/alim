@extends('layouts.master')
@section('title') Persetujuan Cuti @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 100%);border:1px solid #bfdbfe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);border-color:#4338ca}
.stat-card{transition:all .25s ease;cursor:default;border-left:3px solid transparent}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.stat-card.pending{border-left-color:#f59e0b}
.stat-card.approved{border-left-color:#10b981}
.stat-card.total{border-left-color:#6366f1}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze th:first-child,.table-freeze td:first-child{position:sticky;left:0;z-index:10;background:#fff;min-width:200px;box-shadow:2px 0 5px rgba(0,0,0,.05)}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
[data-bs-theme="dark"] .table-freeze th:first-child,[data-bs-theme="dark"] .table-freeze td:first-child{background:#1e293b}
[data-bs-theme="dark"] .table-freeze thead th{background:#1e293b}
@media print{.no-print{display:none!important}}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Cuti & Izin @endslot
    @slot('title') Persetujuan @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Persetujuan Cuti & Izin</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Tinjau dan setujui atau tolak pengajuan cuti GTK</p>
    </div>
    <a href="{{ route('user.cuti.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card pending h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded-3 fs-2"><i class="ri-time-line text-warning"></i></span></div>
                    <div><p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:.5px">Menunggu</p><h3 class="fw-bold ff-secondary mb-0">{{ $pending->count() }}</h3></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card approved h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded-3 fs-2"><i class="ri-checkbox-circle-line text-success"></i></span></div>
                    <div><p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:.5px">Disetujui</p><h3 class="fw-bold ff-secondary mb-0">—</h3></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card total h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-danger-subtle rounded-3 fs-2"><i class="ri-close-circle-line text-danger"></i></span></div>
                    <div><p class="text-uppercase fw-medium text-muted mb-1" style="font-size:10px;letter-spacing:.5px">Ditolak</p><h3 class="fw-bold ff-secondary mb-0">—</h3></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <h5 class="card-title mb-0"><i class="ri-git-pull-request-line me-1 text-primary"></i> Daftar Pengajuan Menunggu</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>GTK</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Durasi</th>
                        <th>Alasan</th>
                        <th width="200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pending as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-light text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;font-size:.75rem;font-weight:600">{{ strtoupper(substr($p->user->name ?? 'N', 0, 2)) }}</div>
                                <div><div class="fw-semibold" style="font-size:.875rem">{{ $p->user->name ?? '-' }}</div><div class="text-muted" style="font-size:.75rem">{{ $p->user->nik ?? '-' }}</div></div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $p->template->nama ?? '-' }}</span></td>
                        <td style="font-size:.85rem">{{ $p->tanggal_mulai->format('d M') }} - {{ $p->tanggal_selesai->format('d M Y') }}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $p->jumlah_hari }} hari</span></td>
                        <td style="font-size:.8rem;color:#6b7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p->alasan ?? '-' }}</td>
                        <td class="no-print">
                            <div class="d-flex gap-2 align-items-center">
                                <form method="POST" action="{{ route('user.cuti.approve', ['userId' => $userId, 'id' => $p->id]) }}" class="d-inline flex-grow-1">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Setujui pengajuan ini?')">
                                        <i class="ri-check-line me-1"></i> Setuju
                                    </button>
                                </form>
                                <button class="btn btn-outline-danger btn-sm" onclick="showRejectModal('{{ $p->id }}')">
                                    <i class="ri-close-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5">
                        <i class="ri-checkbox-circle-line text-muted" style="font-size:3rem"></i>
                        <h6 class="mt-2 text-muted">Tidak ada pengajuan menunggu</h6>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pending->hasPages())
        <div class="px-3 py-2 border-top no-print">{{ $pending->withQueryString()->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>

@component('components.modal')
    @slot('id') rejectModal @endslot
    @slot('title') <i class="ri-close-line me-1"></i> Tolak Pengajuan Cuti @endslot
    <form method="POST" id="rejectForm">
        @csrf
        <div class="mb-3">
            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger"><i class="ri-close-line me-1"></i> Tolak</button>
        </div>
    </form>
@endcomponent
@endsection

@section('js')
<script>
function showRejectModal(id) {
    document.getElementById('rejectForm').action = '{{ route("user.cuti.reject", ["userId" => $userId, "id" => ""]) }}/' + id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endsection