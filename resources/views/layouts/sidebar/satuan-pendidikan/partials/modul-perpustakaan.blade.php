{{-- Modul Perpustakaan — untuk Koordinator/Kepala Perpustakaan --}}
@php
    $pustakaUrl = function (string $routeName, array $extra = []) use ($userId) {
        try {
            return route($routeName, array_merge(['userId' => $userId], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.index']) ? ' active' : '' }}"
       href="{{ $pustakaUrl('user.perpustakaan.index') }}">
        <i class="ri-book-2-line"></i><span>Dashboard Perpustakaan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.', 'user.perpustakaan.imports.catalog', 'user.perpustakaan.imports.genres', 'user.perpustakaan.genres.', 'user.perpustakaan.racks.', 'user.perpustakaan.units.', 'user.perpustakaan.operators.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_pustaka_koleksi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.', 'user.perpustakaan.imports.catalog', 'user.perpustakaan.imports.genres', 'user.perpustakaan.genres.', 'user.perpustakaan.racks.', 'user.perpustakaan.units.', 'user.perpustakaan.operators.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_pustaka_koleksi">
        <i class="ri-book-mark-line"></i><span>Koleksi</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.', 'user.perpustakaan.imports.catalog', 'user.perpustakaan.imports.genres', 'user.perpustakaan.genres.', 'user.perpustakaan.racks.', 'user.perpustakaan.units.', 'user.perpustakaan.operators.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_pustaka_koleksi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.catalog.index') }}">Katalog Buku</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.']) && request('collection') === 'reference' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.catalog.index', ['collection' => 'reference']) }}">Kitab &amp; Referensi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.imports.catalog']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.imports.catalog.form') }}">Import Katalog</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.imports.genres']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.imports.genres.form') }}">Import Genre</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.genres.']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.genres.index') }}">Kategori &amp; Klasifikasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.racks.']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.racks.index') }}">Manajemen Rak</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.units.']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.units.index') }}">Aturan Peminjaman</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.operators.']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.operators.index') }}">Petugas Santri</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.', 'user.perpustakaan.rooms.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_pustaka_sirkulasi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.', 'user.perpustakaan.rooms.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_pustaka_sirkulasi">
        <i class="ri-exchange-line"></i><span>Sirkulasi</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.', 'user.perpustakaan.rooms.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_pustaka_sirkulasi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'borrow' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'borrow']) }}">Transaksi Peminjaman</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'return' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'return']) }}">Transaksi Pengembalian</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'renew' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'renew']) }}">Perpanjangan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'overdue' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'overdue']) }}">Buku Terlambat</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'history' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'history']) }}">Riwayat Sirkulasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.rooms.']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.rooms.index') }}">Ruang &amp; Agenda</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.members.', 'user.perpustakaan.imports.members', 'user.perpustakaan.visits.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_pustaka_anggota" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.perpustakaan.members.', 'user.perpustakaan.imports.members', 'user.perpustakaan.visits.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_pustaka_anggota">
        <i class="ri-id-card-line"></i><span>Keanggotaan</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.perpustakaan.members.', 'user.perpustakaan.imports.members', 'user.perpustakaan.visits.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_pustaka_anggota">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.members.']) && request('member_type') === 'student' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.members.index', ['member_type' => 'student']) }}">Anggota Santri</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.members.']) && request('member_type') === 'gtk' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.members.index', ['member_type' => 'gtk']) }}">Anggota GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.members.']) && ! request('member_type') ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.members.index') }}">Kartu Anggota</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.imports.members']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.imports.members.form') }}">Import Anggota</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.visits.']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.visits.index') }}">Kunjungan Harian</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_pustaka_denda" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_pustaka_denda">
        <i class="ri-money-dollar-circle-line"></i><span>Denda &amp; Bebas Pustaka</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_pustaka_denda">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) && request('tab') !== 'free' && request('status') !== 'unpaid' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.fines.index') }}">Daftar Denda</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) && request('status') === 'unpaid' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.fines.index', ['status' => 'unpaid']) }}">Pembayaran Denda</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) && request('tab') === 'free' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.fines.index', ['tab' => 'free']) }}">Surat Bebas Pustaka</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ $pustakaUrl('user.perpustakaan.fines.index', ['tab' => 'free']) }}">Bebas Pustaka Massal</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.stock-opnames.', 'user.perpustakaan.programs.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_pustaka_inventaris" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.perpustakaan.stock-opnames.', 'user.perpustakaan.programs.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_pustaka_inventaris">
        <i class="ri-archive-2-line"></i><span>Inventaris &amp; Literasi</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.perpustakaan.stock-opnames.', 'user.perpustakaan.programs.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_pustaka_inventaris">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.stock-opnames.']) ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.stock-opnames.index') }}">Stock Opname</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.']) && request('condition') === 'problem' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.catalog.index', ['condition' => 'problem']) }}">Buku Rusak / Hilang</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request()->route('type') === 'procurement' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'procurement']) }}">Pengadaan Buku</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request()->route('type') === 'donation' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'donation']) }}">Sumbangan Buku</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request()->route('type') === 'weeding' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'weeding']) }}">Penyusutan Koleksi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request()->route('type') === 'literacy' && ! request('category') ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'literacy']) }}">Pojok Baca</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request('category') === 'Resensi Buku' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'literacy', 'category' => 'Resensi Buku']) }}">Resensi Buku</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request('category') === 'Program Literasi' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'literacy', 'category' => 'Program Literasi']) }}">Program Literasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request('category') === 'Lomba Literasi' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'literacy', 'category' => 'Lomba Literasi']) }}">Lomba Literasi</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_pustaka_laporan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_pustaka_laporan">
        <i class="ri-bar-chart-2-line"></i><span>Laporan Perpustakaan</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_pustaka_laporan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) && request('tab') === 'loans' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.reports.index', ['tab' => 'loans']) }}">Statistik Peminjaman</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) && request('tab') === 'popular' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.reports.index', ['tab' => 'popular']) }}">Buku Terpopuler</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) && request('tab') === 'members' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.reports.index', ['tab' => 'members']) }}">Anggota Teraktif</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) && request('tab') === 'annual' ? ' active' : '' }}"
                   href="{{ $pustakaUrl('user.perpustakaan.reports.index', ['tab' => 'annual']) }}">Laporan Tahunan</a>
            </li>
        </ul>
    </div>
</li>
