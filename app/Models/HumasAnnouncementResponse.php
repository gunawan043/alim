<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumasAnnouncementResponse extends Model
{
    use HasUuids;

    protected $table = 'humas_announcement_responses';

    protected $fillable = [
        'humas_announcement_id', 'student_id', 'parent_name', 'response_type', 'message',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(HumasAnnouncement::class, 'humas_announcement_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
