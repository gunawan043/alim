<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorRecoveryCode extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'code_hash',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->id = $model->id ?: (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Buat kode pemulihan baru. Kode lama dihapus.
     *
     * @return array<int, string> kode plaintext, hanya ditampilkan sekali
     */
    public static function generateFor(User $user, int $count = 8): array
    {
        static::where('user_id', $user->id)->delete();

        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $raw = '';
            for ($j = 0; $j < 10; $j++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $code = substr($raw, 0, 5).'-'.substr($raw, 5, 5);
            $codes[] = $code;

            static::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
            ]);
        }

        return $codes;
    }

    public static function verifyFor(User $user, string $code): bool
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($code)) ?? '');

        if (strlen($normalized) !== 10) {
            return false;
        }

        $formatted = substr($normalized, 0, 5).'-'.substr($normalized, 5, 5);

        foreach (static::where('user_id', $user->id)->whereNull('used_at')->get() as $recoveryCode) {
            if (Hash::check($formatted, $recoveryCode->code_hash)) {
                $recoveryCode->forceFill(['used_at' => now()])->save();

                return true;
            }
        }

        return false;
    }
}
