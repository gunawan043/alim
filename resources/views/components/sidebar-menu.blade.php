{{-- Dynamic sidebar menu (multi-level recursive) --}}
@if($sidebarMenu && $sidebarMenu->count())

@php
$isSuperAdmin = $isSidebarSuperAdmin ?? false;
$sidebarUserId = session('active_user_id') ?? \Illuminate\Support\Facades\Auth::id();

/**
 * Build URL for a sidebar menu item.
 */
function buildMenuUrl($item, $userId, $isSuperAdmin = false) {
    $routeName = trim($item->route_with_role ?? $item->route ?? '');
    $url = trim($item->url ?? '');
    $routeQuery = $item->route_query ?? '';

    if ($url) {
        return $url . $routeQuery;
    }

    if (!$routeName) {
        return '#';
    }

    if (!Route::has($routeName)) {
        return '#';
    }

    $params = [];
    $needsRole = str_starts_with($routeName, 'user.');

    if ($needsRole) {
        if (!$userId && !$isSuperAdmin) {
            return '#';
        }
        $params['userId'] = $userId;
    }

    if (!empty($item->route_params)) {
        if (is_array($item->route_params)) {
            $params = array_merge($params, $item->route_params);
        } else {
            $decoded = json_decode($item->route_params, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $params = array_merge($params, $decoded);
            }
        }
    }

    try {
        return route($routeName, $params) . $routeQuery;
    } catch (\Exception $e) {
        return '#';
    }
}

/**
 * Check if a sidebar menu item is the active one.
 */
