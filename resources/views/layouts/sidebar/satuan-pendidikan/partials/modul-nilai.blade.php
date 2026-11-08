{{-- Modul Nilai — mode: manage (KSP/Waka/TU) | read (koordinator/tim) --}}
@php $nilaiMode = $nilaiMode ?? 'read'; @endphp

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai.']) ? ' active' : '' }}"
       href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
        <i class="ri-survey-line"></i><span>Data Nilai</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'nilai_kelas', 'title' => 'Kelas yang nilainya belum lengkap'])</a>
</li>

@if($nilaiMode === 'manage')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai-kelas.']) ? ' active' : '' }}"
           href="{{ route('user.schools.nilai-kelas.index', ['userId' => $userId]) }}">
            <i class="ri-table-line"></i><span>Leger &amp; Cetak Rapor</span>
        </a>
    </li>
@endif
