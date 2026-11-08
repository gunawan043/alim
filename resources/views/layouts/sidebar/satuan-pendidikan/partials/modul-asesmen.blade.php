{{-- Modul Bank Soal & Asesmen --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asesmen.', 'user.kisi-kisi-soal.', 'user.bank-soal.', 'user.soal.', 'user.paket-soal.', 'user.tu-paket-soal.', 'user.bank-soal-terpusat.', 'user.review-soal.', 'user.ljk.']) ? ' active' : '' }}"
       href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">
        <i class="ri-file-edit-line"></i><span>Asesmen</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'review_pending', 'title' => 'Dokumen menunggu review Anda'])</a>
</li>
