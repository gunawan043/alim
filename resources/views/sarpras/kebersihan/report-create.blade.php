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

    @if($selectedArea)
        <div id="cleaningChecklist" data-area-id="{{ $selectedArea->id }}" data-report-date="{{ $today }}" data-autosave-url="{{ route('sarpras.kebersihan.report.autosave') }}" data-finalize-url="{{ route('sarpras.kebersihan.report.finalize') }}" data-csrf="{{ csrf_token() }}" data-locked="{{ $isLocked ? '1' : '0' }}">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div><div class="text-muted small">Area Kerja</div><h5 class="mb-1">{{ $selectedArea->name }}</h5><div class="small text-muted">{{ $selectedArea->building?->building_name ?? '-' }} · {{ $tasks->count() }} item pekerjaan</div></div>
                        <div class="d-flex flex-column align-items-end gap-1"><span class="badge bg-info-subtle text-info"><i class="ri-calendar-check-line me-1"></i>{{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }}</span>@if($isLocked)<span class="badge bg-success-subtle text-success">Laporan sudah dikirim</span>@endif</div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Petugas Pelaksana <span class="text-danger">*</span></label>
                            @if($canManage)
                                <select id="cleaningOfficer" class="form-select" {{ $isLocked ? 'disabled' : '' }}>
                                    <option value="">— Pilih Petugas —</option>
                                    @foreach($officers as $user)<option value="{{ $user->id }}" {{ old('officer_id', $report?->officer_id ?? auth()->id()) === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                                <input type="hidden" id="cleaningOfficer" value="{{ auth()->id() }}">
                            @endif
                        </div>
                        <div class="col-md-4"><label class="form-label">Menggantikan Petugas</label><select id="cleaningSubstitute" class="form-select" {{ $isLocked ? 'disabled' : '' }}><option value="">— Tidak menggantikan —</option>@foreach($officers as $user)<option value="{{ $user->id }}" {{ old('substitute_for_user_id', $report?->substitute_for_user_id) === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Alasan Menggantikan</label><input id="cleaningAbsence" class="form-control" value="{{ old('absence_reason', $report?->absence_reason) }}" placeholder="Contoh: petugas sakit" {{ $isLocked ? 'disabled' : '' }}></div>
                    </div>
                    <div class="mt-3"><div class="d-flex justify-content-between small text-muted mb-1"><span>Progres pekerjaan</span><span id="checklistProgressText">0 / {{ $tasks->count() }} selesai</span></div><div class="progress" style="height:7px"><div class="progress-bar bg-success" id="checklistProgressBar" style="width:0%"></div></div></div>
                    <div class="d-flex justify-content-between mt-2"><small id="cleaningSaveStatus" class="text-muted"><i class="ri-cloud-line me-1"></i>Tersimpan otomatis</small><small class="text-muted">Perubahan langsung disimpan</small></div>
                </div>
            </div>

            @php
                $groupedTasks = $tasks->groupBy(fn ($task) => $task->group_label ?: 'Pekerjaan');
                $taskNumber = 0;
            @endphp

            @if($groupedTasks->isNotEmpty())
                <div class="accordion" id="taskGroups">
                    @foreach($groupedTasks as $groupLabel => $groupTasks)
                        <div class="accordion-item mb-2 border rounded-3 overflow-hidden">
                            <h2 class="accordion-header"><button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} py-3" type="button" data-bs-toggle="collapse" data-bs-target="#taskGroup{{ $loop->index }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}"><span class="fw-semibold">{{ $groupLabel }}</span><span class="badge bg-primary-subtle text-primary ms-2">{{ $groupTasks->count() }} item</span></button></h2>
                            <div id="taskGroup{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#taskGroups">
                                <div class="accordion-body p-0">
                                    @foreach($groupTasks as $task)
                                        @php
                                            $taskNumber++;
                                            $item = $reportItems->get($task->id);
                                            $selected = old('items.'.$task->id.'.result', $item?->result ?? 'belum');
                                            $beforeUrl = $item?->before_photo_url ?: ($item?->before_photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($item->before_photo_path) : null);
                                            $afterUrl = $item?->after_photo_url ?: ($item?->after_photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($item->after_photo_path) : null);
                                        @endphp
                                        <div class="p-3 border-bottom task-row" data-task-id="{{ $task->id }}">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div><div class="fw-semibold">{{ $taskNumber }}. {{ $task->title }}</div><div class="small text-muted">{{ $task->standard ?? 'Sesuai standar kebersihan' }}</div></div>
                                                <span class="badge bg-light text-dark border text-nowrap">{{ ucfirst($task->frequency) }}</span>
                                            </div>
                                            <div class="btn-group w-100 mt-2" role="group" aria-label="Status pekerjaan">
                                                <input type="radio" class="btn-check task-result" name="result_{{ $task->id }}" id="selesai_{{ $task->id }}" value="sudah" {{ $selected === 'sudah' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-success py-2 fw-semibold" for="selesai_{{ $task->id }}"><i class="ri-check-line me-1"></i>Selesai</label>
                                                <input type="radio" class="btn-check task-result" name="result_{{ $task->id }}" id="belum_{{ $task->id }}" value="belum" {{ $selected === 'belum' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-danger py-2 fw-semibold" for="belum_{{ $task->id }}"><i class="ri-close-line me-1"></i>Belum</label>
                                            </div>
                                            <button class="btn btn-link btn-sm px-0 mt-2 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#taskDetail{{ $task->id }}"><i class="ri-edit-2-line me-1"></i>Catatan / foto bukti</button>
                                            <div class="collapse" id="taskDetail{{ $task->id }}">
                                                <div class="row g-2 mt-1">
                                                    <div class="col-md-4"><label class="form-label small">Kondisi</label><select class="form-select form-select-sm task-condition" {{ $isLocked ? 'disabled' : '' }}>@foreach(\App\Models\CleaningReport::CONDITION_OPTIONS as $condition)<option value="{{ $condition }}" {{ old('items.'.$task->id.'.condition', $item?->condition ?? 'bersih') === $condition ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $condition)) }}</option>@endforeach</select></div>
                                                    <div class="col-md-8"><label class="form-label small">Catatan</label><input class="form-control form-control-sm task-notes" value="{{ old('items.'.$task->id.'.notes', $item?->notes) }}" placeholder="Keterangan bila perlu" {{ $isLocked ? 'disabled' : '' }}></div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small">Foto Sebelum</label>
                                                        <input type="url" class="form-control form-control-sm mb-1 task-url task-before-url" value="{{ old('items.'.$task->id.'.before_url', $item?->before_photo_url) }}" placeholder="Link Google Drive" {{ $isLocked ? 'disabled' : '' }}>
                                                        @if($beforeUrl)<a href="{{ $beforeUrl }}" target="_blank" class="small text-success d-block mb-1"><i class="ri-image-line me-1"></i>Foto sebelum tersimpan</a>@endif
                                                        <input type="file" id="before_file_{{ $task->id }}" class="form-control form-control-sm task-file task-before-file" accept="image/*" {{ $isLocked ? 'disabled' : '' }}>
                                                        <button type="button" class="btn btn-outline-primary btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="before_file_{{ $task->id }}" data-preview="before_preview_{{ $task->id }}" {{ $isLocked ? 'disabled' : '' }}><i class="ri-camera-line me-1"></i>Kamera + watermark</button>
                                                        <div id="before_preview_{{ $task->id }}"></div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small">Foto Sesudah</label>
                                                        <input type="url" class="form-control form-control-sm mb-1 task-url task-after-url" value="{{ old('items.'.$task->id.'.after_url', $item?->after_photo_url) }}" placeholder="Link Google Drive" {{ $isLocked ? 'disabled' : '' }}>
                                                        @if($afterUrl)<a href="{{ $afterUrl }}" target="_blank" class="small text-success d-block mb-1"><i class="ri-image-line me-1"></i>Foto sesudah tersimpan</a>@endif
                                                        <input type="file" id="after_file_{{ $task->id }}" class="form-control form-control-sm task-file task-after-file" accept="image/*" {{ $isLocked ? 'disabled' : '' }}>
                                                        <button type="button" class="btn btn-outline-success btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="after_file_{{ $task->id }}" data-preview="after_preview_{{ $task->id }}" {{ $isLocked ? 'disabled' : '' }}><i class="ri-camera-line me-1"></i>Kamera + watermark</button>
                                                        <div id="after_preview_{{ $task->id }}"></div>
                                                    </div>
                                                </div>
                                            </div>
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
        async function saveRow(row) {
            if (locked) return;
            const taskId = row.dataset.taskId;
            const result = row.querySelector('.task-result:checked')?.value || 'belum';
            const form = new FormData();
            form.append('_token', root.dataset.csrf);
            form.append('area_id', root.dataset.areaId);
            form.append('report_date', root.dataset.reportDate);
            form.append('officer_id', officerId());
            form.append('task_id', taskId);
            form.append('result', result);
            form.append('condition', row.querySelector('.task-condition')?.value || 'bersih');
            form.append('notes', row.querySelector('.task-notes')?.value || '');
            form.append('before_url', row.querySelector('.task-before-url')?.value || '');
            form.append('after_url', row.querySelector('.task-after-url')?.value || '');
            const beforeFile = row.querySelector('.task-before-file')?.files?.[0];
            const afterFile = row.querySelector('.task-after-file')?.files?.[0];
            if (beforeFile) form.append('before_file', beforeFile);
            if (afterFile) form.append('after_file', afterFile);

            rowStatus(row, 'Menyimpan…', 'muted');
            if (saveStatus) saveStatus.innerHTML = '<i class="ri-loader-4-line me-1"></i>Menyimpan perubahan…';
            try {
                const response = await fetch(root.dataset.autosaveUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
                    body: form
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal menyimpan.');
                rowStatus(row, '✓ Tersimpan ' + (data.saved_at || ''), 'success');
                if (saveStatus) saveStatus.innerHTML = '<i class="ri-cloud-done-line me-1"></i>Tersimpan otomatis';
                updateProgress();
            } catch (error) {
                rowStatus(row, 'Gagal menyimpan: ' + error.message, 'danger');
                if (saveStatus) saveStatus.innerHTML = '<i class="ri-error-warning-line me-1"></i>Belum tersimpan';
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
