@menuallowed('tugas-staf-penitipan-barang')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: STAF PENITIPAN BARANG (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Modul penitipan barang belum memiliki halaman khusus. Sidebar tetap
     ditampilkan agar tugas ini tidak "hilang", dengan tautan ke modul
     aset personal yang boleh dibuka semua GTK.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $titipUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Staf Penitipan Barang</span></li>

<li class="nav-item">
    <span class="nav-link text-muted px-3" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Pencatatan barang titipan santri. Modul khusus sedang disiapkan.
    </span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.']) ? ' active' : '' }}"
       href="{{ $titipUrl('sarpras.user.dashboard') }}">
        <i class="ri-archive-2-line"></i><span>Aset &amp; Barang Saya</span>
    </a>
</li>
@endmenuallowed
