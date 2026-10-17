@extends('layouts.master')
@section('title', 'Permintaan Akses Edit')
@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">Kurikulum</a> @endslot
        @slot('title') Permintaan Akses Edit @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="ri-information-line fs-5"></i>
        <div class="small">
            <strong>Kebijakan dokumen rumpun:</strong> dokumen kurikulum (TP, ATP, PROTA, PROSEM, Modul Ajar/RPM) adalah milik bersama rumpun —
            dikerjakan <em>satu dokumen secara keroyokan</em>. Guru serumpun yang belum terdaftar sebagai pengampu dapat
            <strong>melihat &amp; menyalin</strong>; untuk ikut <strong>mengedit</strong> silakan ajukan permintaan di bawah ini.
            Pemegang izin dapat mencabut akses kembali kapan saja.
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-add-circle-line me-1"></i>Ajukan Permintaan Akses Edit</h5>
                </div>
                <div class="card-body">
                    @if($requestableSubjects->isEmpty())
                        <p class="text-muted mb-0 small">
                            Tidak ada mapel serumpun yang perlu diminta — Anda sudah memiliki akses edit, atau belum terhubung ke rumpun mapel.
                        </p>
                    @else
                        <form method="POST" action="{{ route('user.kurikulum.akses-edit.store', ['userId' => $userId]) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">— pilih mapel serumpun —</option>
                                    @foreach($requestableSubjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ $activeAy && $ay->id === $activeAy->id ? 'selected' : '' }}>
                                            {{ $ay->name }}{{ $ay->is_active ? ' (aktif)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan / Catatan</label>
                                <textarea name="reason" rows="2" maxlength="1000" class="form-control" placeholder="Mis. ikut menyusun ATP & PROTA mapel serumpun semester ini."></textarea>
                            </div>
                            <button class="btn btn-primary w-100"><i class="ri-send-plane-line me-1"></i>Kirim Permintaan</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-1"></i>Permintaan Saya</h5>
                    <span class="badge bg-secondary-subtle text-secondary">{{ $myRequests->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mapel</th>
                                    <th>TA</th>
                                    <th>Status</th>
                                    <th>Diputuskan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($myRequests as $grant)
                                    <tr>
                                        <td class="fw-medium">{{ $grant->subject?->name ?? '—' }}</td>
                                        <td class="small">{{ $grant->academicYear?->name ?? '—' }}</td>
                                        <td>
                                            @php
                                                $badge = match($grant->status) {
                                                    'approved' => 'bg-success-subtle text-success',
                                                    'rejected' => 'bg-danger-subtle text-danger',
                                                    'revoked' => 'bg-secondary-subtle text-secondary',
                                                    default => 'bg-warning-subtle text-warning',
                                                };
                                                $label = match($grant->status) {
                                                    'approved' => 'Disetujui',
                                                    'rejected' => 'Ditolak',
                                                    'revoked' => 'Dicabut',
                                                    default => 'Menunggu',
                                                };
                                            @endphp
                                            <span class="badge {{ $badge }}">{{ $label }}</span>
                                            @if($grant->decision_note)
                                                <div class="small text-muted mt-1">{{ $grant->decision_note }}</div>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            {{ $grant->decided_at?->locale('id')->translatedFormat('d M Y') ?? '—' }}
                                            @if($grant->decider)<br>{{ $grant->decider->name }}@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada permintaan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-user-received-line me-1"></i>Menunggu Keputusan Anda</h5>
                    <span class="badge bg-warning-subtle text-warning">{{ $pendingForMe->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pemohon</th>
                                    <th>Mapel</th>
                                    <th>TA</th>
                                    <th>Alasan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingForMe as $grant)
                                    <tr>
                                        <td class="fw-medium">{{ $grant->requester?->name ?? '—' }}</td>
                                        <td>{{ $grant->subject?->name ?? '—' }}</td>
                                        <td class="small">{{ $grant->academicYear?->name ?? '—' }}</td>
                                        <td class="small text-muted" style="max-width:220px">{{ $grant->reason ?: '—' }}</td>
                                        <td class="text-end text-nowrap">
                                            <form method="POST" class="d-inline" action="{{ route('user.kurikulum.akses-edit.approve', ['userId' => $userId, 'id' => $grant->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success"><i class="ri-check-line me-1"></i>Setujui</button>
                                            </form>
                                            <form method="POST" class="d-inline" action="{{ route('user.kurikulum.akses-edit.reject', ['userId' => $userId, 'id' => $grant->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-soft-danger"><i class="ri-close-line me-1"></i>Tolak</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada permintaan yang menunggu keputusan Anda.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-shield-check-line me-1"></i>Akses Edit Aktif</h5>
                    <span class="badge bg-success-subtle text-success">{{ $activeGrants->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pemegang Izin</th>
                                    <th>Mapel</th>
                                    <th>TA</th>
                                    <th>Disetujui</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeGrants as $grant)
                                    <tr>
                                        <td class="fw-medium">{{ $grant->requester?->name ?? '—' }}</td>
                                        <td>{{ $grant->subject?->name ?? '—' }}</td>
                                        <td class="small">{{ $grant->academicYear?->name ?? '—' }}</td>
                                        <td class="small text-muted">{{ $grant->decided_at?->locale('id')->translatedFormat('d M Y') ?? '—' }}</td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline" action="{{ route('user.kurikulum.akses-edit.revoke', ['userId' => $userId, 'id' => $grant->id]) }}"
                                                  onsubmit="return confirm('Cabut akses edit {{ $grant->requester?->name }} untuk {{ $grant->subject?->name }}?');">
                                                @csrf
                                                <button class="btn btn-sm btn-soft-secondary"><i class="ri-lock-line me-1"></i>Cabut</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada akses edit aktif yang Anda kelola.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
