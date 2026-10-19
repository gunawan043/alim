@extends('layouts.master')
@section('title') Checklist Pekerjaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.kebersihan.index') }}">Kebersihan</a> @endslot
        @slot('title') Checklist Pekerjaan
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.kebersihan.report.create') }}" class="row g-2 align-items-end">
                @if(request('area_type'))<input type="hidden" name="area_type" value="{{ request('area_type') }}">@endif
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Area Kerja</label>
                    @if($areas->count() > 1)
                        <select name="area_id" class="form-select" required>
                            <option value="">— Pilih Area —</option>
                            @foreach($areas as $area)<option value="{{ $area->id }}" {{ $selectedArea?->id === $area->id ? 'selected' : '' }}>{{ $area->name }}</option>@endforeach
                        </select>
                    @elseif($selectedArea)
                        <div class="form-control bg-light d-flex justify-content-between align-items-center"><span>{{ $selectedArea->name }}</span><span class="badge bg-primary-subtle text-primary">Otomatis</span></div>
                    @else
                        <div class="alert alert-warning mb-0 small">Belum ada area kerja yang ditugaskan kepada Anda.</div>
                    @endif
                </div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="report_date" class="form-control" value="{{ $today }}"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-eye-line me-1"></i>Tampilkan</button></div>
                <div class="col-md-1"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-light w-100"><i class="ri-arrow-left-line"></i></a></div>
            </form>
        </div>
    </div>

    @php $defaultOfficer = $substitute ? auth()->id() : ($report?->officer_id ?? auth()->id()); @endphp

    @if($selectedArea)
        <div id="cleaningChecklist" data-area-id="{{ $selectedArea->id }}" data-report-date="{{ $today }}" data-autosave-url="{{ route('sarpras.kebersihan.report.autosave') }}" data-finalize-url="{{ route('sarpras.kebersihan.report.finalize') }}" data-csrf="{{ csrf_token() }}" data-locked="{{ $isLocked ? '1' : '0' }}">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div><div class="text-muted small">Area Kerja</div><h5 class="mb-1">{{ $selectedArea->name }}</h5><div class="small text-muted">{{ $selectedArea->building?->building_name ?? '-' }} · {{ $tasks->count() }} item pekerjaan</div></div>
                        <div class="d-flex flex-column align-items-end gap-1"><span class="badge bg-info-subtle text-info"><i class="ri-calendar-check-line me-1"></i>{{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }}</span>@if($isLocked)<span class="badge bg-success-subtle text-success">Laporan sudah dikirim</span>@endif</div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-5">
                            <label class="form-label">Petugas Pelaksana <span class="text-danger">*</span></label>
                            @if($canManage)
                                <select id="cleaningOfficer" class="form-select" {{ $isLocked ? 'disabled' : '' }}>
                                    <option value="">— Pilih Petugas —</option>
                                    @foreach($officers as $user)<option value="{{ $user->id }}" {{ (string) old('officer_id', $defaultOfficer) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                                <input type="hidden" id="cleaningOfficer" value="{{ auth()->id() }}">
                            @endif
                        </div>
                        @if($substitute)
                            <div class="col-md-7"><div class="alert alert-info mb-0 py-2 small"><i class="ri-user-shared-line me-1"></i>Anda mengisi area milik <strong>{{ $substitute->name }}</strong>. Atur alasan penggantian di bagian bawah halaman.</div></div>
                        @endif
                    </div>
                    <div class="mt-3"><div class="d-flex justify-content-between small text-muted mb-1"><span>Progres pekerjaan</span><span id="checklistProgressText">0 / {{ $tasks->count() }} selesai</span></div><div class="progress" style="height:7px"><div class="progress-bar bg-success" id="checklistProgressBar" style="width:0%"></div></div></div>
                    <div class="d-flex justify-content-between mt-2"><small id="cleaningSaveStatus" class="text-muted"><i class="ri-cloud-line me-1"></i>Tersimpan otomatis</small><small class="text-muted">Perubahan langsung disimpan</small></div>
                    <div class="d-flex flex-wrap gap-3 small text-muted mt-2"><span><i class="ri-check-line text-success me-1"></i>Selesai</span><span><i class="ri-close-line text-danger me-1"></i>Belum</span><span><i class="ri-edit-2-line text-primary me-1"></i>Isi Keterangan</span></div>
                </div>
            </div>

            @php
                $frequencyGroups = [
                    'Harian' => ['Harian'],
                    'Pekanan' => ['Mingguan', '2 minggu'],
                    'Bulanan' => ['Bulanan', 'Per 3 bulan', 'Per 6 bulan', 'Tahunan'],
                ];
                $tasksByFrequency = collect();
                foreach ($frequencyGroups as $label => $frequencies) {
                    $subset = $tasks->filter(fn ($task) => in_array($task->frequency, $frequencies, true))->values();
                    if ($subset->isNotEmpty()) $tasksByFrequency[$label] = $subset;
                }
                $knownFrequencies = collect($frequencyGroups)->flatten()->all();
                $otherTasks = $tasks->filter(fn ($task) => ! in_array($task->frequency, $knownFrequencies, true))->values();
                if ($otherTasks->isNotEmpty()) $tasksByFrequency['Lainnya'] = $otherTasks;
            @endphp

            @if($tasksByFrequency->isNotEmpty())
                <ul class="nav nav-pills nav-fill gap-2 mb-3" role="tablist">
                    @foreach($tasksByFrequency as $frequencyLabel => $frequencyTasks)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#freqTab{{ $loop->index }}">
                                {{ $frequencyLabel }} <span class="badge bg-light text-dark ms-1">{{ $frequencyTasks->count() }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    @foreach($tasksByFrequency as $frequencyLabel => $frequencyTasks)
                        @php $taskNumber = 0; @endphp
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="freqTab{{ $loop->index }}" role="tabpanel">
                            <div class="card mb-3">
                                <div class="card-body p-0">
                                    @foreach($frequencyTasks as $task)
                                        @php
                                            $taskNumber++;
                                            $item = $reportItems->get($task->id);
                                            $selected = old('items.'.$task->id.'.result', $item?->result);
                                            $beforeUrl = $item?->before_photo_url ?: ($item?->before_photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($item->before_photo_path) : null);
                                            $afterUrl = $item?->after_photo_url ?: ($item?->after_photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($item->after_photo_path) : null);
                                        @endphp
                                        <div class="p-3 p-md-4 border-bottom task-row" data-task-id="{{ $task->id }}">
                                            <div class="task-main">
                                                <div class="task-content">
                                                    @if($task->group_label)<div class="text-uppercase small text-primary fw-semibold mb-1">{{ $task->group_label }}</div>@endif
                                                    <div class="fw-semibold">{{ $taskNumber }}. {{ $task->title }}</div>
                                                    <div class="small text-muted">{{ $task->standard ?? 'Sesuai standar kebersihan' }}</div>
                                                    <span class="badge bg-light text-dark border mt-2 d-md-none">{{ ucfirst($task->frequency) }}</span>
                                                </div>
                                                <div class="task-buttons" role="group" aria-label="Tindakan pekerjaan">
                                                    <input type="radio" class="btn-check task-result" name="result_{{ $task->id }}" id="selesai_{{ $task->id }}" value="sudah" {{ $selected === 'sudah' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                                    <label class="btn task-icon-btn btn-outline-success" for="selesai_{{ $task->id }}" title="Selesai" aria-label="Selesai"><i class="ri-check-line"></i><span class="visually-hidden">Selesai</span></label>
                                                    <input type="radio" class="btn-check task-result" name="result_{{ $task->id }}" id="belum_{{ $task->id }}" value="belum" {{ $selected === 'belum' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                                    <label class="btn task-icon-btn btn-outline-danger" for="belum_{{ $task->id }}" title="Belum" aria-label="Belum"><i class="ri-close-line"></i><span class="visually-hidden">Belum</span></label>
                                                    <button class="btn task-icon-btn task-evidence-btn btn-outline-primary evidence-trigger" type="button" data-bs-toggle="modal" data-bs-target="#taskEvidenceModal" data-task-id="{{ $task->id }}" data-task-title="{{ e($task->title) }}" title="Isi keterangan" aria-label="Isi keterangan" {{ $isLocked ? 'disabled' : '' }}><i class="ri-edit-2-line"></i><span>Isi Keterangan</span></button>
                                                </div>
                                            </div>
                                            <input type="hidden" class="task-condition" value="{{ old('items.'.$task->id.'.condition', $item?->condition ?? 'bersih') }}">
                                            <input type="hidden" class="task-notes" value="{{ old('items.'.$task->id.'.notes', $item?->notes) }}">
                                            <input type="hidden" class="task-before-url" value="{{ old('items.'.$task->id.'.before_url', $beforeUrl) }}">
                                            <input type="hidden" class="task-after-url" value="{{ old('items.'.$task->id.'.after_url', $afterUrl) }}">
                                            <div class="task-save-status small text-muted mt-2"></div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="card"><div class="card-body"><x-empty-state title="Belum ada pekerjaan" message="Area ini belum memiliki uraian pekerjaan. Hubungi koordinator." /></div></div>
            @endif
            <div id="cleaningFinalizeError" class="alert alert-danger d-none mt-3"></div>
            @if($tasks->isNotEmpty() && ! $isLocked)
                <div class="card mb-3" style="position:sticky;bottom:0;z-index:5;box-shadow:0 -8px 24px rgba(0,0,0,.08)"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2"><div class="small text-muted"><i class="ri-cloud-done-line me-1 text-success"></i>Setiap perubahan tersimpan otomatis.</div><div class="d-flex gap-2"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-light">Kembali</a><button type="button" id="finalizeCleaning" class="btn btn-success px-4"><i class="ri-send-plane-line me-1"></i>Kirim Laporan</button></div></div></div>
            @endif
        </div>
    @else
        <div class="card"><div class="card-body"><x-empty-state title="Pilih area kerja" message="Pilih area terlebih dahulu untuk menampilkan checklist pekerjaan." /></div></div>
    @endif

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-user-shared-line me-2 text-primary"></i>Menggantikan Petugas</h5><p class="text-muted small mb-0">Pilih petugas yang tidak masuk untuk membuka area kerjanya. Area Anda sendiri tetap tersedia di atas.</p></div>@if($substitute)<a href="{{ route('sarpras.kebersihan.report.create', ['report_date' => $today, 'area_type' => request('area_type')]) }}" class="btn btn-sm btn-outline-secondary">Batalkan penggantian</a>@endif</div>
        <div class="card-body">
            <input type="hidden" id="cleaningSubstitute" value="{{ $substitute?->id }}">
            @if($substitute)
                <div class="alert alert-primary d-flex flex-wrap justify-content-between gap-2 align-items-center"><div><i class="ri-user-star-line me-1"></i>Menggantikan <strong>{{ $substitute->name }}</strong>@if($selectedArea) · Area: {{ $selectedArea->name }}@endif</div><div class="d-flex align-items-center gap-2"><span class="small">Alasan</span><input id="cleaningAbsence" class="form-control form-control-sm" style="min-width:220px" value="{{ old('absence_reason', $report?->absence_reason) }}" placeholder="Contoh: sakit / izin"></div></div>
            @else
                <div class="alert alert-light border small"><i class="ri-information-line me-1"></i>Pilih salah satu area di bawah untuk mulai menggantikan petugas. Daftar ini hanya menampilkan petugas yang memiliki penugasan aktif.</div>
            @endif
            @if($substituteOfficers->isEmpty())
                <div class="text-muted small">Belum ada petugas lain dengan penugasan area.</div>
            @else
                <div class="row g-2">
                    @foreach($substituteOfficers as $officer)
                        @php
                            $officerAreas = $substituteAssignments->get($officer->id, collect())
                                ->map(fn ($assignment) => $assignment->area)
                                ->filter()
                                ->when(request('area_type'), fn ($collection) => $collection->where('area_type', request('area_type')))
                                ->unique('id');
                        @endphp
                        @if($officerAreas->isNotEmpty())
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="d-flex align-items-center gap-2 mb-2"><span class="avatar-xs"><span class="avatar-title rounded-circle bg-primary-subtle text-primary"><i class="ri-user-line"></i></span></span><strong>{{ $officer->name }}</strong><span class="badge bg-light text-dark border">{{ $officerAreas->count() }} area</span></div>
                                    <div class="d-flex flex-wrap gap-1">@foreach($officerAreas as $area)<a href="{{ route('sarpras.kebersihan.report.create', ['area_id' => $area->id, 'substitute_for_user_id' => $officer->id, 'report_date' => $today, 'area_type' => request('area_type')]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-map-pin-line me-1"></i>{{ $area->name }}</a>@endforeach</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="taskEvidenceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><div><h5 class="modal-title mb-0"><i class="ri-upload-cloud-2-line me-1 text-primary"></i>Isi Keterangan &amp; Bukti</h5><small class="text-muted" id="evidenceTaskTitle">Pekerjaan</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-5"><label class="form-label">Kondisi</label><select id="evidenceCondition" class="form-select">@foreach(\App\Models\CleaningReport::CONDITION_OPTIONS as $condition)<option value="{{ $condition }}">{{ ucfirst(str_replace('_',' ', $condition)) }}</option>@endforeach</select></div>
                        <div class="col-12 col-md-7"><label class="form-label">Catatan</label><textarea id="evidenceNotes" class="form-control" rows="2" placeholder="Tulis keterangan pekerjaan…"></textarea></div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Foto Sebelum</label>
                            <a id="evidenceBeforeExisting" href="#" target="_blank" class="small text-success d-none mb-1"><i class="ri-image-line me-1"></i>Foto sebelum tersimpan</a>
                            <input type="url" id="evidenceBeforeUrl" class="form-control mb-2" placeholder="Link Google Drive">
                            <input type="file" id="evidenceBeforeFile" class="form-control" accept="image/*">
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="evidenceBeforeFile" data-preview="evidenceBeforePreview"><i class="ri-camera-line me-1"></i>Kamera + watermark</button>
                            <div id="evidenceBeforePreview"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Foto Sesudah</label>
                            <a id="evidenceAfterExisting" href="#" target="_blank" class="small text-success d-none mb-1"><i class="ri-image-line me-1"></i>Foto sesudah tersimpan</a>
                            <input type="url" id="evidenceAfterUrl" class="form-control mb-2" placeholder="Link Google Drive">
                            <input type="file" id="evidenceAfterFile" class="form-control" accept="image/*">
                            <button type="button" class="btn btn-outline-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="evidenceAfterFile" data-preview="evidenceAfterPreview"><i class="ri-camera-line me-1"></i>Kamera + watermark</button>
                            <div id="evidenceAfterPreview"></div>
                        </div>
                    </div>
                    <div id="evidenceSaveStatus" class="small mt-3"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="evidenceSaveBtn"><i class="ri-save-line me-1"></i>Simpan Keterangan</button></div>
            </div>
        </div>
    </div>

    @include('sarpras.partials.camera-modal')
@endsection

@push('script')
<script>
    (function () {
        const root = document.getElementById('cleaningChecklist');
        if (!root) return;
        const locked = root.dataset.locked === '1';
        const total = {{ $tasks->count() }};
        const progressText = document.getElementById('checklistProgressText');
        const progressBar = document.getElementById('checklistProgressBar');
        const saveStatus = document.getElementById('cleaningSaveStatus');
        const finalizeError = document.getElementById('cleaningFinalizeError');
        const saveTimers = new WeakMap();
        const offlineRows = new Map();
        let evidenceRow = null;
        let tabChannel = null;
        const evidenceDrafts = new Map();

        window.addEventListener('beforeunload', function (event) {
            if (!offlineRows.size) return;
            event.preventDefault();
            event.returnValue = '';
        });
        window.addEventListener('online', function () { flushOfflineRows(); });

        try {
            tabChannel = new BroadcastChannel('sarpras-cleaning-' + root.dataset.areaId + '-' + root.dataset.reportDate);
            tabChannel.onmessage = function () {
                if (saveStatus) saveStatus.innerHTML = '<i class="ri-error-warning-line me-1"></i>Data diperbarui di tab lain. Muat ulang bila perlu.';
            };
        } catch (error) {
            tabChannel = null;
        }

        function isNetworkFailure(error) {
            return !navigator.onLine
                || error instanceof TypeError
                || /failed to fetch|networkerror|load failed/i.test(error.message || '');
        }

        function queueOfflineRow(row, overrides) {
            offlineRows.set(row.dataset.taskId, { row: row, overrides: overrides || {} });
            rowStatus(row, 'Menunggu koneksi…', 'warning');
            if (saveStatus) saveStatus.innerHTML = '<i class="ri-cloud-off-line me-1"></i>Menunggu koneksi — perubahan akan dikirim otomatis.';
        }

        async function flushOfflineRows() {
            if (!navigator.onLine || !offlineRows.size) return;
            if (saveStatus) saveStatus.innerHTML = '<i class="ri-loader-4-line me-1"></i>Menyambung kembali…';
            const entries = Array.from(offlineRows.entries());
            for (const entry of entries) {
                const queued = entry[1];
                if (!queued.row || !document.body.contains(queued.row)) {
                    offlineRows.delete(entry[0]);
                    continue;
                }
                await saveRow(queued.row, queued.overrides);
            }
        }

        function officerId() { return document.getElementById('cleaningOfficer')?.value || '{{ auth()->id() }}'; }
        function updateProgress() {
            const done = document.querySelectorAll('.task-result[value="sudah"]:checked').length;
            const percent = total ? Math.round((done / total) * 100) : 0;
            if (progressText) progressText.textContent = done + ' / ' + total + ' selesai';
            if (progressBar) progressBar.style.width = percent + '%';
        }
        function rowStatus(row, text, tone) {
            const el = row.querySelector('.task-save-status');
            if (el) { el.textContent = text; el.className = 'task-save-status small mt-2 text-' + (tone || 'muted'); }
        }
        async function saveRow(row, overrides = {}) {
            if (locked || !row) return false;
            const taskId = row.dataset.taskId;
            const result = row.querySelector('.task-result:checked')?.value || 'belum';
            const condition = overrides.condition ?? row.querySelector('.task-condition')?.value ?? 'bersih';
            const notes = overrides.notes ?? row.querySelector('.task-notes')?.value ?? '';
            const beforeUrl = overrides.beforeUrl ?? row.querySelector('.task-before-url')?.value ?? '';
            const afterUrl = overrides.afterUrl ?? row.querySelector('.task-after-url')?.value ?? '';
            const form = new FormData();
            form.append('_token', root.dataset.csrf);
            form.append('area_id', root.dataset.areaId);
            form.append('report_date', root.dataset.reportDate);
            form.append('officer_id', officerId());
            form.append('substitute_for_user_id', document.getElementById('cleaningSubstitute')?.value || '');
            form.append('absence_reason', document.getElementById('cleaningAbsence')?.value || '');
            form.append('task_id', taskId);
            form.append('result', result);
            form.append('condition', condition);
            form.append('notes', notes);
            form.append('before_url', beforeUrl);
            form.append('after_url', afterUrl);
            if (overrides.beforeFile) form.append('before_file', overrides.beforeFile);
            if (overrides.afterFile) form.append('after_file', overrides.afterFile);

            rowStatus(row, 'Menyimpan…', 'muted');
            if (saveStatus) saveStatus.innerHTML = '<i class="ri-loader-4-line me-1"></i>Menyimpan perubahan…';
            if (!navigator.onLine) {
                queueOfflineRow(row, overrides);
                return false;
            }
            try {
                const response = await fetch(root.dataset.autosaveUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
                    body: form
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal menyimpan.');
                row.querySelector('.task-condition').value = condition;
                row.querySelector('.task-notes').value = notes;
                const savedBeforeUrl = Object.prototype.hasOwnProperty.call(data, 'before_url') ? (data.before_url || '') : beforeUrl;
                const savedAfterUrl = Object.prototype.hasOwnProperty.call(data, 'after_url') ? (data.after_url || '') : afterUrl;
                row.querySelector('.task-before-url').value = savedBeforeUrl;
                row.querySelector('.task-after-url').value = savedAfterUrl;
                if (evidenceRow === row) {
                    evidenceBeforeUrl.value = savedBeforeUrl;
                    evidenceAfterUrl.value = savedAfterUrl;
                    updateExistingEvidenceLinks();
                    renderEvidencePreview(document.getElementById('evidenceBeforePreview'), savedBeforeUrl);
                    renderEvidencePreview(document.getElementById('evidenceAfterPreview'), savedAfterUrl);
                }
                // File sudah tersimpan di server; kosongkan input agar tidak
                // terunggah ulang saat tombol simpan ditekan lagi.
                if (overrides.beforeFile && evidenceBeforeFile) evidenceBeforeFile.value = '';
                if (overrides.afterFile && evidenceAfterFile) evidenceAfterFile.value = '';
                offlineRows.delete(taskId);
                rowStatus(row, '✓ Tersimpan ' + (data.saved_at || ''), 'success');
                if (saveStatus) saveStatus.innerHTML = '<i class="ri-cloud-done-line me-1"></i>Tersimpan otomatis';
                updateProgress();
                try { tabChannel?.postMessage({ taskId: taskId, at: data.saved_at || '' }); } catch (error) {}
                return true;
            } catch (error) {
                if (isNetworkFailure(error)) {
                    queueOfflineRow(row, overrides);
                    return false;
                }
                rowStatus(row, 'Gagal menyimpan: ' + error.message, 'danger');
                if (saveStatus) saveStatus.innerHTML = '<i class="ri-error-warning-line me-1"></i>Belum tersimpan';
                return false;
            }
        }
        function scheduleSave(row) {
            clearTimeout(saveTimers.get(row));
            saveTimers.set(row, setTimeout(function () { saveRow(row); }, 350));
        }

        document.querySelectorAll('.task-row').forEach(function (row) {
            row.addEventListener('change', function (event) {
                if (event.target.matches('.task-result, .task-condition, .task-file, .task-url')) saveRow(row);
            });
            row.addEventListener('input', function (event) {
                if (event.target.matches('.task-notes, .task-url')) scheduleSave(row);
            });
        });
        document.querySelectorAll('.task-result').forEach(function (input) { input.addEventListener('change', updateProgress); });
        updateProgress();

        const evidenceModalEl = document.getElementById('taskEvidenceModal');
        const evidenceCondition = document.getElementById('evidenceCondition');
        const evidenceNotes = document.getElementById('evidenceNotes');
        const evidenceBeforeUrl = document.getElementById('evidenceBeforeUrl');
        const evidenceAfterUrl = document.getElementById('evidenceAfterUrl');
        const evidenceSaveStatus = document.getElementById('evidenceSaveStatus');
        const evidenceBeforeFile = document.getElementById('evidenceBeforeFile');
        const evidenceAfterFile = document.getElementById('evidenceAfterFile');
        const evidenceBeforeExisting = document.getElementById('evidenceBeforeExisting');
        const evidenceAfterExisting = document.getElementById('evidenceAfterExisting');

        function renderEvidencePreview(container, url) {
            if (!container) return;
            container.innerHTML = '';
            if (!url) return;

            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Foto bukti tersimpan';
            image.className = 'img-fluid rounded border mt-2';
            image.style.maxHeight = '110px';
            image.addEventListener('error', function () { image.classList.add('d-none'); });
            container.appendChild(image);

            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'small text-success d-inline-block mt-1';
            link.innerHTML = '<i class="ri-external-link-line me-1"></i>Buka foto tersimpan';
            container.appendChild(link);
        }

        function updateExistingEvidenceLinks() {
            const before = evidenceBeforeUrl.value;
            const after = evidenceAfterUrl.value;
            evidenceBeforeExisting.classList.toggle('d-none', !before);
            if (before) evidenceBeforeExisting.href = before;
            evidenceAfterExisting.classList.toggle('d-none', !after);
            if (after) evidenceAfterExisting.href = after;
        }

        function setEvidenceFile(input, file) {
            if (!input) return;
            try {
                input.value = '';
                if (!file) return;
                if (typeof DataTransfer === 'undefined') return;
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
            } catch (error) {
                // Preview tetap ditampilkan meski browser menolak set file.
            }
        }

        function renderLocalFilePreview(container, file) {
            if (!container || !file) return;
            container.innerHTML = '';
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = 'Pratinjau foto';
            image.className = 'img-fluid rounded border mt-2';
            image.style.maxHeight = '110px';
            container.appendChild(image);
            const note = document.createElement('div');
            note.className = 'small text-muted mt-1';
            note.innerHTML = '<i class="ri-check-line me-1"></i>Foto siap disimpan';
            container.appendChild(note);
        }

        function saveEvidenceDraft() {
            if (!evidenceRow) return;
            // Sinkronkan isian modal ke field baris agar draft tidak hilang
            // saat modal ditutup atau berpindah pekerjaan.
            const conditionField = evidenceRow.querySelector('.task-condition');
            const notesField = evidenceRow.querySelector('.task-notes');
            const beforeUrlField = evidenceRow.querySelector('.task-before-url');
            const afterUrlField = evidenceRow.querySelector('.task-after-url');
            if (conditionField) conditionField.value = evidenceCondition.value;
            if (notesField) notesField.value = evidenceNotes.value;
            if (beforeUrlField) beforeUrlField.value = evidenceBeforeUrl.value;
            if (afterUrlField) afterUrlField.value = evidenceAfterUrl.value;

            const beforePreview = document.getElementById('evidenceBeforePreview');
            const afterPreview = document.getElementById('evidenceAfterPreview');
            evidenceDrafts.set(evidenceRow.dataset.taskId, {
                condition: evidenceCondition.value,
                notes: evidenceNotes.value,
                beforeUrl: evidenceBeforeUrl.value,
                afterUrl: evidenceAfterUrl.value,
                beforeFile: evidenceBeforeFile.files?.[0] || null,
                afterFile: evidenceAfterFile.files?.[0] || null,
                beforePreview: beforePreview ? beforePreview.innerHTML : '',
                afterPreview: afterPreview ? afterPreview.innerHTML : ''
            });
        }

        function restoreEvidenceDraft(row) {
            const draft = evidenceDrafts.get(row.dataset.taskId);
            if (!draft) {
                evidenceCondition.value = row.querySelector('.task-condition')?.value || 'bersih';
                evidenceNotes.value = row.querySelector('.task-notes')?.value || '';
                evidenceBeforeUrl.value = row.querySelector('.task-before-url')?.value || '';
                evidenceAfterUrl.value = row.querySelector('.task-after-url')?.value || '';
                setEvidenceFile(evidenceBeforeFile, null);
                setEvidenceFile(evidenceAfterFile, null);
                updateExistingEvidenceLinks();
                renderEvidencePreview(document.getElementById('evidenceBeforePreview'), evidenceBeforeUrl.value);
                renderEvidencePreview(document.getElementById('evidenceAfterPreview'), evidenceAfterUrl.value);
                return;
            }

            evidenceCondition.value = draft.condition;
            evidenceNotes.value = draft.notes;
            evidenceBeforeUrl.value = draft.beforeUrl;
            evidenceAfterUrl.value = draft.afterUrl;
            setEvidenceFile(evidenceBeforeFile, draft.beforeFile);
            setEvidenceFile(evidenceAfterFile, draft.afterFile);
            updateExistingEvidenceLinks();

            const beforePreview = document.getElementById('evidenceBeforePreview');
            const afterPreview = document.getElementById('evidenceAfterPreview');
            if (beforePreview) beforePreview.innerHTML = draft.beforePreview || '';
            if (afterPreview) afterPreview.innerHTML = draft.afterPreview || '';
            if (!draft.beforePreview && draft.beforeFile) renderLocalFilePreview(beforePreview, draft.beforeFile);
            if (!draft.afterPreview && draft.afterFile) renderLocalFilePreview(afterPreview, draft.afterFile);
            if (!draft.beforePreview && !draft.beforeFile) renderEvidencePreview(beforePreview, draft.beforeUrl);
            if (!draft.afterPreview && !draft.afterFile) renderEvidencePreview(afterPreview, draft.afterUrl);
        }

        evidenceBeforeFile.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                renderLocalFilePreview(document.getElementById('evidenceBeforePreview'), this.files[0]);
            }
        });
        evidenceAfterFile.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                renderLocalFilePreview(document.getElementById('evidenceAfterPreview'), this.files[0]);
            }
        });

        document.querySelectorAll('.evidence-trigger').forEach(function (button) {
            button.addEventListener('click', function () {
                const nextRow = button.closest('.task-row');
                if (!nextRow) return;
                if (evidenceRow && evidenceRow !== nextRow) {
                    saveEvidenceDraft();
                }
                evidenceRow = nextRow;
                document.getElementById('evidenceTaskTitle').textContent = button.dataset.taskTitle || 'Pekerjaan';
                evidenceSaveStatus.textContent = '';
                restoreEvidenceDraft(evidenceRow);
            });
        });

        evidenceModalEl.addEventListener('hidden.bs.modal', function () {
            if (!evidenceRow) return;
            saveEvidenceDraft();
            evidenceRow = null;
        });

        document.getElementById('evidenceSaveBtn')?.addEventListener('click', async function () {
            const row = evidenceRow;
            if (!row) return;
            if (!row.querySelector('.task-result:checked')) {
                evidenceSaveStatus.textContent = 'Pilih Selesai atau Belum terlebih dahulu.';
                return;
            }
            this.disabled = true;
            evidenceSaveStatus.textContent = 'Menyimpan…';
            const ok = await saveRow(row, {
                condition: evidenceCondition.value,
                notes: evidenceNotes.value,
                beforeUrl: evidenceBeforeUrl.value,
                afterUrl: evidenceAfterUrl.value,
                beforeFile: evidenceBeforeFile.files?.[0] || null,
                afterFile: evidenceAfterFile.files?.[0] || null,
            });
            this.disabled = false;
            if (ok) {
                evidenceSaveStatus.textContent = '✓ Keterangan tersimpan';
                evidenceDrafts.delete(row.dataset.taskId);
                if (evidenceRow === row) evidenceRow = null;
                bootstrap.Modal.getInstance(evidenceModalEl)?.hide();
            } else {
                evidenceSaveStatus.textContent = 'Gagal menyimpan keterangan.';
            }
        });

        const finalizeButton = document.getElementById('finalizeCleaning');
        if (finalizeButton) {
            finalizeButton.addEventListener('click', async function () {
                finalizeButton.disabled = true;
                finalizeError.classList.add('d-none');
                const form = new FormData();
                form.append('_token', root.dataset.csrf);
                form.append('area_id', root.dataset.areaId);
                form.append('report_date', root.dataset.reportDate);
                form.append('officer_id', officerId());
                form.append('substitute_for_user_id', document.getElementById('cleaningSubstitute')?.value || '');
                form.append('absence_reason', document.getElementById('cleaningAbsence')?.value || '');
                try {
                    const response = await fetch(root.dataset.finalizeUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf }, body: form });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Laporan belum lengkap.');
                    window.location.href = data.redirect;
                } catch (error) {
                    finalizeError.textContent = error.message;
                    finalizeError.classList.remove('d-none');
                    finalizeButton.disabled = false;
                }
            });
        }
    })();
