{{-- config-driven sidebar renderer --}}
@php
use App\Services\SidebarMenuService;
use Illuminate\Support\Facades\Auth;

$menuService = app(SidebarMenuService::class);
$menu = $menuService->getAccessibleMenu();
$currentRoute = request()->route() ? request()->route()->getName() : '';

/**
 * Build URL for a node
 */
function menuUrl($node, $userId) {
    if (empty($node['route'])) return '#';
    $params = [];
    foreach (($node['params'] ?? []) as $k => $v) {
        if ($v === '__userId__') $params[$k] = $userId;
        elseif ($v === '__firstBookId__') $params[$k] = $node['route'] === 'user.schools.guru-mapel.w1'
            ? (app(\App\Services\SidebarMenuService::class)->findNode('buku_admin_guru') ? 'none' : 'none')
            : 'none';
    }
    try {
        return route($node['route'], $params) . ($node['query'] ?? '');
    } catch (\Exception $e) {
        return '#';
    }
}

/**
 * Check if a menu node or its children is active
 */
function isMenuActive($node, $routeName) {
    if (!$routeName) return false;
    if (!empty($node['children'])) {
        foreach ($node['children'] as $child) {
            if (isMenuActive($child, $routeName)) return true;
        }
        return false;
    }
    $nodeRoute = $node['route'] ?? '';
    if (!$nodeRoute) return false;
    // exact match
    if ($nodeRoute === $routeName) return true;
    // prefix match
    if (str_starts_with($routeName, $nodeRoute . '.')) return true;
    if (str_starts_with($nodeRoute . '.', $routeName)) return true;
    return false;
}

/**
 * Check if current route matches a specific node
 */
function isRouteActive($node, $routeName) {
    if (!$routeName || empty($node['route'])) return false;
    $r = $node['route'];
    return $r === $routeName
        || str_starts_with($routeName, $r . '.')
        || str_starts_with($r . '.', $routeName);
}

$userId = auth()->id();
@endphp

@foreach($menu as $key => $node)
    {{-- Section divider (menu title) --}}
    @if(isset($node['is_group']) && $node['is_group'])
        {{-- parent with children --}}
        @php
            $isActiveParent = isMenuActive($node, $currentRoute);
        @endphp
        <li class="nav-item">
            <a class="nav-link menu-link{{ $isActiveParent ? ' active' : '' }}"
               href="#collapse_{{$key}}"
               data-bs-toggle="collapse"
               role="button"
               aria-expanded="{{ $isActiveParent ? 'true' : 'false' }}"
               aria-controls="collapse_{{$key}}">
                @if(!empty($node['icon']))
                    <i class="{{ $node['icon'] }}"></i>
                @endif
                <span>{{ $node['label'] }}</span>
            </a>
            <div class="collapse menu-dropdown{{ $isActiveParent ? ' show' : '' }}" id="collapse_{{$key}}">
                <ul class="nav nav-sm flex-column">
                    @foreach($node['children'] as $childKey => $child)
                        @php
                            $childUrl = menuUrl($child, $userId);
                            $childActive = isRouteActive($child, $currentRoute);
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link{{ $childActive ? ' active' : '' }}"
                               href="{{ $childUrl }}"
                               style="font-size:0.85rem">
                                {{ $child['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </li>
    @else
        {{-- single menu item (no children) --}}
        @php
            $itemUrl = menuUrl($node, $userId);
            $itemActive = isRouteActive($node, $currentRoute);
        @endphp
        @if($itemUrl !== '#' || isset($node['icon']))
        <li class="nav-item">
            <a class="nav-link menu-link{{ $itemActive ? ' active' : '' }}"
               href="{{ $itemUrl }}">
                @if(!empty($node['icon']))
                    <i class="{{ $node['icon'] }}"></i>
                @endif
                <span>{{ $node['label'] }}</span>
            </a>
        </li>
        @endif
    @endif
@endforeach