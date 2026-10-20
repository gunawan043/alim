@php
    $showAll = $isSystemAdmin ?? false;
    $pustakaUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
    $isKoordinator = $hasJabatan('Koordinator Perpustakaan') || $hasJabatan('Kepala Perpustakaan');
    $isStaf = $hasJabatan('Staf Perpustakaan');
    $isPustaka = $showAll || $isKoordinator || $isStaf;
@endphp

<li class="menu-title"><span>Utama</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}" href="{{ route('root') }}">
        <i class="ri-home-6-line"></i><span>Beranda</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}" href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}" href="{{ route('user.notifications.index', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}" href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>
@if($showAll || $isKoordinator)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}" href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
            <i class="ri-check-double-line"></i><span>Approval Center</span>
        </a>
    </li>
@endif

@if($isPustaka && menu_allowed('perpustakaan'))
    <li class="menu-title"><span>Perpustakaan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.index']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.index') }}">
            <i class="ri-dashboard-3-line"></i><span>Dashboard</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.catalog.index') }}">
            <i class="ri-book-mark-line"></i><span>Katalog &amp; Eksemplar</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.genres.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.genres.index') }}">
            <i class="ri-price-tag-3-line"></i><span>Genre Buku</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.loans.index') }}">
            <i class="ri-exchange-line"></i><span>Peminjaman &amp; Pengembalian</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.rooms.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.rooms.index') }}">
            <i class="ri-calendar-event-line"></i><span>Ruang &amp; Agenda</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.members.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.members.index') }}">
            <i class="ri-id-card-line"></i><span>Anggota &amp; Kartu</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.visits.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.visits.index') }}">
            <i class="ri-footprint-line"></i><span>Kunjungan Harian</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.stock-opnames.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.stock-opnames.index') }}">
            <i class="ri-file-list-2-line"></i><span>Stock Opname</span>
        </a>
    </li>

    @if($showAll || $isKoordinator)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.units.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.units.index') }}">
                <i class="ri-settings-4-line"></i><span>Unit &amp; Pengaturan</span>
            </a>
        </li>
    @endif

    <li class="menu-title"><span>Referensi</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>
@endif
