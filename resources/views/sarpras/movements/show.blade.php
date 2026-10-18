@extends('layouts.master')
@section('title') Detail Mutasi Aset @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.movements.index') }}">Mutasi Aset</a> @endslot
        @slot('title') Detail Mutasi
    @endcomponent

    @php $tone = ['requested'=>'warning','approved'=>'info','in_transit'=>'primary','received'=>'info','verified'=>'success','completed'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$movement->status] ?? 'secondary'; @endphp

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">{{ $movement->movement_number }}</h5><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst(str_replace('_',' ', $movement->status)) }}</span></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0"><tbody>
                        <tr><td class="text-muted" style="width:160px">Aset</td><td>@if($movement->asset)<a href="{{ route('sarpras.assets.passport', ['uuid' => $movement->asset->id]) }}" class="fw-medium">{{ $movement->asset->asset_name }}</a><br><code class="small">{{ $movement->asset->asset_code }}</code>@else - @endif</td></tr>
                        <tr><td class="text-muted">Dari Ruang</td><td>{{ $movement->fromRoom?->room_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Ke Ruang</td><td>{{ $movement->toRoom?->room_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Alasan</td><td>{{ $movement->reason ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Justifikasi</td><td>{{ $movement->justification ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Pengaju</td><td>{{ $movement->requester?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Penyetuju</td><td>{{ $movement->approver?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Pengirim</td><td>{{ $movement->carrier?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Penerima</td><td>{{ $movement->receiver?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Verifikator</td><td>{{ $movement->verifier?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Dibuat</td><td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td></tr>
                    </tbody></table>
                </div>
                <div class="card-footer bg-transparent d-flex flex-wrap gap-2">
                    @if($movement->status === 'requested')<button class="btn btn-success" data-movement-action="{{ route('sarpras.movements.approve', ['id' => $movement->id]) }}"><i class="ri-check-line me-1"></i>Setujui</button>@endif
                    @if($movement->status === 'approved')<button class="btn btn-primary" data-movement-action="{{ route('sarpras.movements.start_transit', ['id' => $movement->id]) }}"><i class="ri-truck-line me-1"></i>Mulai Kirim</button>@endif
                    @if($movement->status === 'in_transit')<button class="btn btn-info" data-movement-action="{{ route('sarpras.movements.received', ['id' => $movement->id]) }}"><i class="ri-inbox-archive-line me-1"></i>Konfirmasi Terima</button>@endif
                    @if($movement->status === 'received')<button class="btn btn-success" data-movement-action="{{ route('sarpras.movements.verify', ['id' => $movement->id]) }}"><i class="ri-verified-badge-line me-1"></i>Verifikasi</button>@endif
                    @if($movement->status === 'verified')<button class="btn btn-success" data-movement-action="{{ route('sarpras.movements.complete', ['id' => $movement->id]) }}"><i class="ri-check-double-line me-1"></i>Selesaikan</button>@endif
                    <a href="{{ route('sarpras.movements.index') }}" class="btn btn-light">Kembali</a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-header"><h6 class="card-title mb-0">Riwayat Persetujuan</h6></div><div class="card-body">
                @forelse($movement->approvals as $approval)
                    <div class="d-flex gap-2 mb-3"><span class="avatar-xs"><span class="avatar-title rounded-circle bg-primary-subtle text-primary"><i class="ri-user-line"></i></span></span><div><div class="fw-medium small">{{ $approval->user?->name ?? '-' }}</div><div class="text-muted small">{{ ucfirst($approval->action) }} · {{ $approval->created_at?->format('d/m/Y H:i') }}</div>@if($approval->notes)<div class="small">{{ $approval->notes }}</div>@endif</div></div>
                @empty
                    <p class="text-muted small mb-0">Belum ada riwayat persetujuan.</p>
                @endforelse
            </div></div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.querySelectorAll('[data-movement-action]').forEach(function (button) {
    button.addEventListener('click', async function () {
        button.disabled = true;
        try {
            const response = await fetch(button.dataset.movementAction, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Aksi gagal.');
            window.location.reload();
        } catch (error) {
            button.disabled = false;
            if (window.Swal) Swal.fire('Gagal', error.message, 'error'); else alert(error.message);
        }
    });
});
</script>
@endpush