function isActiveMenuItem($item, $activeSidebarMenu, $routeName) {
    if (!$item) return false;

    // Direct object match
    if ($activeSidebarMenu && $activeSidebarMenu->id === $item->id) {
        return true;
    }

    // Route name match
    $itemRoute = trim($item->route_with_role ?? $item->route ?? '');
    if ($itemRoute && $routeName) {
        if ($itemRoute === $routeName) return true;

        // Also check stripped version
        $stripped = preg_replace('/^user\./', '', $itemRoute);
        $routeStripped = preg_replace('/^user\./', '', $routeName);
        if ($stripped === $routeStripped) return true;
    }

    // Child match: if any child is active, mark parent too
    if ($item->children) {
        foreach ($item->children as $child) {
            if (isActiveMenuItem($child, $activeSidebarMenu, $routeName)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Render a menu item recursively (all levels).
 */
function renderMenuItem($item, $userId, $isSuperAdmin, $activeSidebarMenu, $routeName) {
    $url = buildMenuUrl($item, $userId, $isSuperAdmin);
    $hasChildren = $item->children && $item->children->count() > 0;
    $shouldShow = $hasChildren || ($url !== '#');

    if (!$shouldShow) {
        return;
    }

    // Group header: always render as section title with children listed directly
    if ($item->is_group_header) {
        echo '<li class="menu-title"><span>' . e($item->name) . '</span></li>';
        if ($hasChildren) {
            foreach ($item->children as $child) {
                renderMenuItem($child, $userId, $isSuperAdmin, $activeSidebarMenu, $routeName);
            }
        }
        return;
    }

    $isActive = isActiveMenuItem($item, $activeSidebarMenu, $routeName);

    // Regular parent (with children)
    if ($hasChildren) {
        $menuId = ($item->html_id ?? 'menu') . '_' . $item->id;
        echo '<li class="nav-item">';
        echo '<a class="nav-link menu-link' . ($isActive ? ' active' : '') . '" href="#' . e($menuId) . '" data-bs-toggle="collapse" role="button" aria-expanded="' . ($isActive ? 'true' : 'false') . '">';
        if ($item->icon) {
            echo '<i class="' . e($item->icon) . '"></i>';
        }
        echo '<span>' . e($item->name) . '</span>';
        echo '</a>';
        echo '<div class="collapse menu-dropdown' . ($isActive ? ' show' : '') . '" id="' . e($menuId) . '">';
        echo '<ul class="nav nav-sm flex-column">';
        foreach ($item->children as $child) {
            renderMenuItem($child, $userId, $isSuperAdmin, $activeSidebarMenu, $routeName);
        }
        echo '</ul>';
        echo '</div>';
        echo '</li>';
    } else {
        // Leaf item
        echo '<li class="nav-item">';
        echo '<a class="nav-link' . ($isActive ? ' active' : '') . '" href="' . e($url) . '" style="font-size:0.85rem">';
        if ($item->icon) {
            echo '<i class="' . e($item->icon) . ' me-1"></i>';
        }
        echo e($item->name);
        echo '</a>';
        echo '</li>';
    }
}
@endphp

<ul class="navbar-nav" id="sidebar-nav">

@foreach($sidebarMenu as $menuItem)

    @if($menuItem->is_group_header)
        {{-- Group header: show title, then render all children --}}
        <li class="menu-title">
            <span>{{ $menuItem->name }}</span>
        </li>
        @if($menuItem->children && $menuItem->children->count())
            @foreach($menuItem->children as $child)
                @php
                    $childUrl = buildMenuUrl($child, $sidebarUserId, $isSuperAdmin);
                    $childHasChildren = $child->children && $child->children->count() > 0;
                    $childShouldShow = $childHasChildren || ($childUrl !== '#');
                @endphp

                @if($childShouldShow)
                    <?php
                        $childUrl = $childUrl !== '#' ? $childUrl : '#';
                        $childActive = isActiveMenuItem($child, $activeSidebarMenu ?? null, $activeSidebarRoute ?? '');
                    ?>
                    @if($childHasChildren)
                        @php
                            $childMenuId = ($child->html_id ?? 'menu') . '_' . $child->id;
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link menu-link{{ $childActive ? ' active' : '' }}" href="#{{ $childMenuId }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $childActive ? 'true' : 'false' }}">
                                @if($child->icon)
                                    <i class="{{ $child->icon }}"></i>
                                @endif
                                <span>{{ $child->name }}</span>
                            </a>
                            <div class="collapse menu-dropdown{{ $childActive ? ' show' : '' }}" id="{{ $childMenuId }}">
                                <ul class="nav nav-sm flex-column">
                                    @foreach($child->children as $grandchild)
                                        @php
                                            renderMenuItem($grandchild, $sidebarUserId, $isSuperAdmin, $activeSidebarMenu ?? null, $activeSidebarRoute ?? '');
                                        @endphp
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link{{ $childActive ? ' active' : '' }}" href="{{ $childUrl !== '#' ? $childUrl : '#' }}" style="font-size:0.85rem">
                                @if($child->icon)
                                    <i class="{{ $child->icon }} me-1"></i>
                                @endif
                                {{ $child->name }}
                            </a>
                        </li>
                    @endif
                @endif
            @endforeach
        @endif

    @elseif($menuItem->children && $menuItem->children->count())
        @php
            $menuId = ($menuItem->html_id ?? 'menu') . '_' . $menuItem->id;
            $menuActive = isActiveMenuItem($menuItem, $activeSidebarMenu ?? null, $activeSidebarRoute ?? '');
        @endphp
        <li class="nav-item">
            <a class="nav-link menu-link{{ $menuActive ? ' active' : '' }}" href="#{{ $menuId }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $menuActive ? 'true' : 'false' }}">
                @if($menuItem->icon)
                    <i class="{{ $menuItem->icon }}"></i>
                @endif
                <span>{{ $menuItem->name }}</span>
            </a>
            <div class="collapse menu-dropdown{{ $menuActive ? ' show' : '' }}" id="{{ $menuId }}">
                <ul class="nav nav-sm flex-column">
                    @foreach($menuItem->children as $child)
                        @php
                            renderMenuItem($child, $sidebarUserId, $isSuperAdmin, $activeSidebarMenu ?? null, $activeSidebarRoute ?? '');
                        @endphp
                    @endforeach
                </ul>
            </div>
        </li>

    @else
        @php
            $url = buildMenuUrl($menuItem, $sidebarUserId, $isSuperAdmin);
            $leafActive = isActiveMenuItem($menuItem, $activeSidebarMenu ?? null, $activeSidebarRoute ?? '');
        @endphp
        @if($url !== '#' || $isSuperAdmin)
        <li class="nav-item">
            <a class="nav-link menu-link{{ $leafActive ? ' active' : '' }}" href="{{ $url !== '#' ? $url : '#' }}">
                @if($menuItem->icon)
                    <i class="{{ $menuItem->icon }}"></i>
                @endif
                <span>{{ $menuItem->name }}</span>
            </a>
        </li>
        @endif

    @endif

@endforeach

</ul>

@if(Auth::check() && Auth::user()->role()->hasPermission('sa-sidebar-menus-all-access'))
    <li class="nav-item mt-3">
        <a class="nav-link menu-link text-warning"
           href="{{ Route::has('user.sa.sidebar-menus.index') ? route('user.sa.sidebar-menus.index', ['userId' => $sidebarUserId]) : '#' }}">
            <i class="ri-settings-2-line"></i>
            <span>Kelola Menu</span>
        </a>
    </li>
@endif

@endif
