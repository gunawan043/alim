@extends('layouts.master')
@section('title') Peminjaman Aset @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Peminjaman Aset @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">Daftar Peminjaman Aset</h5>
                    </div>
                    <div class="col-sm-auto">
                        <a href="{{ route('sarpras.peminjaman.create') }}" class="btn btn-success">
                            <i class="ri-add-line align-bottom me-1"></i> Request Peminjaman
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            @foreach(['pending','approved','dipinjam','dikembalikan','terlambat','dibatalkan'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_',' ',$s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama aset..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('sarpras.peminjaman.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-nowrap align-middle">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Aset</th>
                                <th>Peminjam</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Kondisi Pinjam</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loans as $l)
                            <tr>
                                <td>{{ $loop->iteration + ($loans->currentPage() - 1) * $loans->perPage() }}</td>
                                <td>
                                    <a href="{{ route('sarpras.aset.show', ['id' => $l->asset_id]) }}" class="fw-medium link-primary">
                                        {{ $l->asset?->asset_name ?? '-' }}
                                    </a>
                                    <br><small class="text-muted">{{ $l->asset?->asset_code }}</small>
                                </td>
                                <td>{{ $l->borrower?->name ?? '-' }}</td>
                                <td>{{ $l->loan_date?->format('d/m/Y') }}</td>
                                <td>
                                    {{ $l->expected_return_date?->format('d/m/Y') }}
                                    @if($l->status === 'dipinjam' && $l->expected_return_date->isPast())
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst(str_replace('_',' ',$l->condition_on_loan ?? '-')) }}</td>
                                <td>
                                    @php
                                        $colors = [
                                            'pending'=>'warning','approved'=>'info','dipinjam'=>'primary',
                                            'dikembalikan'=>'success','terlambat'=>'danger','dibatalkan'=>'secondary','hilang'=>'danger'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $colors[$l->status] ?? 'secondary' }}-subtle text-{{ $colors[$l->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_',' ',$l->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ route('sarpras.peminjaman.show', ['id' => $l->id]) }}"><i class="ri-eye-line me-2"></i>Detail</a></li>
                                            @if($l->status === 'pending')
                                            <li><a class="dropdown-item text-success" href="{{ route('sarpras.peminjaman.approve', ['id' => $l->id]) }}" onclick="return confirm('Setuju?')"><i class="ri-check-line me-2"></i>Approve</a></li>
                                            <li><a class="dropdown-item text-danger" href="{{ route('sarpras.peminjaman.reject', ['id' => $l->id]) }}"><i class="ri-close-line me-2"></i>Tolak</a></li>
                                            @endif
                                            @if($l->status === 'approved')
                                            <li><a class="dropdown-item text-primary" href="{{ route('sarpras.peminjaman.handover', ['id' => $l->id]) }}" onclick="return confirm('Serahkan aset?')"><i class="ri-hand coin-line me-2"></i>Serahkan</a></li>
                                            @endif
                                            @if($l->status === 'dipinjam')
                                            <li>
                                                <a class="dropdown-item text-success" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#returnModal{{ $l->id }}">
                                                    <i class="ri-arrow-left-circle-line me-2"></i>Kembalikan
                                                </a>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>

                                    {{-- Modal Return --}}
                                    @if($l->status === 'dipinjam')
                                    <div class="modal fade" id="returnModal{{ $l->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Pengembalian Aset</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('sarpras.peminjaman.return', ['id' => $l->id]) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p>Aset: <strong>{{ $l->asset?->asset_name }}</strong></p>
                                                        <div class="mb-3">
                                                            <label class="form-label">Kondisi Saat Dikembalikan <span class="text-danger">*</span></label>
                                                            <select name="condition_on_return" class="form-select" required>
                                                                @foreach(App\Models\AssetLoan::CONDITION_OPTIONS as $c)
                                                                    <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Catatan Kerusakan</label>
                                                            <textarea name="damage_notes" class="form-control" rows="2"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-success"><i class="ri-check-line me-1"></i>Konfirmasi Kembali</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="avatar-lg mx-auto mb-3"><div class="avatar-title bg-light rounded-circle"><i class="ri-exchange-funds-line fs-1 text-muted"></i></div></div>
                                    <h5 class="text-muted">Belum ada data peminjaman</h5>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $loans])
            </div>
        </div>
    </div>
</div>
@endsection
