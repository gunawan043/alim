@extends('layouts.master')
@section('title') Audit Massal @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.qr.index') }}">QR Code</a> @endslot
    @slot('title') Audit Massal @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Audit Massal Aset Rusak</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.qr.bulk-audit.submit') }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" id="checkAll"></th>
                                    <th>Nama Aset</th><th>Kode</th><th>Ruang</th><th>Kondisi</th><th>Update Kondisi</th><th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $a)
                                <tr>
                                    <td><input type="checkbox" name="audits[{{ $loop->index }}][asset_id]" value="{{ $a->id }}" class="asset-check"></td>
                                    <td>{{ $a->asset_name }}</td>
                                    <td><code>{{ $a->asset_code ?? '-' }}</code></td>
                                    <td>{{ $a->room?->room_name ?? '-' }}</td>
                                    <td>
                                        @php $kc=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger']; @endphp
                                        <span class="badge bg-{{ $kc[$a->condition] ?? 'secondary' }}-subtle text-{{ $kc[$a->condition] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_',' ',$a->condition)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <select name="audits[{{ $loop->index }}][condition]" class="form-select form-select-sm" required>
                                            @foreach(App\Models\Asset::CONDITION_OPTIONS as $c)
                                                <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="audits[{{ $loop->index }}][notes]" class="form-control form-control-sm" placeholder="Catatan"></td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center">Semua aset dalam kondisi baik.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($assets->isNotEmpty())
                    <button type="submit" class="btn btn-success mt-3"><i class="ri-save-line me-1"></i> Simpan Audit</button>
                    @endif
                </form>
                @if($assets->hasPages())
                <div class="mt-3">{{ $assets->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$('#checkAll').on('change', function() { $('.asset-check').prop('checked', this.checked); });
</script>
@endsection
