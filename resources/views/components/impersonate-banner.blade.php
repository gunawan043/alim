@if (session('impersonate.active') && auth()->check())
    <div class="alert alert-warning rounded-0 mb-0 py-2 px-3 d-flex align-items-center justify-content-between"
         role="alert"
         data-impersonate-banner>
        <div class="d-flex align-items-center gap-2">
            <i class="ri-mask-line fs-20"></i>
            <span>
                Anda sedang login sebagai
                <strong>{{ auth()->user()->name }}</strong>
                ({{ implode(', ', auth()->user()->effectiveRoles() ?: ['(tanpa role)']) }}).<br>
                Super Admin asli:
                <strong>{{ session('impersonate.actor_name', 'Unknown') }}</strong>
                — klik 'Stop Impersonate' untuk kembali tanpa perlu login ulang.
            </span>
        </div>
        <form method="POST"
              action="{{ route('impersonate.stop') }}"
              class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-dark">
                <i class="ri-logout-box-r-line align-bottom"></i> Stop Impersonate
            </button>
        </form>
    </div>
@endif