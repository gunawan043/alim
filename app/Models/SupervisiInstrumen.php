<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupervisiInstrumen extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'supervisi_instrumen';

    public const JENIS_GURU_UMUM = 'guru_umum';

    public const JENIS_GURU_AGAMA = 'guru_agama';

    public const JENIS_OPTIONS = [
        self::JENIS_GURU_UMUM => 'Guru Umum',
        self::JENIS_GURU_AGAMA => 'Guru Agama',
    ];

    protected $fillable = [
        'school_id', 'nama', 'jenis', 'deskripsi', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function bagian(): HasMany
    {
        return $this->hasMany(SupervisiInstrumenBagian::class, 'instrumen_id')->orderBy('urutan');
    }
}
