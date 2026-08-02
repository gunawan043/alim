@extends('layouts.master')

@section('title', 'Konfigurasi Izin — ' . ucfirst(str_replace('_', ' ', $permitType)))

@section('content')
<div class="container-fluid">

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('li_4') <a href="{{ route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Konfigurasi Kuota</a> @endslot
        @slot('title') {{ ucfirst(str_replace('_', ' ', $permitType)) }} @endslot
    @endcomponent

    <form method="POST" action="{{ route('user.asrama.leave-policies.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
        @csrf
        <input type="hidden" name="permit_type" value="{{ $permitType }}" />

        {{-- Row 1: Status & Approval --}}
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="card-title mb-0 fw-semibold text-primary"><i class="ri-settings-3-line me-2"></i>Status & Approval</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_enabled" name="is_enabled" value="1" {{ old('is_enabled', $policy->is_enabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_enabled">
                                Aktifkan jenis izin ini
                                <small class="d-block text-muted">Jika tidak dicentang, jenis izin ini disembunyikan dari pilihan pengajuan.</small>
                            </label>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="requires_approval" name="requires_approval" value="1" {{ old('requires_approval', $policy->requires_approval ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="requires_approval">
                                Memerlukan persetujuan
                                <small class="d-block text-muted">Jika dimatikan, izin otomatis disetujui saat diajukan (kecuali auto-approve rules di bawah).</small>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Kuota per Periode --}}
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="card-title mb-0 fw-semibold text-primary"><i class="ri-calendar-check-line me-2"></i>Kuota per Periode</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Kosongkan untuk tanpa batas. Periode lebih spesifik (mingguan) menimpa periode lebih umum (bulanan) bila keduanya diisi.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="quota_per_week" class="form-label">Per Minggu</label>
                                <input type="number" name="quota_per_week" id="quota_per_week" min="0" max="9999"
                                       value="{{ old('quota_per_week', $policy->quota_per_week ?? '') }}"
                                       class="form-control" placeholder="Kosongkan = tanpa batas">
                            </div>
                            <div class="col-md-6">
                                <label for="quota_per_month" class="form-label">Per Bulan</label>
                                <input type="number" name="quota_per_month" id="quota_per_month" min="0" max="9999"
                                       value="{{ old('quota_per_month', $policy->quota_per_month ?? '') }}"
                                       class="form-control" placeholder="Kosongkan = tanpa batas">
                            </div>
                            <div class="col-md-6">
                                <label for="quota_per_semester" class="form-label">Per Semester</label>
                                <input type="number" name="quota_per_semester" id="quota_per_semester" min="0" max="9999"
                                       value="{{ old('quota_per_semester', $policy->quota_per_semester ?? '') }}"
                                       class="form-control" placeholder="Kosongkan = tanpa batas">
                            </div>
                            <div class="col-md-6">
                                <label for="quota_per_year" class="form-label">Per Tahun Ajaran</label>
                                <input type="number" name="quota_per_year" id="quota_per_year" min="0" max="9999"
                                       value="{{ old('quota_per_year', $policy->quota_per_year ?? '') }}"
                                       class="form-control" placeholder="Kosongkan = tanpa batas">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2.5: Kuota Pulang (hanya untuk permit_type = pulang) --}}
                @if ($permitType === 'pulang')
                <div class="card mt-4 border-primary">
                    <div class="card-header bg-primary-subtle text-primary">
                        <h4 class="card-title mb-0"><i class="ri-home-heart-line me-2"></i>Kuota Pulang</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Atur batas izin <strong>pulang</strong> dan tentukan apakah izin khusus (keluar kota, berobat, dll.) ikut dihitung ke dalam kuota yang sama.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pulang_quota" class="form-label">Batas Kuota Pulang</label>
                                <input type="number" name="pulang_quota" id="pulang_quota" min="0" max="9999"
                                       value="{{ old('pulang_quota', $policy->pulang_quota ?? '') }}"
                                       class="form-control" placeholder="Kosongkan = tanpa batas">
                                <small class="text-muted">Maks pengajuan pulang per periode di bawah.</small>
                            </div>
                            <div class="col-md-6">
                                <label for="pulang_quota_period" class="form-label">Periode</label>
                                <select name="pulang_quota_period" id="pulang_quota_period" class="form-select">
                                    <option value="">— Pilih periode —</option>
                                    <option value="monthly" {{ old('pulang_quota_period', $policy->pulang_quota_period ?? '') === 'monthly' ? 'selected' : '' }}>Per Bulan</option>
                                    <option value="quarterly" {{ old('pulang_quota_period', $policy->pulang_quota_period ?? '') === 'quarterly' ? 'selected' : '' }}>Per Quarter (3 Bulan)</option>
                                    <option value="semester" {{ old('pulang_quota_period', $policy->pulang_quota_period ?? '') === 'semester' ? 'selected' : '' }}>Per Semester</option>
                                    <option value="yearly" {{ old('pulang_quota_period', $policy->pulang_quota_period ?? '') === 'yearly' ? 'selected' : '' }}>Per Tahun Ajaran</option>
                                </select>
                                <small class="text-muted">Wajib diisi jika batas kuota diisi.</small>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Row 2.6: Mode Kuota Izin Khusus (untuk keluar_kota, berobat, sakit, keperluan_keluarga, lainnya) --}}
                @php
                    $specialTypes = ['keluar_kota', 'berobat', 'sakit', 'keperluan_keluarga', 'lainnya'];
                @endphp
                @if (in_array($permitType, $specialTypes, true))
                <div class="card mt-4 border-info">
                    <div class="card-header bg-info-subtle text-info">
                        <h4 class="card-title mb-0"><i class="ri-shield-keyhole-line me-2"></i>Pengaturan Kuota Izin Khusus</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Tentukan apakah izin khusus ini dibatasi oleh kuota atau digabung dengan kuota izin pulang.</p>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mode Kuota</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="special_quota_mode" id="sqm_none" value="none" {{ old('special_quota_mode', $policy->special_quota_mode ?? 'none') === 'none' ? 'checked' : '' }}>
                                <label class="form-check-label" for="sqm_none">
                                    <strong>Tanpa Batas</strong>
                                    <small class="d-block text-muted">Izin khusus tidak memiliki kuota sendiri (default).</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="special_quota_mode" id="sqm_shared" value="shared_with_pulang" {{ old('special_quota_mode', $policy->special_quota_mode ?? '') === 'shared_with_pulang' ? 'checked' : '' }}>
                                <label class="form-check-label" for="sqm_shared">
                                    <strong>Hitung Bersama Kuota Pulang</strong>
                                    <small class="d-block text-muted">Pengajuan izin khusus dihitung sebagai bagian dari kuota izin pulang (mis. total pulang + keluar kota = X per bulan).</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="special_quota_mode" id="sqm_own" value="own_quota" {{ old('special_quota_mode', $policy->special_quota_mode ?? '') === 'own_quota' ? 'checked' : '' }}>
                                <label class="form-check-label" for="sqm_own">
                                    <strong>Pakai Kuota Sendiri (di Bagian "Kuota per Periode" di Atas)</strong>
                                    <small class="d-block text-muted">Izin khusus punya kuota sendiri sesuai field mingguan/bulanan/semester/tahunan.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Row 3: Auto-approve --}}
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="card-title mb-0"><i class="ri-checkbox-circle-line me-2"></i>Auto-Approve (Lewati Approval)</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Pengajuan langsung berstatus approved ketika diajukan oleh peran tertentu.</p>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="auto_approve_gtk" name="auto_approve_gtk" value="1" {{ old('auto_approve_gtk', $policy->auto_approve_gtk ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_approve_gtk">
                                Auto-approve GTK
                                <small class="d-block text-muted">Pengajuan oleh GTK langsung disetujui (mis. guru pengasuh yang mendampingi izin berobat).</small>
                            </label>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="auto_approve_kepala_asrama" name="auto_approve_kepala_asrama" value="1" {{ old('auto_approve_kepala_asrama', $policy->auto_approve_kepala_asrama ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_approve_kepala_asrama">
                                Auto-approve Kepala Asrama
                                <small class="d-block text-muted">Pengajuan oleh kepala asrama langsung disetujui.</small>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Row 4: Emergency-specific (only when permit_type = darurat) --}}
                @if ($permitType === 'darurat')
                <div class="card mt-4 border-danger">
                    <div class="card-header bg-danger-subtle text-danger">
                        <h4 class="card-title mb-0"><i class="ri-alarm-warning-line me-2"></i>Pengaturan Darurat</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="emergency_bypass_quota" name="emergency_bypass_quota" value="1" {{ old('emergency_bypass_quota', $policy->emergency_bypass_quota ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="emergency_bypass_quota">
                                Bypass Kuota
                                <small class="d-block text-muted">Izin darurat tidak menghitung kuota walau kuota jenis izin lain sudah habis.</small>
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="emergency_notify_wa_kepala" name="emergency_notify_wa_kepala" value="1" {{ old('emergency_notify_wa_kepala', $policy->emergency_notify_wa_kepala ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="emergency_notify_wa_kepala">
                                Notifikasi WhatsApp ke Kepala Asrama
                                <small class="d-block text-muted">Kirim WA otomatis setiap ada pengajuan darurat.</small>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="emergency_approver_roles_string" class="form-label fw-semibold">Peran yang Berwenang Menyetujui</label>
                            <input type="text" name="emergency_approver_roles_string" id="emergency_approver_roles_string"
                                   value="{{ old('emergency_approver_roles_string', is_array($policy->emergency_approver_roles ?? null) ? implode(', ', $policy->emergency_approver_roles) : 'kepala_asrama, admin_asrama') }}"
                                   placeholder="kepala_asrama, admin_asrama"
                                   class="form-control">
                            <small class="text-muted">Pisahkan dengan koma. Contoh: kepala_asrama, admin_asrama</small>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="d-flex justify-content-end gap-3 mt-4 mb-5">
                    <a href="{{ route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-secondary">
                        <i class="ri-arrow-go-back-line me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Simpan Konfigurasi
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
