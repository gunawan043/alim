@extends('layouts.master')

@section('title', 'Lapor Insiden')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2')
            <a href="{{ route('user.keamanan.incidents.index', ['userId' => $userId]) }}">Insiden Keamanan</a>
        @endslot
        @slot('title') Lapor Insiden @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-alert-line me-1 text-danger"></i> Formulir Laporan Insiden</h5>
                </div>
                <form method="POST" action="{{ route('user.keamanan.incidents.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenis Insiden</label>
                                <select name="incident_type_id" class="form-select">
                                    <option value="">— Pilih jenis —</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}" {{ old('incident_type_id') === $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tingkat Keparahan <span class="text-danger">*</span></label>
                                <select name="severity" class="form-select" required>
                                    @foreach(\App\Models\Security\SecurityIncident::SEVERITY_OPTIONS as $value => $label)
                                        <option value="{{ $value }}" {{ old('severity', 'sedang') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Judul Insiden <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                                       placeholder="Contoh: Orang tidak dikenal masuk area asrama" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Waktu Kejadian <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="occurred_at" class="form-control"
                                       value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Zona Area</label>
                                <select name="zone_id" class="form-select">
                                    <option value="">— Tidak spesifik —</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ old('zone_id') === $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pos Jaga</label>
                                <select name="post_id" class="form-select">
                                    <option value="">— Otomatis —</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}" {{ old('post_id') === $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Uraian Kejadian <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="5" required
                                          placeholder="Kronologi, siapa yang terlibat, tindakan awal yang dilakukan...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <a href="{{ route('user.keamanan.incidents.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-danger ms-auto">
                            <i class="ri-send-plane-line me-1"></i> Simpan Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
