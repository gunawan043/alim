@extends('layouts.master')

@section('title', 'Antrean Klinik UKS')

@section('css')
    @include('uks._ui-styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $hasFilter = request()->filled('date') || request()->filled('queue_status') || request()->filled('triage') || request()->filled('search');
        $queueLabels = \App\Models\Uks\UksPatient::$queueLabels;
        $triageLabels = \App\Models\Uks\UksPatient::$triageLabels;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Klinik @endslot
        @slot('title') Antrean & Triase @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach([
            ['label' => 'Menunggu', 'value' => $stats['waiting'], 'icon' => 'ri-time-line', 'color' => 'warning'],
            ['label' => 'Dipanggil', 'value' => $stats['called'], 'icon' => 'ri-megaphone-line', 'color' => 'info'],
            ['label' => 'Dalam Pemeriksaan', 'value' => $stats['in_progress'], 'icon' => 'ri-stethoscope-line', 'color' => 'primary'],
            ['label' => 'Menunggu Obat', 'value' => $stats['pharmacy'], 'icon' => 'ri-capsule-line', 'color' => 'success'],
            ['label' => 'Selesai Hari Ini', 'value' => $stats['done'], 'icon' => 'ri-check-double-line', 'color' => 'secondary'],
        ] as $card)
            <div class="col-xl col-md-4 col-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-sm rounded bg-{{ $card['color'] }}-subtle d-flex align-items-center justify-content-center">
                                <i class="{{ $card['icon'] }} text-{{ $card['color'] }} fs-5"></i>
                            </span>
                            <div>
                                <p class="text-muted text-uppercase fs-11 mb-1">{{ $card['label'] }}</p>
                                <h4 class="mb-0 fw-bold">{{ $card['value'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($stats['p1'] > 0)
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="ri-alarm-warning-line fs-4"></i>
            <div><strong>{{ $stats['p1'] }} pasien prioritas P1 (darurat)</strong> pada antrean ini. Tangani segera.</div>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Antrean Klinik — {{ $date->translatedFormat('d F Y') }}</h5>
                    <p class="text-muted mb-0 fs-12">Urutan otomatis: triase P1 → P2 → P3, lalu nomor antrean.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <form method="GET" action="{{ route('user.uks.clinic.queue', ['userId' => $userId]) }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama / No. RM / antrean" style="min-width: 220px;">
                            <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                        </form>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#queueFilterModal">
                            <i class="bx bx-filter-alt align-bottom me-1"></i>Filter
                        </button>
                        <a href="{{ route('user.uks.patients.create', ['userId' => $userId]) }}" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i>Daftarkan Pasien
                        </a>
                        <a href="{{ route('user.uks.pharmacy.index', ['userId' => $userId]) }}" class="btn btn-soft-success">
                            <i class="ri-capsule-line me-1"></i>Farmasi
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($hasFilter)
            <div class="card-header py-2 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold"><i class="ri-filter-3-line me-1"></i>Filter Aktif:</span>
                    @if(request('date'))
                        <span class="filter-badge active">Tanggal: {{ \Carbon\Carbon::parse(request('date'))->format('d M Y') }}</span>
                    @endif
                    @if(request('queue_status'))
                        <span class="filter-badge active">Status: {{ $queueLabels[request('queue_status')] ?? request('queue_status') }}</span>
                    @endif
                    @if(request('triage'))
                        <span class="filter-badge active">Triase: {{ request('triage') }}</span>
                    @endif
                    @if(request('search'))
                        <span class="filter-badge active">Cari: {{ request('search') }}</span>
                    @endif
                    <a href="{{ route('user.uks.clinic.queue', ['userId' => $userId]) }}" class="btn btn-sm btn-link text-danger ms-auto">
                        <i class="ri-close-circle-line me-1"></i>Hapus Semua
                    </a>
                </div>
            </div>
        @endif

        <div class="card-header py-2 uks-quick-filter">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted me-1"><i class="ri-flashlight-line me-1"></i>Quick Filter:</span>
                <a class="filter-badge {{ ! request('queue_status') ? 'active' : '' }}" href="{{ route('user.uks.clinic.queue', ['userId' => $userId, 'date' => $date->toDateString()]) }}">Semua</a>
                <a class="filter-badge {{ request('queue_status') === 'menunggu' ? 'active' : '' }}" href="{{ route('user.uks.clinic.queue', ['userId' => $userId, 'date' => $date->toDateString(), 'queue_status' => 'menunggu']) }}"><i class="ri-time-line"></i>Menunggu</a>
                <a class="filter-badge {{ request('queue_status') === 'dipanggil' ? 'active' : '' }}" href="{{ route('user.uks.clinic.queue', ['userId' => $userId, 'date' => $date->toDateString(), 'queue_status' => 'dipanggil']) }}"><i class="ri-megaphone-line"></i>Dipanggil</a>
                <a class="filter-badge {{ request('queue_status') === 'pemeriksaan' ? 'active' : '' }}" href="{{ route('user.uks.clinic.queue', ['userId' => $userId, 'date' => $date->toDateString(), 'queue_status' => 'pemeriksaan']) }}"><i class="ri-stethoscope-line"></i>Pemeriksaan</a>
                <a class="filter-badge {{ request('queue_status') === 'farmasi' ? 'active' : '' }}" href="{{ route('user.uks.clinic.queue', ['userId' => $userId, 'date' => $date->toDateString(), 'queue_status' => 'farmasi']) }}"><i class="ri-capsule-line"></i>Farmasi</a>
                <a class="filter-badge {{ request('triage') === 'P1' ? 'active' : '' }}" href="{{ route('user.uks.clinic.queue', ['userId' => $userId, 'date' => $date->toDateString(), 'triage' => 'P1']) }}"><i class="ri-alarm-warning-line"></i>P1 Darurat</a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze" id="queueTable">
                    <thead class="table-light">
                        <tr>
                            <th data-column="pasien">Pasien</th>
                            <th data-column="antrean">Antrean</th>
                            <th data-column="triase">Triase</th>
                            <th data-column="keluhan">Keluhan</th>
                            <th data-column="waktu">Datang / Tunggu</th>
                            <th data-column="status">Status</th>
                            <th data-column="action" class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients as $patient)
                            @php
                                $student = $patient->student;
                                $dorm = $student?->activeDormitoryResident?->dormitory?->name;
                                $triageColor = $patient->triage_color;
                            @endphp
                            <tr>
                                <td data-column="pasien">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0">
                                            <div class="avatar-title rounded-circle bg-{{ $student?->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $student?->gender === 'P' ? 'danger' : 'primary' }} fw-bold">
                                                {{ strtoupper(substr($student?->name ?? '?', 0, 1)) }}
                                            </div>
                                        </div>
                                        <div class="ms-2">
                                            <div class="fw-semibold">{{ $student?->name ?? '-' }}</div>
                                            <div class="text-muted fs-11">
                                                {{ $patient->medical_record_no ?? '-' }}
                                                @if($dorm) · {{ $dorm }} @endif
                                            </div>
                                            @if($patient->allergies)
                                                <div class="badge bg-danger-subtle text-danger mt-1"><i class="ri-error-warning-line me-1"></i>Alergi: {{ \Illuminate\Support\Str::limit($patient->allergies, 35) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-column="antrean"><span class="badge bg-dark">{{ $patient->queue_number ?? '—' }}</span></td>
                                <td data-column="triase"><span class="badge bg-{{ $triageColor }}-subtle text-{{ $triageColor }}">{{ $patient->triage_level ?? '-' }}</span></td>
                                <td data-column="keluhan" class="fs-13">{{ \Illuminate\Support\Str::limit($patient->chief_complaint ?? '-', 60) }}</td>
                                <td data-column="waktu" class="fs-12 text-muted">
                                    {{ $patient->arrived_at?->format('H:i') ?? $patient->admitted_at?->format('H:i') ?? '-' }}
                                    <div><i class="ri-hourglass-line me-1"></i>{{ $patient->waiting_label }}</div>
                                </td>
                                <td data-column="status">
                                    <span class="badge bg-{{ $patient->queue_status === 'selesai' ? 'success' : ($patient->queue_status === 'batal' ? 'secondary' : ($patient->queue_status === 'farmasi' ? 'info' : 'warning')) }}-subtle text-{{ $patient->queue_status === 'selesai' ? 'success' : ($patient->queue_status === 'batal' ? 'secondary' : ($patient->queue_status === 'farmasi' ? 'info' : 'warning')) }}">
                                        {{ $patient->queue_label }}
                                    </span>
                                </td>
                                <td data-column="action" class="text-end">
                                    <div class="dropdown uks-table-actions">
                                        <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                    <i class="ri-file-user-line text-primary me-2"></i>Lembar Pemeriksaan
                                                </a>
                                            </li>
                                            @if(in_array($patient->queue_status, [\App\Models\Uks\UksPatient::QUEUE_WAITING, \App\Models\Uks\UksPatient::QUEUE_CALLED], true))
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('user.uks.clinic.call', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                        @csrf
                                                        <button class="dropdown-item"><i class="ri-megaphone-line text-info me-2"></i>Panggil Pasien</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ route('user.uks.clinic.start', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                        @csrf
                                                        <button class="dropdown-item"><i class="ri-play-line text-primary me-2"></i>Mulai Pemeriksaan</button>
                                                    </form>
                                                </li>
                                            @endif
                                            @if($patient->queue_status === \App\Models\Uks\UksPatient::QUEUE_IN_PROGRESS)
                                                <li>
                                                    <form method="POST" action="{{ route('user.uks.clinic.finish', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                        @csrf
                                                        <button class="dropdown-item"><i class="ri-check-line text-success me-2"></i>Selesaikan</button>
                                                    </form>
                                                </li>
                                            @endif
                                            @if($patient->queue_status === \App\Models\Uks\UksPatient::QUEUE_PHARMACY)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.uks.pharmacy.index', ['userId' => $userId]) }}">
                                                        <i class="ri-capsule-line text-success me-2"></i>Buka Farmasi
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="border-0 p-0">
                                    <div class="uks-empty">
                                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                        <h5 class="mt-2">Belum ada antrean</h5>
                                        <p class="text-muted mb-3">Daftarkan pasien baru untuk memulai antrean klinik.</p>
                                        <a href="{{ route('user.uks.patients.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Daftarkan Pasien</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($patients->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $patients->withQueryString()])
            </div>
        @endif
    </div>

    <x-uks.filter-modal id="queueFilterModal" title="Filter Antrean Klinik" :action="route('user.uks.clinic.queue', ['userId' => $userId])">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Tanggal & Pencarian</div>
                    <div class="mb-2">
                        <label class="form-label">Tanggal Antrean</label>
                        <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label">Cari Pasien</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / No. RM / antrean">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-filter-3-line"></i>Status & Triase</div>
                    <div class="mb-2">
                        <label class="form-label">Status Antrean</label>
                        <select name="queue_status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach($queueLabels as $key => $label)
                                <option value="{{ $key }}" {{ request('queue_status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Triase</label>
                        <select name="triage" class="form-select">
                            <option value="">Semua Triase</option>
                            @foreach($triageLabels as $key => $label)
                                <option value="{{ $key }}" {{ request('triage') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </x-uks.filter-modal>
@endsection
