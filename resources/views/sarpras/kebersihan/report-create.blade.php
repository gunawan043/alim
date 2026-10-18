@extends('layouts.master')
@section('title') Laporan Kebersihan Harian @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.kebersihan.index') }}">Kebersihan</a> @endslot
        @slot('title') Laporan Harian
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.kebersihan.report.create') }}" class="row g-2 align-items-end">@if(request('area_type'))<input type="hidden" name="area_type" value="{{ request('area_type') }}">@endif
                <div class="col-md-5"><label class="form-label small fw-semibold">Area Kerja</label><select name="area_id" class="form-select" required><option value="">— Pilih Area —</option>@foreach($areas as $area)<option value="{{ $area->id }}" {{ $selectedArea?->id === $area->id ? 'selected' : '' }}>{{ $area->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="report_date" class="form-control" value="{{ $today }}"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-eye-line me-1"></i>Tampilkan</button></div>
                <div class="col-md-2"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-light w-100">Batal</a></div>
            </form>
        </div>
    </div>

    @if($selectedArea)
        <form method="POST" action="{{ route('sarpras.kebersihan.report.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="area_id" value="{{ $selectedArea->id }}">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-check-line me-1"></i>{{ $selectedArea->name }}</h5><div class="text-muted small">{{ $selectedArea->building?->building_name ?? '-' }} · {{ $selectedArea->tasks->count() }} item pekerjaan</div></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Tanggal Laporan <span class="text-danger">*</span></label><input type="date" name="report_date" class="form-control" value="{{ old('report_date', $today) }}" required></div>
                        <div class="col-md-4"><label class="form-label">Petugas Pelaksana <span class="text-danger">*</span></label>@if($canManage)<select name="officer_id" class="form-select" required><option value="">— Pilih —</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ old('officer_id', auth()->id()) === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select>@else<input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly><input type="hidden" name="officer_id" value="{{ auth()->id() }}">@endif</div>
                        <div class="col-md-4"><label class="form-label">Menggantikan Petugas</label><select name="substitute_for_user_id" class="form-select"><option value="">— Tidak menggantikan —</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ old('substitute_for_user_id') === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></div>
                        <div class="col-12"><label class="form-label">Alasan Menggantikan</label><input type="text" name="absence_reason" class="form-control" value="{{ old('absence_reason') }}" placeholder="Contoh: petugas sakit / izin"></div>
                        <div class="col-md-4"><label class="form-label">Kondisi Keseluruhan <span class="text-danger">*</span></label><select name="overall_condition" class="form-select" required>@foreach(App\Models\CleaningReport::CONDITION_OPTIONS as $condition)<option value="{{ $condition }}" {{ old('overall_condition') === $condition ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $condition)) }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Skor (0–100)</label><input type="number" name="score" class="form-control" min="0" max="100" value="{{ old('score') }}"></div>
                        <div class="col-md-4"><label class="form-label">Catatan</label><input type="text" name="notes" class="form-control" value="{{ old('notes') }}"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-list-check-2 me-1"></i>Checklist Pekerjaan</h5><p class="text-muted small mb-0">Isi status, kondisi, catatan, serta foto/link Google Drive sebelum dan sesudah.</p></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead class="table-light"><tr><th style="width:40px">No</th><th>Uraian Pekerjaan</th><th style="width:150px">Hasil</th><th style="width:170px">Kondisi</th><th style="width:190px">Catatan</th><th style="width:190px">Foto Sebelum</th><th style="width:190px">Foto Sesudah</th></tr></thead><tbody>
                    @forelse($tasks as $index => $task)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td><div class="fw-medium">{{ $task->title }}</div><small class="text-muted">{{ $task->standard }}</small></td>
                            <td><select name="items[{{ $task->id }}][result]" class="form-select form-select-sm" required>@foreach(App\Models\CleaningReportItem::RESULT_OPTIONS as $result)<option value="{{ $result }}" {{ old('items.'.$task->id.'.result', 'sudah') === $result ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $result)) }}</option>@endforeach</select></td>
                            <td><select name="items[{{ $task->id }}][condition]" class="form-select form-select-sm">@foreach(App\Models\CleaningReport::CONDITION_OPTIONS as $condition)<option value="{{ $condition }}" {{ old('items.'.$task->id.'.condition', 'bersih') === $condition ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $condition)) }}</option>@endforeach</select></td>
                            <td><input name="items[{{ $task->id }}][notes]" class="form-control form-control-sm" value="{{ old('items.'.$task->id.'.notes') }}" placeholder="Keterangan"></td>
                            <td>
                                <input type="url" name="items[{{ $task->id }}][before_url]" class="form-control form-control-sm mb-1" value="{{ old('items.'.$task->id.'.before_url') }}" placeholder="Link Google Drive">
                                <input type="file" id="before_file_{{ $task->id }}" name="before_files[{{ $task->id }}]" class="form-control form-control-sm" accept="image/*">
                                <button type="button" class="btn btn-outline-primary btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="before_file_{{ $task->id }}" data-preview="before_preview_{{ $task->id }}"><i class="ri-camera-line me-1"></i>Kamera + watermark</button>
                                <div id="before_preview_{{ $task->id }}"></div>
                            </td>
                            <td>
                                <input type="url" name="items[{{ $task->id }}][after_url]" class="form-control form-control-sm mb-1" value="{{ old('items.'.$task->id.'.after_url') }}" placeholder="Link Google Drive">
                                <input type="file" id="after_file_{{ $task->id }}" name="after_files[{{ $task->id }}]" class="form-control form-control-sm" accept="image/*">
                                <button type="button" class="btn btn-outline-success btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="after_file_{{ $task->id }}" data-preview="after_preview_{{ $task->id }}"><i class="ri-camera-line me-1"></i>Kamera + watermark</button>
                                <div id="after_preview_{{ $task->id }}"></div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">Area ini belum memiliki uraian pekerjaan.</td></tr>
                    @endforelse
                </tbody></table></div></div>
                @if($tasks->isNotEmpty())<div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-light">Batal</a><button class="btn btn-success"><i class="ri-send-plane-line me-1"></i>Kirim Laporan</button></div>@endif
            </div>
        </form>
    @else
        <div class="card"><div class="card-body"><x-empty-state title="Pilih area kerja" message="Pilih area terlebih dahulu untuk menampilkan checklist kebersihan." /></div></div>
    @endif

    @include('sarpras.partials.camera-modal')
@endsection
