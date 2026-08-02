<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PensionSetting extends Model
{
    protected $fillable = ['setting_key', 'setting_value', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('setting_key', $key)->first();

        return $row ? $row->setting_value : $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, (string) $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        return (bool) static::get($key, $default ? '1' : '0');
    }

    public static function allSettings(): array
    {
        return static::pluck('setting_value', 'setting_key')->toArray();
    }

    public static function updateSetting(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => (string) $value]
        );
    }
}
