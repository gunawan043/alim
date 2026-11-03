@props([
    'id',
    'label',
    'icon' => 'ri-folder-3-line',
    'open' => false,
])

{{-- Section sidebar yang bisa dilipat. Dipakai agar sidebar jabatan
     struktural tidak menampilkan ratusan tautan sekaligus. --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $open ? '' : ' collapsed' }}"
       href="#{{ $id }}"
       data-bs-toggle="collapse"
       role="button"
       aria-expanded="{{ $open ? 'true' : 'false' }}"
       aria-controls="{{ $id }}">
        <i class="{{ $icon }}"></i><span>{{ $label }}</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ $open ? ' show' : '' }}" id="{{ $id }}">
        <ul class="nav nav-sm flex-column">
            {{ $slot }}
        </ul>
    </div>
</li>
