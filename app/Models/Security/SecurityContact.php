<?php

namespace App\Models\Security;

class SecurityContact extends SecurityModel
{
    protected $table = 'security_contacts';

    public const CATEGORY_OPTIONS = [
        'internal' => 'Internal Pesantren',
        'eksternal' => 'Eksternal (Polisi/Damkar/RS)',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = ['name', 'role', 'phone', 'category', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORY_OPTIONS[$this->category] ?? $this->category;
    }
}
