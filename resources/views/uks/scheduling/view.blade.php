@extends('layouts.master')

@php
    use App\Models\User;
    // Fetch eligible UKS staff
    $staffs = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['uks_kepala', 'uks_admin_putra', 'uks_admin_putri']))
        ->orderBy('name')
        ->get();
@endphp

@section('title', 'Rincian Shift UKS — ' . $assignment->shift_date)
@section('subtitle', 'Rincian Penugasan Shift')

@section('css')
<style>
    .detail-row {
        display: flex;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }
    .detail-row:last-child { border-bottom: 0; }
    .detail-label { flex: 0 0 20%; color: #64748b; font-size: 14px; }
    .detail-value { flex: 1; font-weight: 500; font-size: 14px; color: #1e293b; }
    .badge-shift { padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
</style>
@endsection

@section('content')
    @php $userId = Auth::id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2')
            <a href="{{ route('user.uks.scheduling.index', ['userId' => Auth::id()]) }}">Penjadwalan Shift UKS</a>
        @endslot
        @slot('title') Rincian Shift : {{ $assignment->shift_date }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-gradient-white border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ri-calendar-check-line me-2"></i>Rincian Penugasan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <div class="detail-label">Tanggal Shift</div>
                        <div class="detail-value">{{ \Carbon\Carbon::parse($assignment->shift_date)->isoFormat('dddd, D MMMM Y') }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Tipe Shift</div>
                        <div class="detail-value">
                            <span class="badge-shift {{
                                match($assignment->shift_type) {
                                    'pagi' => 'bg-warning-subtle text-warning',
                                    'siang' => 'bg-warning-subtle text-warning',
                                    'malam' => 'bg-info-subtle text-info',
                                    'full_day' => 'bg-success-subtle text-success',
                                    default => 'bg-secondary-subtle text-secondary'
                                }
                            }}">
                                {{ ucfirst($assignment->shift_type) }}
                            </span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Waktu Mulai</div>
                        <div class="detail-value">
                            @if($assignment->start_time)
                                <i class="ri-time-line me-1 text-muted"></i>
                                <strong>{{ date('H:i', strtotime($assignment->start_time))) }}</strong>
                            @else
                                {{-- Use default based on type --}}
                                @php
                                    $defaults = [
                                        'pagi'  => '06:00',
                                        'siang' => '12:00',
                                        'malam' => '18:00',
                                        'full_day' => '06:00',
                                    ];
                                    echo $defaults[$assignment->shift_type] ?? '—';
                                @endphp
                            @endif
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Waktu Selesai</div>
                        <div class="detail-value">
                            @if($assignment->end_time)
                                <i class="ri-time-line me-1 text-muted"></i>
                                <strong>{{ date('H:i', strtotime($assignment->end_time))) }}</strong>
                            @else
                                @php
                                    $defaults = [
                                        'pagi'      => '12:00',
                                        'siang'     => '18:00',
                                        'malam'     => '06:00 (hari berikutnya)',
                                        'full_day'  => '18:00',
                                    ];
                                    echo $defaults[$assignment->shift_type] ?? '—';
                                @endphp
                            @endif
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Catatan</div>
                        <div class="detail-value">
                            @if($assignment->notes)
                                <p class="mb-0 fw-normal" style="font-size:14px;">{{ $assignment->notes }}</p>
                            @else
                                <span class="text-muted">Tidak ada catatan</span>
                            @endif
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Dibuat Oleh</div>
                        <div class="detail-value">
                            <i class="ri-user-line me-2 text-primary"></i>{{ $assignment->createdBy?->name ?? 'Administrator' }}
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Tanggal & Waktu Dibuat</div>
                        <div class="detail-value">
                            <i class="ri-calendar-event-line me-2 text-secondary"></i>
                            {{ \Carbon\Carbon::parse($assignment->created_at)->isoFormat('dddd, D MMMM Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('user.uks.scheduling.index', ['userId' => Auth::id()]) }}" class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-circle-line me-1"></i>Kembali ke Jadwal
                </a>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditShift" onclick="populateEditForm()">
                    <i classri-edit-line me-1"></i>Edit
                </button>
                <form method="POST" action="{{ route('user.uks.scheduling.destroy', ['userId' => Auth::id(), 'uuid' => $assignment->id]) }}" class="d-inline-flex">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin menghapus penugasan ini?')">
                        <i class="ri-delete-bin-line me-1"></i>Hapus
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <h5 class="card-header">Anggota Terkait</h5>
                <div class="card-body text-center">
                    @if($assignment->assignedTo)
                        <div class="avatar-xl mx-auto mb-3">
                            <span class="avatar-title bg-primary text-white rounded-circle fs-2">
                                {{ strtoupper(substr($assignment->assignedTo->name, 0, 1)) }}
                            </span>
                        </div>
                        <h5 class="card-title">{{ $assignment->assignedTo->name }}</h5>
                        <p class="card-text text-muted small mb-2">{{ $assignment->assignedTo->email }}</p>
                        <div class="text-muted small">
                            ID: {{ $assignment->assignedTo->id }}
                        </div>
                    @else
                        <div class="avatar-xl mx-auto mb-3">
                            <span class="avatar-title bg-secondary text-white rounded-circle fs-2">?</span>
                        </div>
                        <p class="text-muted">Data anggota tidak ditemukan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="modalEditShift" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Penugasan Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.uks.scheduling.update', ['userId' => Auth::id(), 'uuid' => $assignment->id]) }}">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="id" value="{{ $assignment->id }}">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Shift</label>
                            <input type="date" class="form-control" name="shift_date" value="{{ $assignment->shift_date }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe Shift</label>
                            <select class="form-select" name="shift_type" required>
                                <option value="pagi" {{ $assignment->shift_type == 'pagi' ? 'selected' : '' }}>Pagi (06:00–12:00)</option>
                                <option value="siang" {{ $assignment->shift_type == 'siang' ? 'selected' : '' }}>Siang (12:00–18:00)</option>
                                <option value="malam" {{ $assignment->shift_type == 'malam' ? 'selected' : '' }}>Malam (18:00–06:00)</option>
                                <option value="full_day" {{ $assignment->shift_type == 'full_day' ? 'selected' : '' }}>Full Day (06:00–18:00)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Anggota UKS</label>
                            <select class="form-select" name="assigned_to" required>
                                <option value="">— Pilih anggota —</option>
                                @foreach($staffs as $s)
                                    <option value="{{ $s->id }}" {{ ($assignment->assigned_to_id === $s->id) ? 'selected' : '' }}>
                                        {{ $s->name }} ({{ $s->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Mulai (opsional)</label>
                                <input type="time" class="form-control" name="start_time" value="{{ $assignment->start_time }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Selesai (opsional)</label>
                                <input type="time" class="form-control" name="end_time" value="{{ $assignment->end_time }}">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="notes" rows="2" maxlength="500">{{ $assignment->notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Populate select with staff list (simplified - could load via API)
        const select = document.querySelector('select[name="assigned_to"]');
        // In real app, this would be populated with current assignment's user and other possible staff
        if ("{{ $assignment->assignedTo?->id }}" && "{{ $assignment->assignedTo?->name }}") {
            select.innerHTML = '<option value="{{ $assignment->assignedTo?->id }}" selected>' +
                '{{ $assignment->assignedTo?->name }} ({{ $assignment->assignedTo?->email }})</option>';
        } else {
            select.innerHTML += '<option value="">'— Pilih anggota —</option>';
        }
    });
</script>
@endsection