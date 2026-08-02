<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AbsensiGtkSetting extends Model
{
    use HasUuids;

    protected $table = 'absensi_gtk_settings';

    protected $fillable = ['key', 'value', 'type', 'description'];

    public static function get(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        if (! $row) {
            return $default;
        }

        return match ($row->type) {
            'int', 'integer' => (int) $row->value,
            'bool', 'boolean' => (bool) $row->value,
            'json' => json_decode((string) $row->value, true),
            default => $row->value,
        };
    }

    public static function set(string $key, $value, string $type = 'string', ?string $description = null): void
    {
        $payload = match ($type) {
            'json' => json_encode($value),
            'bool', 'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $payload, 'type' => $type, 'description' => $description],
        );
    }
}
