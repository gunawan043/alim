@extends('layouts.master')
@section('title') Kaldik & Agenda Kegiatan @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
    .color-swatches { gap: 6px; }
    .color-swatch {
        width: 28px; height: 28px; border-radius: 50%;
        cursor: pointer; border: 2px solid transparent;
        transition: all 0.15s ease; display: inline-block;
    }
    .color-swatch:hover { transform: scale(1.15); }
    .color-swatch input { display: none; }
    .color-swatch.selected { border-color: #fff; box-shadow: 0 0 0 2px #3B82F6; }
    .color-swatch-label {
        width: 24px; height: 24px; border-radius: 50%;
        display: inline-block; border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    /* ── Calendar ── */
    #kaldik-calendar {
        font-size: 0.875rem;
    }
    /* Today — bold & colored border */
    #kaldik-calendar .fc-day-today {
        background-color: rgba(59, 130, 246, 0.06) !important;
    }
    #kaldik-calendar .fc-day-today .fc-daygrid-day-number {
        background: #3B82F6;
        color: #fff !important;
        border-radius: 50%;
        width: 28px; height: 28px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700;
    }
    /* Day header number */
    #kaldik-calendar .fc-daygrid-day-number {
        font-size: 1rem;
        color: #64748b;
        padding: 4px 6px;
        font-weight: 500;
    }
    /* Event bar — clean strip */
    #kaldik-calendar .fc-event {
        background-color: transparent !important;
        border: none !important;
        border-left: 3px solid currentColor !important;
        border-right: 3px solid currentColor !important;
        border-radius: 3px;
        margin: 1px 4px;
        padding: 2px 6px;
        cursor: pointer;
    }
    #kaldik-calendar .fc-event:hover {
        background-color: rgba(0,0,0,0.05) !important;
    }
    #kaldik-calendar .fc-event .fc-event-main-frame {
        padding: 1px 0;
    }
    #kaldik-calendar .fc-event .fc-event-title {
        font-size: 0.75rem;
        font-weight: 600;
        padding-left: 2px;
    }
    #kaldik-calendar .fc-event .fc-event-time {
        font-size: 1.2rem;
    }
    /* Toolbar */
    #kaldik-calendar .fc-toolbar-title {
        font-size: 1.1rem !important;
        font-weight: 600;
        color: #1e293b;
    }
    #kaldik-calendar .fc-button {
        font-size: 0.8rem !important;
        padding: 4px 10px !important;
    }
    /* Column header */
    #kaldik-calendar .fc-col-header-cell-cushion {
        font-size: 0.78rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    /* Week view event */
    #kaldik-calendar .fc-timegrid-event .fc-event-title {
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* ── Upcoming Card ── */
    .upcoming-card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        transition: all 0.15s;
        cursor: pointer;
        overflow: hidden;
        min-width: 0;
    }
    .upcoming-card:hover {
        border-color: #3B82F6;
        box-shadow: 0 2px 8px rgba(59,130,246,0.12);
        transform: translateY(-1px);
    }
    .upcoming-card .card-body {
        overflow: hidden;
        min-width: 0;
    }
    .upcoming-card .upcoming-date {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
    }
    .upcoming-card .upcoming-title {
        font-size: 0.82rem;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.3;
        word-break: break-word;
    }
    .upcoming-card .upcoming-wunit {
        font-size: 0.72rem;
        color: #64748b;
        word-break: break-word;
    }

    /* ── Legend ── */
    .legend-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('title') Kaldik & Agenda Kegiatan @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="row">
                {{-- LEFT SIDEBAR ─────────────────────────────────────── --}}
                <div class="col-xl-3">
                    {{-- Action Button ─────────────────────────────── --}}
                    @can('create', \App\Models\Kaldik::class)
                        <button class="btn btn-primary w-100 mb-3" id="btn-new-event">
                            <i class="ri-add-line"></i> Tambah Kegiatan
                        </button>
                    @endcan

                    {{-- Filters Card ─────────────────────────────── --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-uppercase" style="letter-spacing:0.5px;font-size:0.7rem;color:#64748b">Filter Kategori</label>
                                <select class="form-control form-control-sm" id="filter-category" name="category">
                                    <option value="">Semua</option>
                                    <option value="kaldik" {{ request('category') === 'kaldik' ? 'selected' : '' }}>Kaldik</option>
                                    <option value="agenda" {{ request('category') === 'agenda' ? 'selected' : '' }}>Agenda Kegiatan</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label small fw-semibold text-uppercase" style="letter-spacing:0.5px;font-size:0.7rem;color:#64748b">Tahun Ajaran</label>
                                <select class="form-control form-control-sm" id="filter-academic-year">
                                    <option value="">Semua</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester_text }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Color Legend ─────────────────────────────── --}}
                    <div class="card mb-3">
                        <div class="card-body py-2 px-3">
                            <div class="small fw-semibold text-uppercase mb-2" style="font-size:0.7rem;color:#64748b;letter-spacing:0.5px">Legenda</div>
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="legend-dot" style="background:#3B82F6"></span>
                                    <span class="small fw-medium">Kaldik</span>
                                    <span class="text-muted small" style="font-size:0.7rem">— kegiatan pondok</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="legend-dot" style="background:#F59E0B"></span>
                                    <span class="small fw-medium">Agenda</span>
                                    <span class="text-muted small" style="font-size:0.7rem">— kegiatan satuan kerja</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Context Info ─────────────────────────────── --}}
                    <div class="card mb-3">
                        <div class="card-body py-2 px-3">
                            @if(!$isGlobal && $userWorkUnitId)
                                @php
                                    $wu = \App\Models\WorkUnit::find($userWorkUnitId);
                                @endphp
                                @if($wu)
                                <div class="d-flex align-items-center gap-2">
                                    <i data-feather="briefcase" style="width:14px;height:14px;color:#64748b"></i>
                                    <span class="small fw-semibold">{{ $wu->name }}</span>
                                </div>
                                @endif
                            @elseif($isGlobal)
                                <div class="d-flex align-items-center gap-2">
                                    <i data-feather="globe" style="width:14px;height:14px;color:#64748b"></i>
                                    <span class="small fw-semibold text-primary">Semua Satuan Kerja</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Upcoming Events ─────────────────────────── --}}
                    <div class="mb-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold" style="font-size:0.9rem">Agenda Mendatang</span>
                            </div>
                            <span class="badge bg-light text-dark" style="font-size:0.68rem" id="upcoming-count"></span>
                        </div>
                    </div>
                    <div class="mb-3 mt-2 overflow-hidden" data-simplebar style="max-height: 340px">
                        <div class="mt-1" id="upcoming-event-list"></div>
                    </div>
                </div>

                {{-- RIGHT CALENDAR ─────────────────────────────────── --}}
                <div class="col-xl-9">
                    <div class="card card-h-100">
                        <div class="card-body">
                            <div id="kaldik-calendar"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div style='clear:both'></div>

            {{-- ADD / EDIT MODAL ──────────────────────────────── --}}
            <div class="modal fade" id="event-modal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <div class="modal-header p-3 bg-primary-subtle">
                            <h5 class="modal-title" id="modal-title">Agenda</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                        </div>
                        <div class="modal-body p-4">
                            <form class="needs-validation" name="event-form" id="form-event" novalidate>
                                <div class="text-end">
                                    <a href="#" class="btn btn-sm btn-soft-primary" id="edit-event-btn" data-id="edit-event" onclick="editEvent(this)" role="button">Edit</a>
                                </div>

                                {{-- VIEW MODE ───────────────────────── --}}
                                <div class="event-details">
                                    <div class="d-flex mb-2">
                                        <div class="flex-grow-1 d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <i class="ri-calendar-event-line text-muted fs-16"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="d-block fw-semibold mb-0" id="event-start-date-tag"></h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="ri-time-line text-muted fs-16"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="d-block fw-semibold mb-0">
                                                <span id="event-timepicker1-tag"></span>
                                                <span id="event-time-divider" class="d-none"> – </span>
                                                <span id="event-timepicker2-tag"></span>
                                            </h6>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="ri-stack-line text-muted fs-16"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="d-block fw-semibold mb-0">
                                                <span id="event-category-tag" class="badge"></span>
                                            </h6>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="ri-government-line text-muted fs-16"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="d-block fw-semibold mb-0"><span id="event-workunit-tag">-</span></h6>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="ri-book-read-line text-muted fs-16"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="d-block fw-semibold mb-0"><span id="event-academic-year-tag">-</span></h6>
                                        </div>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="ri-discuss-line text-muted fs-16"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="d-block text-muted mb-0" id="event-description-tag"></p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-2" id="event-color-row" style="display:none">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="ri-palette-line text-muted fs-16"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="d-block fw-semibold mb-0">Warna: <span id="event-color-tag"></span></h6>
                                        </div>
                                    </div>
                                </div>

                                {{-- FORM MODE ─────────────────────────── --}}
                                <div class="event-form">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="mb-2">
                                                <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                                <input class="form-control" placeholder="Contoh: Libur Semester Ganjil"
                                                    type="text" name="title" id="event-title" required value="" />
                                                <div class="invalid-feedback">Nama kegiatan wajib diisi.</div>
                                            </div>
                                        </div>

                                        @if($isGlobal)
                                        {{-- Super Admin / Administrator: boleh pilih kategori --}}
                                        <div class="col-12">
                                            <div class="mb-2">
                                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                                <select class="form-select" name="event-category" id="event-category" required>
                                                    <option value="">-- Pilih Kategori --</option>
                                                    <option value="bg-primary-subtle">Kaldik</option>
                                                    <option value="bg-warning-subtle">Agenda Kegiatan</option>
                                                </select>
                                                <div class="invalid-feedback">Pilih kategori.</div>
                                            </div>
                                        </div>
                                        @else
                                        {{-- Admin TU: hidden, auto-agenda --}}
                                        <input type="hidden" name="event-category" id="event-category" value="bg-warning-subtle">
                                        @endif
                                        <div class="col-12">
                                            <div class="mb-2">
                                                <label class="form-label">Tipe</label>
                                                <select class="form-select" name="event-type" id="event-type">
                                                    <option value="">-- Pilih Tipe --</option>
                                                    <option value="tahunan">Tahunan</option>
                                                    <option value="mid_semester">Mid Semester</option>
                                                    <option value="lainnya">Lainnya</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-2">
                                                <label class="form-label">Warna</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="color-swatches d-flex gap-1 flex-wrap">
                                                        <label class="color-swatch" data-color="#3B82F6" style="background:#3B82F6">
                                                            <input type="radio" name="event-color" value="#3B82F6">
                                                        </label>
                                                        <label class="color-swatch" data-color="#10B981" style="background:#10B981">
                                                            <input type="radio" name="event-color" value="#10B981">
                                                        </label>
                                                        <label class="color-swatch" data-color="#F59E0B" style="background:#F59E0B">
                                                            <input type="radio" name="event-color" value="#F59E0B">
                                                        </label>
                                                        <label class="color-swatch" data-color="#EF4444" style="background:#EF4444">
                                                            <input type="radio" name="event-color" value="#EF4444">
                                                        </label>
                                                        <label class="color-swatch" data-color="#8B5CF6" style="background:#8B5CF6">
                                                            <input type="radio" name="event-color" value="#8B5CF6">
                                                        </label>
                                                        <label class="color-swatch" data-color="#EC4899" style="background:#EC4899">
                                                            <input type="radio" name="event-color" value="#EC4899">
                                                        </label>
                                                        <label class="color-swatch" data-color="#06B6D4" style="background:#06B6D4">
                                                            <input type="radio" name="event-color" value="#06B6D4">
                                                        </label>
                                                        <label class="color-swatch" data-color="#64748B" style="background:#64748B">
                                                            <input type="radio" name="event-color" value="#64748B">
                                                        </label>
                                                        <label class="color-swatch" data-color="#84CC16" style="background:#84CC16">
                                                            <input type="radio" name="event-color" value="#84CC16">
                                                        </label>
                                                        <label class="color-swatch" data-color="#F97316" style="background:#F97316">
                                                            <input type="radio" name="event-color" value="#F97316">
                                                        </label>
                                                    </div>
                                                </div>
                                                <small class="text-muted">Pilih warna untuk menandai kegiatan di kalender</small>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-2">
                                                <label class="form-label">Tahun Ajaran</label>
                                                <select class="form-select" name="event-academic-year" id="event-academic-year">
                                                    <option value="">-- Pilih --</option>
                                                    @foreach($academicYears as $ay)
                                                        <option value="{{ $ay->id }}">{{ $ay->name }} ({{ $ay->semester_text }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @if($isGlobal)
                                        <div class="col-12" id="work-unit-form-group">
                                            <div class="mb-2">
                                                <label class="form-label">Satuan Kerja</label>
                                                <select class="form-select" name="event-work-unit" id="event-work-unit">
                                                    <option value="">Pondok (Semua)</option>
                                                    @foreach($workUnits as $wu)
                                                        <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-12">
                                            <div class="mb-2">
                                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="text" id="event-start-date" class="form-control flatpickr flatpickr-input"
                                                        placeholder="Pilih tanggal" readonly required>
                                                    <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-2">
                                                <label class="form-label">Deskripsi / Keterangan</label>
                                                <textarea class="form-control" id="event-description" rows="2"
                                                    placeholder="Keterangan tambahan..."></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input type="checkbox" name="event-is-active" class="form-check-input" id="event-is-active" value="1" checked>
                                                <label class="form-check-label" for="event-is-active">Aktif</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" id="eventid" name="eventid" value="" />
                                <input type="hidden" id="event-database-id" value="" />

                                <div class="hstack gap-2 justify-content-end mt-3">
                                    <button type="button" class="btn btn-soft-danger" id="btn-delete-event">
                                        <i class="ri-delete-bin-line align-bottom"></i> Hapus
                                    </button>
                                    <button type="submit" class="btn btn-success" id="btn-save-event">
                                        <i class="ri-save-line align-bottom"></i> Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/fullcalendar/index.global.min.js') }}"></script>
    <script>
        // Pass PHP data to JS
        window.KALDIK_EVENTS = @json($kaldikEvents);
        window.KALDIK_CAN_CREATE = @json($canCreate);
        window.KALDIK_CAN_UPDATE = @json($canUpdate);
        window.KALDIK_IS_ADMIN_TU = @json($isAdminTU ?? false);
        window.KALDIK_IS_GLOBAL = @json($isGlobal ?? false);
        window.KALDIK_USER_WORK_UNIT_ID = @json($userWorkUnitId);
        window.KALDIK_STORE_URL = "{{ route('user.kaldik.store', ['userId' => $userId]) }}";
        window.KALDIK_UPDATE_URL_PREFIX = "{{ route('user.kaldik.update', ['userId' => $userId, 'kaldikId' => '__id__']) }}";
        window.KALDIK_DESTROY_URL_PREFIX = "{{ route('user.kaldik.destroy', ['userId' => $userId, 'kaldikId' => '__id__']) }}";
        window.KALDIK_TOGGLE_URL_PREFIX = "{{ route('user.kaldik.toggle', ['userId' => $userId, 'kaldikId' => '__id__']) }}";
        window.KALDIK_CSRF_TOKEN = "{{ csrf_token() }}";
        window.KALDIK_USER_ID = "{{ $userId }}";
    </script>
    <script src="{{ URL::asset('build/js/pages/kaldik-calendar.init.js') }}"></script>
@endsection
