<!-- start page title -->
@php
$items = $crumbs ?? [];
if ($li_1) array_unshift($items, ['label' => $li_1, 'url' => $li_1_url ?? 'javascript:void(0)' ]);
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">{{ $title ?? '' }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    @foreach($items as $i => $item)
                        @if($i < count($items) - 1)
                            <li class="breadcrumb-item"><a href="{{ $item['url'] ?? 'javascript:void(0)' }}">{{ $item['label'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

{{-- View-As / Login-As banner: tampil di bawah breadcrumb pada halaman yang punya breadcrumb. --}}
@auth
    @php
        $__crumbUser = auth()->user();
        $__crumbRole = session('view_as_role');
        $__crumbUserId = session('view_as_user_id');
        $__crumbIsAdminViewing = is_string($__crumbRole) && $__crumbRole !== ''
            && $__crumbUser
            && method_exists($__crumbUser, 'isSystemAdmin')
            && $__crumbUser->isSystemAdmin();
        $__crumbIsLoginAs = ! empty($__crumbUserId);
        $__crumbShow = $__crumbIsAdminViewing || $__crumbIsLoginAs;
    @endphp
    @if($__crumbShow)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-{{ $__crumbIsLoginAs ? 'success' : 'warning' }} rounded mb-3 py-2 px-3 d-flex align-items-center justify-content-between"
                     role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-{{ $__crumbIsLoginAs ? 'login-box-line' : 'mask-line' }} fs-20"></i>
                        <span>
                            @if($__crumbIsLoginAs)
                                Anda sedang login sebagai
                                <strong>{{ $__crumbUser->name }}</strong>
                                ({{ $__crumbRole }}).
                            @else
                                Anda sedang melihat sebagai
                                <strong>{{ $__crumbRole }}</strong>.
                            @endif
                            <small class="text-muted ms-1">Hak akses dan menu mengikuti role ini.</small>
                        </span>
                    </div>
                    <form method="POST"
                          action="{{ $__crumbIsLoginAs ? route('system.view-as.restore') : route('system.view-as.reset') }}"
                          class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-{{ $__crumbIsLoginAs ? 'outline-success' : 'dark' }}">
                            <i class="ri-logout-box-r-line align-bottom"></i> {{ $__crumbIsLoginAs ? 'Restore Admin' : 'Stop Viewing As' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endauth
