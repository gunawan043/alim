@extends('layouts.master')
@section('title', 'Edit Draft AI')
@section('css')
    @include('kurikulum._styles')
@endsection
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $isRpm = $record->type === 'rpm';
        $tujuanText = implode("\n", (array) ($content['tujuan'] ?? []));
        $mediaText = implode("\n", (array) ($content['media'] ?? []));
        $kbc = is_array($content['kbc'] ?? null) ? $content['kbc'] : [];
        $kbcPilar = is_array($kbc['pilar'] ?? null) ? implode("\n", $kbc['pilar']) : (string) ($kbc['pilar'] ?? '');
        $aktivitasJson = json_encode($content['aktivitas'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $langkahJson = json_encode($content['langkah'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.ai.show', ['userId' => $userId, 'id' => $record->id]) }}">Draft AI</a> @endslot
        @slot('title') Edit Draft AI @endslot
    @endcomponent

    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa draft.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-1"><i class="ri-edit-2-line me-1 text-primary"></i>Edit Draft {{ $isRpm ? ($labels['perangkat_short'] ?? 'RPM') : 'Projek' }}</h5>
                    <p class="text-muted mb-0 small">{{ $record->title }} · {{ $profile?->name ?? 'Profil default' }}</p>
                </div>
                <a href="{{ route('user.kurikulum.ai.show', ['userId' => $userId, 'id' => $record->id]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('user.kurikulum.ai.update', ['userId' => $userId, 'id' => $record->id]) }}">
        @csrf @method('PUT')
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Informasi Utama</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label">Judul <span class="text-danger">*</span></label><input name="judul" class="form-control" required maxlength="255" value="{{ old('judul', $content['judul'] ?? $record->title) }}"></div>
                    @if($isRpm)
                        <div class="col-md-4"><label class="form-label">Capaian</label><input name="capaian" class="form-control" maxlength="5000" value="{{ old('capaian', $content['capaian'] ?? '') }}"></div>
                        <div class="col-md-6"><label class="form-label">Tujuan Pembelajaran <span class="text-muted small">(satu per baris)</span></label><textarea name="tujuan_text" rows="4" class="form-control">{{ old('tujuan_text', $tujuanText) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Media <span class="text-muted small">(satu per baris)</span></label><textarea name="media_text" rows="4" class="form-control">{{ old('media_text', $mediaText) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Asesmen Formatif</label><textarea name="asesmen_formatif" rows="3" class="form-control">{{ old('asesmen_formatif', data_get($content, 'asesmen.formatif')) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Asesmen Sumatif</label><textarea name="asesmen_sumatif" rows="3" class="form-control">{{ old('asesmen_sumatif', data_get($content, 'asesmen.sumatif')) }}</textarea></div>
                        <div class="col-md-12"><label class="form-label">Refleksi</label><textarea name="refleksi" rows="3" class="form-control">{{ old('refleksi', $content['refleksi'] ?? '') }}</textarea></div>
                        <div class="col-12"><label class="form-label">Langkah Pembelajaran <span class="text-muted small">(JSON array)</span></label><textarea name="langkah_json" rows="8" class="form-control font-monospace small">{{ old('langkah_json', $langkahJson) }}</textarea><div class="form-text">Format: array objek dengan <code>pertemuan</code>, <code>pembuka</code>, <code>inti</code>, dan <code>penutup</code>.</div></div>
                    @else
                        <div class="col-md-4"><label class="form-label">Tema</label><input name="tema" class="form-control" maxlength="500" value="{{ old('tema', $content['tema'] ?? '') }}"></div>
                        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" rows="3" class="form-control">{{ old('deskripsi', $content['deskripsi'] ?? '') }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">{{ $labels['values_label'] ?? 'Dimensi / Nilai' }}</label><div class="border rounded p-2">@foreach($values as $key => $label)<div class="form-check"><input class="form-check-input" type="checkbox" name="dimensions[]" value="{{ $key }}" id="dim-{{ $key }}" @checked(in_array($key, (array) old('dimensions', $content['dimensi'] ?? []), true))><label class="form-check-label" for="dim-{{ $key }}">{{ $label }}</label></div>@endforeach</div></div>
                        <div class="col-md-6"><label class="form-label">Aktivitas <span class="text-muted small">(JSON array)</span></label><textarea name="activities_json" rows="8" class="form-control font-monospace small">{{ old('activities_json', $aktivitasJson) }}</textarea></div>
                    @endif
                </div>
            </div>
        </div>

        @if($isRpm && ($profile?->isKemenag() ?? false))
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-heart-3-line text-danger me-1"></i>Integrasi KBC</h5></div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Tema KBC</label><input name="kbc_tema" class="form-control" value="{{ old('kbc_tema', $kbc['tema'] ?? '') }}" maxlength="500"></div>
                    <div class="col-md-6"><label class="form-label">Pilar <span class="text-muted small">(satu per baris)</span></label><textarea name="kbc_pilar" rows="2" class="form-control">{{ old('kbc_pilar', $kbcPilar) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Materi Insersi</label><textarea name="kbc_materi" rows="2" class="form-control">{{ old('kbc_materi', $kbc['materi_insersi'] ?? '') }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Indikator</label><textarea name="kbc_indikator" rows="2" class="form-control">{{ old('kbc_indikator', $kbc['indikator'] ?? '') }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Refleksi</label><textarea name="kbc_refleksi" rows="2" class="form-control">{{ old('kbc_refleksi', $kbc['refleksi'] ?? '') }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Asesmen</label><textarea name="kbc_asesmen" rows="2" class="form-control">{{ old('kbc_asesmen', $kbc['asesmen'] ?? '') }}</textarea></div>
                </div></div>
            </div>
        @endif

        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Perubahan Draft</button>
            <a href="{{ route('user.kurikulum.ai.show', ['userId' => $userId, 'id' => $record->id]) }}" class="btn btn-light">Batal</a>
        </div>
    </form>
@endsection
