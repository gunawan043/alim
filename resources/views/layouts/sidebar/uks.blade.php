<!-- UKS — Usaha Kesehatan Sekolah -->
@php
    $uksActive = $isActiveFn($currentRoute, 'user.uks.');
    $uksHealthCheckActive  = $isActiveFn($currentRoute, 'health-checkups.index');
    $uksImmunActive        = $isActiveFn($currentRoute, 'immunizations.index');
    $uksPermitActive       = $isActiveFn($currentRoute, 'health-permits.index');
    $uksMetricsActive      = $isActiveFn($currentRoute, 'health-metrics.index');
    $uksCounselActive      = $isActiveFn($currentRoute, 'counseling-records.index');
    $uksMedicineActive     = $isActiveFn($currentRoute, 'medicine-inventory.index')
                            || $isActiveFn($currentRoute, 'medicine-logs.index');
    $uksReferralActive     = $isActiveFn($currentRoute, 'facility-referrals.index');
    $uksSanitationActive   = $isActiveFn($currentRoute, 'sanitation-inspections.index');
    $uksGtkHealthActive    = $isActiveFn($currentRoute, 'user.uks.gtk-health');
    $uksStudentHealthActive = $isActiveFn($currentRoute, 'user.uks.student-health');
@endphp

<li class="nav-item">
    <a class="nav-link menu-link{{ $uksActive ? ' active' : '' }}"
       href="#uks" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $uksActive ? 'true' : 'false' }}" aria-controls="uks">
        <i class="ri-heart-pulse-line"></i>
        <span>UKS</span>
    </a>
    <div class="collapse menu-dropdown{{ $uksActive ? ' show' : '' }}" id="uks">
        <ul class="nav nav-sm flex-column">

            {{-- ═══ Pelayanan Kesehatan ═══ --}}
            <li class="menu-title ps-1" style="font-size:0.7rem; margin-top:.25rem"><i class="ri-stethoscope-line me-1"></i>Pelayanan Kesehatan</li>
            <li class="nav-item">
                <a class="nav-link{{ $uksHealthCheckActive ? ' active' : '' }}"
                   href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Medical Check-up</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $uksImmunActive ? ' active' : '' }}"
                   href="{{ route('user.uks.immunizations.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Imunisasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $uksPermitActive ? ' active' : '' }}"
                   href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Izin Sakit</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $uksCounselActive ? ' active' : '' }}"
                   href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Konseling</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $uksMetricsActive ? ' active' : '' }}"
                   href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Antropometri</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $uksStudentHealthActive ? ' active' : '' }}"
                   href="{{ route('user.uks.student-health.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-user-heart-line me-1"></i>Data Kesehatan Santri
                </a>
            </li>

            {{-- ═══ Data Kesehatan ═══ --}}
            <li class="menu-title ps-1" style="font-size:0.7rem; margin-top:.75rem"><i class="ri-heart-pulse-line me-1"></i>Data Kesehatan</li>
            <li class="nav-item">
                <a class="nav-link{{ $uksGtkHealthActive ? ' active' : '' }}"
                   href="{{ route('user.uks.gtk-health.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-heart-pulse-line me-1"></i>Data Kesehatan GTK
                </a>
            </li>

            {{-- ═══ Farmasi & Logistik ═══ --}}
            <li class="menu-title ps-1" style="font-size:0.7rem; margin-top:.75rem"><i class="ri-capsule-line me-1"></i>Farmasi & Logistik</li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.medicine-inventory') ? ' active' : '' }}"
                   href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Stok Obat</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.medicine-logs') ? ' active' : '' }}"
                   href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Pemberian Obat</a>
            </li>

            {{-- ═══ Rujukan & Lingkungan ═══ --}}
            <li class="menu-title ps-1" style="font-size:0.7rem; margin-top:.75rem"><i class="ri-shield-check-line me-1"></i>Rujukan & Lingkungan</li>
            <li class="nav-item">
                <a class="nav-link{{ $uksReferralActive ? ' active' : '' }}"
                   href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Faskes Rujukan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $uksSanitationActive ? ' active' : '' }}"
                   href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Inspeksi Sanitasi</a>
            </li>

            {{-- ═══ Status Perawatan Pasien ═══ --}}
            <li class="menu-title ps-1" style="font-size:0.7rem; margin-top:.75rem"><i class="ri-heart-pulse-fill me-1"></i>Status Perawatan</li>
            <li class="nav-item">
                <a class="nav-link{{ $isActiveFn($currentRoute, 'user.uks.treatment-status') || $isActiveFn($currentRoute, 'user.uks.patients') ? ' active' : '' }}"
                   href="{{ route('user.uks.patients.index', ['userId' => $userId, 'status' => 'rawat_uks']) }}"
                   style="font-size:0.85rem">Status Perawatan UKS</a>
            </li>
        </ul>
    </div>
</li>
