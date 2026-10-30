<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class City extends Model
{
    protected $table = 'indonesia_cities';

    protected $fillable = [
        'code',
        'province_code',
        'name',
        'meta',
    ];

    public $timestamps = false;

    /**
     * Nama kota untuk dokumen: tanpa prefiks KOTA/KABUPATEN dan dalam Title Case.
     * Contoh: "KOTA MATARAM" -> "Mataram".
     */
    public function getDisplayNameAttribute(): string
    {
        return function_exists('format_city_name')
            ? format_city_name($this->name)
            : Str::title(Str::lower(trim((string) $this->name)));
    }
}
