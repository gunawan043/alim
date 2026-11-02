@extends('layouts.master')
@section('title', 'Pengaturan Notifikasi Kembali — '.($dormitory->name ?? 'Asrama'))
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Asrama</a> @endslot
        @slot('li_2') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Izin &amp; Kunjungan</a> @endslot
        @slot('title') Pengaturan Notifikasi Kembali @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('user.asrama.notifications.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">
                            <i class="ri-notification-3-line me-1 text-primary"></i>
                            Pengingat Santri Belum Kembali — {{ $dormitory->name }}
                        </h5>
                        <p class="text-muted mb-0 small">Berlaku untuk izin pulang santri pada asrama ini.</p>
                    </div>
                    <div class="card-body">
                        {{-- Status --}}
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="is_enabled" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_enabled" value="1"
                                   id="is_enabled" {{ old('is_enabled', $setting->is_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_enabled">Aktifkan pengingat otomatis</label>
                            <div class="form-text">Sistem memeriksa izin yang melewati batas kembali setiap menit.</div>
                        </div>

                        <hr>

                        {{-- Interval & jumlah --}}
                        <h6 class="text-uppercase text-muted fs-11 mb-3">Jadwal Pengingat</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="interval_minutes" class="form-label">Kirim pengingat setiap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="interval_minutes" id="interval_minutes"
                                           class="form-control @error('interval_minutes') is-invalid @enderror"
                                           value="{{ old('interval_minutes', $setting->interval_minutes) }}" min="5" max="1440" required>
                                    <span class="input-group-text">menit</span>
                                </div>
                                <div class="form-text">Diulang selama santri belum kembali.</div>
                                @error('interval_minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="max_reminders" class="form-label">Maksimal pengingat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="max_reminders" id="max_reminders"
                                           class="form-control @error('max_reminders') is-invalid @enderror"
                                           value="{{ old('max_reminders', $setting->max_reminders) }}" min="1" max="20" required>
                                    <span class="input-group-text">kali</span>
                                </div>
                                @error('max_reminders')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="escalate_after_minutes" class="form-label">Eskalasi ke petugas setelah</label>
                                <div class="input-group">
                                    <input type="number" name="escalate_after_minutes" id="escalate_after_minutes"
                                           class="form-control @error('escalate_after_minutes') is-invalid @enderror"
                                           value="{{ old('escalate_after_minutes', $setting->escalate_after_minutes) }}" min="15" max="10080" required>
                                    <span class="input-group-text">menit</span>
                                </div>
                                <div class="form-text">Dihitung dari batas waktu kembali.</div>
                                @error('escalate_after_minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <hr>

                        {{-- Pengingat H-1 --}}
                        <h6 class="text-uppercase text-muted fs-11 mb-3">Pengingat Sebelum Batas Kembali (H-1)</h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="h1_reminder_enabled" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="h1_reminder_enabled" value="1"
                                           id="h1_reminder_enabled" {{ old('h1_reminder_enabled', $setting->h1_reminder_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="h1_reminder_enabled">
                                        Kirim pengingat sehari sebelum batas kembali
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="h1_reminder_time" class="form-label">Jam kirim <span class="text-danger">*</span></label>
                                <input type="time" name="h1_reminder_time" id="h1_reminder_time"
                                       class="form-control @error('h1_reminder_time') is-invalid @enderror"
                                       value="{{ old('h1_reminder_time', $setting->h1_reminder_time) }}" required>
                                @error('h1_reminder_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <hr>

                        {{-- Kanal --}}
                        <h6 class="text-uppercase text-muted fs-11 mb-3">Platform Notifikasi <span class="text-danger">*</span></h6>
                        <div class="row g-2">
                            @foreach($channelLabels as $key => $label)
                                <div class="col-md-6">
                                    <div class="form-check border rounded px-4 py-2">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="{{ $key }}"
                                               id="channel_{{ $key }}"
                                               {{ in_array($key, old('channels', $setting->enabledChannels()), true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="channel_{{ $key }}">{{ $label }}</label>
                                        <div class="form-text mb-0">
                                            @switch($key)
                                                @case('whatsapp') Pesan WhatsApp ke nomor wali santri (mahrom utama). @break
                                                @case('push') Notifikasi aplikasi mobile wali yang sudah terhubung. @break
                                                @case('email') Email wali yang tercatat pada data santri. @break
                                                @case('in_app') Tersimpan di portal/pusat notifikasi wali. @break
                                            @endswitch
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('channels')<div class="text-danger small mt-2">{{ $message }}</div>@enderror

                        <hr>

                        {{-- Petugas --}}
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="notify_staff" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="notify_staff" value="1"
                                   id="notify_staff" {{ old('notify_staff', $setting->notify_staff) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_staff">Beritahu petugas asrama saat eskalasi</label>
                        </div>

                        <hr>

                        {{-- Template --}}
                        <h6 class="text-uppercase text-muted fs-11 mb-3">Template Pesan (Opsional)</h6>
                        <textarea name="message_template" id="message_template" rows="4"
                                  class="form-control @error('message_template') is-invalid @enderror"
                                  placeholder="Biarkan kosong untuk memakai pesan bawaan.">{{ old('message_template', $setting->message_template) }}</textarea>
                        <div class="form-text">
                            Placeholder tersedia: <code>{santri}</code>, <code>{wali}</code>, <code>{estimasi}</code>, <code>{asrama}</code>.
                        </div>
                        @error('message_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-information-line me-1 text-info"></i> Cara Kerja</h6></div>
                <div class="card-body small text-muted">
                    <ol class="ps-3 mb-3">
                        <li class="mb-2">Saat kartu izin/kunjungan dibuat, <strong>tanggal &amp; jam kembali wajib diisi</strong>.</li>
                        <li class="mb-2">Begitu batas kembali terlewati, wali menerima pengingat pertama.</li>
                        <li class="mb-2">Pengingat diulang setiap <strong>{{ $setting->interval_minutes }} menit</strong> hingga maksimal <strong>{{ $setting->max_reminders }} kali</strong>.</li>
                        <li class="mb-2">Setelah <strong>{{ $setting->escalate_after_minutes }} menit</strong> dari batas kembali, izin ditandai <em>overdue</em>{{ $setting->notify_staff ? ' dan petugas asrama diberi tahu' : '' }}.</li>
                        <li>Pengingat berhenti otomatis saat santri discan kembali.</li>
                    </ol>
                    <div class="alert alert-warning mb-0 py-2">
                        <i class="ri-server-line me-1"></i>
                        Pengingat berjalan melalui scheduler Laravel (<code>schedule:run</code>) yang harus aktif setiap menit di server.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
