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
        @if (Auth::user()->hasPermissionTo('gtk-update') || Auth::user()->hasPermissionTo('gtk-delete'))
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
            @if(\App\Support\GtkAccess::canResetPassword(auth()->user(), $gtk, request()->attributes->get('schoolContextId')))
                <li>
                    <button class="dropdown-item reset-password"
                            data-id="{{ $gtk->id }}"
                            data-email="{{ $gtk->email }}"
                            data-url="{{ route('user.gtk.reset-password', ['userId' => $userId, 'uuid' => $gtk->id]) }}">
                        <i class="ri-lock-password-line text-secondary me-2"></i> Reset Password
                    </button>
                </li>
            @endif
            @if(\App\Support\GtkAccess::canImpersonate(auth()->user(), $gtk, request()->attributes->get('schoolContextId')))
                <li>
                    <form method="POST" action="{{ route('impersonate.start', ['targetUser' => $gtk->id]) }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="ri-login-box-line text-success me-2"></i> Masuk sebagai GTK
                        </button>
                    </form>
                </li>
            @endif
            <li><hr class="dropdown-divider"></li>
            <li>
                <button class="dropdown-item text-danger delete-btn" data-id="{{ $gtk->id }}" data-name="{{ $gtk->name }}">
                    <i class="ri-delete-bin-line text-danger me-2"></i> Hapus
                </button>
            </li>
        @endif
    </ul>
</div>
