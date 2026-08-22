@extends('layouts.master')
@section('title') Pengajuan Kenaikan Jabatan GTK @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('li_2') <a href="#">Pengajuan Jabatan</a> @endslot
        @slot('title') Pengajuan Kenaikan Jabatan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Pengajuan Kenaikan Jabatan</h5>
                            <p class="text-muted mb-0">
                                @if($canApprove)
                                    Kelola semua pengajuan kenaikan jabatan GTK
                                @else
                                    Daftar pengajuan jabatan Anda
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            @if(!$canApprove)
                                <a href="{{ route('user.gtk-position-proposals.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Ajukan Jabatan
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Diajukan</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="proposal_type" class="form-control">
                                <option value="">Semua Jenis</option>
                                <option value="promosi" {{ request('proposal_type') == 'promosi' ? 'selected' : '' }}>Promosi</option>
                                <option value="demosi" {{ request('proposal_type') == 'demosi' ? 'selected' : '' }}>Demosi</option>
                                <option value="rotasi" {{ request('proposal_type') == 'rotasi' ? 'selected' : '' }}>Rotasi</option>
                                <option value="mutasi" {{ request('proposal_type') == 'mutasi' ? 'selected' : '' }}>Mutasi</option>
                                <option value="penugasan" {{ request('proposal_type') == 'penugasan' ? 'selected' : '' }}>Penugasan Baru</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.gtk-position-proposals.index') }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>GTK</th>
                                    <th>Jenis</th>
                                    <th>Jabatan Tujuan</th>
                                    <th>Sekolah</th>
                                    <th>Status</th>
                                    <th>TMT</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proposals as $proposal)
                                    <tr>
                                        <td>{{ $loop->iteration + ($proposals->currentPage() - 1) * $proposals->perPage() }}</td>
                                        <td>
                                            <div>
                                                <span class="fw-medium">{{ $proposal->user?->name ?? '-' }}</span>
                                                <br>
                                                <small class="text-muted">Diajukan oleh: {{ $proposal->proposer?->name ?? '-' }}</small>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-soft-primary text-primary">{{ $proposal->proposal_type_label }}</span></td>
                                        <td>
                                            {{ $proposal->proposed_jabatan_text ?? $proposal->proposedPosition?->nama ?? '-' }}
                                            @if($proposal->proposedWorkUnit)
                                                <br><small class="text-muted">{{ $proposal->proposedWorkUnit }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $proposal->proposedSchool?->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-soft-{{ $proposal->status_badge }} text-{{ $proposal->status_badge }}">
                                                {{ $proposal->status_label }}
                                            </span>
                                        </td>
                                        <td>{{ $proposal->tmt ? $proposal->tmt->format('d M Y') : '-' }}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('user.gtk-position-proposals.show', $proposal->id) }}"
                                                   class="btn btn-sm btn-light" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if($canApprove && $proposal->status === 'submitted')
                                                    <form action="{{ route('user.gtk-position-proposals.approve', $proposal->id) }}"
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Setujui pengajuan ini?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Setujui">
                                                            <i class="ri-check-line"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('user.gtk-position-proposals.reject', $proposal->id) }}"
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Tolak pengajuan ini?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Tolak">
                                                            <i class="ri-close-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(!$canApprove && $proposal->status === 'submitted')
                                                    <form action="{{ route('user.gtk-position-proposals.cancel', $proposal->id) }}"
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Batalkan pengajuan ini?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Batalkan">
                                                            <i class="ri-close-circle-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                                            Belum ada pengajuan jabatan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $proposals->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
