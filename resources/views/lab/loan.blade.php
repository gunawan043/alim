@extends('layouts.master')
@section('title', 'Peminjaman Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Peminjaman & Pengembalian @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-arrow-right-up-line me-1 text-primary"></i>Form Peminjaman</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.loan.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="mb-2"><label class="form-label">Laboratorium</label><input class="form-control" value="{{ $lab?->name ?? '-' }}" disabled></div>
        <div class="mb-2"><label class="form-label">Nama Peminjam</label><input name="borrower_name" class="form-control" value="{{ auth()->user()->name }}" required></div>
        <div class="mb-2"><label class="form-label">Instansi</label><input name="institution" class="form-control" placeholder="Satuan pendidikan / instansi"></div>
        <div class="mb-2"><label class="form-label">Tanggal Peminjaman</label><input type="date" name="loan_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
        <div class="mb-2"><label class="form-label">Rencana Kembali</label><input type="date" name="planned_return_date" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Tujuan Peminjaman</label><textarea name="purpose" class="form-control" rows="2" required></textarea></div>
        <div class="table-responsive"><table class="table table-sm" id="loanRows"><thead><tr><th>Nama Alat</th><th style="width:70px">Jml</th><th>Ket.</th></tr></thead><tbody>
        @for($i=0;$i<5;$i++)<tr><td><select name="items[{{ $i }}][asset_id]" class="form-select form-select-sm" {{ $i===0?'required':'' }}><option value="">— pilih alat —</option>@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_name }} · {{ $asset->quantity }} {{ $asset->unit }}</option>@endforeach</select></td><td><input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm" min="1" value="1"></td><td><input name="items[{{ $i }}][notes]" class="form-control form-control-sm" placeholder="Keterangan"></td></tr>@endfor
        </tbody></table></div>
        <button type="button" class="btn btn-sm btn-outline-primary mb-2" onclick="addLoanRow()"><i class="ri-add-line"></i> Tambah Baris</button>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Ajukan Peminjaman</button>
    </form></div></div></div>
    <div class="col-xl-8"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Riwayat Peminjaman</h5></div><div class="card-body p-0"><div class="table-container"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>Peminjam</th><th>Instansi</th><th>Tanggal</th><th>Rencana Kembali</th><th>Alat</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($loans as $row)<tr><td class="fw-semibold">{{ $row->borrower_name }}</td><td>{{ $row->institution ?: '-' }}</td><td>{{ $row->loan_date?->format('d/m/Y') }}</td><td>{{ $row->planned_return_date?->format('d/m/Y') ?? '-' }}</td><td>{{ $row->items->pluck('item_name')->join(', ') ?: '-' }}</td><td><span class="badge bg-{{ in_array($row->status, ['returned','closed']) ? 'success' : (in_array($row->status, ['rejected','lost']) ? 'danger' : 'info') }}-subtle text-{{ in_array($row->status, ['returned','closed']) ? 'success' : (in_array($row->status, ['rejected','lost']) ? 'danger' : 'info') }}">{{ $row->status }}</span></td><td class="text-nowrap"><a href="{{ route('lab.loan.print', ['userId' => $userId, 'loan' => $row->id]) }}" target="_blank" class="btn btn-sm btn-soft-secondary"><i class="ri-printer-line"></i></a>@if($row->status === 'approved')<form method="POST" action="{{ route('lab.loan.handover', ['userId' => $userId, 'loan' => $row->id]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-soft-success" title="Serahkan"><i class="ri-hand-coin-line"></i></button></form>@endif @if($row->status === 'handed_over')<button class="btn btn-sm btn-soft-warning" data-bs-toggle="modal" data-bs-target="#returnModal{{ $row->id }}"><i class="ri-arrow-go-back-line"></i></button>@endif</td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada peminjaman.</td></tr>@endforelse
    </tbody></table></div></div>@if($loans instanceof \Illuminate\Pagination\LengthAwarePaginator && $loans->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $loans])</div>@endif</div></div>
</div>
@foreach($loans as $row)
@if($row->status === 'handed_over')
<div class="modal fade" id="returnModal{{ $row->id }}" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('lab.loan.return', ['userId' => $userId, 'loan' => $row->id]) }}">@csrf<div class="modal-header"><h5 class="modal-title">Pengembalian — {{ $row->borrower_name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><table class="table table-sm"><thead><tr><th>Alat</th><th>Jumlah</th><th>Kembali</th><th>Kondisi Kembali</th><th>Catatan</th></tr></thead><tbody>@foreach($row->items as $item)<tr><td>{{ $item->item_name }}<input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}"></td><td>{{ $item->quantity }}</td><td><input type="number" name="items[{{ $loop->index }}][quantity_returned]" class="form-control form-control-sm" min="0" max="{{ $item->quantity }}" value="{{ $item->quantity }}"></td><td><select name="items[{{ $loop->index }}][condition_on_return]" class="form-select form-select-sm"><option value="baik">Baik</option><option value="rusak_ringan">Rusak Ringan</option><option value="rusak_sedang">Rusak Sedang</option><option value="rusak_berat">Rusak Berat</option><option value="hilang">Hilang</option></select></td><td><input name="items[{{ $loop->index }}][damage_notes]" class="form-control form-control-sm"></td></tr>@endforeach</tbody></table></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan Pengembalian</button></div></form></div></div></div>
@endif
@endforeach
@endsection
@section('script')
<script>
let loanIndex=5;
function addLoanRow(){const t=document.querySelector('#loanRows tbody');const options=`@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_name }} · {{ $asset->quantity }} {{ $asset->unit }}</option>@endforeach`;const tr=document.createElement('tr');tr.innerHTML=`<td><select name="items[${loanIndex}][asset_id]" class="form-select form-select-sm"><option value="">— pilih alat —</option>${options}</select></td><td><input type="number" name="items[${loanIndex}][quantity]" class="form-control form-control-sm" min="1" value="1"></td><td><input name="items[${loanIndex}][notes]" class="form-control form-control-sm"></td>`;t.appendChild(tr);loanIndex++;}
</script>
@endsection
