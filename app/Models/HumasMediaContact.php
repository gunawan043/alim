<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumasMediaContact extends Model
{
    use HasUuids;

    protected $table = 'humas_media_contacts';

    protected $fillable = ['school_id', 'name', 'institution', 'type', 'contact_person', 'phone', 'email', 'address', 'is_active', 'notes', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
