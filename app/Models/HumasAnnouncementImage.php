<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumasAnnouncementImage extends Model
{
    use HasUuids;

    protected $table = 'humas_announcement_images';

    protected $fillable = ['humas_announcement_id', 'path', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(HumasAnnouncement::class, 'humas_announcement_id');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? asset('storage/'.$this->path) : null;
    }
}
