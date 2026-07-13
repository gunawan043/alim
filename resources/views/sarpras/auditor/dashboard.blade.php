@extends('layouts.master')
@section('title') Portal Auditor @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.auditor.dashboard') }}">Portal Auditor</a> @endslot
    @slot('title') Dashboard @endslot
@endcomponent

{{-- Stats --}}
<div class="row mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card widget-shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-light rounded"><i class="mdi mdi-clipboard-check text-info fs-3"></i></div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-1">{{ $sessions->count() }}</h3>
                        <p class="text-muted mb-0">Total Session</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card widget-shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-light rounded"><i class="mdi mdi-pencil text-primary fs-3"></i></div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-1 text-primary">{{ $sessions->where('status','in_progress')->count() }}</h3>
                        <p class="text-muted mb-0">Sedang Berjalan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card widget-shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-light rounded"><i class="mdi mdi-check-decagram text-success fs-3"></i></div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-1 text-success">{{ $sessions->where('status','closed')->count() }}</h3>
                        <p class="text-muted mb-0">Selesai</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card widget-shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-light rounded"><i class="mdi mdi-alert text-danger fs-3"></i></div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-1 text-danger">{{ $discrepancies }}</h3>
                        <p class="text-muted mb-0">Selisih (All Time)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Active Sessions --}}
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="card-title mb-0"><i class="mdi mdi-history me-1"></i> Session Audit</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createSessionModal">
            <i class="mdi mdi-plus me-1"></i> Session Baru
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Jenis</th>
                        <th>Auditor</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $s)
                    <tr>
                        <td><code>{{ $s->session_code }}</code></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $s->audit_type ?? '')) }}</td>
                        <td>{{ $s->auditor?->name ?? '-' }}</td>
                        <td>{{ $s->room?->room_name ?? 'Semua Ruang' }}</td>
                        <td>
                            @if($s->status == 'in_progress')
                                <span class="badge bg-info">BERJALAN</span>
                            @elseif($s->status == 'closed')
                                <span class="badge bg-success">SELESAI</span>
                            @else
                                <span class="badge bg-secondary">{{ $s->status }}</span>
                            @endif
                        </td>
                        <td>{{ $s->started_at?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('sarpras.auditor.session.show', $s->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ri-eye-line"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada session</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Session Modal --}}
<div class="modal fade" id="createSessionModal">
    <div class="modal-dialog">
        <form id="createForm" method="POST" action="{{ route('sarpras.auditor.session.start') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Mulai Session Audit</h5></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Jenis Audit</label>
                    <select name="audit_type" class="form-select" id="auditType">
                        <option value="periodic">Periodic Audit</option>
                        <option value="stock_opname">Stock Opname</option>
                        <option value="spot_check">Spot Check</option>
                        <option value="surveillance">Surveillance</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Lokasi (Opsional)</label>
                    <select name="target_room_id" class="form-select">
                        <option value="">Semua Ruang</option>
                        @foreach($rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->room_name }} — Lt. {{ $room->floor }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Mulai</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('createForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch('{{ route('sarpras.auditor.session.start') }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: fd
    }).then(r => r.json()).then(function(data) {
        if (data.success) {
            window.location.href = '{{ route('sarpras.auditor.session.show', '') }}/' + data.session.id;
        }
    });
});
</script>
@endpush
