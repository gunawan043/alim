@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Session;

    $__viewAsUser = Auth::user();
    $__viewAsRole = Session::get('view_as_role');
    $__viewAsUserId = Session::get('view_as_user_id');
    $__isViewingAs = is_string($__viewAsRole) && $__viewAsRole !== ''
        && $__viewAsUser
        && method_exists($__viewAsUser, 'isSystemAdmin')
        && $__viewAsUser->isSystemAdmin();
    $__isLoginAs = ! empty($__viewAsUserId);
@endphp

@if($__isLoginAs || $__isViewingAs)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-{{ $__isLoginAs ? 'success' : 'warning' }} rounded-0 mb-3 py-2 px-3 d-flex align-items-center justify-content-between"
                 role="alert"
                 data-view-as-badge>
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-{{ $__isLoginAs ? 'login-box-line' : 'mask-line' }} fs-20"></i>
                    <span>
                        @if($__isLoginAs)
                            Anda sedang login sebagai
                            <strong>{{ $__viewAsUser->name }}</strong>
                            ({{ $__viewAsRole }}).
                        @else
                            Anda sedang melihat sebagai
                            <strong>{{ $__viewAsRole }}</strong>.
                        @endif
                        <small class="text-muted ms-1">Hak akses dan menu mengikuti role ini.</small>
                    </span>
                </div>
                <form method="POST"
                      action="{{ $__isLoginAs ? route('system.view-as.restore') : route('system.view-as.reset') }}"
                      class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-{{ $__isLoginAs ? 'outline-success' : 'dark' }}">
                        <i class="ri-logout-box-r-line align-bottom"></i> {{ $__isLoginAs ? 'Restore Admin' : 'Stop Viewing As' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif