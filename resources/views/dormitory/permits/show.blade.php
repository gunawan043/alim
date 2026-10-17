@extends('layouts.master')
@section('title') Detail Izin @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 13px;
            transition: all 0.2s;
            margin: 4px;
            cursor: pointer;
        }
        .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
        .timeline { list-style: none; padding: 0; margin: 0; }
        .timeline-row { position: relative; padding-left: 0; }
        .timeline-dot {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        .timeline-line {
            width: 1.5px;
            height: 32px;
            background-color: #dee2e6;
            margin: 4px auto 0;
        }
        .horizontal-timeline-wrapper {
            padding: 24px 16px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .horizontal-timeline {
            display: flex;
            align-items: stretch;
            gap: 0;
            margin: 0;
            padding: 0;
            list-style: none;
            min-width: max-content;
        }
        .horizontal-step {
            position: relative;
            flex: 0 0 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 0 8px;
        }
        .step-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            position: relative;
            z-index: 2;
            margin-bottom: 14px;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #e9ecef;
        }
        .step-icon-pending {
            background-color: #f8f9fa;
            color: #adb5bd !important;
            border: 2px dashed #adb5bd;
            box-shadow: none;
        }
        .horizontal-step::after {
            content: '';
            position: absolute;
            top: 26px;
            left: calc(50% + 28px);
            right: calc(-50% + 28px);
            height: 2px;
            background-color: #e9ecef;
            z-index: 1;
        }
        .horizontal-step.step-last::after { display: none; }
        .step-pending::after {
            background-image: linear-gradient(to right, #e9ecef 60%, transparent 0%);
            background-size: 10px 2px;
            background-repeat: repeat-x;
            background-color: transparent;
        }
        .step-content { padding: 0 4px; width: 100%; }
        .step-content strong { font-size: 0.82rem; color: #212529; }
        .step-content small { font-size: 0.73rem; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('li_4') {{ $permit->student?->name ?? 'Izin' }} @endslot
        @slot('title') Detail Perizinan @endslot
    @endcomponent

    @if(session('success'))
        <x-modal-result
            id="permitResultModal"
            title="Berhasil!"
            :message="session('success')"
            primary-action-label="Selesai"
        />
    @elseif(session('error'))
        <x-modal-result
            id="permitResultModal"
            icon="https://cdn.lordicon.com/tdrtiskw.json"
            primary-color="#ffffff"
            secondary-color="#dc3545"
            title="Gagal!"
            :message="session('error')"
            primary-action-label="Tutup"
        />
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Status Banner --}}
    @php
        $statusConfig = [
            'pending'   => ['icon' => 'ri-time-line', 'text' => 'Menunggu Persetujuan', 'color' => 'warning', 'bg' => '#fff3cd', 'border' => '#ffc107'],
            'approved'  => ['icon' => 'ri-check-double-line', 'text' => 'Disetujui / Menunggu Penjemputan', 'color' => 'primary', 'bg' => '#cfe2ff', 'border' => '#0d6efd'],
            'picked_up' => ['icon' => 'ri-truck-line', 'text' => 'Sudah Dijemput (Sedang Pulang)', 'color' => 'info', 'bg' => '#cff4fc', 'border' => '#0dcaf0'],
            'returned'  => ['icon' => 'ri-login-box-line', 'text' => 'Sudah Kembali ke Asrama', 'color' => 'success', 'bg' => '#d1e7dd', 'border' => '#198754'],
            'rejected'  => ['icon' => 'ri-close-circle-line', 'text' => 'Ditolak', 'color' => 'danger', 'bg' => '#f8d7da', 'border' => '#dc3545'],
            'overdue'   => ['icon' => 'ri-alert-line', 'text' => 'Telat Pulang', 'color' => 'danger', 'bg' => '#f8d7da', 'border' => '#dc3545'],
        ];
        $st = $statusConfig[$permit->status] ?? ['icon' => 'ri-file-list-line', 'text' => ucfirst($permit->status), 'color' => 'secondary', 'bg' => '#e9ecef', 'border' => '#6c757d'];
        $isLive = in_array($permit->status, ['approved', 'overdue']) && !$permit->actual_return_datetime;
    @endphp

    {{-- Top Status Banner --}}
    <div class="card mb-2 shadow-sm border-start border-2" style="border-left-color: {{ $st['border'] }} !important;">
        <div class="card-body py-3 px-4">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center bg-{{ $st['color'] }}-subtle">
                            <i class="ri-{{ $st['icon'] }} text-{{ $st['color'] }} fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $permit->student?->name ?? 'Santri Tidak Diketahui' }}</h5>
                            <div class="text-muted fs-13">
                                @if($permit->student?->nisn)
                                    <span>NISN: <strong class="text-body">{{ $permit->student->nisn }}</strong></span>
                                    <span class="mx-1">•</span>
                                @endif
                                <span>Kamar: <strong class="text-body">{{ $permit->room?->name ?? '—' }}</strong></span>
                                @if($permit->student?->currentClassHistory)
                                    <span class="mx-1">•</span>
                                    <span>
                                        Kelas:
                                        <strong class="text-body">
                                            @php $ch = $permit->student->currentClassHistory; @endphp
                                            {{ $ch->studyGroup ? trim(($ch->studyGroup->gradeLevel?->name ?? '') . ' ' . ($ch->studyGroup->name ?? '')) : trim(($ch->gradeLevel?->name ?? '') . ' ' . ($ch->name ?? '—')) }}
                                        </strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <span class="badge rounded-pill px-3 py-2 fs-14 fw-semibold border"
                          style="background-color:{{ $st['bg'] }};color:#333;border-color:{{ $st['border'] }} !important;">
                        <i class="ri-{{ $st['icon'] }} me-1"></i>{{ $st['text'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Timeline — full width, horizontal --}}
        <div class="col-12 mb-2 mt-1">
            @php
                $timeline = [];

                if ($permit->created_at) {
                    $timeline[] = [
                        'icon' => 'ri-file-add-line', 'color' => 'primary', 'bg' => '#eef2ff',
                        'label' => 'Permohonan Diajukan',
                        'date' => $permit->created_at->format('D/M/Y H:i'),
                        'desc' => '',
                    ];
                }

                if ($permit->approved_by) {
                    $approvedDate = $permit->approved_at ?? $permit->updated_at;
                    $timeline[] = [
                        'icon' => 'ri-check-double-line', 'color' => 'success', 'bg' => '#dcfce7',
                        'label' => 'Disetujui',
                        'date' => $approvedDate ? $approvedDate->format('D/M/Y H:i') : '—',
                        'desc' => 'oleh ' . ($permit->approvedBy?->name ?? 'Admin'),
                    ];
                }

                if ($permit->pickup_scanned_at) {
                    $pickerName = $permit->pickup_details['picker_name'] ?? $permit->companion_name;
                    $pickerRelation = $permit->pickup_details['picker_relation'] ?? $permit->companion_relation;
                    $descParts = [];
                    if ($pickerName) {
                        $descParts[] = 'Penjemput: ' . $pickerName . ($pickerRelation ? ' (' . ucfirst($pickerRelation) . ')' : '');
                    }
                    if ($permit->pickup_scanned_by) {
                        $descParts[] = 'oleh ' . (\App\Models\User::find($permit->pickup_scanned_by)?->name ?? 'Scanner');
                    } else {
                        $descParts[] = 'via QR Publik';
                    }
                    $timeline[] = [
                        'icon' => 'ri-logout-box-r-line', 'color' => 'info', 'bg' => '#cff4fc',
                        'label' => 'Berangkat',
                        'date' => $permit->pickup_scanned_at->format('D/M/Y H:i'),
                        'desc' => implode(' · ', $descParts),
                    ];
                } elseif ($permit->scanned_at) {
                    $timeline[] = [
                        'icon' => 'ri-qr-scan-2-line', 'color' => 'info', 'bg' => '#cff4fc',
                        'label' => 'Berangkat (QR)',
                        'date' => $permit->scanned_at->format('D/M/Y H:i'),
                        'desc' => 'via QR Publik',
                    ];
                }

                if ($permit->return_scanned_at) {
                    $timeline[] = [
                        'icon' => 'ri-login-box-line', 'color' => 'success', 'bg' => '#dcfce7',
                        'label' => 'Kedatangan',
                        'date' => $permit->return_scanned_at->format('D/M/Y H:i'),
                        'desc' => $permit->return_scanned_by ? 'oleh ' . (\App\Models\User::find($permit->return_scanned_by)?->name ?? 'Scanner') : 'via QR Publik',
                    ];
                } elseif ($permit->actual_return_datetime) {
                    $timeline[] = [
                        'icon' => 'ri-login-box-line', 'color' => 'success', 'bg' => '#dcfce7',
                        'label' => 'Kedatangan',
                        'date' => $permit->actual_return_datetime->format('D/M/Y H:i'),
                        'desc' => 'dicatat manual',
                    ];
                }

                $isPendingStep = !in_array($permit->status, ['returned', 'rejected']);
                $isLive = in_array($permit->status, ['approved', 'overdue']) && !$permit->actual_return_datetime;
            @endphp

        </div>

        <div class="col-lg-8">
            {{-- Informasi Izin --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="ri-file-text-line me-2 text-primary"></i>Informasi Izin</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle fs-14 mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted py-3" style="width:35%;white-space:nowrap;"><i class="ri-user-3-line me-2"></i>Santri</td>
                                    <td class="py-3 fw-semibold">{{ $permit->student?->name ?? '—' }}</td>
                                </tr>
                                @if($permit->student?->nisn)
                                <tr>
                                    <td class="text-muted"><i class="ri-article-line me-2"></i>NISN</td>
                                    <td>{{ $permit->student->nisn }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="text-muted"><i class="ri-home-8-line me-2"></i>Kamar</td>
                                    <td class="fw-semibold">{{ $permit->room?->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><i class="ri-list-check me-2"></i>Jenis Izin</td>
                                    <td><span class="badge bg-primary-subtle text-primary px-2 py-1">{{ $permit->permit_type_text }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><i class="ri-map-pin-line me-2"></i>Tujuan</td>
                                    <td>{{ $permit->destination ?: '—' }}</td>
                                </tr>
                                @if($permit->purpose)
                                <tr>
                                    <td class="text-muted"><i class="ri-edit-line me-2"></i>Keperluan</td>
                                    <td>{{ $permit->purpose }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Jadwal Perjalanan --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold"><i class="ri-calendar-event-line me-2 text-primary"></i>Jadwal Perjalanan</h6>
                    @if($isLive)
                        <span class="badge bg-danger-subtle text-danger"><i class="ri-flashlight-line me-1"></i>Masih Pulang</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle fs-14 mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted py-3" style="width:35%;"><i class="ri-logout-box-r-line me-2 text-info"></i>Kepulangan</td>
                                    <td class="py-3">{{ $permit->departure_datetime ? $permit->departure_datetime->format('D/M/Y H:i') : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><i class="ri-arrow-right-line me-2 text-muted"></i>Estimasi Kembali</td>
                                    <td>
                                        <span class="text-muted">{{ $permit->expected_return_datetime ? $permit->expected_return_datetime->format('D/M/Y H:i') : '—' }}</span>
                                        @if($permit->expected_return_datetime && $permit->expected_return_datetime->isPast() && !$permit->actual_return_datetime)
                                            <span class="badge bg-danger ms-1">Terlambat</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($permit->actual_return_datetime)
                                <tr>
                                    <td class="text-success"><i class="ri-login-box-line me-2"></i>Kedatangan Aktual</td>
                                    <td class="text-success">{{ $permit->actual_return_datetime->format('D/M/Y H:i') }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td class="text-danger"><i class="ri-alarm-warning-line me-2"></i>Status Akhir</td>
                                    <td><span class="badge bg-warning-subtle text-warning">Masih belum kembali</span></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



    {{-- Timeline Status --}}
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-light border-bottom d-flex align-items-center justify-content-between py-3">
                    <h6 class="mb-0 fs-14 fw-bold" style="display:inline-flex; align-items:center; gap:8px;">
                        <i class="ri-timeline-line me-1 text-primary"></i> Timeline Perizinan
                    </h6>
                </div>
                <div class="card-body p-3">
                    @php
                        $timeline = [
                            ['icon' => 'ri-file-add-line',  'color' => 'primary',   'bg_color' => 'bg-primary-subtle',  'label' => 'Permohonan Diajukan',  'date' => $permit->created_at?->format('D/M/Y H:i')],
                            ['icon' => 'ri-check-double-line', 'color' => 'success', 'bg_color' => 'bg-success-subtle','label' => 'Disetujui',            'date' => $permit->approved_at?->format('D/M/Y H:i')],
                            ['icon' => 'ri-logout-box-r-line', 'color' => 'info',    'bg_color' => 'bg-info-subtle',   'label' => 'Berangkat',            'date' => $permit->pickup_scanned_at?->format('D/M/Y H:i')],
                            ['icon' => 'ri-login-box-line',    'color' => 'secondary','bg_color' => 'bg-secondary-subtle','label' => 'Kembali',              'date' => $permit->actual_return_datetime?->format('D/M/Y H:i')],
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

                    <div class="timeline">
                        @forelse($timeline as $index => $item)
                            @php $isLast = $index === count($timeline) - 1; @endphp
                            @if(!empty($item['date']))
                                <div class="timeline-row d-flex align-items-start">
                                    <div class="timeline-icon-col flex-shrink-0 me-2">
                                        <div class="timeline-dot {{ $item['bg_color'] }} rounded-circle d-flex align-items-center justify-content-center"
                                             style="width:30px;height:30px;">
                                            <i class="ri-{{ $item['icon'] }} text-{{ $item['color'] }}" style="font-size:0.85rem;"></i>
                                        </div>
                                        @if(!$isLast)
                                            <div class="timeline-line mt-1 mx-auto" style="width:1.5px;height:24px;background-color:#dee2e6;"></div>
                                        @endif
                                    </div>
                                    <div class="timeline-content flex-grow-1 pb-2">
                                        <div class="d-flex justify-content-between align-items-start gap-1">
                                            <div>
                                                <span class="fw-semibold" style="font-size:0.8rem;">{{ $item['label'] }}</span>
                                                @if($current === -1 && $index <= $current)
                                                    <span class="text-danger ms-1" style="font-size:0.75rem;">— Ditolak</span>
                                                @elseif($current === 1 && $index > $current)
                                                    <span class="text-muted ms-1" style="font-size:0.75rem;">— Belum</span>
                                                @endif
                                            </div>
                                            <span class="text-muted text-nowrap" style="font-size:0.75rem;">{{ $item['date'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-muted text-center py-3 mb-0" style="font-size:0.8rem;">Belum ada aktivitas.</p>
                        @endforelse

                        @if(in_array($permit->status, ['approved', 'overdue']) && !$permit->actual_return_datetime)
                            <div class="timeline-row d-flex align-items-start opacity-50">
                                <div class="timeline-icon-col flex-shrink-0 me-2">
                                    <div class="timeline-dot bg-light border rounded-circle d-flex align-items-center justify-content-center"
                                         style="width:30px;height:30px;border-style:dashed!important;">
                                        <i class="ri-time-line text-muted" style="font-size:0.8rem;"></i>
                                    </div>
                                </div>
                                <div class="timeline-content flex-grow-1 pb-0">
                                    <span class="text-muted" style="font-size:0.75rem;">Menunggu status kembali...</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

            {{-- Dokumen Lampiran --}}
            @if($permit->document_url)
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="ri-attachment-2 me-2 text-primary"></i>Dokumen Lampiran</h6>
                </div>
                <div class="card-body py-3">
                    <a href="{{ asset('storage/' . $permit->document_url) }}"
                       target="_blank" class="btn btn-primary btn-sm">
                        <i class="ri-download-line me-1"></i> Download Dokumen
                    </a>
                </div>
            </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            {{-- Data Penjemput --}}
            @php
                $hasRencana = $permit->companion_name || $permit->companion_relation || $permit->companion_phone;
                $pickerName = $permit->pickup_details['picker_name'] ?? $permit->companion_name;
                $pickerRelation = $permit->pickup_details['picker_relation'] ?? $permit->companion_relation;
                $hasScan = !empty($pickerName) || !empty($pickerRelation);
            @endphp
            @if($hasRencana || $hasScan)
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold"><i class="ri-user-heart-line me-2 text-primary"></i>Data Penjemput</h6>
                    @if($hasScan)
                        <span class="badge bg-success-subtle text-success">
                            <i class="ri-checkbox-circle-line me-1"></i>Sudah Dijemput
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning">
                            <i class="ri-time-line me-1"></i>Menunggu
                        </span>
                    @endif
                </div>
                <div class="card-body p-0">
                    {{-- Data Saat Scan (aktual) --}}
                    @if($hasScan)
                    <div class="px-3 py-3 bg-success-subtle bg-opacity-10 border-bottom">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ri-user-star-line text-success fs-18 me-2"></i>
                            <span class="fw-semibold text-success small">Data Saat Penjemputan (Aktual)</span>
                        </div>
                        <table class="table table-borderless align-middle fs-14 mb-0">
                            <tbody>
                                @if($pickerName)
                                <tr>
                                    <td class="text-muted py-1" style="width:40%;"><i class="ri-user-3-line me-1"></i>Nama</td>
                                    <td class="py-1 fw-semibold">{{ $pickerName }}</td>
                                </tr>
                                @endif
                                @if($pickerRelation)
                                <tr>
                                    <td class="text-muted py-1"><i class="ri-group-line me-1"></i>Hubungan</td>
                                    <td class="py-1">{{ ucfirst($pickerRelation) }}</td>
                                </tr>
                                @endif
                                @if($permit->pickup_scanned_at)
                                <tr>
                                    <td class="text-muted py-1"><i class="ri-time-line me-1"></i>Waktu</td>
                                    <td class="py-1 small">
                                        {{ $permit->pickup_scanned_at->format('d/m/Y H:i') }}
                                        @if($permit->pickup_scanned_by)
                                            @php $scannerUser = \App\Models\User::find($permit->pickup_scanned_by); @endphp
                                            <span class="text-muted">· oleh {{ $scannerUser?->name ?? 'Scanner' }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- Data Rencana (dari pengajuan) --}}
                    @if($hasRencana)
                    <div class="px-3 py-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ri-file-text-line text-muted fs-18 me-2"></i>
                            <span class="fw-semibold text-muted small">
                                {{ $hasScan ? 'Data Rencana (dari Wali)' : 'Data Rencana' }}
                            </span>
                        </div>
                        <table class="table table-borderless align-middle fs-14 mb-0">
                            <tbody>
                                @if($permit->companion_name)
                                <tr>
                                    <td class="text-muted py-1" style="width:40%;"><i class="ri-user-3-line me-1"></i>Nama</td>
                                    <td class="py-1">
                                        {{ $permit->companion_name }}
                                        @if($pickerName && $pickerName !== $permit->companion_name)
                                            <span class="badge bg-warning-subtle text-warning small ms-1">berbeda dari yang direncanakan</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @if($permit->companion_relation)
                                <tr>
                                    <td class="text-muted py-1"><i class="ri-group-line me-1"></i>Hubungan</td>
                                    <td class="py-1">{{ $permit->companion_relation }}</td>
                                </tr>
                                @endif
                                @if($permit->companion_phone)
                                <tr>
                                    <td class="text-muted py-1"><i class="ri-phone-line me-1"></i>Telepon</td>
                                    <td class="py-1">
                                        <a href="tel:{{ $permit->companion_phone }}" class="text-decoration-none text-body">
                                            {{ $permit->companion_phone }}
                                        </a>
                                    </td>
                                </tr>
                                @endif
                                @if($permit->companion_is_mahrom)
                                <tr>
                                    <td colspan="2" class="pt-1">
                                        <span class="badge bg-dark-subtle text-body">
                                            <i class="ri-shield-check-line me-1"></i>Mahrom
                                        </span>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Info Santri Masih Pulang --}}
            @if($isLive)
            <div class="card mb-4 shadow-sm border-start border-1 border-danger">
                <div class="card-header bg-light border-bottom py-2">
                    <h6 class="card-title mb-0 fw-bold text-danger"><i class="ri-alarm-warning-line me-2"></i>Info Penting</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted fs-14">
                        <i class="ri-error-warning-line me-1 text-danger"></i>
                        Santri ini masih dalam status pulang dan belum mencatat kedatangan.
                        Estimasi kembali: <strong class="text-body">{{ $permit->expected_return_datetime ? $permit->expected_return_datetime->format('D/M/Y H:i') : '—' }}</strong>.
                    </p>
                </div>
            </div>
            @endif

            {{-- Cetak Kartu --}}
            @if(in_array($permit->status, ['approved', 'picked_up', 'returned', 'overdue']))
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="ri-printer-line me-2 text-info"></i>Cetak Kartu</h6>
                </div>
                <div class="card-body py-3">
                    <a href="{{ route('user.asrama.permits.card', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                       target="_blank" class="btn btn-outline-primary w-100 btn-sm">
                        <i class="ri-printer-line me-1"></i> Cetak Kartu Izin
                    </a>
                </div>
            </div>
            @endif

                       {{-- Admin Actions --}}
            @if($permit->status === 'pending' || in_array($permit->status, ['approved', 'overdue']))
            <div class="card mb-4 shadow-sm border-start border-1 border-warning">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold text-warning"><i class="ri-settings-3-line me-2"></i>Aksi Admin</h6>
                </div>
                <div class="card-body p-3 d-flex flex-column gap-2">
                    @if($permit->status === 'pending')
                        <button type="button" class="btn btn-success w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="ri-check-line me-1"></i> Setujui Izin
                        </button>
                        <button type="button" class="btn btn-outline-danger w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ri-close-line me-1"></i> Tolak Izin
                        </button>
                    @endif
                    @if(in_array($permit->status, ['approved', 'overdue']))
                        <button type="button" class="btn btn-warning w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                            <i class="ri-edit-box-line me-1"></i> Ubah Status
                        </button>
                    @endif
                </div>
            </div>
            @endif

            {{-- Record Return --}}
            @if($isLive)
            <div class="card mb-4 shadow-sm border-start border-1 border-info">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold text-info"><i class="ri-login-box-line me-2"></i>Pencatatan</h6>
                </div>
                <div class="card-body p-3">
                    <button type="button" class="btn btn-info w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#returnModal">
                        <i class="ri-login-box-line me-1"></i> Catat Kedatangan
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($permit->status === 'pending')
    {{-- Modal Setujui Izin dengan Check Kuota --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Setujui Izin Santri?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="approveForm" action="{{ route('user.asrama.permits.approve', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-6"><strong>Santri:</strong> {{ $permit->student?->name }}</div>
                            <div class="col-6"><strong>Tujuan:</strong> {{ $permit->destination ?? '—' }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12"><small class="text-muted">{{ $permit->departure_datetime?->format('d/m/Y H:i') }} → {{ $permit->expected_return_datetime?->format('d/m/Y H:i') }}</small></div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2 d-block">Jenis Izin</label>
                            <select name="permit_type" class="form-select">
                                @foreach(['pulang','sakit','berobat','keperluan_keluarga','keluar_kota','darurat','lainnya'] as $type)
                                    <option value="{{ $type }}" {{ $permit->permit_type == $type ? 'selected' : '' }}>
                                        {!! match($type) {
                                            'pulang' => 'Pulang',
                                            'sakit' => 'Sakit',
                                            'berobat' => 'Berobat',
                                            'keperluan_keluarga' => 'Keperluan Keluarga',
                                            'keluar_kota' => 'Keluar Kota',
                                            'darurat' => 'Darurat',
                                            'lainnya' => 'Lainnya',
                                            default => ucfirst($type),
                                        }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2 d-block">Catatan Persetujuan</label>
                            <textarea name="approval_note" class="form-control" rows="2" placeholder="Masukkan catatan persetujuan (opsional)"></textarea>
                        </div>
                        <!-- Hidden fields -->
                        <input type="hidden" name="student_id" value="{{ $permit->student_id }}">
                        <input type="hidden" name="asrama_uuid" value="{{ $dormitory->id }}">
                        <input type="hidden" name="permit_type_original" value="{{ $permit->permit_type }}">
                        <input type="hidden" name="departure_datetime" value="{{ $permit->departure_datetime?->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="approveSubmitBtn">
                            <i class="ri-check-line me-1"></i> Setujui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Peringatan Kuota --}}
    <div class="modal fade" id="quotaWarningModal" tabindex="-1" aria-labelledby="quotaWarningModalLabel">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title" id="quotaWarningModalLabel"><i class="ri-error-warning-line text-danger me-2"></i>Peringatan Kuota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning fs-14 mb-0" id="quotaWarningContent">
                        <!-- isi diisi via JS -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" class="btn btn-outline-danger ms-2" id="editPermitTypeLink">
                        <i class="ri-edit-line me-1"></i> Ubah Jenis Izin
                    </a>
                    <button type="button" class="btn btn-success" id="forceApproveBtn">
                        <i class="ri-check-line me-1"></i> Tetap Setujui
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tolak Izin --}}
    <x-modal-confirm
        id="rejectModal"
        icon="https://cdn.lordicon.com/tdrtiskw.json"
        secondary-color="#dc3545"
        title="Tolak Izin Santri?"
        submit-label="Ya, Tolak"
        submit-icon="ri-close-line"
        submit-class="btn-danger"
        form-id="rejectForm"
    >
        <form method="POST" id="rejectForm"
              action="{{ route('user.asrama.permits.reject', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
            @csrf
            <div class="mb-2">
                <div class="fw-semibold">Santri: {{ $permit->student?->name }}</div>
            </div>
            <div class="mb-2">
                <label class="form-label" for="rejection_reason">
                    Alasan Penolakan <span class="text-danger">*</span>
                </label>
                <textarea name="rejection_reason"
                          id="rejection_reason"
                          class="form-control"
                          rows="3"
                          placeholder="Jelaskan alasan penolakan..."
                          required></textarea>
            </div>
        </form>
    </x-modal-confirm>
    @endif

    {{-- Modal: Ubah Status --}}
    @if(in_array($permit->status, ['approved', 'overdue']))
    <x-modal id="updateStatusModal" size="sm">
        @slot('title')<i class="ri-update-3-line me-1"></i>Ubah Status Izin @endslot
        <form method="POST" id="updateStatusForm"
              action="{{ route('user.asrama.permits.update-status', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Pilih Status Baru</label>
                <select name="status" class="form-select" required>
                    <option value="">-- Pilih status --</option>
                    <option value="picked_up" {{ old('status', $permit->status == 'picked_up' ? 'selected' : '') }}>Sudah Dijemput (Sedang Pulang)</option>
                    <option value="returned" {{ old('status', $permit->status == 'returned' ? 'selected' : '') }}>Sudah Kembali ke Asrama</option>
                    <option value="overdue" {{ old('status', $permit->status == 'overdue' ? 'selected' : '') }}>Terlambat Pulang</option>
                </select>
                <small class="text-muted mt-1 d-block">Status ini akan memperbarui tanggal dan catatan izin santri</small>
            </div>
        </form>
        @slot('actions')
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="updateStatusForm" class="btn btn-primary">
                <i class="ri-check-line me-1"></i> Simpan Perubahan
            </button>
        @endslot
    </x-modal>
    @endif

    {{-- Modal: Catat Kedatangan --}}
    @if($isLive)
    <x-modal-confirm
        id="returnModal"
        icon="https://cdn.lordicon.com/lupuorrc.json"
        secondary-color="#0dcaf0"
        title="Catat Kedatangan Santri?"
        submit-label="Simpan"
        submit-icon="ri-check-line"
        submit-class="btn-info"
        form-id="returnForm"
    >
        <form method="POST" id="returnForm"
              action="{{ route('user.asrama.permits.return', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
            @csrf
            <div class="mb-2">
                <div class="fw-semibold">Santri: {{ $permit->student?->name }}</div>
                <small class="text-muted">
                    Estimasi kembali: {{ $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '—' }}
                </small>
            </div>
            <label class="form-label fw-semibold">Waktu Kembali <span class="text-danger">*</span></label>
            <input type="datetime-local" name="actual_return_datetime" class="form-control"
                   value="{{ now()->format('Y-m-d\TH:i') }}" required>
        </form>
    </x-modal-confirm>
    @endif

    {{-- Back button --}}
    <div class="mt-3 mb-4">
        <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
           class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
        </a>
    </div>
@endsection

@section('script')
<script>
// Auto-show result modal (success/error) on page load if present
(function () {
    const resultModalEl = document.getElementById('permitResultModal');
    if (resultModalEl) {
        const resultModal = new bootstrap.Modal(resultModalEl, { backdrop: 'static', keyboard: true });
        resultModal.show();
        resultModalEl.addEventListener('hidden.bs.modal', function () {
            resultModalEl.remove();
        });
    }
})();

// Token search modal AJAX handler (existing)
document.getElementById('tokenSearchForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = this;
    const tokenInput = form.querySelector('input[name="token"]');
    const resultDiv = document.getElementById('tokenResult');

    if (!tokenInput || !tokenInput.value.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Peringatan',
            text: 'Harap masukkan token QR terlebih dahulu.',
            confirmButtonColor: '#fd7e14'
        });
        return;
    }

    if (resultDiv) resultDiv.innerHTML = '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div></div>';

    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });

        const data = await response.json();

        if (!resultDiv) return;

        if (response.ok && data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success">${data.message || 'Scan berhasil!'}</div>`;
            setTimeout(() => {
                new bootstrap.Modal(document.getElementById('tokenSearchModal')).hide();
                if (data.redirect) window.location.href = data.redirect;
                else location.reload();
            }, 1500);
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">${data.message || 'Scan gagal, coba lagi.'}</div>`;
        }
    } catch (error) {
        if (resultDiv) resultDiv.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memproses scan.</div>';
        Swal.fire({ icon: 'error', title: 'Kesalahan', text: 'Server tidak merespons.' });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('approveForm');
    if (!form) return;

    const asramaUuid = form.querySelector('[name="asrama_uuid"]').value;
    const studentId = form.querySelector('[name="student_id"]').value;
    const originalPermitType = form.querySelector('[name="permit_type_original"]').value;
    const departureDatetime = form.querySelector('[name="departure_datetime"]').value;
    const approveBtn = document.getElementById('approveSubmitBtn');
    const warningContent = document.getElementById('quotaWarningContent');
    const forceApproveBtn = document.getElementById('forceApproveBtn');
    const editPermitTypeLink = document.getElementById('editPermitTypeLink');

    const baseUrl = `{{ route('user.asrama.permits.quota.check', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}`;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const originalHTML = approveBtn.innerHTML;
        approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
        approveBtn.disabled = true;

        try {
            const newPermitType = form.querySelector('select[name="permit_type"]').value;
            const permitTypeChanged = newPermitType !== originalPermitType;

            if (!permitTypeChanged) {
                const res = await fetch(baseUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        permit_type: originalPermitType,
                        departure_datetime: departureDatetime,
                    })
                });

                if (!res.ok) {
                    // Jika gagal API (e.g. 419 csrf), izinkan submit normal
                    console.warn('Quota check skipped: HTTP ' + res.status);
                } else {
                    const data = await res.json();
                    if (data.over) {
                        const periodLabel = data.period_label_id || 'periode ini';
                        let html = `Kuota untuk izin <strong>${originalPermitType}</strong> sudah penuh.<br>`;
                        html += `<strong>${data.used}/${data.quota}</strong> terpakai pada ${periodLabel}.<br>`;
                        if (data.remaining !== null && data.remaining !== undefined) {
                            html += `<br><strong>Tersisa ${data.remaining} slot</strong>.`;
                        }
                        html += `<br><br>Untuk melanjutkan, ubah jenis izin menjadi <em>darurat</em> atau yang lain di sebelah kiri, lalu klik Setujui lagi.`;
                        warningContent.innerHTML = html;
                        const m = new bootstrap.Modal(document.getElementById('quotaWarningModal'));
                        m.show();
                        approveBtn.innerHTML = originalHTML;
                        approveBtn.disabled = false;
                        return;
                    }
                }
            }

            // Lolos cek: kirim form asli (tanpa validator kuota karena sudah lolos)
            form.submit();
        } catch (err) {
            console.error('Quota check error:', err);
            approveBtn.innerHTML = originalHTML;
            approveBtn.disabled = false;
            alert('Terjadi kesalahan saat memeriksa kuota.');
        }
    });

    if (forceApproveBtn) {
        forceApproveBtn.addEventListener('click', function () {
            // Tutup modal warning dan submit form langsung
            const warningModalEl = document.getElementById('quotaWarningModal');
            const modal = bootstrap.Modal.getInstance(warningModalEl);
            if (modal) modal.hide();
            form.submit();
        });
    }

    if (editPermitTypeLink) {
        editPermitTypeLink.addEventListener('click', function (e) {
            e.preventDefault();
            // Tutup modal warning & buka kembali modal approve tanpa submit
            const warningModalEl = document.getElementById('quotaWarningModal');
            const m = bootstrap.Modal.getInstance(warningModalEl);
            if (m) m.hide();
            const approveModalEl = document.getElementById('approveModal');
            const am = new bootstrap.Modal(approveModalEl);
            am.show();
            // Tampilkan fokus pada select
            setTimeout(() => {
                const sel = approveModalEl.querySelector('select[name="permit_type"]');
                if (sel) {
                    sel.focus();
                    sel.classList.add('is-invalid');
                    setTimeout(() => sel.classList.remove('is-invalid'), 3000);
                }
            }, 400);
        });
    }
});
</script>
@endsection