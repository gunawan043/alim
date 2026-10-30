@extends('layouts.master')
@section('title', 'Pesan Barang BUMP')

@section('css')
<style>
    .card-animate{transition:all .3s ease}.card-animate:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(15,23,42,.1)}
    .requester-card{border-left:4px solid #0a5f9e}.form-label{font-size:.8rem;font-weight:600}
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bump-orders.index',['userId'=>$userId]) }}">Pemesanan BUMP</a> @endslot
        @slot('title') Pesan Barang @endslot
    @endcomponent

    @if($error)<div class="alert alert-danger"><i class="ri-error-warning-line me-1"></i>{{ $error }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @if(empty($paymentMethods))
        <div class="alert alert-warning"><i class="ri-wallet-3-line me-1"></i>Metode pembayaran BUMP belum dapat dimuat. Informasi pemesan tetap ditampilkan, tetapi pemesanan belum dapat dikirim sampai koneksi/metode pembayaran tersedia.</div>
    @endif

    <form method="GET" action="{{ route('user.bump-orders.create',['userId'=>$userId]) }}" class="card mb-3">
        <div class="card-body py-2"><div class="input-group input-group-sm"><span class="input-group-text"><i class="ri-search-line"></i></span><input name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama barang, SKU, atau barcode..."><button class="btn btn-primary">Cari Barang</button></div></div>
    </form>

    <form method="POST" action="{{ route('user.bump-orders.store',['userId'=>$userId]) }}" id="bumpOrderForm">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                {{-- Informasi pemesan selalu tampil --}}
                <div class="card requester-card">
                    <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-user-3-line me-1 text-primary"></i>Informasi Pemesan</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Satuan Kerja</label>
                                @if($units->count() === 1)
                                    <input type="hidden" name="work_unit_id" value="{{ $units->first()->id }}">
                                    <input class="form-control" value="{{ $units->first()->name }} ({{ $units->first()->code }})" readonly>
                                @elseif($units->count() > 1)
                                    <select name="work_unit_id" class="form-select" required>
                                        @foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('work_unit_id')===$unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>@endforeach
                                    </select>
                                @else
                                    <input class="form-control is-invalid" value="Satuan kerja belum terdeteksi" readonly>
                                @endif
                            </div>
                            <div class="col-md-3"><label class="form-label">Tanggal Pemesanan</label><input class="form-control" value="{{ \Illuminate\Support\Carbon::parse($autoDate)->locale('id')->translatedFormat('d F Y') }}" readonly></div>
                            <div class="col-md-3"><label class="form-label">No. WhatsApp</label><input class="form-control" value="{{ $autoPhone ?: 'Belum tercatat pada profil GTK' }}" readonly></div>
                            <div class="col-md-6"><label class="form-label">Nama Pemesan</label><input class="form-control" value="{{ $requesterName ?? auth()->user()?->name }}" readonly></div>
                            <div class="col-md-6"><label class="form-label">Jabatan</label><input class="form-control" value="{{ $requesterJabatan ?? '—' }}" readonly></div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                @if($paymentMethods)
                                    <select name="payment_method" class="form-select" required>
                                        @foreach($paymentMethods as $method)<option value="{{ $method['code'] }}">{{ $method['name'] }}{{ ($method['requires_payment']??false) ? ' — bayar saat proses' : '' }}</option>@endforeach
                                    </select>
                                    <small class="text-muted">Status pembayaran mengikuti BUMP, bukan otomatis lunas.</small>
                                @else
                                    <input class="form-control is-invalid" value="Metode pembayaran belum tersedia" readonly>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Barang</h5><button type="button" class="btn btn-sm btn-soft-primary" onclick="addBumpRow()"><i class="ri-add-line me-1"></i>Tambah</button></div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table align-middle mb-0" id="bumpItems"><thead class="table-light"><tr><th>Barang</th><th>Qty</th><th>Harga</th><th>Satuan</th><th>Subtotal</th><th></th></tr></thead><tbody></tbody></table></div></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-animate"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-shopping-cart-2-line me-1 text-primary"></i>Ringkasan</h5></div><div class="card-body">
                    <div class="d-flex justify-content-between mb-3"><span class="text-muted">Total</span><strong id="bumpTotal">Rp 0</strong></div>
                    <button class="btn btn-primary w-100" @disabled(empty($paymentMethods) || $units->isEmpty())><i class="ri-send-plane-line me-1"></i>Kirim Pesanan</button>
                    <a href="{{ route('user.bump-orders.index',['userId'=>$userId]) }}" class="btn btn-light w-100 mt-2">Batal</a>
                </div></div>
            </div>
        </div>
    </form>

    <template id="bumpRowTemplate"><tr>
        <td><select class="form-select form-select-sm" onchange="fillBumpProduct(this)"><option value="">— Pilih barang —</option>@foreach($products as $p)<option value="{{ $p['id'] }}" data-name="{{ $p['nama'] }}" data-price="{{ $p['harga_jual'] }}" data-unit="{{ $p['satuan'] }}" data-stock="{{ $p['stok'] }}">{{ $p['nama'] }} — {{ $p['sku'] }} (stok {{ $p['stok'] }})</option>@endforeach</select><input type="hidden" class="js-product" name="items[__INDEX__][product_id]"></td>
        <td><input type="number" min="0.01" step="0.01" name="items[__INDEX__][qty]" class="form-control form-control-sm js-qty" value="1" required oninput="recalcBump()"></td>
        <td class="js-price">—</td><td class="js-unit">—</td><td class="text-end js-subtotal">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-soft-danger" onclick="this.closest('tr').remove();recalcBump()"><i class="ri-delete-bin-line"></i></button></td>
    </tr></template>
@endsection

@section('script')
<script>
let bumpIndex=0;
function addBumpRow(){const t=document.getElementById('bumpRowTemplate');if(!t)return;document.querySelector('#bumpItems tbody').insertAdjacentHTML('beforeend',t.innerHTML.replaceAll('__INDEX__',bumpIndex++));recalcBump();}
function fillBumpProduct(sel){const o=sel.options[sel.selectedIndex],r=sel.closest('tr');r.querySelector('.js-product').value=o.value||'';r.querySelector('.js-price').textContent=o.dataset.price?('Rp '+Number(o.dataset.price).toLocaleString('id-ID')):'—';r.querySelector('.js-unit').textContent=o.dataset.unit||'—';recalcBump();}
function recalcBump(){let total=0;document.querySelectorAll('#bumpItems tbody tr').forEach(r=>{const o=r.querySelector('select option:checked');const price=Number(o?.dataset.price||0);const qty=Number(r.querySelector('.js-qty')?.value||0);const line=price*qty;total+=line;r.querySelector('.js-subtotal').textContent='Rp '+line.toLocaleString('id-ID');});document.getElementById('bumpTotal').textContent='Rp '+total.toLocaleString('id-ID');}
document.addEventListener('DOMContentLoaded',()=>addBumpRow());
</script>
@endsection
