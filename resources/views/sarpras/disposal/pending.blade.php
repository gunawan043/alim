@extends('layouts.master')
@section('title', 'Penghapusan Aset')

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Pending Penghapusan @endslot
@endcomponent

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fs-4">Pending Penghapusan Aset</h4>
    <span class="badge bg-warning text-dark">{{ $candidates->where('condition', 'dihapus')->count() }} asset(s) menunggu persetujuan</span>
</div>

<div class="row g-4">
    @forelse($candidates as $asset)
    <div class="col-xl-4 col-lg-6">
        <div class="card card-h-100 border-start border-3 {{ $asset->condition === 'dihapus' ? 'border-warning' : 'border-danger' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-medium fs-14">{{ $asset->asset_name }}</h6>
                        <p class="text-muted mb-0">Kode: {{ $asset->asset_code }}</p>
                    </div>
                    <span class="badge {{ $asset->condition === 'dihapus' ? 'bg-warning' : 'bg-danger' }}">
                        {{ $asset->condition === 'dihapus' ? 'Sudah Diusulkan' : 'Perlu Dihapus' }}
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Kategori: {{ $asset->category->name ?? '-' }}</small><br>
                    <small class="text-muted">Ruang: {{ $asset->room->nama_ruang ?? '-' }}</small><br>
                    <small class="text-muted">Nilai Aset: Rp {{ number_format($asset->acquisition_price ?? $asset->current_value ?? 0, 0, ',', '.') }}</small><br>
                    <small class="text-muted">Kondisi Terakhir: {{ $asset->condition }}</small>
                </div>

                @if($asset->disposal_method)
                <div class="mb-3 p-2 bg-light rounded">
                    <small><strong>Metode:</strong> {{ ucfirst(str_replace('_', ' ', $asset->disposal_method)) }}</small><br>
                    @if($asset->disposal_reason)
                    <small><strong>Alasan:</strong> {{ Str::limit($asset->disposal_reason, 60) }}</small>
                    @endif
                </div>
                @endif

                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-success btn-lg flex-grow-1" onclick="approveAsset({{ $asset->id }})">
                        <i class="ri-check-line me-1"></i>Approve
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="rejectAsset({{ $asset->id }})">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info">Tidak ada aset yang diusulkan penghapusannya.</div>
    </div>
    @endforelse
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Penghapusan Aset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Metode Penghapusan <span class="text-danger">*</span></label>
                        <select name="disposal_method" class="form-select" required>
                            <option value="">Pilih...</option>
                            <option value="sell">Jual</option>
                            <option value="scrap">Scrap</option>
                            <option value="transfer">Transfer ke Instansi Lain</option>
                            <option value="donate">Donasi</option>
                            <option value="destroy">Dimusnahkan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penghapusan <span class="text-danger">*</span></label>
                        <textarea name="disposal_reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nilai Penjualan/Scrap</label>
                        <input type="number" name="disposal_value" class="form-control" min="0" step="1000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentAssetId = null;
const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));

function approveAsset(id) {
    currentAssetId = id;
    document.getElementById('approveForm').action = '{{ url("sarpras") }}/disposal/' + id + '/approve';
    approveModal.show();
}

function rejectAsset(id) {
    if (confirm('Reject penghapusan aset ini? Aset akan dikembalikan ke kondisi baik.')) {
        fetch('{{ url("sarpras") }}/disposal/' + id + '/reject', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ reason: 'Rejected by admin' })
        }).then(() => location.reload());
    }
}
</script>
@endpush