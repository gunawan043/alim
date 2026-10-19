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
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ $selectedArea?->id === $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                            @endforeach
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
        <form method="POST" action="{{ route('sarpras.kebersihan.report.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="area_id" value="{{ $selectedArea->id }}">
            <input type="hidden" name="overall_condition" value="bersih">

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div><div class="text-muted small">Area Kerja</div><h5 class="mb-1">{{ $selectedArea->name }}</h5><div class="small text-muted">{{ $selectedArea->building?->building_name ?? '-' }} · {{ $tasks->count() }} item pekerjaan</div></div>
                        <span class="badge bg-info-subtle text-info align-self-start"><i class="ri-calendar-check-line me-1"></i>{{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }}</span>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Petugas Pelaksana <span class="text-danger">*</span></label>
                            @if($canManage)
                                <select name="officer_id" class="form-select" required>
                                    <option value="">— Pilih Petugas —</option>
                                    @foreach($officers as $user)<option value="{{ $user->id }}" {{ old('officer_id', auth()->id()) === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                                <input type="hidden" name="officer_id" value="{{ auth()->id() }}">
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Menggantikan Petugas</label>
                            <select name="substitute_for_user_id" class="form-select"><option value="">— Tidak menggantikan —</option>@foreach($officers as $user)<option value="{{ $user->id }}" {{ old('substitute_for_user_id') === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Alasan Menggantikan</label><input type="text" name="absence_reason" class="form-control" value="{{ old('absence_reason') }}" placeholder="Contoh: petugas sakit"></div>
                    </div>
                </div>
            </div>

            @php
                $groupedTasks = $tasks->groupBy(fn ($task) => $task->group_label ?: 'Pekerjaan');
                $taskNumber = 0;
            @endphp

            @forelse($groupedTasks as $groupLabel => $groupTasks)
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ri-folder-line me-1 text-primary"></i>{{ $groupLabel }}</h5></div>
                    <div class="card-body">
                        @foreach($groupTasks as $task)
                            @php
                                $taskNumber++;
                                $selected = old('items.'.$task->id.'.result', 'sudah');
                            @endphp
                            <div class="border rounded-3 p-3 mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div><div class="fw-semibold">{{ $taskNumber }}. {{ $task->title }}</div><div class="small text-muted">{{ $task->standard ?? 'Sesuai standar kebersihan' }}</div></div>
                                    <span class="badge bg-light text-dark border text-nowrap">{{ ucfirst($task->frequency) }}</span>
                                </div>

                                <div class="btn-group w-100 mt-3" role="group" aria-label="Status pekerjaan">
                                    <input type="radio" class="btn-check" name="items[{{ $task->id }}][result]" id="selesai_{{ $task->id }}" value="sudah" {{ $selected === 'sudah' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-success py-3 fw-semibold" for="selesai_{{ $task->id }}"><i class="ri-check-line me-1"></i>Selesai</label>
                                    <input type="radio" class="btn-check" name="items[{{ $task->id }}][result]" id="belum_{{ $task->id }}" value="belum" {{ $selected === 'belum' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-danger py-3 fw-semibold" for="belum_{{ $task->id }}"><i class="ri-close-line me-1"></i>Belum</label>
                                    <input type="radio" class="btn-check" name="items[{{ $task->id }}][result]" id="na_{{ $task->id }}" value="tidak_berlaku" {{ $selected === 'tidak_berlaku' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary py-3" for="na_{{ $task->id }}">N/A</label>
                                </div>

                                <details class="mt-2">
                                    <summary class="small text-primary" style="cursor:pointer"><i class="ri-add-circle-line me-1"></i>Detail, catatan &amp; foto bukti</summary>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4"><label class="form-label small">Kondisi</label><select name="items[{{ $task->id }}][condition]" class="form-select form-select-sm">@foreach(\App\Models\CleaningReport::CONDITION_OPTIONS as $condition)<option value="{{ $condition }}" {{ old('items.'.$task->id.'.condition', 'bersih') === $condition ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $condition)) }}</option>@endforeach</select></div>
                                        <div class="col-md-8"><label class="form-label small">Catatan</label><input name="items[{{ $task->id }}][notes]" class="form-control form-control-sm" value="{{ old('items.'.$task->id.'.notes') }}" placeholder="Keterangan bila perlu"></div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Foto Sebelum</label>
                                            <input type="url" name="items[{{ $task->id }}][before_url]" class="form-control form-control-sm mb-1" value="{{ old('items.'.$task->id.'.before_url') }}" placeholder="Link Google Drive">
                                            <input type="file" id="before_file_{{ $task->id }}" name="before_files[{{ $task->id }}]" class="form-control form-control-sm" accept="image/*">
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="before_file_{{ $task->id }}" data-preview="before_preview_{{ $task->id }}"><i class="ri-camera-line me-1"></i>Kamera + watermark</button>
                                            <div id="before_preview_{{ $task->id }}"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Foto Sesudah</label>
                                            <input type="url" name="items[{{ $task->id }}][after_url]" class="form-control form-control-sm mb-1" value="{{ old('items.'.$task->id.'.after_url') }}" placeholder="Link Google Drive">
                                            <input type="file" id="after_file_{{ $task->id }}" name="after_files[{{ $task->id }}]" class="form-control form-control-sm" accept="image/*">
                                            <button type="button" class="btn btn-outline-success btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="after_file_{{ $task->id }}" data-preview="after_preview_{{ $task->id }}"><i class="ri-camera-line me-1"></i>Kamera + watermark</button>
                                            <div id="after_preview_{{ $task->id }}"></div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body"><x-empty-state title="Belum ada pekerjaan" message="Area ini belum memiliki uraian pekerjaan. Hubungi koordinator." /></div></div>
            @endforelse

            @if($tasks->isNotEmpty())
                <div class="card mb-3" style="position:sticky;bottom:0;z-index:5;box-shadow:0 -8px 24px rgba(0,0,0,.08)"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2"><div class="small text-muted"><i class="ri-information-line me-1"></i>Tekan Selesai/Belum pada setiap pekerjaan, lalu simpan.</div><div class="d-flex gap-2"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-light">Batal</a><button class="btn btn-success px-4"><i class="ri-save-line me-1"></i>Simpan Checklist</button></div></div></div>
            @endif
        </form>
    @else
        <div class="card"><div class="card-body"><x-empty-state title="Pilih area kerja" message="Pilih area terlebih dahulu untuk menampilkan checklist pekerjaan." /></div></div>
    @endif

    @include('sarpras.partials.camera-modal')
@endsection
