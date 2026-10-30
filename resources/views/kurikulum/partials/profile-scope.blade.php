@if(($curriculumProfile ?? null) || ($profileScope ?? null))
    <div class="card card-animate mb-3 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                {{-- Kiri: label + segmented button --}}
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-filter-3-line text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 stat-label">Tampilan Profil</p>
                            <small class="text-muted">Pilih cakupan data yang ingin ditampilkan</small>
                        </div>
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Filter Profil">
                        <a href="{{ request()->fullUrlWithQuery(['profile_scope' => 'active']) }}"
                           class="btn {{ ($profileScope ?? 'active') === 'active' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            <i class="ri-checkbox-circle-line me-1"></i> Profil Aktif
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['profile_scope' => 'all']) }}"
                           class="btn {{ ($profileScope ?? 'active') === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            <i class="ri-stack-line me-1"></i> Semua Profil
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['profile_scope' => 'legacy']) }}"
                           class="btn {{ ($profileScope ?? 'active') === 'legacy' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            <i class="ri-archive-line me-1"></i> Arsip Lama
                        </a>
                    </div>
                </div>

                {{-- Kanan: badge profil aktif --}}
                @if($curriculumProfile ?? null)
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Profil:</span>
                        <span class="badge bg-success-subtle text-success px-3 py-2">
                            <i class="ri-shield-check-line me-1"></i>{{ $curriculumProfile->name }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var scope = @json($profileScope ?? 'active');
            document.querySelectorAll('form[method="GET"]').forEach(function (form) {
                if (form.querySelector('input[name="profile_scope"]')) return;
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'profile_scope';
                input.value = scope;
                form.appendChild(input);
            });
        });
    </script>
@endonce