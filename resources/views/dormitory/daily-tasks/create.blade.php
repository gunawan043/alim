@extends('layouts.master')
@section('title', 'Buat Tugas Harian')
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.daily-tasks.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Tugas Harian</a> @endslot
        @slot('title') Buat Tugas Harian @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa input:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="mb-0"><i class="ri-add-circle-line me-1 text-primary"></i>Tugas Harian Baru</h5>
            <small class="text-muted">Tugas akan muncul setiap hari pada shift yang dipilih.</small>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('user.asrama.daily-tasks.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nama Tugas <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" maxlength="191" required
                               placeholder="Contoh: Cek kebersihan kamar dan kunci pintu">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Shift <span class="text-danger">*</span></label>
                        <select name="shift" class="form-select @error('shift') is-invalid @enderror" required>
                            @foreach($shifts as $value => $shift)
                                <option value="{{ $value }}" @selected(old('shift', $defaultShift) === $value)>
                                    {{ $shift['label'] }} ({{ $shift['time'] }})
                                </option>
                            @endforeach
                        </select>
                        @error('shift')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keterangan / Standar Tugas</label>
                        <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Jelaskan apa yang harus dicek atau dilakukan...">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Urutan Tampil</label>
                        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', 0) }}" min="0" max="9999">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   id="task-active" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="task-active">Aktifkan tugas ini</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('user.asrama.daily-tasks.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Batal</a>
                    <button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Tugas</button>
                </div>
            </form>
        </div>
    </div>
@endsection
