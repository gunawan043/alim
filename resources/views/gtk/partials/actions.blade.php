<div class="dropdown">
    <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown">
        <i class="ri-more-2-fill"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @php $userId = $userId ?? request()->route('userId') ?? Auth::id(); @endphp
        <li>
            <a class="dropdown-item" href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $gtk->id]) }}">
                <i class="ri-eye-fill text-info me-2"></i> Lihat Detail
            </a>
        </li>
        @if ($userId != Auth::id() && Auth::user()->cannot('view-gtk', $gtk->id) || $userId == Auth::id() && Auth::user()->cannot('view-self', $gtk->id))
            @php abort(403, 'Unauthorized access.'); @endphp
        @endif
        @if (Auth::user()->role()->hasPermission('gtk-update') || Auth::user()->role()->hasPermission('gtk-delete'))
            <li>
                <a class="dropdown-item" href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $gtk->id]) }}">
                    <i class="ri-pencil-fill text-primary me-2"></i> Edit
                </a>
            </li>
            <li>
                <button class="dropdown-item toggle-status" data-id="{{ $gtk->id }}" data-status="{{ $gtk->is_active }}">
                    <i class="ri-toggle-{{ $gtk->is_active ? 'fill' : 'line' }} text-warning me-2"></i>
                    {{ $gtk->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </li>
            <li>
                <button class="dropdown-item reset-password" data-id="{{ $gtk->id }}" data-email="{{ $gtk->email }}">
                    <i class="ri-lock-password-line text-secondary me-2"></i> Reset Password
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <button class="dropdown-item text-danger delete-btn" data-id="{{ $gtk->id }}" data-name="{{ $gtk->name }}">
                    <i class="ri-delete-bin-line text-danger me-2"></i> Hapus
                </button>
            </li>
        @endif
    </ul>
</div>
