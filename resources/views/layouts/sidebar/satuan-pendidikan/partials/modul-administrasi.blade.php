{{-- Modul Administrasi (surat, dokumen mutu, arsip) --}}
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.surat-masuk.', 'waka.surat-keluar.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_surat" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['waka.surat-masuk.', 'waka.surat-keluar.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_surat">
        <i class="ri-mail-send-line"></i><span>Surat Menyurat</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['waka.surat-masuk.', 'waka.surat-keluar.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_surat">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['waka.surat-keluar.']) ? ' active' : '' }}"
                   href="{{ route('waka.surat-keluar.index') }}">Surat Keluar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['waka.surat-masuk.']) ? ' active' : '' }}"
                   href="{{ route('waka.surat-masuk.index') }}">Surat Masuk</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
       href="{{ route('user.dokumen-iso.index', ['userId' => $userId]) }}">
        <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.arsip.']) ? ' active' : '' }}"
       href="{{ route('user.arsip.index', ['userId' => $userId]) }}">
        <i class="ri-archive-2-line"></i><span>Arsip &amp; Penutupan Periode</span>
    </a>
</li>
