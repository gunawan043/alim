<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryNotificationSetting extends Model
{
    protected $table = 'dormitory_notification_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Kanal notifikasi yang didukung. */
    public const CHANNELS = ['whatsapp', 'push', 'email', 'in_app'];

    public const CHANNEL_LABELS = [
        'whatsapp' => 'WhatsApp Wali',
        'push' => 'Push (Aplikasi Wali)',
        'email' => 'Email Wali',
        'in_app' => 'Notifikasi Sistem / Portal',
    ];

    protected $fillable = [
        'dormitory_id',
        'is_enabled',
        'h1_reminder_enabled',
        'h1_reminder_time',
        'interval_minutes',
        'max_reminders',
        'escalate_after_minutes',
        'notify_staff',
        'channels',
        'message_template',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'h1_reminder_enabled' => 'boolean',
        'notify_staff' => 'boolean',
        'interval_minutes' => 'integer',
        'max_reminders' => 'integer',
        'escalate_after_minutes' => 'integer',
        'channels' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    /**
     * Nilai default (belum tersimpan) untuk satu asrama.
     */
    public static function defaultsFor(string $dormitoryId): self
    {
        return new self([
            'dormitory_id' => $dormitoryId,
            'is_enabled' => true,
            'h1_reminder_enabled' => true,
            'h1_reminder_time' => '16:00',
            'interval_minutes' => 30,
            'max_reminders' => 3,
            'escalate_after_minutes' => 180,
            'notify_staff' => true,
            'channels' => ['whatsapp', 'push', 'in_app'],
        ]);
    }

    /**
     * Ambil pengaturan asrama; jika belum ada pakai default tanpa menulis DB.
     */
    public static function forDormitory(string $dormitoryId): self
    {
        return static::where('dormitory_id', $dormitoryId)->first()
            ?? static::defaultsFor($dormitoryId);
    }

    /**
     * @return array<int, string>
     */
    public function enabledChannels(): array
    {
        $channels = is_array($this->channels) ? $this->channels : [];

        return array_values(array_intersect(self::CHANNELS, $channels));
    }

    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->enabledChannels(), true);
    }
}
