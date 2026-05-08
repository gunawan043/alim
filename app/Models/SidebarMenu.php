<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SidebarMenu extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'icon',
        'route',
        'url',
        'route_params',
        'parent_id',
        'order',
        'is_group_header',
        'is_active',
        'guard_name',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SidebarMenu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SidebarMenu::class, 'parent_id')->orderBy('order');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Role::class, 'sidebar_menu_role', 'sidebar_menu_id', 'role_id');
    }

    // Scope: menu level-1 (parent_id = null) ordered
    public function scopeTopLevel($q)
    {
        return $q->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    // Scope: children of a parent, ordered
    public function scopeSubMenus($q, string $parentId)
    {
        return $q->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('order');
    }

    // Scope: only items with route or url (not group headers)
    public function scopeMenuItems($q)
    {
        return $q->where('is_group_header', false);
    }

    // Scope: accessible by given role IDs
    public function scopeAccessibleBy($q, array $roleIds)
    {
        if (empty($roleIds)) return $q->whereRaw('1=0');

        return $q->whereHas('roles', fn ($rq) => $rq->whereIn('roles.id', $roleIds))
            ->orWhereDoesntHave('roles'); // items tanpa role = untuk semua
    }
}
