<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationPreference extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'whatsapp_enabled',
        'push_enabled',
        'lesson_reminder',
        'late_notification',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'lesson_reminder' => 'boolean',
        'late_notification' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Preferensi pengguna; dibuat otomatis dengan nilai default bila belum ada.
     */
    public static function forUser(User $user): self
    {
        $prefs = static::firstOrCreate(['user_id' => $user->id], static::defaults());
        $missing = [];

        foreach (static::defaults() as $key => $value) {
            if ($prefs->getAttribute($key) === null) {
                $prefs->{$key} = $value;
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            $prefs->save();
        }

        return $prefs;
    }

    /**
     * Nilai default tanpa menyentuh database (untuk loop massal).
     *
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            'email_enabled' => true,
            'whatsapp_enabled' => true,
            'push_enabled' => true,
            'lesson_reminder' => true,
            'late_notification' => true,
        ];
    }
}
