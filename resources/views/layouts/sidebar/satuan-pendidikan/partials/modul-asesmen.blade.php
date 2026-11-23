{{-- Modul Bank Soal & Asesmen --}}
@php
    $asesmenFull = $asesmenFull ?? false;
    // Menu jabatan: kisi-kisi lintas guru hanya bila meminta lingkup=semua.
    // Menu guru tetap lingkup pribadi (mengikuti SK).
    $asesmenBroad = $asesmenBroad ?? false;
@endphp

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asesmen.', 'user.kisi-kisi-soal.', 'user.bank-soal.', 'user.soal.', 'user.soal-saya.', 'user.paket-soal.', 'user.tu-paket-soal.', 'user.bank-soal-terpusat.', 'user.review-soal.', 'user.ljk.']) ? ' active' : '' }}"
       href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">
        <i class="ri-file-edit-line"></i><span>Pusat Asesmen</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'review_pending', 'title' => 'Dokumen menunggu review Anda'])</a>
</li>

@if($asesmenFull)
    {{-- Semua soal yang pernah dibuat guru, lintas semua paket/koleksi. --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.soal-saya.']) ? ' active' : '' }}"
           href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}"
           title="Semua soal yang pernah Anda buat, dari semua paket">
            <i class="ri-quill-pen-line"></i><span>Soal Saya</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bank-soal.']) ? ' active' : '' }}"
           href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}">
            <i class="ri-archive-2-line"></i><span>Paket Soal</span>
        </a>
    </li>
    @if($asesmenBroad)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kisi-kisi-soal.']) ? ' active' : '' }}"
               href="{{ route('user.kisi-kisi-soal.index', ['userId' => $userId, 'lingkup' => 'semua']) }}"
               title="Melihat mapel seluruh guru pada satuan pendidikan">
                <i class="ri-file-list-3-line"></i><span>Kisi-kisi (semua mapel)</span>
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.paket-soal.']) ? ' active' : '' }}"
           href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}">
            <i class="ri-stack-line"></i><span>Paket Soal (STS/SAS)</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ljk.']) ? ' active' : '' }}"
           href="{{ route('user.ljk.index', ['userId' => $userId]) }}">
            <i class="ri-scan-2-line"></i><span>LJK</span>
        </a>
    </li>
@endif
