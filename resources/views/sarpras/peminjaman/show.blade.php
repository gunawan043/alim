@extends('layouts.master')
@section('title') Detail Peminjaman @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.peminjaman.index') }}">Peminjaman</a> @endslot
    @slot('title') Detail @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- STATUS BADGE --}}
@php
    $statusConfig = [
        'pending'     => ['warning', 'Menunggu Persetujuan'],
        'approved'    => ['info',    'Disetujui — Menunggu Penyerahan'],
        'dipinjam'    => ['primary', 'Sedang Dipinjam'],
        'dikembalikan'=> ['success', 'Sudah Dikembalikan'],
        'terlambat'  => ['danger',  'Terlambat'],
        'dibatalkan' => ['secondary','Dibatalkan'],
        'hilang'     => ['danger',  'Hilang'],
    ];
    $sc = $statusConfig[$loan->status] ?? ['secondary','-'];
@endphp
<div class="alert alert-{{ $sc[0] }} d-flex align-items-center gap-2 py-2">
    <i class="ri-information-line fs-5"></i>
    <strong>Status:</strong> {{ $sc[1] }}
</div>

<div class="row">
    {{-- MAIN CONTENT --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detail Peminjaman</h5>
                <div class="d-flex gap-1 flex-wrap">
                    @if($loan->status === 'pending')
                        <a href="{{ route('sarpras.peminjaman.approve', ['id' => $loan->id]) }}" class="btn btn-success btn-sm" onclick="return confirm('Setuju dengan peminjaman ini?')">
                            <i class="ri-check-line me-1"></i> Approve
                        </a>
                        <a href="{{ route('sarpras.peminjaman.reject', ['id' => $loan->id]) }}" class="btn btn-outline-danger btn-sm">
                            <i class="ri-close-line me-1"></i> Tolak
                        </a>
                    @endif
                    @if($loan->status === 'approved')
                        <a href="{{ route('sarpras.peminjaman.handover', ['id' => $loan->id]) }}" class="btn btn-primary btn-sm" onclick="return confirm('Serahkan aset ke peminjam sekarang?')">
                            <i class="ri-hand-coin-line me-1"></i> Serahkan Aset
                        </a>
                    @endif
                    @if(!in_array($loan->status, ['dipinjam']))
                        <form action="{{ route('sarpras.peminjaman.destroy', ['id' => $loan->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data peminjaman ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="ri-delete-bin-line"></i></button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted fw-medium" style="width:200px">Aset</td>
                                <td>
                                    @if($loan->asset)
                                        <a href="{{ route('sarpras.aset.show', ['id' => $loan->asset_id]) }}" class="fw-medium">{{ $loan->asset->asset_name }}</a>
                                        <br><small class="text-muted">{{ $loan->asset->asset_code }}</small>
                                    @else — @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Peminjam</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{ $loan->borrower?->name ?? '-' }}</span>
                                        @if($loan->borrower?->email)
                                            <small class="text-muted">({{ $loan->borrower->email }})</small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Unit Kerja</td><td>{{ $loan->workUnit?->name ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Satuan Pendidikan</td><td>{{ $loan->school?->name ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Tujuan Peminjaman</td><td>{{ $loan->purpose }}</td></tr>
                            <tr><td class="text-muted fw-medium">Tanggal Pinjam</td><td>{{ $loan->loan_date?->format('d/m/Y') }}</td></tr>
                            <tr><td class="text-muted fw-medium">Waktu Pinjam</td><td>{{ $loan->loan_time ? substr($loan->loan_time, 0, 5) : '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Rencana Kembali</td>
                                <td>
                                    {{ $loan->expected_return_date?->format('d/m/Y') }}
                                    @if($loan->status === 'dipinjam' && $loan->expected_return_date && $loan->expected_return_date->isPast())
                                        <span class="badge bg-danger ms-1">OVERDUE</span>
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Kondisi Saat Dipinjam</td><td>{{ ucfirst(str_replace('_',' ', $loan->condition_on_loan ?? '-')) }}</td></tr>
                            @if($loan->approved_at)
                            <tr>
                                <td class="text-muted fw-medium">Disetujui</td>
                                <td>{{ $loan->approver?->name ?? '-' }} &mdash; {{ $loan->approved_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                            {{-- Aktual kembali (bila sudah dikembalikan) --}}
                            @if(in_array($loan->status, ['dikembalikan', 'terlambat']))
                            <tr><td class="text-muted fw-medium">Tanggal Kembali</td><td>{{ $loan->actual_return_date ? $loan->actual_return_date->format('d/m/Y') : '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Waktu Kembali</td><td>{{ $loan->actual_return_time ? substr($loan->actual_return_time, 0, 5) : '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Kondisi Saat Dikembalikan</td><td>{{ ucfirst(str_replace('_',' ', $loan->condition_on_return ?? '-')) }}</td></tr>
                            @if($loan->damage_notes)
                            <tr>
                                <td class="text-muted fw-medium">Catatan Kerusakan</td>
                                <td><span class="text-danger">{{ $loan->damage_notes }}</span></td>
                            </tr>
                            @endif
                            <tr><td class="text-muted fw-medium">Diterima Oleh</td><td>{{ $loan->returnedToUser?->name ?? '-' }}</td></tr>
                            @endif
                            @if($loan->notes)
                            <tr><td class="text-muted fw-medium">Catatan</td><td>{{ $loan->notes }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- FORM PENGEMBALIAN --}}
        @if($loan->status === 'dipinjam')
        <div class="card mt-3 border-primary">
            <div class="card-header bg-primary-subtle">
                <h5 class="card-title mb-0 text-primary"><i class="ri-arrow-left-circle-line me-1"></i> Konfirmasi Pengembalian Aset</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('sarpras.peminjaman.return', ['id' => $loan->id]) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kondisi Saat Dikembalikan <span class="text-danger">*</span></label>
                            <select name="condition_on_return" class="form-select" required>
                                <option value="">— Pilih Kondisi —</option>
                                @foreach(App\Models\AssetLoan::CONDITION_OPTIONS as $c)
                                    <option value="{{ $c }}">{{ ucfirst(str_replace('_',' ', $c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Kerusakan <span class="text-muted small">(jika ada)</span></label>
                            <textarea name="damage_notes" class="form-control" rows="2" placeholder="Jelaskan jika ada kerusakan..."></textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success"><i class="ri-check-line me-1"></i> Konfirmasi Pengembalian</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- FORM PENOLAKAN --}}
        @if($loan->status === 'pending')
        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger-subtle">
                <h5 class="card-title mb-0 text-danger"><i class="ri-close-line me-1"></i> Tolak Peminjaman</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('sarpras.peminjaman.reject', ['id' => $loan->id]) }}" method="POST">
                    @csrf
                    <p class="text-muted small mb-2">Berikan alasan penolakan:</p>
                    <textarea name="rejection_reason" class="form-control mb-3" rows="2" required placeholder="Contoh: Aset sedang dalam pemeliharaan, tidak tersedia..."></textarea>
                    <button type="submit" class="btn btn-danger"><i class="ri-close-line me-1"></i> Tolak Peminjaman</button>
                </form>
            </div>
        </div>
        @endif
    </div>

    {{-- SIDEBAR --}}
    <div class="col-lg-4">
        @if($loan->asset)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Info Aset</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">Nama</td><td class="fw-medium">{{ $loan->asset->asset_name }}</td></tr>
                    <tr><td class="text-muted small">Kode</td><td><code class="small">{{ $loan->asset->asset_code ?? '-' }}</code></td></tr>
                    <tr><td class="text-muted small">Kategori</td><td>{{ $loan->asset->category?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Kondisi</td>
                        <td>
                            @php $kc=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary']; @endphp
                            <span class="badge bg-{{ $kc[$loan->asset->condition] ?? 'secondary' }}-subtle text-{{ $kc[$loan->asset->condition] ?? 'secondary' }} small">
                                {{ ucfirst(str_replace('_',' ', $loan->asset->condition)) }}
                            </span>
                        </td>
                    </tr>
                    <tr><td class="text-muted small">Status</td><td>{{ ucfirst(str_replace('_',' ', $loan->asset->status)) }}</td></tr>
                    <tr><td class="text-muted small">Ruang</td><td>{{ $loan->asset->room?->room_name ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="card-footer bg-transparent border-top">
                <a href="{{ route('sarpras.aset.show', ['id' => $loan->asset_id]) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="ri-eye-line me-1"></i> Lihat Detail Aset
                </a>
            </div>
        </div>
        @endif

        {{-- TIMELINE STATUS --}}
        <div class="card mt-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-time-line me-1"></i> Timeline</h5></div>
            <div class="card-body p-0">
                <ul class="timeline timeline-simple mb-0">
                    <li class="timeline-item mb-0">
                        <div class="timeline-icon bg-primary-subtle text-primary">📝</div>
                        <div class="timeline-content">
                            <small class="text-muted">{{ $loan->created_at->format('d/m/Y H:i') }}</small>
                            <p class="mb-0 small fw-medium">Pengajuan Peminjaman</p>
                            <p class="text-muted small mb-0">oleh {{ $loan->borrower?->name }}</p>
                        </div>
                    </li>
                    @if($loan->approved_at)
                    <li class="timeline-item mb-0">
                        <div class="timeline-icon bg-success-subtle text-success">✓</div>
                        <div class="timeline-content">
                            <small class="text-muted">{{ $loan->approved_at->format('d/m/Y H:i') }}</small>
                            <p class="mb-0 small fw-medium">@if($loan->status === 'dibatalkan') Ditolak @else Disetujui @endif</p>
                            <p class="text-muted small mb-0">oleh {{ $loan->approver?->name }}</p>
                        </div>
                    </li>
                    @endif
                    @if($loan->actual_return_date)
                    <li class="timeline-item mb-0">
                        <div class="timeline-icon bg-info-subtle text-info">↩</div>
                        <div class="timeline-content">
                            <small class="text-muted">{{ $loan->actual_return_date->format('d/m/Y') }}</small>
                            <p class="mb-0 small fw-medium">Dikembalikan</p>
                            <p class="text-muted small mb-0">{{ $loan->returnedToUser?->name }}</p>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection