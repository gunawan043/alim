@menuallowed('tugas-koordinator-osipp')
@php
    $osippUserId = request()->route('userId') ?? auth()->id();
    $osippUrl = fn (string $name, array $extra = []) => route($name, array_merge(['userId' => $osippUserId], $extra));
    $osippActive = fn (string $name) => isActiveAny($currentRoute ?? '', [$name]);
@endphp

<li class="menu-title"><span>OSIPP</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.dashboard') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.dashboard') }}">
        <i class="ri-dashboard-3-line"></i><span>Ringkasan OSIPP</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.periode.') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.periode.index') }}">
        <i class="ri-calendar-event-line"></i><span>Periode Kepengurusan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.anggota.') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.anggota.index') }}">
        <i class="ri-group-line"></i><span>Anggota & Jabatan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.qism.') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.qism.index') }}">
        <i class="ri-node-tree"></i><span>Qism & Bidang</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.program.') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.program.index') }}">
        <i class="ri-list-check-2"></i><span>Program Kerja</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.kegiatan.') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.kegiatan.index') }}">
        <i class="ri-flag-line"></i><span>Kegiatan & Verifikasi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.administrasi.') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.administrasi.index') }}">
        <i class="ri-folder-shield-2-line"></i><span>Administrasi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.pembinaan.') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.pembinaan.index') }}">
        <i class="ri-user-heart-line"></i><span>Pembinaan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $osippActive('osipp.evaluasi.') ? ' active' : '' }}"
       href="{{ $osippUrl('osipp.evaluasi.index') }}">
        <i class="ri-award-line"></i><span>Evaluasi Kepengurusan</span>
    </a>
</li>
@endmenuallowed
