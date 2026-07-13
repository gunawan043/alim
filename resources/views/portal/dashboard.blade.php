@extends('layouts.master')
@section('title') Portal Wali Santri @endsection

@section('breadcrumb')
    @component('components.breadcrumb')
        @slot('title') Halo, {{ $wali->role }} @endslot
        <a href="{{ route('portal.timeline', ['token' => $token]) }}" class="btn btn-sm btn-outline-secondary ms-3">
            <i class="ri-history-line me-1"></i> Timeline
        </a>
        <a href="{{ route('portal.notifications', ['token' => $token]) }}" class="btn btn-sm btn-outline-primary ms-2">
            <i class="ri-notification-3-line me-1"></i> Notifikasi
            @php
                $unreadCount = \App\Models\NotificationUniversal::where('user_id', $wali->user_id)->where('is_read', false)->where('is_archived', false)->where(function($q){ $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()); })->count();
            @endphp
            @if($unreadCount > 0)
                <span class="badge bg-danger rounded-pill">{{ $unreadCount }}</span>
            @endif
        </a>
    @endcomponent
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-body">

        {{-- Welcome --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center">
                    <i class="ri-user-heart-line me-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Selamat datang di Portal Wali Santri!</strong><br>
                        Di sini Anda bisa mengajukan izin pulang, penjengukan, dan izin sakit untuk anak asuhan Anda.
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="ri-check-line me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Children List --}}
        @if($students->isNotEmpty())
            <div class="row g-4 mb-4">
                @foreach($students as $w)
                    <div class="col-xl-4 col-md-6">
                        <div class="card shadow-sm border-start border-primary border-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px;height: 48px;">
                                            <i class="ri-child-line" style="font-size: 1.2rem;"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fw-bold">{{ $w->student->name }}</h5>
                                        <small class="text-muted">
                                            {{ $w->student->nis ?? 'N/A' }} &middot;
                                            {{ $w->student->dormitory->name ?? 'N/A' }}
                                        </small>
                                    </div>
                                </div>

                                {{-- Quick Actions --}}
                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#leaveModal{{ $w->student_id }}">
                                        <i class="ri-home-line me-1"></i>Izin Pulang
                                    </button>
                                    <button class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#visitModal{{ $w->student_id }}">
                                        <i class="ri-footprint-line me-1"></i>Penjengukan
                                    </button>
                                    <button class="btn btn-sm btn-outline-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#healthModal{{ $w->student_id }}">
                                        <i class="ri-hospital-line me-1"></i>Izin Sakit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ═══════ LEAVE MODAL ═══════ --}}
                    <div class="modal fade" id="leaveModal{{ $w->student_id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <form action="{{ route('portal.leave', ['token' => $token]) }}" method="POST">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="ri-home-line me-2"></i>Formulir Izin Pulang</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="student_id" value="{{ $w->student_id }}">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Jenis Izin <span class="text-danger">*</span></label>
                                                <select name="permit_type" class="form-select" required>
                                                    <option value="">-- Pilih --</option>
                                                    <option value="sakit">Sakit</option>
                                                    <option value="keluarga">Keperluan Keluarga</option>
                                                    <option value="libur">Libur</option>
                                                    <option value="acara">Acara Khusus</option>
                                                    <option value="izin_khusus">Izin Khusus</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Destinasi <span class="text-danger">*</span></label>
                                                <input type="text" name="destination" class="form-control" placeholder="Ke mana?" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Waktu Pulang <span class="text-danger">*</span></label>
                                                <input type="datetime-local" name="departure_datetime" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Jadwal Kembali <span class="text-danger">*</span></label>
                                                <input type="datetime-local" name="expected_return_at" class="form-control" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Alasan <span class="text-danger">*</span></label>
                                                <textarea name="reason" class="form-control" rows="3" required placeholder="Jelaskan alasan permohonan..."></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nama Pendamping</label>
                                                <input type="text" name="companion_name" class="form-control" placeholder="Opsional">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">No. HP Pendamping</label>
                                                <input type="text" name="companion_phone" class="form-control" placeholder="Opsional">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-sm btn-primary">Kirim Permohonan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- ═══════ VISIT MODAL ═══════ --}}
                    <div class="modal fade" id="visitModal{{ $w->student_id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <form action="{{ route('portal.visit', ['token' => $token]) }}" method="POST">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="ri-footprint-line me-2"></i>Formulir Permohonan Penjengukan</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="student_id" value="{{ $w->student_id }}">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Nama Pengunjung <span class="text-danger">*</span></label>
                                                <input type="text" name="visitor_name" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Hubungan <span class="text-danger">*</span></label>
                                                <input type="text" name="visitor_relation" class="form-control" placeholder="Ayah/Ibu/Kakek/Nenek" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">No. HP <span class="text-danger">*</span></label>
                                                <input type="text" name="visitor_phone" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Jumlah Pengunjung <span class="text-danger">*</span></label>
                                                <input type="number" name="visitor_count" class="form-control" min="1" max="5" value="1" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tanggal Kunjungan Dari <span class="text-danger">*</span></label>
                                                <input type="date" name="visit_from" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Sampai Tanggal <span class="text-danger">*</span></label>
                                                <input type="date" name="visit_until" class="form-control" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Tujuan <span class="text-danger">*</span></label>
                                                <textarea name="purpose" class="form-control" rows="2" required placeholder="Tujuan kunjungan..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-sm btn-info text-white">Kirim Permohonan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- ═══════ HEALTH MODAL ═══════ --}}
                    <div class="modal fade" id="healthModal{{ $w->student_id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <form action="{{ route('portal.health', ['token' => $token]) }}" method="POST">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="ri-hospital-line me-2"></i>Formulir Izin Sakit</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="student_id" value="{{ $w->student_id }}">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                                <select name="permit_type" class="form-select" required>
                                                    <option value="sakit">Izin Sakit (Dalam Asrama)</option>
                                                    <option value="rawat_jalan">Rawat Jalan</option>
                                                    <option value="kontrol">Kontrol Dokter</option>
                                                    <option value="istirahat">Istirahat</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Fasilitas Medis</label>
                                                <input type="text" name="medical_facility" class="form-control" placeholder="RS/Klinik nama">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                                <input type="date" name="start_date" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                                <input type="date" name="end_date" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nama Dokter</label>
                                                <input type="text" name="doctor_name" class="form-control" placeholder="Opsional">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Keluhan / Keterangan <span class="text-danger">*</span></label>
                                                <textarea name="description" class="form-control" rows="3" required placeholder="Apa yang dialami santri..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-sm btn-success">Kirim Permohonan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                @endforeach
            </div>

            {{-- Recent Activity --}}
            <h5 class="mt-4 mb-3"><i class="ri-history-line me-2"></i>Aktivitas Terakhir</h5>
            <div class="accordion" id="activityAccordion">
                @foreach(['leave' => 'Izin Pulang', 'visits' => 'Penjengukan', 'health' => 'Izin Sakit'] as $key => $label)
                    @php($recent = $key === 'leave' ? $recentLeave : ($key === 'visits' ? $recentVisits : $recentHealth))
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#col{{ $key }}">
                                {{ $label }} ({{ $recent->count() }})
                            </button>
                        </h2>
                        <div id="col{{ $key }}" class="accordion-collapse collapse" data-bs-parent="#activityAccordion">
                            <div class="accordion-body py-0">
                                @forelse($recent as $row)
                                    <div class="px-3 py-2 border-bottom small">
                                        <span class="badge bg-{{ $key === 'leave' ? 'warning' : ($key === 'visits' ? 'info' : 'success') }} me-2">
                                            {{ $row->status }}
                                        </span>
                                        {{ $row->created_at?->format('d/m/Y H:i') }} &middot;
                                        {{ $row->description ?? '-' }}
                                    </div>
                                @empty
                                    <div class="px-3 py-2 text-muted small">Belum ada riwayat</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="ri-user-forbid-line text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">Tidak ada data asuhan</h5>
                <p class="text-muted">Silakan hubungi admin boarding untuk mengatur akses Anda.</p>
            </div>
        @endif

    </div>
</div>
@endsection
