{{-- Modul Bank Soal & Asesmen --}}
@php $asesmenFull = $asesmenFull ?? false; @endphp

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asesmen.', 'user.kisi-kisi-soal.', 'user.bank-soal.', 'user.soal.', 'user.paket-soal.', 'user.tu-paket-soal.', 'user.bank-soal-terpusat.', 'user.review-soal.', 'user.ljk.']) ? ' active' : '' }}"
       href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">
        <i class="ri-file-edit-line"></i><span>Pusat Asesmen</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'review_pending', 'title' => 'Dokumen menunggu review Anda'])</a>
</li>

@if($asesmenFull)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bank-soal.']) ? ' active' : '' }}"
           href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}">
            <i class="ri-archive-2-line"></i><span>Bank Soal</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.paket-soal.']) ? ' active' : '' }}"
           href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}">
            <i class="ri-stack-line"></i><span>Paket Soal</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ljk.']) ? ' active' : '' }}"
           href="{{ route('user.ljk.index', ['userId' => $userId]) }}">
            <i class="ri-scan-2-line"></i><span>LJK</span>
        </a>
    </li>
@endif
