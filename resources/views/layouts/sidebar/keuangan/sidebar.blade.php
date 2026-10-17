{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KEUANGAN
     ───────────────────────────────────────────────────────────────────────────
     Role    : Keuangan
     Jabatan : 3 —
       Kepala Departemen Keuangan
       Bendahara Penerimaan (Kasir/SPP)
       Akuntan / Staf Pembukuan
     ───────────────────────────────────────────────────────────────────────────
     CATATAN:
       • Modul tagihan/kasir/akuntansi ditangani APLIKASI TERPISAH
       • Sidebar ini hanya menampilkan modul yang ADA di aplikasi ini
       • Fokus: data referensi santri/GTK + payroll + laporan
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $keuanganUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaKeuangan = $hasJabatan('Kepala Departemen Keuangan')
                     || $hasJabatan('Kepala Keuangan');
    $isBendahara      = $hasJabatan('Bendahara Penerimaan')
                     || $hasJabatan('Bendahara Sekolah')
                     || $hasJabatan('Bendahara');
    $isAkuntan        = $hasJabatan('Akuntan')
                     || $hasJabatan('Staf Pembukuan')
                     || $hasJabatan('Staf Keuangan');

    $isStruktural     = $isKepalaKeuangan;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 1 — UTAMA
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Utama</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i><span>Beranda</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}"
       href="{{ $keuanganUrl('user.profile.my') }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ $keuanganUrl('user.notifications.index') }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ $keuanganUrl('user.todos.index') }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>

@if($showAll || $isStruktural)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ $keuanganUrl('user.approvals.index') }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — DASHBOARD
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard']) ? ' active' : '' }}"
       href="{{ $keuanganUrl('user.dashboard') }}">
        <i class="ri-money-dollar-circle-line"></i><span>Dashboard</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — DATA SANTRI & MUTASI (READ-ONLY)
     Alur integrasi keuangan ke data santri (aplikasi terpisah):
       - Lookup santri untuk validasi tagihan
       - Cek status aktif vs mutasi (Lulus/DO/Pindah)
       - Rekonsiliasi piutang santri
     Akses: semua jabatan Keuangan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeuangan || $isBendahara || $isAkuntan) && menu_allowed('keuangan'))
    <li class="menu-title"><span>Data Santri & Mutasi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.students.index') }}">
            <i class="ri-user-star-line"></i><span>Master Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.mahroms.']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.students.mahroms.global') }}">
            <i class="ri-parent-line"></i><span>Data Wali Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-lulus.', 'user.mutations-do.']) ? ' active' : '' }}"
           href="#keuangan_mutasi" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-lulus.', 'user.mutations-do.']) ? 'true' : 'false' }}"
           aria-controls="keuangan_mutasi">
            <i class="ri-arrow-left-right-line"></i><span>Riwayat Mutasi</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-lulus.', 'user.mutations-do.']) ? ' show' : '' }}"
             id="keuangan_mutasi">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-in.']) ? ' active' : '' }}"
                       href="{{ $keuanganUrl('user.mutations-in.index') }}">
                        Santri Masuk / Pindahan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-out.']) ? ' active' : '' }}"
                       href="{{ $keuanganUrl('user.mutations-out.index') }}">
                        Santri Keluar / Pindah
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-lulus.']) ? ' active' : '' }}"
                       href="{{ $keuanganUrl('user.mutations-lulus.index') }}">
                        Santri Lulus
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-do.']) ? ' active' : '' }}"
                       href="{{ $keuanganUrl('user.mutations-do.index') }}">
                        Santri Drop Out
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.alumni.']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.alumni.index') }}">
            <i class="ri-graduation-cap-line"></i><span>Data Alumni</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — PAYROLL GTK
     Akses: Kepala & Akuntan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeuangan || $isAkuntan) && menu_allowed('keuangan'))
    <li class="menu-title"><span>Payroll GTK</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.index']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.payroll.index') }}">
            <i class="ri-wallet-3-line"></i><span>Daftar Payroll</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.tunjangan']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.payroll.tunjangan') }}">
            <i class="ri-add-circle-line"></i><span>Tunjangan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.potongan']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.payroll.potongan') }}">
            <i class="ri-subtract-line"></i><span>Potongan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.bpjstk']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.payroll.bpjstk') }}">
            <i class="ri-shield-check-line"></i><span>BPJS TK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.bpjs-kes']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.payroll.bpjs-kes') }}">
            <i class="ri-heart-pulse-line"></i><span>BPJS Kesehatan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll-slip.index', 'user.payroll-slip.show']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.payroll-slip.index') }}">
            <i class="ri-receipt-line"></i><span>Slip Gaji</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.settings']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.payroll.settings') }}">
            <i class="ri-settings-4-line"></i><span>Pengaturan Payroll</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — KESEJAHTERAAN GTK (lihat saja)
     Akses: Kepala & Akuntan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeuangan || $isAkuntan) && menu_allowed('keuangan'))
    <li class="menu-title"><span>Kesejahteraan GTK</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kesejahteraan.index', 'user.kesejahteraan.asuransi', 'user.kesejahteraan.benefit']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.kesejahteraan.index') }}">
            <i class="ri-hand-heart-line"></i><span>Kesejahteraan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kesejahteraan.klaim']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.kesejahteraan.klaim') }}">
            <i class="ri-file-shield-2-line"></i><span>Klaim Asuransi</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — LAPORAN
     Akses: semua jabatan Keuangan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeuangan || $isBendahara || $isAkuntan) && menu_allowed('keuangan-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.keuangan']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.laporan.keuangan') }}">
            <i class="ri-file-chart-2-line"></i><span>Laporan Keuangan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.index']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.laporan.index') }}">
            <i class="ri-file-list-2-line"></i><span>Semua Laporan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.gtk']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.laporan.gtk') }}">
            <i class="ri-team-line"></i><span>Laporan GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.laporan.santri') }}">
            <i class="ri-user-star-line"></i><span>Laporan Santri</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — REFERENSI
     Akses: semua jabatan Keuangan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeuangan || $isBendahara || $isAkuntan) && menu_allowed('keuangan-pelengkap'))
    <li class="menu-title"><span>Referensi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.institution-decrees.']) ? ' active' : '' }}"
           href="{{ $keuanganUrl('user.institution-decrees.index') }}">
            <i class="ri-file-paper-2-line"></i><span>SK & Keputusan</span>
        </a>
    </li>
@endif