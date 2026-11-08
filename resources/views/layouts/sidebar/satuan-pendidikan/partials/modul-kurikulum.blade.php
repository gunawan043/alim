{{-- Modul Kurikulum — mode: authoring (guru) | manage (tim kurikulum) | supervisor (KSP) --}}
@php $kurikulumMode = $kurikulumMode ?? 'authoring'; @endphp
@php $kurikulumAlsoManage = $kurikulumAlsoManage ?? false; @endphp
{{-- $kurikulumSkipCommon: sembunyikan menu yang sudah tampil di section Mengajar
     (dipakai guru yang sekaligus pengelola kurikulum agar tidak dobel). --}}
@php $kurikulumSkipCommon = $kurikulumSkipCommon ?? false; @endphp
{{-- $kurikulumBroad: tampilkan dokumen semua guru (menu jabatan). Di Menu Guru
     parameter tidak dikirim sehingga halaman memakai lingkup pribadi. --}}
@php $kurikulumQuery = ($kurikulumBroad ?? false) ? ['lingkup' => 'semua'] : []; @endphp
{{-- Label collapse; menu jabatan memakai label berbeda agar tidak tampak
     sebagai duplikat Menu Guru. --}}
@php $perangkatLabel = $perangkatLabel ?? 'Perangkat & Bahan Ajar'; @endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
@if(! $kurikulumSkipCommon)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.profil.']) ? ' active' : '' }}"
           href="{{ route('user.kurikulum.profil.index', ['userId' => $userId] + $kurikulumQuery) }}">
            <i class="ri-information-line"></i><span>Profil &amp; Info Kurikulum</span>
        </a>
    </li>
@endif

@if(! $kurikulumSkipCommon && ($showAll || $canAccessKurikulum))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.index']) ? ' active' : '' }}"
           href="{{ route('user.kurikulum.index', ['userId' => $userId] + $kurikulumQuery) }}">
            <i class="ri-map-2-line"></i><span>Peta Kurikulum</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.proyek.']) ? ' active' : '' }}"
           href="{{ route('user.kurikulum.proyek.index', ['userId' => $userId] + $kurikulumQuery) }}">
            <i class="ri-community-line"></i><span>Projek P5 / P5RA</span>
        </a>
    </li>
@endif

@if($kurikulumMode === 'authoring' && ($showAll || ($isGuru && $canAccessKurikulum)))
    @php
        $perangkatActive = isActiveAny($currentRoute, [
            'user.kurikulum.cp.', 'user.kurikulum.tp.', 'user.kurikulum.atp.',
            'user.kurikulum.prota.', 'user.kurikulum.prosem.',
            'user.kurikulum.perangkat.', 'user.kurikulum.ai.', 'user.kurikulum.akses-edit.',
        ]);
    @endphp
    <li class="nav-item">
        <a class="nav-link menu-link{{ $perangkatActive ? ' active' : '' }}"
           href="#sp{{ $uid }}_perangkat" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $perangkatActive ? 'true' : 'false' }}" aria-controls="sp{{ $uid }}_perangkat">
            <i class="ri-file-list-3-line"></i><span>{{ $perangkatLabel }}</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ $perangkatActive ? ' show' : '' }}" id="sp{{ $uid }}_perangkat">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.cp.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.cp.index', ['userId' => $userId] + $kurikulumQuery) }}">Capaian Pembelajaran</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.tp.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.tp.index', ['userId' => $userId] + $kurikulumQuery) }}">Tujuan Pembelajaran</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.atp.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.atp.index', ['userId' => $userId] + $kurikulumQuery) }}">ATP</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.prota.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.prota.index', ['userId' => $userId] + $kurikulumQuery) }}">PROTA</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.prosem.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId] + $kurikulumQuery) }}">PROSEM</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.perangkat.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId] + $kurikulumQuery) }}">
                        Perangkat Pembelajaran
                        @include('layouts.sidebar.partials.indicator-badge', ['key' => 'perangkat_review', 'title' => 'Perangkat menunggu review Anda'])
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.import.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.import.form', ['userId' => $userId] + $kurikulumQuery) }}">
                        <i class="ri-upload-cloud-2-line me-1"></i>Import Perangkat (Excel)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.ai.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.ai.index', ['userId' => $userId] + $kurikulumQuery) }}">AI Assistant (RPM &amp; Soal)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.akses-edit.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.akses-edit.index', ['userId' => $userId] + $kurikulumQuery) }}">Permintaan Akses Edit</a>
                </li>
            </ul>
        </div>
    </li>
@endif

@if(in_array($kurikulumMode, ['manage', 'supervisor'], true) && ($showAll || $isKspSupervisor))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.perangkat.ksp-report']) ? ' active' : '' }}"
           href="{{ route('user.kurikulum.perangkat.ksp-report', ['userId' => $userId] + $kurikulumQuery) }}">
            <i class="ri-shield-check-line"></i><span>Persetujuan KSP Perangkat</span>
            @include('layouts.sidebar.partials.indicator-badge', ['key' => 'perangkat_ksp', 'title' => 'Perangkat final menunggu persetujuan KSP'])
        </a>
    </li>
@endif

@if(($kurikulumMode === 'manage' || ($kurikulumAlsoManage && $kurikulumMode !== 'authoring')) && ($showAll || $isCurriculumManager))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.rumpun.']) ? ' active' : '' }}"
           href="{{ route('user.kurikulum.rumpun.index', ['userId' => $userId] + $kurikulumQuery) }}">
            <i class="ri-node-tree"></i><span>Master Rumpun</span>
        </a>
    </li>
@endif
