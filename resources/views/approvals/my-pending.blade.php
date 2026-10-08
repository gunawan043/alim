@extends('layouts.master')
@section('title') Approval Saya @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Approval Saya @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success"><i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Approval Saya</h5>
                            <p class="text-muted mb-0">Permintaan yang menunggu persetujuan Anda.</p>
                        </div>
                        <div class="col-sm-auto">
                            <span class="badge bg-warning-subtle text-warning fs-12">
                                {{ $requests->total() }} menunggu
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tipe</th>
                                    <th>Pemohon</th>
                                    <th>Tahap</th>
                                    <th>Tanggal</th>
                                    <th class="text-center" style="min-width: 230px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    @php $currentStep = $req->actions->where('action', 'PENDING')->first(); @endphp
                                    <tr>
                                        <td><strong>{{ $req->request_type_text }}</strong></td>
                                        <td>{{ $req->requestedBy?->name ?? '-' }}</td>
                                        <td>
                                            @if($currentStep)
                                                <span class="badge bg-warning-subtle text-warning">
                                                    Step {{ $currentStep->step_order }} — {{ $currentStep->role_name }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">-</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $req->created_at->format('d/m/Y H:i') }}</small></td>
                                        <td class="text-center">
                                            <a href="{{ route('user.approvals.show', ['userId' => auth()->id(), 'approvalUuid' => $req->id]) }}"
                                               class="btn btn-sm btn-soft-secondary me-1">
                                                <i class="ri-eye-line"></i> Review
                                            </a>

                                            <form method="POST"
                                                  action="{{ route('user.approvals.approve', ['userId' => auth()->id(), 'approvalUuid' => $req->id]) }}"
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success me-1"
                                                        onclick="return confirm('Setujui tahap ini?')">
                                                    <i class="ri-check-double-line"></i> Setujui
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-outline-danger btn-reject"
                                                    data-bs-toggle="modal" data-bs-target="#rejectModal"
                                                    data-reject-url="{{ route('user.approvals.reject', ['userId' => auth()->id(), 'approvalUuid' => $req->id]) }}"
                                                    data-request-info="{{ $req->request_type_text }} — {{ $req->requestedBy?->name ?? '-' }}">
                                                <i class="ri-close-circle-line"></i> Tolak
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Tidak ada approval yang menunggu tindakan Anda.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($requests->hasPages())
                        @include('shared._pagination', ['paginator' => $requests])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tolak --}}
    <div class="modal fade zoomIn" id="rejectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            Pengajuan: <strong id="rejectRequestInfo">—</strong>
                        </p>
                        <label class="form-label">Alasan penolakan <span class="text-danger">*</span></label>
                        <textarea name="note" rows="3" class="form-control" required
                                  placeholder="Tulis alasan penolakan..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Tolak!</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.btn-reject').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('rejectForm').action = this.dataset.rejectUrl;
                    document.getElementById('rejectRequestInfo').textContent = this.dataset.requestInfo || '—';
                });
            });
        });
    </script>
@endpush
