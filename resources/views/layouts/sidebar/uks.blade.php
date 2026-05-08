<!-- UKS — Usaha Kesehatan Sekolah -->
<li class="menu-title"><span>UKS</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $isActiveFn($currentRoute, 'user.uks.') ? ' active' : '' }}"
       href="#uks" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isActiveFn($currentRoute, 'user.uks.') ? 'true' : 'false' }}"
       aria-controls="uks">
        <i class="ri-heart-pulse-line"></i>
        <span>UKS</span>
    </a>
    <div class="collapse menu-dropdown{{ $isActiveFn($currentRoute, 'user.uks.') ? ' show' : '' }}" id="uks">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.immunizations') ? ' active' : '' }}"
                   href="{{ route('user.uks.immunizations.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Imunisasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.health-checkups') ? ' active' : '' }}"
                   href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Medical Check-up</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.health-permits') ? ' active' : '' }}"
                   href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Izin Sakit</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.medicine-inventory') ? ' active' : '' }}"
                   href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Inventori Obat</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.medicine-logs') ? ' active' : '' }}"
                   href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Pemberian Obat</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.health-metrics') ? ' active' : '' }}"
                   href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Antropometri</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.counseling-records') ? ' active' : '' }}"
                   href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Konseling</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.facility-referrals') ? ' active' : '' }}"
                   href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Faskes Rujukan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.sanitation-inspections') ? ' active' : '' }}"
                   href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Inspeksi Sanitasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'profile.') ? ' active' : '' }}"
                   href="{{ route('user.profile.my', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Profile</a>
            </li>
        </ul>
    </div>
</li>
