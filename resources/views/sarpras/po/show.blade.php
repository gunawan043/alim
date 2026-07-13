@extends('layouts.sarpras')

@section('title', 'Detail Purchase Order')

@section('content')
<div class="card mb-3">
    <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $po->po_number }}</h5>
        <a href="{{ route('sarpras.po.index') }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Informasi Vendor</h6>
                <div class="mb-1"><strong>{{ $po->vendor?->name ?? '-' }}</strong></div>
                @if($po->vendor?->phone)
                    <div class="text-muted small">{{ $po->vendor->phone }} · {{ $po->vendor->email }}</div>
                @endif
                @if($po->vendor?->legal_name)
                    <div class="text-muted small">NPWP: {{ $po->vendor->npwp }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Timeline</h6>
                <div class="row small">
                    <div class="col-6">Order: <strong>{{ $po->order_date?->format('d M Y') ?? '-' }}</strong></div>
                    <div class="col-6">Diharapkan: <strong>{{ $po->expected_date?->format('d M Y') ?? '-' }}</strong></div>
                    <div class="col-6">Diterima: <strong>{{ $po->received_date?->format('d M Y') ?? '-' }}</strong></div>
                    <div class="col-6">Currency: <strong>{{ $po->currency ?? 'IDR' }}</strong></div>
                    <div class="col-6">Term Pembayaran: <strong>{{ $po->payment_term_days ?? 0 }} hari</strong></div>
                    <div class="col-6">Incoterms: <strong>{{ $po->incoterms ?? '-' }}</strong></div>
                </div>
            </div>
        </div>

        <h6 class="mb-2">Daftar Item</h6>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                        <th>Diterima</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($po->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-end">{{ number_format((float)$item->quantity, 2, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float)$item->line_total, 0, ',', '.') }}</td>
                        <td>{{ number_format((float)$item->received_quantity, 2, ',', '.') }}</td>
                        <td>
                            @if($item->received_quantity >= $item->quantity)
                                <span class="badge bg-success">Lengkap</span>
                            @elseif($item->received_quantity > 0)
                                <span class="badge bg-warning">Sebagian</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                @if($po->notes)
                <h6 class="text-muted">Catatan</h6>
                <p class="text-muted">{{ $po->notes }}</p>
                @endif
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><td>Subtotal</td><td class="text-end">Rp {{ number_format((float)$po->subtotal, 0, ',', '.') }}</td></tr>
                    <tr><td>Pajak</td><td class="text-end">Rp {{ number_format((float)$po->tax, 0, ',', '.') }}</td></tr>
                    <tr><td>Diskon</td><td class="text-end">- Rp {{ number_format((float)$po->discount, 0, ',', '.') }}</td></tr>
                    <tr><td>Pengiriman</td><td class="text-end">Rp {{ number_format((float)$po->shipping, 0, ',', '.') }}</td></tr>
                    <tr class="fw-bold fs-5"><td>Total</td><td class="text-end">Rp {{ number_format((float)$po->total, 0, ',', '.') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        @if(in_array($po->status, ['draft', 'submitted']))
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="bi bi-check-circle me-1"></i>Setujui
            </button>
        @endif
        @if(!in_array($po->status, ['received', 'cancelled']))
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#receiveModal">
                <i class="bi bi-truck me-1"></i>Terima Barang
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                <i class="bi bi-x-circle me-1"></i>Batalkan
            </button>
        @endif
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('sarpras.po.approve', $po) }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Setujui PO</h5></div>
                <div class="modal-body">
                    <p>PO ini akan disetujui dan siap untuk diterima.</p>
                    <label class="form-label">Catatan Persetujuan (opsional)</label>
                    <textarea name="approval_note" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('sarpras.po.cancel', $po) }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Batalkan PO</h5></div>
                <div class="modal-body">
                    <label class="form-label">Alasan Pembatalan</label>
                    <textarea name="cancel_reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger">Batalkan PO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Receive Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('sarpras.po.receive', $po) }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Penerimaan Barang</h5></div>
                <div class="modal-body">
                    <p class="text-muted">Masukkan jumlah yang diterima untuk setiap item.</p>
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Item</th><th>Dipesan</th><th>Sudah Diterima</th><th>Kurang</th><th>Jml Sekarang</th></tr></thead>
                        <tbody>
                            @foreach($po->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td>{{ number_format((float)$item->quantity, 2, ',', '.') }}</td>
                                <td>{{ number_format((float)$item->received_quantity, 2, ',', '.') }}</td>
                                <td>{{ number_format((float)($item->quantity - $item->received_quantity), 2, ',', '.') }}</td>
                                <td>
                                    <input type="number" name="items[{{ $item->id }}][received_quantity]" class="form-control form-control-sm" step="0.01" min="0" max="{{ $item->quantity - $item->received_quantity }}" value="0">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Konfirmasi Penerimaan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
