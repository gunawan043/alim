{{-- Modul Bahasa (lintas divisi) — hanya Koordinator Bahasa, Waka, KSP --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.mufradat.']) ? ' active' : '' }}"
       href="{{ route('user.bahasa.mufradat.index', ['userId' => $userId]) }}">
        <i class="ri-book-2-line"></i><span>Hafalan Mufradat</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.violations.']) ? ' active' : '' }}"
       href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}">
        <i class="ri-error-warning-line"></i><span>Pelanggaran Bahasa</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.activities.']) ? ' active' : '' }}"
       href="{{ route('user.bahasa.activities.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-event-line"></i><span>AM Nasyath Syahri</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.']) ? ' active' : '' }}"
       href="{{ route('user.bahasa.academic.nilai', ['userId' => $userId, 'language' => 'arab']) }}">
        <i class="ri-line-chart-line"></i><span>Nilai &amp; Perkembangan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.daily.']) ? ' active' : '' }}"
       href="{{ route('user.bahasa.daily.index', ['userId' => $userId, 'type' => 'percakapan']) }}">
        <i class="ri-chat-3-line"></i><span>Percakapan &amp; Jasus</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.certifications.']) ? ' active' : '' }}"
       href="{{ route('user.bahasa.certifications.index', ['userId' => $userId]) }}">
        <i class="ri-award-line"></i><span>Sertifikasi Bahasa</span>
    </a>
</li>
