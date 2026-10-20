@if(($curriculumProfile ?? null) || ($profileScope ?? null))
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 small">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted"><i class="ri-filter-3-line me-1"></i>Tampilan profil:</span>
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ request()->fullUrlWithQuery(['profile_scope' => 'active']) }}"
                   class="btn {{ ($profileScope ?? 'active') === 'active' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Profil aktif
                </a>
                <a href="{{ request()->fullUrlWithQuery(['profile_scope' => 'all']) }}"
                   class="btn {{ ($profileScope ?? 'active') === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Semua profil
                </a>
                <a href="{{ request()->fullUrlWithQuery(['profile_scope' => 'legacy']) }}"
                   class="btn {{ ($profileScope ?? 'active') === 'legacy' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Arsip lama
                </a>
            </div>
        </div>
        @if($curriculumProfile ?? null)
            <span class="badge bg-success-subtle text-success">{{ $curriculumProfile->name }}</span>
        @endif
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
