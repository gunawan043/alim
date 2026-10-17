<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Portal vendor authentication model.
 * Uses the existing `vendors` table for vendor portal login.
 */
class VendorPortalUser extends Authenticatable implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use CanResetPassword, Notifiable;

    protected $table = 'vendors';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'portal_token',
    ];

    protected $casts = [
        'last_portal_login' => 'datetime',
        'established_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getAuthIdentifierName()
    {
        return 'vendor_code';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('vendor_code', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'category_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(VendorContact::class, 'vendor_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(VendorAddress::class, 'vendor_id');
    }

    public function banks(): HasMany
    {
        return $this->hasMany(VendorBank::class, 'vendor_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class, 'vendor_id');
    }
}
