@extends('layouts.master')
@section('title') Template Kegiatan Asrama @endsection

@section('css')
<style>
    .template-card { transition: all 0.2s ease; border: 1px solid var(--bs-border-color); }
    .template-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .template-item-badge { display: inline-flex; align-items: center; gap: 4px; margin: 2px; }
    .activity-item-row { background: var(--bs-tertiary-bg); border-radius: 6px; padding: 10px; margin-bottom: 8px; }
    .session-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Template Kegiatan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tambah / Edit Template Form --}}
    <div class="card mb-4" id="templateFormCard">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="ri-file-add-line text-primary fs-5"></i>
                <h5 class="mb-0">Tambah / Edit Template Kegiatan</h5>
            </div>
        </div>
        <div class="card-body">
            <form method="POST"
                  action="{{ route('user.asrama.templates.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                  id="templateForm">
                @csrf

                <div class="row g-4">
                    {{-- Session selector --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Sesi <span class="text-danger">*</span>
                        </label>
                        <select name="session" id="templateSession" class="form-control @error('session') is-invalid @enderror" required>
                            <option value="">-- Pilih Sesi --</option>
                            <option value="subuh"  {{ old('session') == 'subuh'  ? 'selected' : '' }}>Subuh</option>
                            <option value="pagi"   {{ old('session') == 'pagi'   ? 'selected' : '' }}>Pagi</option>
                            <option value="siang"  {{ old('session') == 'siang'  ? 'selected' : '' }}>Siang</option>
                            <option value="sore"   {{ old('session') == 'sore'   ? 'selected' : '' }}>Sore</option>
                            <option value="isya"   {{ old('session') == 'isya'   ? 'selected' : '' }}>Isya</option>
                            <option value="malam"  {{ old('session') == 'malam'  ? 'selected' : '' }}>Malam</option>
                        </select>
                        @error('session')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Catatan Template</label>
                        <input type="text" name="notes" id="templateNotes" class="form-control"
                               value="{{ old('notes') }}" placeholder="Contoh: Template kegiatan wajib pagi hari">
                        <div class="form-text">Opsional. Tambahkan catatan untuk template ini.</div>
                    </div>

                    {{-- Activity Items Builder --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Item Aktivitas <span class="text-danger">*</span>
                        </label>
                        <div class="form-text mb-2">Tambahkan item aktivitas yang akan dicatat. Setiap item memiliki: Key (slug), Label (nama tampilan), dan Tipe data.</div>

                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="activityItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:35%;">Key / Slug</th>
                                        <th class="text-center" style="width:35%;">Label (Nama Tampilan)</th>
                                        <th class="text-center" style="width:20%;">Tipe Data</th>
                                        <th class="text-center" style="width:10%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="activityItemsBody">
                                    {{-- Rows injected by JS --}}
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
                                <i class="ri-add-line me-1"></i> Tambah Item
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="loadDefaultBtn">
                                <i class="ri-file-list-line me-1"></i> Muat Default
                            </button>
                        </div>
                        <input type="hidden" name="activity_items_json" id="activityItemsJson" value="{{ old('activity_items_json', '[]') }}">
                        @error('activity_items_json')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex gap-2 justify-content-end mt-4">
                    <button type="button" class="btn btn-light" onclick="toggleTemplateForm()">Batal</button>
                    <button type="submit" class="btn btn-success" id="submitTemplateBtn">
                        <i class="ri-save-line me-1"></i> Simpan Template
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Default activity items by session --}}
    @php
        $defaultItems = [
            'subuh' => [
                ['key' => 'shalat_subuh',  'label' => 'Shalat Subuh',       'type' => 'boolean'],
                ['key' => 'qiyamul_lail',  'label' => 'Qiyamul Lail',       'type' => 'boolean'],
                ['key' => 'mengaji',        'label' => 'Mengaji',             'type' => 'text'],
                ['key' => 'catatan',        'label' => 'Catatan',             'type' => 'textarea'],
            ],
            'malam' => [
                ['key' => 'shalat_isya',    'label' => 'Shalat Isya',        'type' => 'boolean'],
                ['key' => 'mengaji_malam',  'label' => 'Mengaji Malam',      'type' => 'text'],
                ['key' => 'tilawah',        'label' => 'Tilawah',            'type' => 'text'],
                ['key' => 'catatan',        'label' => 'Catatan',            'type' => 'textarea'],
            ],
        ];
    @endphp

    {{-- Template Cards by Session --}}
    @php
        $sessions = ['subuh', 'pagi', 'siang', 'sore', 'isya', 'malam'];
        $sessionIcons = [
            'subuh'  => ['bg-info-subtle text-info',    'ri-sun-line'],
            'pagi'   => ['bg-warning-subtle text-warning', 'ri-sun-foggy-line'],
            'siang'  => ['bg-primary-subtle text-primary', 'ri-sun-cloudy-line'],
            'sore'   => ['bg-warning-subtle text-warning', 'ri-sunset-line'],
            'isya'   => ['bg-dark-subtle text-dark',     'ri-moon-clear-line'],
            'malam'  => ['bg-dark-subtle text-dark',     'ri-star-line'],
        ];
        $sessionLabels = [
            'subuh'  => 'Subuh',
            'pagi'   => 'Pagi',
            'siang'  => 'Siang',
            'sore'   => 'Sore',
            'isya'   => 'Isya',
            'malam'  => 'Malam',
        ];
    @endphp

    <div class="row g-3">
        @foreach($sessions as $session)
            @php
                $template = $templates->firstWhere('session', $session);
                $items = [];
                if ($template && $template->activity_items) {
                    try {
                        $decoded = is_string($template->activity_items) ? json_decode($template->activity_items, true) : $template->activity_items;
                        if (is_array($decoded)) { $items = $decoded; }
                    } catch (\Exception $e) { $items = []; }
                }
                $iconClass = $sessionIcons[$session] ?? ['bg-secondary-subtle text-secondary', 'ri-time-line'];
            @endphp

            <div class="col-xl-4 col-lg-6">
                <div class="card template-card h-100 {{ $template && $template->is_active ? 'border-primary' : 'border-secondary-subtle' }}">
                    {{-- Card Header --}}
                    <div class="card-header bg-transparent">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="session-icon {{ $iconClass[0] }}">
                                    <i class="{{ $iconClass[1] }} fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">{{ $sessionLabels[$session] }}</h5>
                                    <span class="badge {{ $template && $template->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} mt-1">
                                        {{ $template && $template->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Toggle Active Form --}}
                            <form method="POST"
                                  action="{{ route('user.asrama.templates.toggle', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'session' => $session]) }}"
                                  class="d-inline" id="toggleForm{{ ucfirst($session) }}">
                                @csrf
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           onchange="document.getElementById('toggleForm{{ ucfirst($session) }}').submit();"
                                           {{ $template && $template->is_active ? 'checked' : '' }}>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="card-body">
                        @if(count($items) > 0)
                            <p class="text-muted small mb-2">Item Aktivitas:</p>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($items as $item)
                                    @php
                                        $typeIcon = match($item['type'] ?? 'text') {
                                            'boolean'  => 'ri-checkbox-circle-line text-success',
                                            'textarea' => 'ri-file-text-line text-info',
                                            default    => 'ri-text',
                                        };
                                    @endphp
                                    <span class="badge bg-light text-dark template-item-badge" title="Key: {{ $item['key'] ?? '' }} | Tipe: {{ $item['type'] ?? 'text' }}">
                                        <i class="{{ $typeIcon }} small"></i>
                                        {{ $item['label'] ?? ($item['key'] ?? '?') }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted small text-center py-3">
                                <i class="ri-file-list-3-line d-block mb-1" style="font-size:1.5rem;"></i>
                                Belum ada template untuk sesi ini.
                            </div>
                        @endif

                        @if($template && $template->notes)
                            <div class="mt-3 p-2 rounded" style="background:var(--bs-tertiary-bg);">
                                <small class="text-muted">
                                    <i class="ri-sticky-note-line me-1"></i>
                                    {{ $template->notes }}
                                </small>
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer --}}
                    <div class="card-footer bg-transparent border-top">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1"
                                    onclick="loadTemplateForEdit('{{ $session }}')">
                                <i class="ri-edit-line me-1"></i> Edit
                            </button>
                            @if(isset($template) && $template)
                                <a href="{{ route('user.asrama.templates.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'session' => $session]) }}"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus template sesi {{ $sessionLabels[$session] }}?')">
                                    <i class="ri-delete-bin-line"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@endsection

@section('script')
<script>
    /* ================================================================
       Activity Items Builder
    ================================================================ */
    const defaultItemsBySession = @json($defaultItems);

    function buildActivityItemsTable(items) {
        const tbody = document.getElementById('activityItemsBody');
        tbody.innerHTML = '';

        items.forEach(function(item, index) {
            addItemRow(item.key || '', item.label || '', item.type || 'text');
        });
    }

    function addItemRow(key, label, type) {
        const tbody = document.getElementById('activityItemsBody');
        const index = tbody.children.length;

        const tr = document.createElement('tr');
        tr.setAttribute('data-index', index);
        tr.innerHTML =
            '<td>' +
                '<input type="text" class="form-control form-control-sm item-key" ' +
                '       placeholder="contoh: mengaji" value="' + esc(key) + '" ' +
                '       pattern="[a-z0-9_]+" title="Slug: huruf kecil, angka, underscore">' +
            '</td>' +
            '<td>' +
                '<input type="text" class="form-control form-control-sm item-label" ' +
                '       placeholder="Contoh: Mengaji" value="' + esc(label) + '">' +
            '</td>' +
            '<td>' +
                '<select class="form-control form-control-sm item-type">' +
                    '<option value="text"    ' + (type === 'text'    ? 'selected' : '') + '>Text</option>' +
                    '<option value="boolean" ' + (type === 'boolean' ? 'selected' : '') + '>Boolean (Ya/Tidak)</option>' +
                    '<option value="textarea" ' + (type === 'textarea' ? 'selected' : '') + '>Textarea</option>' +
                '</select>' +
            '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)" title="Hapus">' +
                    '<i class="ri-delete-bin-line"></i>' +
                '</button>' +
            '</td>';
        tbody.appendChild(tr);
    }

    function removeItemRow(btn) {
        const tr = btn.closest('tr');
        tr.remove();
        syncItemsJson();
    }

    function syncItemsJson() {
        const rows = document.querySelectorAll('#activityItemsBody tr');
        const items = [];
        rows.forEach(function(row) {
            items.push({
                key:   row.querySelector('.item-key').value.trim(),
                label: row.querySelector('.item-label').value.trim(),
                type:  row.querySelector('.item-type').value
            });
        });
        document.getElementById('activityItemsJson').value = JSON.stringify(items);
    }

    function esc(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    document.getElementById('addItemBtn').addEventListener('click', function() {
        addItemRow('', '', 'text');
    });

    document.getElementById('loadDefaultBtn').addEventListener('click', function() {
        const session = document.getElementById('templateSession').value;
        if (!session) {
            Swal.fire({ icon: 'warning', title: 'Pilih Sesi', text: 'Pilih sesi terlebih dahulu sebelum memuat default.' });
            return;
        }
        const defaults = defaultItemsBySession[session] || [];
        if (defaults.length === 0) {
            Swal.fire({ icon: 'info', title: 'Tidak Ada Default', text: 'Tidak ada template default untuk sesi ini.' });
            return;
        }
        buildActivityItemsTable(defaults);
        syncItemsJson();
        Swal.fire({ icon: 'success', title: 'Default Dimuat', text: 'Item default sesi ' + session + ' telah dimuat.', timer: 1500, showConfirmButton: false });
    });

    document.getElementById('templateForm').addEventListener('submit', function() {
        syncItemsJson();
        // Validate
        const json = document.getElementById('activityItemsJson').value;
        try {
            const items = JSON.parse(json);
            if (!Array.isArray(items) || items.length === 0) {
                Swal.fire({ icon: 'error', title: 'Item Aktivitas Kosong', text: 'Tambahkan setidaknya satu item aktivitas.' });
                event.preventDefault();
                return false;
            }
            const invalid = items.filter(i => !i.key || !i.label);
            if (invalid.length > 0) {
                Swal.fire({ icon: 'error', title: 'Item Tidak Lengkap', text: 'Pastikan setiap item memiliki Key dan Label.' });
                event.preventDefault();
                return false;
            }
        } catch(e) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Format item aktivitas tidak valid.' });
            event.preventDefault();
            return false;
        }

        const btn = document.getElementById('submitTemplateBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
        return true;
    });

    /* ================================================================
       Toggle Form Card visibility
    ================================================================ */
    function toggleTemplateForm() {
        const card = document.getElementById('templateFormCard');
        if (card.style.display === 'none') {
            card.style.display = '';
            card.scrollIntoView({ behavior: 'smooth' });
        } else {
            card.style.display = 'none';
        }
    }

    /* ================================================================
       Load existing template for editing
    ================================================================ */
    function loadTemplateForEdit(session) {
        const templatesData = @json($templates->keyBy('session'));
        const tmpl = templatesData[session];

        document.getElementById('templateSession').value = session;

        if (tmpl && tmpl.notes) {
            document.getElementById('templateNotes').value = tmpl.notes;
        } else {
            document.getElementById('templateNotes').value = '';
        }

        let items = [];
        if (tmpl && tmpl.activity_items) {
            try {
                const decoded = typeof tmpl.activity_items === 'string'
                    ? JSON.parse(tmpl.activity_items)
                    : tmpl.activity_items;
                if (Array.isArray(decoded)) { items = decoded; }
            } catch(e) { items = []; }
        }

        if (items.length > 0) {
            buildActivityItemsTable(items);
        } else {
            buildActivityItemsTable([]);
        }
        syncItemsJson();

        document.getElementById('templateFormCard').style.display = '';
        document.getElementById('templateFormCard').scrollIntoView({ behavior: 'smooth' });
    }

    // Initialize with empty row if no items
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('activityItemsBody');
        if (tbody.children.length === 0) {
            addItemRow('', '', 'text');
        }
        document.getElementById('templateFormCard').style.display = 'none';
    });
</script>
@endsection
