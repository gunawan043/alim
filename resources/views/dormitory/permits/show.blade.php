@extends('layouts.master')
@section('title') Detail Perizinan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('title') Detail @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Overdue Warning --}}
    @if($permit->isOverdue)
        <div class="alert alert-danger d-flex align-items-center gap-3" role="alert">
            <i class="ri-alarm-warning-line fs-2"></i>
            <div>
                <strong class="d-block">Santri Terlambat Kembali!</strong>
                Izin ini sudah melebihi taksiran kembali
                ({{ $permit->expected_return_datetime->format('d/m/Y H:i') }}).
                @if($permit->overdue_notified_count > 0)
                    Notifikasi overdue sudah dikirim <strong>{{ $permit->overdue_notified_count }}x</strong>.
                @endif
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Left: Permit Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-file-list-line me-2 text-primary"></i>
                            Detail Perizinan
                        </h5>
                        @if($permit->status === 'pending')
                            <span class="badge bg-warning-subtle text-warning fs-6">Menunggu</span>
                        @elseif($permit->status === 'approved')
                            <span class="badge bg-success-subtle text-success fs-6">Disetujui</span>
                        @elseif($permit->status === 'rejected')
                            <span class="badge bg-danger-subtle text-danger fs-6">Ditolak</span>
                        @elseif($permit->status === 'returned')
                            <span class="badge bg-secondary-subtle text-secondary fs-6">Sudah Pulang</span>
                        @elseif($permit->status === 'overdue' || $permit->isOverdue)
                            <span class="badge bg-danger fs-6">Terlambat</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        {{-- Permit Type --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Jenis Izin</div>
                                <div class="fw-semibold">{{ $permit->permit_type_text }}</div>
                            </div>
                        </div>
                        {{-- Destination --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Tujuan</div>
                                <div class="fw-semibold">{{ $permit->destination ?: '—' }}</div>
                            </div>
                        </div>
                        {{-- Departure --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Tanggal & Jam Berangkat</div>
                                <div class="fw-semibold">
                                    @if($permit->departure_datetime)
                                        {{ $permit->departure_datetime->format('d/m/Y') }}
                                        <span class="text-muted">{{ $permit->departure_datetime->format('H:i') }}</span>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- Expected Return --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Taksiran Kembali</div>
                                <div class="fw-semibold">
                                    @if($permit->expected_return_datetime)
                                        {{ $permit->expected_return_datetime->format('d/m/Y') }}
                                        <span class="text-muted">{{ $permit->expected_return_datetime->format('H:i') }}</span>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- Actual Return --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Tanggal & Jam Kembali (Aktual)</div>
                                <div class="fw-semibold {{ $permit->actual_return_datetime ? 'text-success' : 'text-muted' }}">
                                    {{ $permit->actual_return_datetime ? $permit->actual_return_datetime->format('d/m/Y H:i') : '— belum kembali —' }}
                                </div>
                            </div>
                        </div>
                        {{-- Room --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Kamar</div>
                                <div class="fw-semibold">{{ $permit->room?->name ?? '—' }}</div>
                            </div>
                        </div>
                        {{-- Purpose --}}
                        @if($permit->purpose)
                        <div class="col-12">
                            <div class="bg-light rounded p-3">
                                <div class="text-muted small mb-1">Keperluan / Keterangan</div>
                                <div class="fw-semibold">{{ $permit->purpose }}</div>
                            </div>
                        </div>
                        @endif
                        {{-- Approval Note --}}
                        @if($permit->approval_note)
                        <div class="col-12">
                            <div class="bg-warning-subtle rounded p-3">
                                <div class="text-muted small mb-1">Catatan Persetujuan / Penolakan</div>
                                <div class="fw-semibold">{{ $permit->approval_note }}</div>
                            </div>
                        </div>
                        @endif
                        {{-- Created info --}}
                        <div class="col-md-4">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Diajukan oleh</div>
                                <div class="fw-semibold">{{ $permit->creator?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Disetujui oleh</div>
                                <div class="fw-semibold">{{ $permit->approvedBy?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Diajukan pada</div>
                                <div class="fw-semibold">{{ $permit->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Student + Mahrom + Actions --}}
        <div class="col-lg-4">
            {{-- Student Info --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-user-line me-2 text-primary"></i>Data Santri</h5>
                </div>
                <div class="card-body">
                    @if($permit->student)
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="avatar-md">
                                <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-5 fw-bold">
                                    {{ strtoupper(substr($permit->student->name, 0, 2)) }}
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold fs-5">{{ $permit->student->name }}</div>
                                @if($permit->student->nisn)
                                    <div class="text-muted small">NISN: {{ $permit->student->nisn }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-muted small">Kamar</div>
                                <div class="fw-semibold">{{ $permit->room?->name ?? '—' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Jenis Kelamin</div>
                                <div class="fw-semibold">{{ $permit->student->gender_text }}</div>
                            </div>
                            @if($permit->student->mobile_phone)
                            <div class="col-12">
                                <div class="text-muted small">No. HP</div>
                                <div class="fw-semibold">{{ $permit->student->mobile_phone }}</div>
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="text-muted">Data tidak tersedia.</div>
                    @endif
                </div>
            </div>

            {{-- Mahrom / Penjemput Info --}}
            @if($permit->companion_name || $permit->mahrom)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-user-heart-line me-2 text-primary"></i>
                        Penjemput
                        @if($permit->companion_is_mahrom)
                            <span class="badge bg-dark-subtle text-dark ms-1">
                                <i class="ri-shield-check-line me-1"></i>Mahrom
                            </span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @if($permit->mahrom)
                        <div class="col-12">
                            <div class="text-muted small">Mahrom</div>
                            <div class="fw-semibold">{{ $permit->mahrom->name }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Hubungan</div>
                            <div class="fw-semibold">{{ $permit->mahrom->relation ?? '—' }}</div>
                        </div>
                        @endif
                        <div class="col-12">
                            <div class="text-muted small">Nama</div>
                            <div class="fw-semibold">{{ $permit->companion_name }}</div>
                        </div>
                        @if($permit->companion_relation)
                        <div class="col-6">
                            <div class="text-muted small">Hubungan</div>
                            <div class="fw-semibold">{{ $permit->companion_relation }}</div>
                        </div>
                        @endif
                        @if($permit->companion_phone)
                        <div class="col-6">
                            <div class="text-muted small">No. Telepon</div>
                            <div class="fw-semibold">{{ $permit->companion_phone }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Action Buttons --}}
            @if($permit->status === 'pending')
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-settings-2-line me-2 text-primary"></i>Aksi</h5>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ route('user.asrama.permits.approve', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan Persetujuan (opsional)</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Catatan jika ada..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-2"
                                onclick="return confirm('Setujui izin ini?')">
                            <i class="ri-check-line me-1"></i> Setujui Izin
                        </button>
                    </form>

                    <hr>

                    <form method="POST"
                          action="{{ route('user.asrama.permits.reject', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="note" class="form-control @error('note') is-invalid @enderror"
                                      rows="2" placeholder="Jelaskan alasan penolakan..." required></textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger w-100"
                                onclick="return confirm('Tolak izin ini?')">
                            <i class="ri-close-line me-1"></i> Tolak Izin
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Record Return Button (approved + not yet returned) --}}
            @if(in_array($permit->status, ['approved', 'overdue']) && !$permit->actual_return_datetime)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-logout-box-r-line me-2 text-primary"></i>Pencatatan Kembali</h5>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ route('user.asrama.permits.return', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100"
                                onclick="return confirm('Catat santri sudah kembali ke asrama?')">
                            <i class="ri-check-line me-1"></i> Catat Kembali
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Back Button --}}
            <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
               class="btn btn-light w-100">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Status Timeline --}}
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-timeline me-2 text-primary"></i>Timeline Status</h5>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        @php
                            $steps = [
                                ['label' => 'Diajukan',     'status' => 'submitted',   'icon' => 'ri-send-plane-line', 'cls' => 'bg-primary'],
                                ['label' => 'Disetujui',    'status' => 'approved',     'icon' => 'ri-checkbox-circle-line', 'cls' => 'bg-success'],
                                ['label' => 'Berangkat',   'status' => 'departed',    'icon' => 'ri-logout-box-r-line', 'cls' => 'bg-info'],
                                ['label' => 'Kembali',      'status' => 'returned',     'icon' => 'ri-login-box-line', 'cls' => 'bg-secondary'],
                            ];

                            $current = match($permit->status) {
                                'pending'   => 0,
                                'approved'  => 1,
                                'rejected'  => -1,
                                'returned'  => 3,
                                'overdue'   => 2,
                                default     => 0,
                            };
                        @endphp

                        @foreach($steps as $idx => $step)
                            @php
                                $done   = $idx < $current;
                                $active = $idx === $current;
                                $rejected = $current === -1;
                            @endphp
                            <div class="col-auto text-center">
                                <div class="mb-2">
                                    @if($rejected)
                                        <div class="avatar-sm rounded-circle bg-danger-subtle border border-2 border-danger">
                                            <i class="ri-close-line text-danger"></i>
                                        </div>
                                    @else
                                        <div class="avatar-sm rounded-circle {{ $done || $active ? $step['cls'] : 'bg-secondary-subtle' }} border border-2 {{ $active ? 'border-dark' : 'border-transparent' }}">
                                            <i class="{{ $step['icon'] }} text-white"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="small fw-semibold {{ $active ? 'text-primary' : ($done ? 'text-success' : 'text-muted') }}">{{ $step['label'] }}</div>
                            </div>
                            @if(!$loop->last)
                                <div class="col-auto d-flex align-items-center">
                                    <div class="progress" style="height:3px; width:60px;">
                                        <div class="progress-bar {{ ($done && !$rejected) ? 'bg-success' : 'bg-secondary' }}" style="width:100%;"></div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if($permit->status === 'rejected')
                        <div class="alert alert-danger mt-3 mb-0 text-center">
                            <i class="ri-close-circle-line me-2"></i>
                            <strong>Izin Ditolak.</strong>
                            @if($permit->approval_note)
                                Alasan: {{ $permit->approval_note }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection