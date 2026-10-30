@extends('layouts.master')
@section('title', 'Buat Pengajuan SIPLah')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.siplah.index', ['userId'=>$userId]) }}">Pengajuan SIPLah</a> @endslot
        @slot('title') Buat Pengajuan @endslot
    @endcomponent

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="alert alert-info small">
        <i class="ri-information-line me-1"></i>
        Pengajuan ini adalah <strong>proposal kebutuhan</strong>, bukan pembelian langsung. BUMP akan memeriksa ketersediaan, penyedia, harga final, dan proses SIPLah.
    </div>

    <form method="POST" action="{{ route('user.siplah.store', ['userId'=>$userId]) }}" enctype="multipart/form-data" id="siplahForm">
        @csrf
        <input type="hidden" name="external_reference" value="{{ old('external_reference', $externalReference) }}">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Data Proposal</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Satuan Kerja</label>
                                @if($units->count() === 1)
                                    <input type="hidden" name="work_unit_id" value="{{ $units->first()->id }}">
                                    <input class="form-control" value="{{ $units->first()->name }} ({{ $units->first()->code }})" readonly>
                                @else
                                    <select name="work_unit_id" class="form-select" required>
                                        <option value="">— Pilih satuan kerja —</option>
                                        @foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('work_unit_id')===$unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>@endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Pengajuan</label>
                                <input class="form-control" value="{{ \Illuminate\Support\Carbon::parse($autoDate)->locale('id')->translatedFormat('d F Y') }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Prioritas</label>
                                <select name="priority" class="form-select">@foreach(['low'=>'Rendah','normal'=>'Normal','high'=>'Tinggi','urgent'=>'Mendesak'] as $v=>$l)<option value="{{ $v }}" @selected(old('priority','normal')===$v)>{{ $l }}</option>@endforeach</select>
                            </div>
                            <div class="col-md-6"><label class="form-label">Nama Program / Kegiatan</label><input name="program_name" class="form-control" value="{{ old('program_name') }}" required></div>
                            <div class="col-md-3"><label class="form-label">Sumber Anggaran</label><input name="budget_source" class="form-control" value="{{ old('budget_source') }}"></div>
                            <div class="col-md-3"><label class="form-label">Waktu Kebutuhan</label><input type="date" name="needed_at" class="form-control" value="{{ old('needed_at') }}"></div>
                            <div class="col-12"><label class="form-label">Alasan / Tujuan Kebutuhan</label><textarea name="purpose" class="form-control" rows="3" required>{{ old('purpose') }}</textarea></div>
                            <div class="col-md-6"><label class="form-label">Penanggung Jawab</label><input class="form-control" value="{{ auth()->user()?->name }}" readonly></div>
                            <div class="col-md-6"><label class="form-label">No. WhatsApp</label><input class="form-control" value="{{ $autoPhone ?: 'Belum tercatat pada profil GTK' }}" readonly></div>
                            <div class="col-12"><label class="form-label">Dokumen Pendukung</label><input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
                            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Rincian Barang</h5><button type="button" class="btn btn-sm btn-soft-primary" onclick="addSiplahRow()"><i class="ri-add-line me-1"></i>Tambah Barang</button></div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table align-middle mb-0" id="siplahItems"><thead class="table-light"><tr><th>Kategori</th><th>Nama Barang</th><th>Spesifikasi</th><th>Qty</th><th>Satuan</th><th>Harga Estimasi</th><th>Total</th><th></th></tr></thead><tbody></tbody></table></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Ringkasan</h5></div><div class="card-body">
                    <p class="text-muted small">Proposal akan disimpan di BUMP sebagai draft. Setelah disetujui, proposal dikirim untuk diproses BUMP.</p>
                    <div class="d-flex justify-content-between mb-3"><span class="text-muted">Total Estimasi</span><strong id="siplahTotal">Rp 0</strong></div>
                    <button class="btn btn-primary w-100" type="submit"><i class="ri-save-3-line me-1"></i>Simpan Draft</button>
                    <a href="{{ route('user.siplah.index', ['userId'=>$userId]) }}" class="btn btn-light w-100 mt-2">Batal</a>
                </div></div>
            </div>
        </div>
    </form>

    <template id="siplahRowTemplate">
        <tr>
            <td><select name="items[__INDEX__][category]" class="form-select form-select-sm"><option value="">— Pilih —</option>@foreach($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach</select></td>
            <td><input name="items[__INDEX__][name]" class="form-control form-control-sm" required></td>
            <td><input name="items[__INDEX__][specification]" class="form-control form-control-sm" placeholder="Ukuran/tipe"></td>
            <td><input type="number" min="0.01" step="0.01" name="items[__INDEX__][qty]" class="form-control form-control-sm js-qty" value="1" required oninput="recalcSiplah()"></td>
            <td><input name="items[__INDEX__][unit]" class="form-control form-control-sm" placeholder="pcs" required></td>
            <td><input type="number" min="0" step="0.01" name="items[__INDEX__][unit_price]" class="form-control form-control-sm js-price" value="0" required oninput="recalcSiplah()"></td>
            <td class="text-end js-line">Rp 0</td>
            <td><button type="button" class="btn btn-sm btn-soft-danger" onclick="this.closest('tr').remove();recalcSiplah()"><i class="ri-delete-bin-line"></i></button></td>
        </tr>
    </template>
@endsection

@section('script')
<script>
let siplahIndex = 0;
function addSiplahRow(){const html=document.getElementById('siplahRowTemplate').innerHTML.replaceAll('__INDEX__', siplahIndex++);document.querySelector('#siplahItems tbody').insertAdjacentHTML('beforeend',html);recalcSiplah();}
function recalcSiplah(){let total=0;document.querySelectorAll('#siplahItems tbody tr').forEach(row=>{const q=parseFloat(row.querySelector('.js-qty')?.value||0),p=parseFloat(row.querySelector('.js-price')?.value||0);const line=q*p;total+=line;const cell=row.querySelector('.js-line');if(cell)cell.textContent='Rp '+line.toLocaleString('id-ID');});document.getElementById('siplahTotal').textContent='Rp '+total.toLocaleString('id-ID');}
document.addEventListener('DOMContentLoaded',()=>addSiplahRow());
</script>
@endsection
