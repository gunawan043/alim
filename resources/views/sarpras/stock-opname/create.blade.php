@extends('layouts.master')
@section('title') Buat Stock Opname @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.stock-opname.index') }}">Stock Opname</a> @endslot
        @slot('title') Buat Sesi
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('sarpras.stock-opname.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ri-clipboard-line me-2 text-primary"></i>Rencana Stock Opname</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Satuan Kerja <span class="text-danger">*</span></label><select name="work_unit_id" id="workUnitSelect" class="form-select" required><option value="">— Pilih —</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" {{ old('work_unit_id') === $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Tanggal Opname <span class="text-danger">*</span></label><input type="date" name="scheduled_date" class="form-control" value="{{ old('scheduled_date', date('Y-m-d')) }}" required></div>
                            <div class="col-12"><label class="form-label">Judul Sesi <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" value="{{ old('title', 'Stock Opname '.now()->format('F Y')) }}" required></div>
                            <div class="col-md-6"><label class="form-label">Ruang (opsional)</label><select name="room_id" class="form-select"><option value="">Seluruh ruang unit</option>@foreach($rooms as $room)<option value="{{ $room->id }}" data-work-unit="{{ $room->work_unit_id }}" {{ old('room_id') === $room->id ? 'selected' : '' }}>{{ $room->building?->building_name ? $room->building->building_name.' — ' : '' }}{{ $room->room_name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Kategori (opsional)</label><select name="category_id" class="form-select"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ old('category_id') === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</select></div>
                            <div class="col-12"><label class="form-label">Petugas Opname <span class="text-danger">*</span></label><select name="officers[]" class="form-select" multiple size="8" required>@foreach($users as $user)<option value="{{ $user->id }}" {{ in_array($user->id, old('officers', [auth()->id()]), true) ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select><small class="text-muted">Pilih petugas URT dan/atau GTK satuan kerja. Tahan Ctrl/Cmd untuk memilih beberapa.</small></div>
                            <div class="col-12"><label class="form-label">Catatan</label><textarea name="description" class="form-control" rows="3" placeholder="Tujuan, jadwal, atau instruksi opname…">{{ old('description') }}</textarea></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary-subtle"><h5 class="card-title mb-0 text-primary"><i class="ri-information-line me-1"></i>Alur</h5></div>
                    <div class="card-body">
                        <ol class="small text-muted ps-3 mb-0">
                            <li class="mb-2">Sesi dibuat dan aset unit otomatis dimuat.</li>
                            <li class="mb-2">Petugas memindai QR unit aset atau mencari kode aset.</li>
                            <li class="mb-2">Temuan dicatat: ditemukan, hilang, rusak, atau pindah ruang.</li>
                            <li class="mb-2">URT dan unit menutup sesi bersama.</li>
                            <li>Selisih menjadi dasar tindak lanjut perbaikan/penghapusan.</li>
                        </ol>
                    </div>
                </div>
                <div class="d-grid gap-2 mt-3"><button class="btn btn-success"><i class="ri-save-line me-1"></i>Buat Sesi</button><a href="{{ route('sarpras.stock-opname.index') }}" class="btn btn-light">Batal</a></div>
            </div>
        </div>
    </form>
@endsection
