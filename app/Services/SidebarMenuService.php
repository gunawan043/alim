<?php

namespace App\Services;

use App\Models\SidebarAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class SidebarMenuService
{
    protected array $config;
    protected array $accessOverrides;

    public function __construct()
    {
        $this->config = config('sidebar', []);
        $this->loadAccessOverrides();
    }

    /**
     * Load access overrides from DB (sidebar_accesses table).
     * These override whatever is in config/sidebar.php.
     */
    protected function loadAccessOverrides(): void
    {
        $this->accessOverrides = SidebarAccess::pluck('allowed_roles', 'menu_key')->toArray();
    }

    /**
     * Reload access overrides (call after saving new access).
     */
    public function reloadAccess(): void
    {
        $this->loadAccessOverrides();
    }

    /**
     * Get allowed roles for a menu key.
     * Priority: sidebar_accesses DB > config 'roles' key > default (all can access).
     */
    public function getAllowedRoles(string $menuKey): ?array
    {
        // DB override
        if (isset($this->accessOverrides[$menuKey])) {
            $roles = $this->accessOverrides[$menuKey];
            // null/empty = all roles can access
            return empty($roles) ? null : $roles;
        }

        // Config fallback
        $node = $this->findNode($menuKey);
        if ($node && isset($node['roles'])) {
            return $node['roles'];
        }

        return null; // null = all roles
    }

    /**
     * Check if current user can access a menu key.
     */
    public function canAccess(string $menuKey): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        $allowed = $this->getAllowedRoles($menuKey);

        // No restriction
        if ($allowed === null) return true;

        return $user->hasAnyRole($allowed);
    }

    /**
     * Check if parent menu should be shown (any child accessible).
     */
    public function hasAccessibleChild(string $parentKey): bool
    {
        $parent = $this->config[$parentKey] ?? null;
        if (!$parent || empty($parent['children'])) return false;

        foreach ($parent['children'] as $childKey => $child) {
            if ($this->canAccess($childKey)) return true;
        }
        return false;
    }

    /**
     * Get all menu nodes that the current user can access.
     */
    public function getAccessibleMenu(): array
    {
        return $this->filterAccessible($this->config);
    }

    protected function filterAccessible(array $items): array
    {
        $result = [];
        foreach ($items as $key => $node) {
            // Check if has children
            if (!empty($node['children'])) {
                $filteredChildren = $this->filterAccessible($node['children']);
                if (!empty($filteredChildren)) {
                    $result[$key] = $node;
                    $result[$key]['children'] = $filteredChildren;
                }
            } else {
                if ($this->canAccess($key)) {
                    $result[$key] = $node;
                }
            }
        }
        return $result;
    }

    /**
     * Find a node in config by key (supports dot notation for children).
     */
    public function findNode(string $key): ?array
    {
        // Top-level
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        // Dot notation: gtk.guru
        $parts = explode('.', $key);
        $node = $this->config[$parts[0]] ?? null;
        for ($i = 1; $i < count($parts); $i++) {
            if (!$node || !isset($node['children'][$parts[$i]])) {
                return null;
            }
            $node = $node['children'][$parts[$i]];
        }
        return $node;
    }

    /**
     * Build URL for a menu node.
     */
    public function buildUrl(array $node, ?string $userId = null): string
    {
        if (empty($node['route'])) return '#';

        $params = [];
        foreach (($node['params'] ?? []) as $k => $v) {
            if ($v === '__userId__') {
                $params[$k] = $userId;
            } elseif ($v === '__firstBookId__') {
                $params[$k] = $this->getFirstBookId();
            } else {
                $params[$k] = $v;
            }
        }

        try {
            return route($node['route'], $params) . ($node['query'] ?? '');
        } catch (\Exception $e) {
            return '#';
        }
    }

    protected function getFirstBookId()
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('GTK')) return 'none';

        $schoolId = $this->getUserSchoolId($user);
        $firstBook = \App\Models\TeacherAdminBook::withoutGlobalScope('school_context')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->first();

        return $firstBook?->id ?? 'none';
    }

    protected function getUserSchoolId($user): ?string
    {
        $employment = $user->employment ?? null;
        if ($employment && $employment->school_id) {
            return $employment->school_id;
        }

        $primaryUnit = $user->primaryWorkUnit;
        if ($primaryUnit && $primaryUnit->work_unit_id) {
            $school = \App\Models\School::where('work_unit_id', $primaryUnit->work_unit_id)
                ->active()->first();
            return $school?->id;
        }

        return null;
    }

    /**
     * Get all menu keys (flat list, including children).
     */
    public function allKeys(): array
    {
        $keys = [];
        foreach ($this->config as $key => $node) {
            $keys[] = $key;
            if (!empty($node['children'])) {
                foreach (array_keys($node['children']) as $childKey) {
                    $keys[] = $childKey;
                }
            }
        }
        return $keys;
    }

    /**
     * Get the config array.
     */
    public function all(): array
    {
        return $this->config;
    }
}