@php
    $impersonateActorName = session('impersonate.actor_name');
    $isImpersonating = session()->has('impersonate.actor_id') && auth()->check();
@endphp
@if ($isImpersonating)
    <div class="alert alert-warning border-0 rounded-0 mb-3 py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 impersonate-banner"
         role="alert"
         data-impersonate-banner>
        <div class="d-flex align-items-center gap-2">
            <i class="ri-mask-line fs-20"></i>
            <span>
                Anda sedang masuk sebagai
                <strong>{{ auth()->user()->name }}</strong>
                ({{ implode(', ', auth()->user()->effectiveRoles() ?: ['(tanpa role)']) }}).<br>
                Akun asal:
                <strong>{{ $impersonateActorName ?: 'Unknown' }}</strong>
                — klik 'Kembali ke Akun Saya' untuk kembali tanpa perlu login ulang.
            </span>
        </div>
        <form method="POST"
              action="{{ route('impersonate.stop') }}"
              class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-dark">
                <i class="ri-logout-box-r-line align-bottom"></i> Kembali ke Akun Saya
            </button>
        </form>
    </div>

    <style>
        /* Tetap terlihat saat halaman di-scroll, tepat di bawah topbar. */
        .impersonate-banner {
            position: sticky;
            top: 70px;
            z-index: 1001;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
        }
    </style>
@endif
