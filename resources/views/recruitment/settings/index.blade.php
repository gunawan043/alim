@extends('layouts.master')
@section('title')
    Recruitment Settings
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Recruitment
        @endslot
        @slot('title')
            Settings
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#general" role="tab">
                                <i class="ri-settings-4-line me-1 align-bottom"></i> General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#stages" role="tab">
                                <i class="ri-timeline-line me-1 align-bottom"></i> Tahapan Seleksi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#email" role="tab">
                                <i class="ri-mail-line me-1 align-bottom"></i> Email Templates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#notifications" role="tab">
                                <i class="ri-notification-line me-1 align-bottom"></i> Notifications
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- General Settings -->
                        <div class="tab-pane active" id="general" role="tabpanel">
                            <form action="{{ route('user.ats.settings.update', ['userId' => $userId]) }}" method="POST">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <h6 class="text-muted mb-3">Auto-Close Settings</h6>

                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="auto_close"
                                                name="auto_close" checked>
                                            <label class="form-check-label" for="auto_close">
                                                Auto-close expired jobs
                                            </label>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Close after (days)</label>
                                            <input type="number" class="form-control" name="close_after_days"
                                                value="0">
                                            <small class="text-muted">0 = close immediately after end date</small>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="send_reminder" name="send_reminder" checked>
                                            <label class="form-check-label" for="send_reminder">
                                                Send expiry reminders
                                            </label>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Reminder days before</label>
                                            <input type="text" class="form-control" name="reminder_days" value="7,3,1">
                                            <small class="text-muted">Pisahkan dengan koma</small>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <h6 class="text-muted mb-3">Application Settings</h6>

                                        <div class="mb-3">
                                            <label class="form-label">Max applications per user</label>
                                            <input type="number" class="form-control" name="max_applications"
                                                value="0">
                                            <small class="text-muted">0 = unlimited</small>
                                        </div>

                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="allow_multiple" name="allow_multiple" checked>
                                            <label class="form-check-label" for="allow_multiple">
                                                Allow multiple applications to same job
                                            </label>
                                        </div>

                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="require_cv"
                                                name="require_cv" checked>
                                            <label class="form-check-label" for="require_cv">
                                                Require CV upload
                                            </label>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Allowed file types</label>
                                            <input type="text" class="form-control" name="file_types"
                                                value="pdf,doc,docx">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Max file size (MB)</label>
                                            <input type="number" class="form-control" name="max_file_size"
                                                value="5">
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-primary">Save Settings</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Tahapan Seleksi -->
                        <div class="tab-pane" id="stages" role="tabpanel">
                            <form action="{{ route('user.ats.settings.stages', ['userId' => $userId]) }}" method="POST">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <h6 class="text-muted mb-3">Default Selection Stages</h6>

                                        <div id="stages-list">
                                            @foreach ($stages ?? ['Administrasi', 'Tes Tertulis', 'Wawancara HR', 'Wawancara User', 'Tes Kesehatan'] as $index => $stage)
                                                <div class="row mb-2 stage-item">
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control" name="stages[]"
                                                            value="{{ $stage }}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-soft-danger"
                                                            onclick="removeStage(this)">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <button type="button" class="btn btn-soft-primary btn-sm mt-2"
                                            onclick="addStage()">
                                            <i class="ri-add-line"></i> Tambah Stage
                                        </button>
                                    </div>

                                    <div class="col-lg-12">
                                        <hr>
                                        <h6 class="text-muted mb-3">Default Interviewers</h6>

                                        <select class="form-control" data-choices data-choices-multiple-remove="true"
                                            name="default_interviewers[]" multiple>
                                            @foreach ($users ?? [] as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}
                                                    ({{ $user->roles->pluck('name')->join(', ') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-primary">Save Stages</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Email Templates -->
                        <div class="tab-pane" id="email" role="tabpanel">
                            <form action="{{ route('user.ats.settings.email-templates', ['userId' => $userId]) }}" method="POST">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <ul class="nav nav-pills mb-3" id="emailTemplates" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="application-tab"
                                                    data-bs-toggle="pill" data-bs-target="#application"
                                                    type="button">Application Received</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="status-tab" data-bs-toggle="pill"
                                                    data-bs-target="#status" type="button">Status Update</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="interview-tab" data-bs-toggle="pill"
                                                    data-bs-target="#interview" type="button">Interview Schedule</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="result-tab" data-bs-toggle="pill"
                                                    data-bs-target="#result" type="button">Interview Result</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="offer-tab" data-bs-toggle="pill"
                                                    data-bs-target="#offer" type="button">Job Offer</button>
                                            </li>
                                        </ul>

                                        <div class="tab-content">
                                            <div class="tab-pane show active" id="application">
                                                <div class="mb-3">
                                                    <label class="form-label">Subject</label>
                                                    <input type="text" class="form-control" name="application_subject"
                                                        value="Lamaran Anda telah kami terima - {{ config('app.name') }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email Body</label>
                                                    <textarea class="form-control" name="application_body" rows="10">Halo {{ '{nama}' }},

Terima kasih telah melamar posisi {{ '{posisi}' }} di {{ config('app.name') }}.

Lamaran Anda telah kami terima dan akan segera diproses oleh tim rekrutmen. Kami akan menghubungi Anda kembali melalui email untuk informasi lebih lanjut mengenai proses seleksi.

Salam,
Tim Rekrutmen {{ config('app.name') }}</textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane" id="status">
                                                <!-- Template untuk status update -->
                                            </div>

                                            <div class="tab-pane" id="interview">
                                                <!-- Template untuk interview -->
                                            </div>

                                            <div class="tab-pane" id="result">
                                                <!-- Template untuk result -->
                                            </div>

                                            <div class="tab-pane" id="offer">
                                                <!-- Template untuk job offer -->
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <p class="text-muted">
                                                <strong>Available variables:</strong><br>
                                                {{ '{nama}' }} - Nama kandidat<br>
                                                {{ '{posisi}' }} - Posisi yang dilamar<br>
                                                {{ '{perusahaan}' }} - Nama perusahaan<br>
                                                {{ '{tanggal}' }} - Tanggal<br>
                                                {{ '{link}' }} - Link/URL
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-primary">Save Templates</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Notifications -->
                        <div class="tab-pane" id="notifications" role="tabpanel">
                            <form action="" method="POST">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <h6 class="text-muted mb-3">Email Notifications</h6>

                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_applicant" checked>
                                            <label class="form-check-label" for="email_applicant">
                                                Send email to applicant on status change
                                            </label>
                                        </div>

                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_admin" checked>
                                            <label class="form-check-label" for="email_admin">
                                                Send email to admin on new application
                                            </label>
                                        </div>

                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_interviewer"
                                                checked>
                                            <label class="form-check-label" for="email_interviewer">
                                                Send email to interviewer on schedule
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <h6 class="text-muted mb-3">WhatsApp Notifications</h6>

                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="wa_applicant">
                                            <label class="form-check-label" for="wa_applicant">
                                                Send WhatsApp to applicant on status change
                                            </label>
                                        </div>

                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="wa_schedule">
                                            <label class="form-check-label" for="wa_schedule">
                                                Send WhatsApp for interview schedule
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                                    </div>
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
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        function addStage() {
            let html = `
        <div class="row mb-2 stage-item">
            <div class="col-md-6">
                <input type="text" class="form-control" name="stages[]" placeholder="Nama tahapan">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-soft-danger" onclick="removeStage(this)">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `;
            document.getElementById('stages-list').insertAdjacentHTML('beforeend', html);
        }

        function removeStage(btn) {
            btn.closest('.stage-item').remove();
        }
    </script>
@endsection
