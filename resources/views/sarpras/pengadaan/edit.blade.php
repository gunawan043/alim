@extends('layouts.master')
@section('title') Edit Request Pengadaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pengadaan.index') }}">Pengadaan</a> @endslot
    @slot('li_3') <a href="{{ route('sarpras.pengadaan.show', ['id' => $procurement->id]) }}">{{ $procurement->request_number }}</a> @endslot
    @slot('title') Edit @endslot
@endcomponent

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0 ps-3"><li>{{ $errors->first() }}</li></ul>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Edit Request Pengadaan</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.pengadaan.update', ['id' => $procurement->id]) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Request <span class="text-danger">*</span></label>
                            <input type="date" name="request_date" class="form-control"
                                value="{{ old('request_date', $procurement->request_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Urgensi <span class="text-danger">*</span></label>
                            <select name="urgency" class="form-select" required>
                                @foreach(App\Models\ProcurementRequest::URGENCY_OPTIONS as $u)
                                    <option value="{{ $u }}" {{ old('urgency', $procurement->urgency) == $u ? 'selected' : '' }}>
                                        {{ ucfirst($u) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sumber Dana</label>
                            <input type="text" name="budget_source" class="form-control"
                                value="{{ old('budget_source', $procurement->budget_source) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Estimasi Budget (Rp)</label>
                            <input type="number" name="total_estimated_budget" class="form-control"
                                value="{{ old('total_estimated_budget', $procurement->total_estimated_budget) }}" min="0" step="1000">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tujuan Pengadaan <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="2" required>{{ old('purpose', $procurement->purpose) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $procurement->notes) }}</textarea>
                        </div>
                    </div>

                    @if($procurement->items->isNotEmpty())
                    <h5 class="mt-4 mb-3">Item Pengadaan</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th><th>Nama Item</th><th>Jumlah</th><th>Satuan</th><th>Harga/Unit Est.</th><th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($procurement->items as $i => $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->unit ?? '-' }}</td>
                                    <td>{{ $item->estimated_price_per_unit ? 'Rp ' . number_format($item->estimated_price_per_unit, 0, ',', '.') : '-' }}</td>
                                    <td>{{ $item->total_estimated_price ? 'Rp ' . number_format($item->total_estimated_price, 0, ',', '.') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            @if($procurement->total_estimated_budget)
                            <tfoot class="table-light fw-medium">
                                <tr>
                                    <th colspan="5" class="text-end">Total Estimasi:</th>
                                    <th>Rp {{ number_format($procurement->total_estimated_budget, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                    <p class="text-muted small">Item tidak bisa diedit via form ini. Hapus request dan buat baru jika perlu mengubah item.</p>
                    @endif

                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Perubahan</button>
                        <a href="{{ route('sarpras.pengadaan.show', ['id' => $procurement->id]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SIDEBAR INFO --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Info Request</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">No. Request</td><td class="fw-medium"><code>{{ $procurement->request_number }}</code></td></tr>
                    <tr><td class="text-muted small">Status</td>
                        <td>
                            @php $c=['draft'=>'secondary','pending'=>'warning','approved'=>'success','rejected'=>'danger','ordered'=>'info','delivered'=>'primary','completed'=>'success','cancelled'=>'secondary']; @endphp
                            <span class="badge bg-{{ $c[$procurement->status] ?? 'secondary' }}-subtle text-{{ $c[$procurement->status] ?? 'secondary' }}">
                                {{ ucfirst(str_replace('_',' ', $procurement->status)) }}
                            </span>
                        </td>
                    </tr>
                    <tr><td class="text-muted small">Requester</td><td>{{ $procurement->requester?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Satuan Pendidikan</td><td>{{ $procurement->school?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Tanggal Dibuat</td><td>{{ $procurement->created_at->format('d/m/Y H:i') }}</td></tr>
                    @if($procurement->approved_at)
                    <tr><td class="text-muted small">Disetujui</td><td>{{ $procurement->approver?->name }} — {{ $procurement->approved_at->format('d/m/Y H:i') }}</td></tr>
                    @endif
                    @if($procurement->rejection_reason)
                    <tr>
                        <td class="text-muted small">Alasan Tolak</td>
                        <td><span class="text-danger small">{{ $procurement->rejection_reason }}</span></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection