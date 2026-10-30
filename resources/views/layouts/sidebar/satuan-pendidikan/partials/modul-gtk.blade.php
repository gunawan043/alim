{{-- Modul GTK & Tugas — mode: admin | supervisi | mapel | supervisor --}}
@php $gtkMode = $gtkMode ?? 'admin'; @endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
@if(in_array($gtkMode, ['admin', 'supervisi', 'mapel'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru', 'user.gtk.indextendik']) ? ' active' : '' }}"
           href="#sp{{ $uid }}_gtk" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.gtk.indexguru', 'user.gtk.indextendik']) ? 'true' : 'false' }}"
           aria-controls="sp{{ $uid }}_gtk">
            <i class="ri-contacts-book-2-line"></i><span>Data GTK</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.gtk.indexguru', 'user.gtk.indextendik']) ? ' show' : '' }}"
             id="sp{{ $uid }}_gtk">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru']) ? ' active' : '' }}"
                       href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}">Guru</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.indextendik']) ? ' active' : '' }}"
                       href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}">Tendik</a>
                </li>
            </ul>
        </div>
    </li>
@endif

@if($gtkMode === 'admin')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.wali-links.']) ? ' active' : '' }}"
           href="{{ route('user.wali-links.index', ['userId' => $userId]) }}">
            <i class="ri-parent-line"></i><span>Penghubungan Wali</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk-requests.']) ? ' active' : '' }}"
           href="#sp{{ $uid }}_pengajuan_gtk" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.gtk-requests.']) ? 'true' : 'false' }}"
           aria-controls="sp{{ $uid }}_pengajuan_gtk">
            <i class="ri-file-add-line"></i><span>Pengajuan GTK</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.gtk-requests.']) ? ' show' : '' }}"
             id="sp{{ $uid }}_pengajuan_gtk">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk-requests.index']) ? ' active' : '' }}"
                       href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}">Data Pengajuan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk-requests.create']) ? ' active' : '' }}"
                       href="{{ route('user.gtk-requests.create', ['userId' => $userId]) }}">Buat Pengajuan GTK</a>
                </li>
            </ul>
        </div>
    </li>
@endif

@if(in_array($gtkMode, ['admin', 'supervisi'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.other-teacher-tasks.']) ? ' active' : '' }}"
           href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}">
            <i class="ri-user-settings-line"></i><span>Tugas Tambahan</span>
        </a>
    </li>
@endif

@if($gtkMode === 'supervisi')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.rapor-gtk.', 'user.kinerja.']) ? ' active' : '' }}"
           href="{{ route('user.rapor-gtk.tahunan', ['userId' => $userId]) }}">
            <i class="ri-file-chart-line"></i><span>Supervisi &amp; Kinerja GTK</span>
        </a>
    </li>
@endif

@if($gtkMode === 'mapel')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.']) ? ' active' : '' }}"
           href="{{ route('user.kinerja.index', ['userId' => $userId]) }}">
            <i class="ri-line-chart-line"></i><span>Evaluasi Guru Mapel</span>
        </a>
    </li>
@endif

@if(in_array($gtkMode, ['supervisi', 'supervisor'], true))
    @php
        $supervisiAdmin = $gtkMode === 'supervisi' && ($showAll || $isStruktural || $isKoorKurik || $isTimKurikulum || $isAdminTU);
        $supervisiActive = isActiveAny($currentRoute, [
            'waka.supervisi.',
            'waka.supervisi-instrumen.',
            'waka.supervisi-pengaturan.',
            'waka.supervisi-sk.',
        ]);
    @endphp
    <li class="nav-item">
        <a class="nav-link menu-link{{ $supervisiActive ? ' active' : '' }}"
           href="#sp{{ $uid }}_supervisi" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $supervisiActive ? 'true' : 'false' }}" aria-controls="sp{{ $uid }}_supervisi">
            <i class="ri-file-excel-2-line"></i><span>Supervisi Pembelajaran</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ $supervisiActive ? ' show' : '' }}" id="sp{{ $uid }}_supervisi">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['waka.supervisi.']) ? ' active' : '' }}"
                       href="{{ route('waka.supervisi.index', $supervisiAdmin ? [] : ['supervisor_id' => $supervisorProfileId]) }}">
                        Alur &amp; Jadwal
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['waka.supervisi-instrumen.']) ? ' active' : '' }}"
                       href="{{ route('waka.supervisi-instrumen.index') }}">Instrumen</a>
                </li>
                @if($supervisiAdmin)
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['waka.supervisi-sk.']) ? ' active' : '' }}"
                           href="{{ route('waka.supervisi-sk.index') }}">Tim &amp; SK</a>
                    </li>
                @endif
            </ul>
        </div>
    </li>
@endif
