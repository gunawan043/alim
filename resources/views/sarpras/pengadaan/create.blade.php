@extends('layouts.master')
@section('title') Request Pengadaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pengadaan.index') }}">Pengadaan</a> @endslot
    @slot('title') Request @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Request Pengadaan Barang</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.pengadaan.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Request <span class="text-danger">*</span></label>
                            <input type="date" name="request_date" class="form-control" value="{{ old('request_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Urgensi <span class="text-danger">*</span></label>
                            <select name="urgency" class="form-select" required>
                                @foreach(App\Models\ProcurementRequest::URGENCY_OPTIONS as $u)
                                    <option value="{{ $u }}">{{ ucfirst($u) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sumber Dana</label>
                            <input type="text" name="budget_source" class="form-control" value="{{ old('budget_source') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tujuan Pengadaan <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="2" required>{{ old('purpose') }}</textarea>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Item Pengadaan</h5>
                    <div id="items-container">
                        <div class="row g-3 item-row mb-2">
                            <div class="col-md-3"><input type="text" name="items[0][item_name]" class="form-control" placeholder="Nama Item" required></div>
                            <div class="col-md-2"><input type="number" name="items[0][quantity]" class="form-control" placeholder="Jumlah" min="1" required></div>
                            <div class="col-md-2"><input type="text" name="items[0][unit]" class="form-control" placeholder="Satuan" value="pcs"></div>
                            <div class="col-md-3"><input type="number" name="items[0][estimated_price_per_unit]" class="form-control" placeholder="Harga/unit" min="0"></div>
                            <div class="col-md-2"><button type="button" class="btn btn-danger btn-remove w-100"><i class="ri-delete-bin-line"></i></button></div>
                        </div>
                    </div>
                    <button type="button" id="add-item" class="btn btn-outline-primary btn-sm mt-2"><i class="ri-add-line me-1"></i>Tambah Item</button>

                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-send-plane-line me-1"></i> Ajukan</button>
                        <a href="{{ route('sarpras.pengadaan.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
let itemCount = 1;
$('#add-item').on('click', function() {
    const html = `<div class="row g-3 item-row mb-2">
        <div class="col-md-3"><input type="text" name="items[${itemCount}][item_name]" class="form-control" placeholder="Nama Item" required></div>
        <div class="col-md-2"><input type="number" name="items[${itemCount}][quantity]" class="form-control" placeholder="Jumlah" min="1" required></div>
        <div class="col-md-2"><input type="text" name="items[${itemCount}][unit]" class="form-control" placeholder="Satuan" value="pcs"></div>
        <div class="col-md-3"><input type="number" name="items[${itemCount}][estimated_price_per_unit]" class="form-control" placeholder="Harga/unit" min="0"></div>
        <div class="col-md-2"><button type="button" class="btn btn-danger btn-remove w-100"><i class="ri-delete-bin-line"></i></button></div>
    </div>`;
    $('#items-container').append(html);
    itemCount++;
});
$(document).on('click', '.btn-remove', function() { $(this).closest('.item-row').remove(); });
</script>
@endsection
