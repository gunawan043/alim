@extends('layouts.master')
@section('title') Pengaturan Rekrutmen @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<style>
    .settings-card { border: 0; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
    .settings-section { padding: 24px; border-bottom: 1px solid var(--bs-border-color); }
    .settings-section:last-child { border-bottom: none; }
    .setting-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed var(--bs-border-color-translucent); }
    .setting-item:last-child { border-bottom: none; }
    .nav-tabs-custom .nav-link { font-size: 0.875rem; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('li_2') Pengaturan @endslot
    @slot('title') Sistem Rekrutmen @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ri-check-line me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    {{-- Sidebar Navigation --}}
    <div class="col-lg-3">
        <div class="card settings-card">
            <div class="card-body p-2">
                <div class="nav flex-column nav-pills" role="tablist">
                    <button class="nav-link text-start px-3 py-2 active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">
                        <i class="ri-settings-3-line me-2 align-middle"></i> Umum
                    </button>
                    <button class="nav-link text-start px-3 py-2" data-bs-toggle="tab" data-bs-target="#tab-stages" type="button">
                        <i class="ri-flow-chart me-2 align-middle"></i> Tahapan Seleksi
                    </button>
                    <button class="nav-link text-start px-3 py-2" data-bs-toggle="tab" data-bs-target="#tab-email" type="button">
                        <i class="ri-mail-open-line me-2 align-middle"></i> Template Email
                    </button>
                    <button class="nav-link text-start px-3 py-2" data-bs-toggle="tab" data-bs-target="#tab-notif" type="button">
                        <i class="ri-notification-3-line me-2 align-middle"></i> Notifikasi
                    </button>
                    <button class="nav-link text-start px-3 py-2" data-bs-toggle="tab" data-bs-target="#tab-interviewer" type="button">
                        <i class="ri-user-search-line me-2 align-middle"></i> Tim Interview
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="col-lg-9">
        <div class="tab-content">

            {{-- TAB: Umum --}}
            <div class="tab-pane active" id="tab-general" role="tabpanel">
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-settings-3-line me-2 text-primary"></i>Pengaturan Umum</h5>
                    </div>
                    <div class="card-body settings-section">
                        <form action="{{ route('user.ats.settings.update', ['userId' => $userId]) }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Lowongan Ditutup Otomatis</label>
                                    <select class="form-select" name="auto_close">
                                        <option value="0" {{ ($settings['auto_close'] ?? null) == '0' ? 'selected' : '' }}>Tidak Aktif</option>
                                        <option value="1" {{ ($settings['auto_close'] ?? null) == '1' ? 'selected' : '' }}>Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ditutup Setelah (hari)</label>
                                    <input type="number" class="form-control" name="close_after_days"
                                        value="{{ $settings['close_after_days'] ?? 30 }}" min="1" max="365">
                                    <small class="text-muted">Lowongan ditutup otomatis setelah X hari</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Pengingat Jadwal</label>
                                    <select class="form-select" name="send_reminder">
                                        <option value="0" {{ ($settings['send_reminder'] ?? null) == '0' ? 'selected' : '' }}>Nonaktif</option>
                                        <option value="1" {{ ($settings['send_reminder'] ?? null) == '1' ? 'selected' : '' }}>Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Hari Sebelum</label>
                                    <input type="number" class="form-control" name="reminder_days"
                                        value="{{ $settings['reminder_days'] ?? 3 }}" min="1" max="30">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Maks. Lamaran per Posisi</label>
                                    <input type="number" class="form-control" name="max_applications"
                                        value="{{ $settings['max_applications'] ?? 0 }}" min="0">
                                    <small class="text-muted">0 = tidak terbatas</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ukuran File Maks (MB)</label>
                                    <input type="number" class="form-control" name="max_file_size"
                                        value="{{ $settings['max_file_size'] ?? 10 }}" min="1" max="50">
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="allow_multiple" value="1"
                                            id="allow_multiple" {{ ($settings['allow_multiple'] ?? null) == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_multiple">
                                            Izinkan pelamar melamar lebih dari satu posisi
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="require_cv" value="1"
                                            id="require_cv" {{ ($settings['require_cv'] ?? null) == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="require_cv">
                                            Wajib upload CV saat melamar
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Simpan Pengaturan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- TAB: Tahapan Seleksi --}}
            <div class="tab-pane" id="tab-stages" role="tabpanel">
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-flow-chart me-2 text-primary"></i>Tahapan Seleksi Default</h5>
                        <p class="text-muted mt-1 mb-0" style="font-size:0.8rem">Konfigurasi tahapan yang akan digunakan sebagai template lowongan baru</p>
                    </div>
                    <div class="card-body settings-section">
                        <form action="{{ route('user.ats.settings.stages', ['userId' => $userId]) }}" method="POST" id="stagesForm">
                            @csrf
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted" style="font-size:0.8rem">Tarik untuk mengubah urutan</span>
                                <button type="button" class="btn btn-sm btn-success" id="addStageBtn">
                                    <i class="ri-add-line me-1"></i> Tambah Tahap
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-centered align-middle" id="stagesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:40px"></th>
                                            <th style="width:40px">#</th>
                                            <th>Nama Tahapan</th>
                                            <th style="width:120px">Durasi (hari)</th>
                                            <th style="width:100px">Warna</th>
                                            <th style="width:80px">Wajib</th>
                                            <th style="width:60px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="stagesBody">
                                        @forelse($stages as $i => $stage)
                                        <tr class="stage-row" data-index="{{ $i }}">
                                            <td><i class="ri-drag-move-2-line text-muted handle" style="cursor:grab"></i></td>
                                            <td class="fw-bold text-muted stage-num">{{ $i + 1 }}</td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="stages[{{ $i }}][name]"
                                                    value="{{ $stage->nama_tahapan }}" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" name="stages[{{ $i }}][durasi]"
                                                    value="{{ $stage->durasi_hari ?? 7 }}" min="1" max="90">
                                            </td>
                                            <td>
                                                <input type="color" class="form-control form-control-color w-100" name="stages[{{ $i }}][warna]"
                                                    value="{{ $stage->warna ?? '#667eea' }}">
                                            </td>
                                            <td>
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="stages[{{ $i }}][wajib]"
                                                        value="1" {{ $stage->is_wajib ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-light text-danger remove-stage">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr id="emptyStageRow">
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="ri-add-circle-line fs-2 d-block mb-2"></i>
                                                Belum ada tahapan. Klik "Tambah Tahap" untuk memulai.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Simpan Tahapan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- TAB: Template Email --}}
            <div class="tab-pane" id="tab-email" role="tabpanel">
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-mail-open-line me-2 text-primary"></i>Template Email Otomatis</h5>
                    </div>
                    <div class="card-body settings-section">
                        <form action="{{ route('user.ats.settings.email-templates', ['userId' => $userId]) }}" method="POST">
                            @csrf
                            {{-- Lolos Seleksi --}}
                            <h6 class="fw-bold mb-3 text-success"><i class="ri-checkbox-circle-line me-1"></i>Lolos Seleksi</h6>
                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Subjek Email</label>
                                <input type="text" class="form-control mb-2" name="templates[lolos_seleksi][subject]"
                                    value="{{ $settings['email_templates']['lolosse'] ?? 'Selamat! Anda Lolos Seleksi' }}"
                                    placeholder="Subjek email...">
                                <label class="form-label small fw-semibold">Isi Pesan</label>
                                <textarea class="form-control" name="templates[lolos_seleksi][body]" rows="4"
                                    placeholder="Isi template email... Gunakan {nama}, {posisi}, {tanggal} sebagai variabel">{{ $settings['email_templates']['lolosse'] ?? '' }}</textarea>
                                <div class="form-text">Variabel: {nama}, {posisi}, {tanggal}, {lokasi}, {waktu}</div>
                            </div>
                            <hr>
                            {{-- Tidak Lolos --}}
                            <h6 class="fw-bold mb-3 text-danger"><i class="ri-close-circle-line me-1"></i>Tidak Lolos</h6>
                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Subjek Email</label>
                                <input type="text" class="form-control mb-2" name="templates[tidak_lolos][subject]"
                                    value="{{ $settings['email_templates']['tidak_lolos']['subject'] ?? 'Hasil Seleksi Rekrutmen' }}">
                                <label class="form-label small fw-semibold">Isi Pesan</label>
                                <textarea class="form-control" name="templates[tidak_lolos][body]" rows="4"
                                    placeholder="Isi template email...">{{ $settings['email_templates']['tidak_lolos']['body'] ?? '' }}</textarea>
                            </div>
                            <hr>
                            {{-- Undangan Interview --}}
                            <h6 class="fw-bold mb-3 text-primary"><i class="ri-calendar-event-line me-1"></i>Undangan Interview</h6>
                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Subjek Email</label>
                                <input type="text" class="form-control mb-2" name="templates[interview][subject]"
                                    value="{{ $settings['email_templates']['interview']['subject'] ?? 'Undangan Interview Rekrutmen' }}">
                                <label class="form-label small fw-semibold">Isi Pesan</label>
                                <textarea class="form-control" name="templates[interview][body]" rows="4">{{ $settings['email_templates']['interview']['body'] ?? '' }}</textarea>
                            </div>
                            <hr>
                            {{-- Diterima --}}
                            <h6 class="fw-bold mb-3" style="color:#1a1a2e"><i class="ri-user-follow-line me-1"></i>Kontrak Kerja</h6>
                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Subjek Email</label>
                                <input type="text" class="form-control mb-2" name="templates[diterima][subject]"
                                    value="{{ $settings['email_templates']['diterima']['subject'] ?? 'Selamat! Anda Diterima' }}">
                                <label class="form-label small fw-semibold">Isi Pesan</label>
                                <textarea class="form-control" name="templates[diterima][body]" rows="4">{{ $settings['email_templates']['diterima']['body'] ?? '' }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan Template
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- TAB: Notifikasi --}}
            <div class="tab-pane" id="tab-notif" role="tabpanel">
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-notification-3-line me-2 text-primary"></i>Pengaturan Notifikasi</h5>
                    </div>
                    <div class="card-body settings-section">
                        <div class="setting-item">
                            <div>
                                <div class="fw-semibold">Notifikasi Email</div>
                                <small class="text-muted">Kirim email saat status lamaran berubah</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>
                        <div class="setting-item">
                            <div>
                                <div class="fw-semibold">Notifikasi WhatsApp</div>
                                <small class="text-muted">Kirim pesan WhatsApp saat jadwal interview</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox">
                            </div>
                        </div>
                        <div class="setting-item">
                            <div>
                                <div class="fw-semibold">Reminder Otomatis</div>
                                <small class="text-muted">Kirim pengingat 3 hari sebelum jadwal</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>
                        <div class="setting-item">
                            <div>
                                <div class="fw-semibold">Notifikasi ke HRD</div>
                                <small class="text-muted">Beritahu HRD saat pelamar baru masuk</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB: Tim Interview --}}
            <div class="tab-pane" id="tab-interviewer" role="tabpanel">
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-user-search-line me-2 text-primary"></i>Tim Interviewer</h5>
                        <p class="text-muted mt-1 mb-0" style="font-size:0.8rem">Pilih staff yang berhak menjadi interviewer</p>
                    </div>
                    <div class="card-body settings-section">
                        <form action="{{ route('user.ats.settings.stages', ['userId' => $userId]) }}" method="POST">
                            @csrf
                            <div class="row">
                                @foreach($users as $user)
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="interviewers[]"
                                            value="{{ $user->id }}"
                                            id="interviewer_{{ $user->id }}"
                                            {{ in_array($user->id, $settings['default_interviewers'] ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label d-flex align-items-center gap-2" for="interviewer_{{ $user->id }}">
                                            <img src="{{ $user->avatar ? URL::asset('images/'.$user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                                                alt="" class="rounded-circle" width="28" height="28">
                                            <div>
                                                <div class="fw-semibold" style="font-size:0.875rem">{{ $user->name }}</div>
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="ri-save-line me-1"></i> Simpan Tim Interview
                            </button>
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
<script src="{{ URL::asset('build/libs/sortablejs/Sortable.min.js') }}"></script>
<script>
    // Sortable stages
    if (document.getElementById('stagesBody')) {
        new Sortable(document.getElementById('stagesBody'), {
            handle: '.handle',
            animation: 150,
            onEnd: function(evt) {
                document.querySelectorAll('.stage-num').forEach((el, i) => el.textContent = i + 1);
            }
        });
    }

    // Add stage
    document.getElementById('addStageBtn').addEventListener('click', function() {
        const tbody = document.getElementById('stagesBody');
        const emptyRow = document.getElementById('emptyStageRow');
        if (emptyRow) emptyRow.remove();

        const index = tbody.children.length;
        const colors = ['#667eea','#34c38f','#f1b44c','#f06548','#50a5f0','#2cb67d'];
        const color = colors[index % colors.length];

        const row = document.createElement('tr');
        row.className = 'stage-row';
        row.dataset.index = index;
        row.innerHTML = `
            <td><i class="ri-drag-move-2-line text-muted handle" style="cursor:grab"></i></td>
            <td class="fw-bold text-muted stage-num">${index + 1}</td>
            <td><input type="text" class="form-control form-control-sm" name="stages[${index}][name]" placeholder="Nama tahapan..." required></td>
            <td><input type="number" class="form-control form-control-sm" name="stages[${index}][durasi]" value="7" min="1" max="90"></td>
            <td><input type="color" class="form-control form-control-color w-100" name="stages[${index}][warna]" value="${color}"></td>
            <td><div class="form-check form-switch d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="stages[${index}][wajib]" value="1" checked></div></td>
            <td><button type="button" class="btn btn-sm btn-light text-danger remove-stage"><i class="ri-delete-bin-line"></i></button></td>
        `;
        tbody.appendChild(row);

        row.querySelector('.remove-stage').addEventListener('click', function() {
            row.remove();
            document.querySelectorAll('.stage-num').forEach((el, i) => el.textContent = i + 1);
        });
    });

    // Remove stage
    document.querySelectorAll('.remove-stage').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('tr').remove();
            document.querySelectorAll('.stage-num').forEach((el, i) => el.textContent = i + 1);
        });
    });

    // Success toast
    @if(session('success'))
    Swal.fire({ icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', timer: 2000, showConfirmButton: false });
    @endif
</script>
@endsection
