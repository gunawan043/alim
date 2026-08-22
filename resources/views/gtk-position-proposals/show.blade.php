@extends('layouts.master')
@section('title') Detail Pengajuan Jabatan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('li_2') <a href="{{ route('user.gtk-position-proposals.index') }}">Pengajuan Jabatan</a> @endslot
        @slot('title') Detail Pengajuan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Detail Pengajuan</h5>
                    <span class="badge bg-soft-{{ $proposal->status_badge }} text-{{ $proposal->status_badge }} fs-6">
                        {{ $proposal->status_label }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <tbody>
                                <tr>
                                    <th width="200">No. Pengajuan</th>
                                    <td>{{ $proposal->id }}</td>
                                </tr>
                                <tr>
                                    <th>GTK yang Diajukan</th>
                                    <td>
                                        <a href="{{ route('user.gtk.show', $proposal->user_id) }}">
                                            {{ $proposal->user?->name ?? '-' }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Jenis Pengajuan</th>
                                    <td>
                                        <span class="badge bg-soft-primary text-primary">{{ $proposal->proposal_type_label }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Jabatan Asal</th>
                                    <td>
                                        {{ $proposal->currentEmployment?->jabatan ?? $proposal->user?->gtkEmployment?->jabatan ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Jabatan Tujuan</th>
                                    <td>
                                        <strong>{{ $proposal->proposed_jabatan_text ?? $proposal->proposedPosition?->nama ?? '-' }}</strong>
                                        @if($proposal->proposedWorkUnit)
                                            <br><small class="text-muted">Unit: {{ $proposal->proposedWorkUnit }}</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Sekolah Tujuan</th>
                                    <td>{{ $proposal->proposedSchool?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Alasan</th>
                                    <td>{{ $proposal->reason ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Nomor SK</th>
                                    <td>{{ $proposal->nomor_sk ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>TMT</th>
                                    <td>{{ $proposal->tmt ? $proposal->tmt->format('d M Y') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Diajukan Oleh</th>
                                    <td>
                                        {{ $proposal->proposer?->name ?? '-' }}
                                        @if($proposal->proposer_role_at_submit)
                                            <br><small class="text-muted">({{ $proposal->proposer_role_at_submit }})</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tanggal Diajukan</th>
                                    <td>{{ $proposal->created_at->format('d M Y, H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if(in_array($proposal->status, ['approved', 'rejected']))
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">Tinjauan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm">
                                <tbody>
                                    <tr>
                                        <th width="200">Peninjau</th>
                                        <td>
                                            {{ $proposal->reviewer?->name ?? '-' }}
                                            <br><small class="text-muted">
                                                {{ $proposal->reviewed_at ? 'Pada: ' . $proposal->reviewed_at->format('d M Y, H:i') : '' }}
                                            </small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Catatan</th>
                                        <td>{{ $proposal->review_notes ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Aksi</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('user.gtk-position-proposals.index') }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
                        </a>

                        @if($canApprove && $proposal->status === 'submitted')
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="ri-check-line me-1"></i> Setujui Pengajuan
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="ri-close-line me-1"></i> Tolak Pengajuan
                            </button>
                        @endif

                        @if(!$canApprove && $proposal->status === 'submitted' && $proposal->proposed_by === auth()->id())
                            <form action="{{ route('user.gtk-position-proposals.cancel', $proposal->id) }}"
                                  method="POST" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="ri-close-circle-line me-1"></i> Batalkan Pengajuan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Info GTK</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        @if($proposal->user?->profile_photo)
                            <img src="{{ $proposal->user->profile_photo }}" alt="{{ $proposal->user->name }}"
                                 class="rounded-circle me-3" width="48" height="48">
                        @endif
                        <div>
                            <h6 class="mb-0">{{ $proposal->user?->name ?? '-' }}</h6>
                            <small class="text-muted">{{ $proposal->user?->gtkEmployment?->jabatan ?? '-' }}</small>
                        </div>
                    </div>
                    <hr>
                    <div class="small text-muted">
                        <p><strong>NIP:</strong> {{ $proposal->user?->gtkProfile?->nip ?? '-' }}</p>
                        <p><strong>Jabatan Saat Ini:</strong> {{ $proposal->currentEmployment?->jabatan ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Approve Modal --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Setujui Pengajuan Jabatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('user.gtk-position-proposals.approve', $proposal->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nomor SK</label>
                            <input type="text" name="nomor_sk" class="form-control" value="{{ old('nomor_sk') }}" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">TMT</label>
                            <input type="date" name="tmt" class="form-control" value="{{ old('tmt') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea name="review_notes" class="form-control" rows="3">{{ old('review_notes') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-check-line me-1"></i> Setujui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Pengajuan Jabatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('user.gtk-position-proposals.reject', $proposal->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="review_notes" class="form-control" rows="4" required>{{ old('review_notes') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="ri-close-line me-1"></i> Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
