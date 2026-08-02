@extends('vendor.layouts.app')
@section('title', 'Buat Permintaan Pengadaan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Permintaan Pengadaan Baru</h4>
    <a href="{{ route('vendor.procurement.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('vendor.procurement.store') }}">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tujuan</label>
                    <input type="text" name="purpose" class="form-control" required placeholder="Contoh: Pembelian ATK untuk semester genap">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Urgensi</label>
                    <select name="urgency" class="form-select" required>
                        <option value="rendah">Rendah</option>
                        <option value="normal" selected>Normal</option>
                        <option value="tinggi">Tinggi</option>
                        <option value="mendesak">Mendesak</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sumber Anggaran</label>
                    <input type="text" name="budget_source" class="form-control" required placeholder="Dana Sekolah / BOS / Swasta">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Metode Pengadaan</label>
                    <select name="procurement_method" class="form-select" required>
                        <option value="pengadaan_langsung">Pengadaan Langsung</option>
                        <option value="tender">Tender</option>
                        <option value="minta_penawaran">Minta Penawaran</option>
                        <option value="pemilihan_terbatas">Pemilihan Terbatas</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Pengiriman</label>
                    <input type="date" name="delivery_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Total Estimasi Anggaran (Rp)</label>
                    <input type="number" name="total_estimated_budget" class="form-control" required min="0" step="1000">
                </div>
            </div>

            <hr>
            <h6 class="mb-3">Item Barang/Jasa</h6>
            <div id="items-container">
                <div class="item-row border rounded p-3 mb-3">
                    <div class="row g-2">
                        <div class="col-md-3"><input type="text" name="items[0][item_name]" class="form-control" placeholder="Nama item" required></div>
                        <div class="col-md-2"><input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" required min="1"></div>
                        <div class="col-md-2"><input type="text" name="items[0][unit]" class="form-control" placeholder="Satuan" required></div>
                        <div class="col-md-3"><input type="number" name="items[0][estimated_unit_price]" class="form-control" placeholder="Harga/Unit" required min="0" step="1000"></div>
                        <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-remove-item w-100"><i class="ri-delete-bin-line"></i></button></div>
                    </div>
                </div>
            </div>
            <button type="button" id="add-item" class="btn btn-outline-primary btn-sm"><i class="ri-add-line"></i> Tambah Item</button>

            <hr>
            <div class="mb-3">
                <label class="form-label">Catatan Tambahan</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('vendor.procurement.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Draft</button>
            </div>
        </form>
    </div>
</div>

<script>
let itemCount = 1;
document.getElementById('add-item').addEventListener('click', function() {
    const row = document.createElement('div');
    row.className = 'item-row border rounded p-3 mb-3';
    row.innerHTML = `
        <div class="row g-2">
            <div class="col-md-3"><input type="text" name="items[${itemCount}][item_name]" class="form-control" placeholder="Nama item" required></div>
            <div class="col-md-2"><input type="number" name="items[${itemCount}][quantity]" class="form-control" placeholder="Qty" required min="1"></div>
            <div class="col-md-2"><input type="text" name="items[${itemCount}][unit]" class="form-control" placeholder="Satuan" required></div>
            <div class="col-md-3"><input type="number" name="items[${itemCount}][estimated_unit_price]" class="form-control" placeholder="Harga/Unit" required min="0" step="1000"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-remove-item w-100"><i class="ri-delete-bin-line"></i></button></div>
        </div>`;
    document.getElementById('items-container').appendChild(row);
    itemCount++;
    row.querySelector('.btn-remove-item').addEventListener('click', function() { row.remove(); });
});
document.querySelectorAll('.btn-remove-item').forEach(btn => {
    btn.addEventListener('click', function() { this.closest('.item-row').remove(); });
});
</script>
@endsection
