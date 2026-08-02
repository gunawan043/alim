<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SidebarAccess extends Model
{
    use HasFactory;

    protected $table = 'sidebar_accesses';

    protected $casts = [
        'allowed_roles' => 'array',
    ];

    /**
     * Check if a role can access this menu item.
     */
    public function canAccess(string $roleName): bool
    {
        if (empty($this->allowed_roles)) {
            return true;
        }

        return in_array($roleName, $this->allowed_roles);
    }

    /**
     * Get access record by menu key.
     */
    public static function getFor(string $menuKey): ?self
    {
        return static::where('menu_key', $menuKey)->first();
    }

    /**
     * Get allowed roles as flat array.
     */
    public function getRolesAttribute(): array
    {
        return $this->allowed_roles ?? [];
    }
}
