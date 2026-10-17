@extends('layouts.master')
@section('title') Detail Izin Sakit @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}">Izin Sakit</a> @endslot
        @slot('title') Detail Izin Sakit @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Izin Sakit</h5>
                        <div>
                            <a href="{{ route('user.uks.health-permits.edit', ['userId' => $userId, 'uuid' => $permit->id]) }}"
                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i> Edit</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:160px">Nama Santri</td><td>{{ $permit->student?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tahun Ajaran</td><td>{{ $permit->academicYear?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Jenis Izin</td><td>{{ $permit->permit_type_text }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tanggal Mulai</td><td>{{ $permit->start_date?->format('d/m/Y') }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tanggal Selesai</td><td>{{ $permit->end_date?->format('d/m/Y') ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Hari Istirahat</td><td>{{ $permit->rest_days }} hari</td></tr>
                            <tr>
                                <td class="fw-semibold text-muted">Status</td>
                                <td>
                                    @php $sts = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','extended'=>'info','cancelled'=>'secondary']; @endphp
                                    <span class="badge bg-{{ $sts[$permit->status] ?? 'secondary' }}">{{ $permit->status_text }}</span>
                                </td>
                            </tr>
                            @if($permit->description)
                                <tr><td class="fw-semibold text-muted">Deskripsi</td><td>{{ $permit->description }}</td></tr>
                            @endif
                            <tr><td class="fw-semibold text-muted">Asrama</td><td>{{ $permit->dormitory?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Wali Dinotifikasi</td>
                                <td>
                                    @if($permit->parent_notified)
                                        <span class="badge bg-success">Ya — {{ $permit->parent_notified_at?->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="badge bg-light text-dark">Belum</span>
                                    @endif
                                </td>
                            </tr>
                            @if($permit->approval_note)
                                <tr><td class="fw-semibold text-muted">Catatan Approval</td><td>{{ $permit->approval_note }}</td></tr>
                            @endif
                        </table>
                    </div>

                    @if($permit->status === 'pending')
                        <hr>
                        <div class="d-flex gap-2 flex-wrap">
                            <form method="POST" action="{{ route('user.uks.health-permits.approve', ['userId' => $userId, 'uuid' => $permit->id]) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm"><i class="ri-check-line me-1"></i> Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('user.uks.health-permits.reject', ['userId' => $userId, 'uuid' => $permit->id]) }}">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm"><i class="ri-close-line me-1"></i> Tolak</button>
                            </form>
                            @if(!$permit->parent_notified)
                                <form method="POST" action="{{ route('user.uks.health-permits.notify-parent', ['userId' => $userId, 'uuid' => $permit->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm"><i class="ri-notification-3-line me-1"></i> Notifikasi Wali</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
