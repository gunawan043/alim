@extends('layouts.sarpras')

@section('title', 'Pembuatan Purchase Order')

@section('content')
@php
    $generatedPoNumber = 'PO-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
@endphp
<div class="card">
    <div class="card-header bg-gradient-primary text-white">
        <h5 class="mb-0">Buat Purchase Order</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('sarpras.po.store') }}" id="poForm">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">PO Number (Auto)</label>
                    <input type="text" class="form-control" value="{{ $generatedPoNumber }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vendor <span class="text-danger">*</span></label>
                    <select name="vendor_id" class="form-select" required>
                        <option value="">-- Pilih Vendor --</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expected Delivery</label>
                    <input type="date" name="expected_date" class="form-control" value="{{ old('expected_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Term (hari)</label>
                    <input type="number" name="payment_term_days" class="form-control" value="{{ old('payment_term_days', 30) }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Incoterms</label>
                    <input type="text" name="incoterms" class="form-control" value="{{ old('incoterms') }}" placeholder="FOB / CIF / DDP / dll">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                </div>
            </div>
            <hr>

            <h6>Items</h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="poItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40%">Sparepart</th>
                            <th width="15%">Kuantitas</th>
                            <th width="15%">Harga Satuan</th>
                            <th width="15%">Subtotal</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic rows via JS -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td id="grandTotal" class="text-end fw-bold">Rp 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addRow()">
                <i class="bi bi-plus"></i> Tambah Baris
            </button>
            <div class="d-grid d-md-flex justify-content-md-end gap-2">
                <a href="{{ route('sarpras.po.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Buat Purchase Order</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let rowCount = 0;
const sparepartOptions = `@foreach($spareparts as $s)<option value="{{ $s->id }}">{{ addslashes($s->name) }} (Stok: {{ $s->stock_quantity ?? 0 }})</option>@endforeach`;

function addRow() {
    const tbody = document.querySelector('#poItemsTable tbody');
    const tr = document.createElement('tr');
    tr.id = `row-${rowCount}`;
    tr.innerHTML = `
        <td><select name="items[${rowCount}][sparepart_id]" class="form-select" required>
            <option value="">-- Pilih --</option>${sparepartOptions}
        </select></td>
        <td><input type="number" name="items[${rowCount}][quantity]" class="form-control qty-input" min="0.01" step="0.01" required oninput="calc()"></td>
        <td><input type="number" name="items[${rowCount}][unit_price]" class="form-control price-input" min="0" required oninput="calc()"></td>
        <td class="subtotal text-end">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(${rowCount})">×</button></td>
    `;
    tbody.appendChild(tr);
    rowCount++;
}
function removeRow(id) {
    document.getElementById(`row-${id}`).remove();
    calc();
}
function calc() {
    let total = 0;
    document.querySelectorAll('#poItemsTable tbody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        const sub = qty * price;
        row.querySelector('.subtotal').textContent = 'Rp ' + sub.toLocaleString('id-ID');
        total += sub;
    });
    document.getElementById('grandTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
</script>
@endpush