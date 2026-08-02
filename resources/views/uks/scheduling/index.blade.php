@extends('layouts.master')

@section('title', 'Penjadwalan Shift UKS — Penugasan')
@section('subtitle', 'Penjadwalan Shift UKS')

@section('css')
<style>
    .shift-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.3s;
    }
    .shift-card:hover {
        box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    }
    .shift-card.pagi { border-left: 4px solid #fbbf24; }
    .shift-card.siang { border-left: 4px solid #f59e0b; }
    .shift-card.malam { border-left: 4px solid #6366f1; }
    .shift-card.full { border-left: 4px solid #10b981; }
    .shift-icon {
        width: 50px; height: 50px;
        border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        font-size: 24px;
    }
    .icon-pagi  { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .icon-siang { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .icon-malam { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
    .icon-full  { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .shift-time {
        font-size: 14px; padding: 4px 8px; border-radius: 6px;
        background: rgba(0,0,0,0.05); display: inline-block;
    }
    .staff-pill {
        display: inline-flex; align-items: center; gap: 6px;
        background: #f8f9fa; padding: 8px 12px; border-radius: 30px;
        font-size: 13px; margin: 4px 4px 4px 0;
        border: 1px solid #e9ecef;
    }
    .staff-pill .name { font-weight: 500; color: #1e293b; }
    .staff-pill .remove {
        color: #dc3545; cursor: pointer; opacity: 0.6;
    }
    .staff-pill .remove:hover { opacity: 1; }
    .date-nav {
        background: linear-gradient(135deg, #405189 0%, #27346c 100%);
        color: white; padding: 18px 24px; border-radius: 12px;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Penjadwalan Shift UKS @endslot
        @slot('title') Penjadwalan Shift UKS @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Date Navigator --}}
    <div class="date-nav mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1 text-white"><i class="ri-calendar-line me-2"></i>Penugasan Shift Hari Ini</h5>
                <p class="mb-0 text-white-50">{{ $viewDate->isoFormat('dddd, D MMMM Y') }}</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="text-white-50 small mb-0">Lihat tanggal:</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $viewDate->format('Y-m-d') }}" style="width: 160px;">
                    <button type="submit" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></button>
                </form>
                <a href="{{ route('user.uks.scheduling.export', ['userId' => Auth::id()]) }}" class="btn btn-sm btn-soft-light">
                    <i class="ri-download-line me-1"></i>Export CSV
                </a>
            </div>
        </div>
    </div>

    {{-- Coverage Statistics --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-start border-{{ ($scheduleByTime['pagi'] ?? []) ? 'warning' : 'secondary' }} border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Shift Pagi</div>
                            <div class="h3 mb-0">{{ count($scheduleByTime['pagi'] ?? []) }}</div>
                        </div>
                        <i class="ri-sun-line fs-1 text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-{{ ($scheduleByTime['siang'] ?? []) ? 'warning' : 'secondary' }} border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Shift Siang</div>
                            <div class="h3 mb-0">{{ count($scheduleByTime['siang'] ?? []) }}</div>
                        </div>
                        <i class="ri-sun-foggy-line fs-1 text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-{{ ($scheduleByTime['malam'] ?? []) ? 'info' : 'secondary' }} border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Shift Malam</div>
                            <div class="h3 mb-0">{{ count($scheduleByTime['malam'] ?? []) }}</div>
                        </div>
                        <i class="ri-moon-line fs-1 text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-{{ ($scheduleByTime['full'] ?? []) ? 'success' : 'secondary' }} border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Shift Full Day</div>
                            <div class="h3 mb-0">{{ count($scheduleByTime['full'] ?? []) }}</div>
                        </div>
                        <i class="ri-24-hours-line fs-1 text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Shift Cards --}}
    <div class="row g-4">
        {{-- Shift Pagi --}}
        <div class="col-lg-6">
            <div class="shift-card pagi p-4 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="shift-icon icon-pagi">
                            <i class="ri-sun-line"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Shift Pagi</h5>
                            <div class="shift-time">
                                <i class="ri-time-line me-1"></i>06:00 — 12:00
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-soft-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddShift" data-shift-type="pagi" data-shift-date="{{ $viewDate->format('Y-m-d') }}">
                        <i class="ri-add-line"></i>
                    </button>
                </div>

                @if(count($scheduleByTime['pagi'] ?? []) > 0)
                    <div class="d-flex flex-wrap">
                        @foreach($scheduleByTime['pagi'] as $assign)
                            <div class="staff-pill">
                                <i class="ri-user-line text-muted"></i>
                                <span class="name">{{ $assign->assignedTo?->name ?? 'Tanpa nama' }}</span>
                                <form method="POST" action="{{ route('user.uks.scheduling.destroy', ['userId' => Auth::id(), 'uuid' => $assign->id]) }}" class="m-0">
                                    @csrf @method('DELETE')
                                    <span class="remove" onclick="if(confirm('Hapus shift ini?')) this.closest('form').submit();" title="Hapus shift">×</span>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning mb-0 py-2">
                        <i class="ri-information-line me-1"></i>Belum ada penugasan shift pagi.
                    </div>
                @endif
            </div>
        </div>

        {{-- Shift Siang --}}
        <div class="col-lg-6">
            <div class="shift-card siang p-4 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="shift-icon icon-siang">
                            <i class="ri-sun-foggy-line"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Shift Siang</h5>
                            <div class="shift-time">
                                <i class="ri-time-line me-1"></i>12:00 — 18:00
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-soft-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddShift" data-shift-type="siang" data-shift-date="{{ $viewDate->format('Y-m-d') }}">
                        <i class="ri-add-line"></i>
                    </button>
                </div>

                @if(count($scheduleByTime['siang'] ?? []) > 0)
                    <div class="d-flex flex-wrap">
                        @foreach($scheduleByTime['siang'] as $assign)
                            <div class="staff-pill">
                                <i class="ri-user-line text-muted"></i>
                                <span class="name">{{ $assign->assignedTo?->name ?? 'Tanpa nama' }}</span>
                                <form method="POST" action="{{ route('user.uks.scheduling.destroy', ['userId' => Auth::id(), 'uuid' => $assign->id]) }}" class="m-0">
                                    @csrf @method('DELETE')
                                    <span class="remove" onclick="if(confirm('Hapus shift ini?')) this.closest('form').submit();" title="Hapus shift">×</span>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning mb-0 py-2">
                        <i class="ri-information-line me-1"></i>Belum ada penugasan shift siang.
                    </div>
                @endif
            </div>
        </div>

        {{-- Shift Malam --}}
        <div class="col-lg-6">
            <div class="shift-card malam p-4 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="shift-icon icon-malam">
                            <i class="ri-moon-line"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Shift Malam</h5>
                            <div class="shift-time">
                                <i class="ri-time-line me-1"></i>18:00 — 06:00 (hari berikutnya)
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-soft-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddShift" data-shift-type="malam" data-shift-date="{{ $viewDate->format('Y-m-d') }}">
                        <i class="ri-add-line"></i>
                    </button>
                </div>

                @if(count($scheduleByTime['malam'] ?? []) > 0)
                    <div class="d-flex flex-wrap">
                        @foreach($scheduleByTime['malam'] as $assign)
                            <div class="staff-pill">
                                <i class="ri-user-line text-muted"></i>
                                <span class="name">{{ $assign->assignedTo?->name ?? 'Tanpa nama' }}</span>
                                <form method="POST" action="{{ route('user.uks.scheduling.destroy', ['userId' => Auth::id(), 'uuid' => $assign->id]) }}" class="m-0">
                                    @csrf @method('DELETE')
                                    <span class="remove" onclick="if(confirm('Hapus shift ini?')) this.closest('form').submit();" title="Hapus shift">×</span>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info mb-0 py-2">
                        <i class="ri-information-line me-1"></i>Belum ada penugasan shift malam.
                    </div>
                @endif
            </div>
        </div>

        {{-- Shift Full Day --}}
        <div class="col-lg-6">
            <div class="shift-card full p-4 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="shift-icon icon-full">
                            <i class="ri-24-hours-line"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Shift Full Day</h5>
                            <div class="shift-time">
                                <i class="ri-time-line me-1"></i>06:00 — 18:00
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-soft-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddShift" data-shift-type="full_day" data-shift-date="{{ $viewDate->format('Y-m-d') }}">
                        <i class="ri-add-line"></i>
                    </button>
                </div>

                @if(count($scheduleByTime['full'] ?? []) > 0)
                    <div class="d-flex flex-wrap">
                        @foreach($scheduleByTime['full'] as $assign)
                            <div class="staff-pill">
                                <i class="ri-user-line text-muted"></i>
                                <span class="name">{{ $assign->assignedTo?->name ?? 'Tanpa nama' }}</span>
                                <form method="POST" action="{{ route('user.uks.scheduling.destroy', ['userId' => Auth::id(), 'uuid' => $assign->id]) }}" class="m-0">
                                    @csrf @method('DELETE')
                                    <span class="remove" onclick="if(confirm('Hapus shift ini?')) this.closest('form').submit();" title="Hapus shift">×</span>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-success mb-0 py-2">
                        <i class="ri-information-line me-1"></i>Belum ada penugasan shift full day.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal: Add Shift --}}
    <div class="modal fade" id="modalAddShift" tabindex="-1" aria-labelledby="modalAddShiftLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddShiftLabel">Tambah Penugasan Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('user.uks.scheduling.store', ['userId' => Auth::id()]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="shift_date" class="form-label">Tanggal Shift</label>
                            <input type="date" class="form-control" id="shift_date" name="shift_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="shift_type" class="form-label">Tipe Shift</label>
                            <select class="form-select" id="shift_type" name="shift_type" required>
                                <option value="pagi">Pagi (06:00–12:00)</option>
                                <option value="siang">Siang (12:00–18:00)</option>
                                <option value="malam">Malam (18:00–06:00)</option>
                                <option value="full_day">Full Day (06:00–18:00)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Anggota UKS</label>
                            <select class="form-select" id="assigned_to" name="assigned_to" required>
                                <option value="">— Pilih anggota —</option>
                                {{-- Will be populated via AJAX or directly with all UKS staff --}}
                                @foreach($uksStaff ?? [] as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="start_time" class="form-label">Mulai (opsional)</label>
                                <input type="time" class="form-control" id="start_time" name="start_time">
                            </div>
                            <div class="col-6">
                                <label for="end_time" class="form-label">Selesai (opsional)</label>
                                <input type="time" class="form-control" id="end_time" name="end_time">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500" placeholder="Tambahkan catatan atau instruksi khusus..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Modal: populate shift date and type from button
        const modal = document.getElementById('modalAddShift');
        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const shiftType = button.getAttribute('data-shift-type');
            const shiftDate = button.getAttribute('data-shift-date');
            document.getElementById('shift_date').value = shiftDate;
            document.getElementById('shift_type').value = shiftType;
        });
    });
</script>
@endsection