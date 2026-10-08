{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: ADMIN UKS (Shared Template)
     ───────────────────────────────────────────────────────────────────────────
     Dipakai oleh 2 tugas tambahan:
       - Admin UKS Putra
       - Admin UKS Putri
     ───────────────────────────────────────────────────────────────────────────
     Wajib passing parameter:
       $sektor      — 'Putra' atau 'Putri'
       $sektorSlug  — 'putra' atau 'putri'
     ───────────────────────────────────────────────────────────────────────────
     Scope: santri + GTK (sesuai sektor gender)
     Fokus: input rekam medis, log obat, stok obat
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;
    $sektor      = $sektor      ?? 'Putra';
    $sektorSlug  = $sektorSlug  ?? 'putra';

    $adminUksUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Admin UKS {{ $sektor }}</span></li>

{{-- Info sektor --}}
<li class="nav-item">
    <span class="nav-link text-muted" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Sektor: <strong>UKS {{ $sektor }}</strong>
    </span>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     REKAM MEDIS — SANTRI & GTK
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Rekam Medis</span></li>

{{-- 1. Rekam Medis Santri (sektor) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.student-health.']) ? ' active' : '' }}"
       href="{{ $adminUksUrl('user.uks.student-health.index') }}">
        <i class="ri-user-heart-line"></i><span>Rekam Medis Santri {{ $sektor }}</span>
    </a>
</li>

{{-- 2. Rekam Medis GTK (sektor) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.gtk-health.']) ? ' active' : '' }}"
       href="{{ $adminUksUrl('user.uks.gtk-health.index') }}">
        <i class="ri-heart-2-line"></i><span>Rekam Medis GTK {{ $sektor }}</span>
    </a>
</li>

{{-- 3. Pemeriksaan Kesehatan (input) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.health-checkups.create', 'user.uks.health-checkups.index']) ? ' active' : '' }}"
       href="{{ $adminUksUrl('user.uks.health-checkups.index') }}">
        <i class="ri-stethoscope-line"></i><span>Pemeriksaan Kesehatan</span>
    </a>
</li>

{{-- 4. Antropometri (input) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.health-metrics.create', 'user.uks.health-metrics.index']) ? ' active' : '' }}"
       href="{{ $adminUksUrl('user.uks.health-metrics.index') }}">
        <i class="ri-scales-3-line"></i><span>Data Antropometri</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     OBAT & LOG
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Obat & Log</span></li>

{{-- 5. Log Pemberian Obat --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.medicine-logs.']) ? ' active' : '' }}"
       href="{{ $adminUksUrl('user.uks.medicine-logs.index') }}">
        <i class="ri-file-list-2-line"></i><span>Log Pemberian Obat</span>
    </a>
</li>

{{-- 6. Stok Obat (input) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.medicine-inventory.']) ? ' active' : '' }}"
       href="{{ $adminUksUrl('user.uks.medicine-inventory.index') }}">
        <i class="ri-capsule-line"></i><span>Stok Obat</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     IZIN SAKIT & IMUNISASI
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Izin Sakit & Imunisasi</span></li>

{{-- 7. Izin Sakit --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.health-permits.']) ? ' active' : '' }}"
       href="{{ $adminUksUrl('user.uks.health-permits.index') }}">
        <i class="ri-file-shield-2-line"></i><span>Izin Sakit</span>
    </a>
</li>

{{-- 8. Imunisasi --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.immunizations.']) ? ' active' : '' }}"
       href="{{ $adminUksUrl('user.uks.immunizations.index') }}">
        <i class="ri-syringe-line"></i><span>Imunisasi</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     ASURANSI / BPJS
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Asuransi</span></li>

{{-- 9. Asuransi / BPJS (Soon) --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-shield-check-line"></i>
        <span>Asuransi / BPJS <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- 10. Notifikasi Wali Santri (Soon) --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-mail-send-line"></i>
        <span>Notifikasi Wali <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>