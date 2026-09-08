<!-- Master Data Sidebar — shared partial untuk role mana pun yang butuh akses Master Data -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

if (! function_exists('isActiveMaster')) {
function isActiveMaster($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
}
@endphp

{{-- Referensi --}}
<li class="menu-title"><span>Referensi</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveMaster($currentRoute, 'user.master-data.') ? ' active' : '' }}"
       href="#master_data" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveMaster($currentRoute, 'user.master-data.') ? 'true' : 'false' }}"
       aria-controls="master_data">
        <i class="ri-database-2-line"></i>
        <span>Master Data</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveMaster($currentRoute, 'user.master-data.') ? ' show' : '' }}" id="master_data">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.jenis-gtk.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.jenis-gtk.index', ['userId' => $userId]) }}">
                    Jenis GTK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.jabatan.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.jabatan.index', ['userId' => $userId]) }}">
                    Jabatan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.satuan-kerja.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.satuan-kerja.index', ['userId' => $userId]) }}">
                    Satuan Kerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.mata-pelajaran.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.mata-pelajaran.index', ['userId' => $userId]) }}">
                    Mata Pelajaran
                </a>
            </li>
        </ul>
    </div>
</li>