</script>
@endpush

@push('css')
<style>
    .task-main { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1rem; align-items: center; }
    .task-buttons { display: flex; align-items: center; gap: .4rem; }
    .task-icon-btn { width: 38px; height: 38px; min-height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-width: 1.5px; border-radius: .6rem !important; line-height: 1; }
    .task-icon-btn i { font-size: 1.05rem; pointer-events: none; }
    .task-icon-btn:hover { color: #fff; }
    .task-buttons .btn-check:checked + .btn-outline-success { background: #198754; border-color: #198754; color: #fff; }
    .task-buttons .btn-check:checked + .btn-outline-danger { background: #dc3545; border-color: #dc3545; color: #fff; }
    .task-evidence-btn { width: auto; padding: 0 .7rem; gap: .35rem; font-size: .78rem; font-weight: 600; white-space: nowrap; background: #f2f8ff; }
    @media (max-width: 767.98px) {
        .task-main { display: block; }
        .task-buttons { display: grid; grid-template-columns: 40px 40px minmax(0, 1fr); gap: .5rem; margin-top: .7rem; align-items: stretch; }
        .task-icon-btn { width: 100%; height: 38px; min-height: 38px; }
        .task-evidence-btn { font-size: .76rem; padding: 0 .5rem; }
    }
</style>
@endpush
