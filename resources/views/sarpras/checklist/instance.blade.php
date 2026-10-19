@extends('layouts.master')
@section('title') Checklist — {{ $instance->template?->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.checklist.index') }}">Checklist</a> @endslot
        @slot('title') {{ $instance->template?->name }}
    @endcomponent

    @php
        $tone = ['in_progress'=>'warning','completed'=>'success','failed'=>'danger','cancelled'=>'secondary'][$instance->status] ?? 'secondary';
        $statusLabel = ['in_progress'=>'Berjalan','completed'=>'Selesai','failed'=>'Ada Temuan','cancelled'=>'Dibatalkan'][$instance->status] ?? ucfirst($instance->status);
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap"><h4 class="mb-0">{{ $instance->template?->name }}</h4><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $statusLabel }}</span></div>
                    <div class="text-muted small mt-1">
                        @if($context instanceof \App\Models\AssetMaintenanceSchedule)
                            <a href="{{ route('sarpras.pemeliharaan.schedule.show', ['id' => $context->id]) }}">{{ $context->maintenance_type }}</a>
                            · {{ $context->next_maintenance_date?->format('d/m/Y') }}
                        @else
                            {{ class_basename($instance->context_type) }}
                        @endif
                        · Pelaksana: {{ $instance->executor?->name ?? '-' }}
                        @if($contextAsset)
                            · <span class="badge bg-primary-subtle text-primary"><i class="ri-qr-code-line me-1"></i>{{ $contextAsset->asset_code }}</span>
                            <span class="text-muted">{{ $contextAsset->asset_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="text-end"><div class="small text-muted">Progres</div><div class="fs-5 fw-bold" id="checklistProgressCount">{{ $progress['answered'] }} / {{ $progress['total'] }}</div><div class="small text-{{ $tone }}" id="checklistProgressFailed">{{ $progress['failed'] }} temuan</div></div>
            </div>
            <div class="progress mt-3" style="height:8px"><div class="progress-bar bg-{{ $tone }}" id="checklistProgressBar" style="width:{{ $progress['percent_complete'] }}%"></div></div>
            @if($frequencyProgress->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @foreach($frequencyProgress as $frequency)
                        <span class="badge bg-light text-dark border">{{ $frequency['label'] }}: {{ $frequency['answered'] }}/{{ $frequency['total'] }}@if($frequency['failed'] > 0) · {{ $frequency['failed'] }} temuan @endif</span>
                    @endforeach
                </div>
            @endif
            @if($instance->status === 'failed')<div class="alert alert-danger mt-3 mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2"><span><i class="ri-error-warning-line me-1"></i>Checklist menemukan penyimpangan. Tindak lanjuti item bertanda X.</span><a href="{{ route('sarpras.repair.index') }}" class="btn btn-sm btn-outline-danger">Antrean Perbaikan</a></div>@endif
        </div>
    </div>

    <form id="checklistInstanceForm" method="POST" action="{{ route('sarpras.checklist.save', ['instanceId' => $instance->id]) }}" @if($canEdit) data-autosave-url="{{ route('sarpras.checklist.autosave', ['instanceId' => $instance->id]) }}" @endif>
        @csrf
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-list-check-2 me-1"></i>Item Pemeriksaan</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light"><tr><th style="width:45px">No</th><th>Item Pemeriksaan</th><th>Standar</th><th>Metode</th><th>Frekuensi</th><th class="text-center" style="width:130px">Hasil</th><th style="width:22%">Catatan</th></tr></thead>
                        <tbody>
                        @php
                            $frequencyGroups = [
                                'Harian' => ['Harian'],
                                'Pekanan' => ['Mingguan', '2 minggu'],
                                'Bulanan' => ['Bulanan', 'Per 3 bulan', 'Per 6 bulan', 'Tahunan'],
                            ];
                            $allItems = $instance->template?->items ?? collect();
                            $groupedItems = [];
                            foreach ($frequencyGroups as $groupLabel => $frequencies) {
                                $subset = $allItems->filter(fn ($item) => in_array($item->frequency, $frequencies, true));
                                if ($subset->isNotEmpty()) {
                                    $groupedItems[$groupLabel] = $subset;
                                }
                            }
                            $knownFrequencies = collect($frequencyGroups)->flatten()->all();
                            $otherItems = $allItems->filter(fn ($item) => ! in_array($item->frequency, $knownFrequencies, true));
                            if ($otherItems->isNotEmpty()) {
                                $groupedItems['Lainnya'] = $otherItems;
                            }
                            $counter = 0;
                        @endphp
                        @forelse($groupedItems as $frequencyLabel => $frequencyItems)
                            <tr class="table-primary"><td colspan="7" class="fw-bold"><i class="ri-calendar-check-line me-1"></i>Pemeriksaan {{ $frequencyLabel }}</td></tr>
                            @php $lastGroup = null; @endphp
                            @foreach($frequencyItems as $item)
                                @php
                                    $response = $responses[$item->id] ?? null;
                                    $passed = $response?->passed;
                                    $checkedV = $response !== null && $passed === true;
                                    $checkedX = $response !== null && $passed === false;
                                    $counter++;
                                @endphp
                                @if($item->group_label && $item->group_label !== $lastGroup)
                                    <tr class="table-secondary"><td colspan="7" class="fw-semibold">{{ $item->group_label }}</td></tr>
                                    @php $lastGroup = $item->group_label; @endphp
                                @endif
                                <tr class="{{ $response && !$passed ? 'table-danger' : '' }}">
                                    <td class="text-center">{{ $counter }}</td>
                                    <td><div class="fw-medium">{{ $item->label }}</div>@if($item->description)<small class="text-muted">{{ $item->description }}</small>@endif</td>
                                    <td class="small">{{ $item->standard ?? '-' }}</td>
                                    <td class="small">{{ $item->method ?? '-' }}</td>
                                    <td class="small"><span class="badge bg-light text-dark border">{{ $item->frequency ?? '-' }}</span></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="answers[{{ $item->id }}]" id="v{{ $item->id }}" value="1" {{ $checkedV ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                            <label class="btn btn-outline-success" for="v{{ $item->id }}"><i class="ri-check-line me-1"></i>Sesuai</label>
                                            <input type="radio" class="btn-check" name="answers[{{ $item->id }}]" id="x{{ $item->id }}" value="0" {{ $checkedX ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                            <label class="btn btn-outline-danger" for="x{{ $item->id }}"><i class="ri-close-line me-1"></i>Tidak</label>
                                        </div>
                                    </td>
                                    <td><input type="text" name="notes[{{ $item->id }}]" class="form-control form-control-sm" value="{{ $response?->notes }}" placeholder="Keterangan…" {{ $canEdit ? '' : 'disabled' }}></td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada item checklist.</td></tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>
            </div>
            @if($canEdit)
                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-muted"><span id="checklistAutoSaveStatus">Perubahan tersimpan otomatis.</span><div>Wajib diisi semua sebelum menyelesaikan checklist.</div></div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary"><i class="ri-save-line me-1"></i>Simpan Progres</button>
                        <button class="btn btn-success" formaction="{{ route('sarpras.checklist.complete', ['instanceId' => $instance->id]) }}"><i class="ri-check-double-line me-1"></i>Selesaikan Checklist</button>
                    </div>
                </div>
            @endif
        </div>
    </form>
@endsection

@push('script')
<script>
    (function () {
        const form = document.getElementById('checklistInstanceForm');
        if (!form || !form.dataset.autosaveUrl) return;

        const token = form.querySelector('input[name="_token"]')?.value || '';
        const statusEl = document.getElementById('checklistAutoSaveStatus');
        const countEl = document.getElementById('checklistProgressCount');
        const failedEl = document.getElementById('checklistProgressFailed');
        const barEl = document.getElementById('checklistProgressBar');
        const noteTimers = new Map();
        const saveQueues = new Map();
        let saveSequence = 0;

        function setStatus(text, tone) {
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.className = 'small text-' + (tone || 'muted');
        }

        function itemIdFromName(name) {
            const match = String(name || '').match(/\[(.+)\]$/);
            return match ? match[1] : null;
        }

        function notesFor(itemId) {
            return form.querySelector('input[name="notes[' + itemId + ']"]')?.value || '';
        }

        function updateProgress(progress) {
            if (!progress) return;
            if (countEl) countEl.textContent = progress.answered + ' / ' + progress.total;
            if (failedEl) failedEl.textContent = progress.failed + ' temuan';
            if (barEl) barEl.style.width = progress.percent_complete + '%';
        }

        async function performSave(body, sequence) {
            try {
                const response = await fetch(form.dataset.autosaveUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: body
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal menyimpan.');
                if (sequence === saveSequence) {
                    setStatus('✓ Tersimpan ' + (data.saved_at || ''), 'success');
                    updateProgress(data.progress);
                }
            } catch (error) {
                if (sequence === saveSequence) {
                    setStatus('Gagal menyimpan: ' + error.message, 'danger');
                }
            }
        }

        function saveItem(itemId, value) {
            const sequence = ++saveSequence;
            setStatus('Menyimpan…', 'muted');
            const body = new FormData();
            body.append('_token', token);
            body.append('template_item_id', itemId);
            body.append('value', value);
            body.append('notes', notesFor(itemId));

            // Urutkan request per item agar catatan lama tidak menimpa
            // catatan terbaru saat pengguna mengetik cepat.
            const previous = saveQueues.get(itemId) || Promise.resolve();
            const task = previous.catch(() => {}).then(function () {
                return performSave(body, sequence);
            });
            saveQueues.set(itemId, task);
            return task;
        }

        form.querySelectorAll('input[type="radio"][name^="answers["]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (!this.checked) return;
                const itemId = itemIdFromName(this.name);
                if (itemId) saveItem(itemId, this.value);
            });
        });

        form.querySelectorAll('input[name^="notes["]').forEach(function (input) {
            input.addEventListener('input', function () {
                const itemId = itemIdFromName(this.name);
                if (!itemId) return;
                clearTimeout(noteTimers.get(itemId));
                noteTimers.set(itemId, setTimeout(function () {
                    const checked = form.querySelector('input[name="answers[' + itemId + ']"]:checked');
                    if (!checked) {
                        setStatus('Pilih Sesuai atau Tidak agar catatan tersimpan otomatis.', 'muted');
                        return;
                    }
                    saveItem(itemId, checked.value);
                }, 650));
            });
        });
    })();
</script>
@endpush
