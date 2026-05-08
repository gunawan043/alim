@extends('layouts.master')
@section('title') Tambah Tugas Tambahan GTK @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">Tugas Tambahan GTK</a> @endslot
        @slot('title') Tambah Tugas @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.gtk-additional-tasks.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Form Tugas Tambahan GTK</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Guru <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">— Pilih Guru —</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}" {{ old('user_id') == $t->id ? 'selected' : '' }}>
                                            {{ $t->name }} ({{ $t->getRoleNames()->first() ?? 'GTK' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SK Referensi</label>
                                <select name="decree_id" class="form-control">
                                    <option value="">— Tidak ada SK —</option>
                                    @foreach($decrees as $d)
                                        <option value="{{ $d->id }}" {{ old('decree_id') == $d->id ? 'selected' : '' }}>
                                            {{ $d->decree_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nama Tugas <span class="text-danger">*</span></label>
                                <input type="text" name="nama_tugas" class="form-control" value="{{ old('nama_tugas') }}"
                                       required placeholder="Contoh: Wali Kelas VII A" list="default-tasks">
                                <datalist id="default-tasks">
                                    @foreach($defaultTasks as $dt)
                                        <option value="{{ $dt }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jam per Minggu (JP)</label>
                                <input type="number" name="hours_per_week" class="form-control"
                                       value="{{ old('hours_per_week') }}" min="0" max="40" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nomor SK</label>
                                <input type="text" name="nomor_sk" class="form-control"
                                       value="{{ old('nomor_sk') }}" placeholder="Opsional">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">TMT (Mulai)</label>
                                <input type="date" name="tmt" class="form-control" value="{{ old('tmt') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">TST (Selesai)</label>
                                <input type="date" name="tst" class="form-control" value="{{ old('tst') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Contoh Tugas Tambahan</h6></div>
                    <div class="card-body p-2">
                        <ul class="list-unstyled mb-0 small">
                            @foreach($defaultTasks as $dt)
                                <li class="py-1 px-2">
                                    <i class="ri-checkbox-blank-circle-fill text-success me-2" style="font-size:6px;vertical-align:middle;"></i>
                                    {{ $dt }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
