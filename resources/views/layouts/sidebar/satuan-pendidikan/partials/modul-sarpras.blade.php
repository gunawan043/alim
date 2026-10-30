{{-- Modul Sarpras — mode: full (KSP/Bendahara/Koor Sarpras) | aset (Kepala TU) --}}
@php $sarprasMode = $sarprasMode ?? 'full'; @endphp

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.ruang.']) ? ' active' : '' }}"
       href="{{ route('sarpras.user.ruang.index', ['userId' => $userId]) }}">
        <i class="ri-door-open-line"></i><span>Data Ruang</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.aset.']) ? ' active' : '' }}"
       href="{{ route('sarpras.user.aset.index', ['userId' => $userId]) }}">
        <i class="ri-archive-2-line"></i><span>Data Aset</span>
    </a>
</li>

@if($sarprasMode === 'full')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.kerusakan.']) ? ' active' : '' }}"
           href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}">
            <i class="ri-tools-line"></i><span>Lapor Kerusakan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.siplah.index', 'user.siplah.create', 'user.siplah.show']) ? ' active' : '' }}"
           href="{{ route('user.siplah.index', ['userId' => $userId]) }}">
            <i class="ri-file-list-3-line"></i><span>Pengajuan SIPLah</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bump-orders.index', 'user.bump-orders.create', 'user.bump-orders.show']) ? ' active' : '' }}"
           href="{{ route('user.bump-orders.index', ['userId' => $userId]) }}">
            <i class="ri-store-3-line"></i><span>Pemesanan Barang BUMP</span>
        </a>
    </li>
@endif
