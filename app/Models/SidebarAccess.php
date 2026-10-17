<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SidebarAccess extends Model
{
    use HasFactory;

    protected $table = 'sidebar_accesses';

    protected $fillable = [
        'menu_key',
        'display_name',
        'allowed_roles',
    ];

    protected $casts = [
        'allowed_roles' => 'array',
    ];

    /**
     * Check if a role can access this menu item.
     */
    public function canAccess(string $roleName): bool
    {
        $roles = $this->allowed_roles ?? [];

        if (empty($roles)) {
            return true;
        }

        return in_array($roleName, $roles);
    }

    /**
     * Get access record by menu key.
     */
    public static function getFor(string $menuKey): ?self
    {
        return static::where('menu_key', $menuKey)->first();
    }

    /**
     * Get all sidebar accesses ordered by display name.
     */
    public static function listAll(): Collection
    {
        return static::orderBy('display_name')->get();
    }

    /**
     * Assign roles to this menu key.
     */
    public function assignRoles(array $roleNames): void
    {
        $this->update(['allowed_roles' => $roleNames]);
    }

    /**
     * Check if a user with given role names can access this menu.
     */
    public function canAccessByRoles(array $roleNames): bool
    {
        $allowed = $this->allowed_roles ?? [];

        if (empty($allowed)) {
            return true;
        }

        foreach ($roleNames as $roleName) {
            if (in_array($roleName, $allowed, true)) {
                return true;
            }
        }

        return false;
    }
}
