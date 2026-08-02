<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtkHealthData extends Model
{
    use HasFactory;

    protected $table = 'gtk_health_data';

    protected $fillable = [
        'user_id',
        'golongan_darah',
        'tekanan_darah',
        'tinggi_badan',
        'berat_badan',
        'lingkar_kepala',
        'riwayat_penyakit',
        'alergi',
        'p3k',
        'keluhan_yang_dialami',
        // Additional vitals
        'pulse',
        'temperature',
        'waist_circumference',
        // Lab baseline
        'cholesterol_total',
        'triglycerides',
        'blood_sugar_fasting',
        'uric_acid',
        'hemoglobin',
        // Lifestyle & history
        'smoking_status',
        'medical_history',
        'ongoing_medication',
    ];

    protected $casts = [
        'tinggi_badan' => 'decimal:2',
        'berat_badan' => 'decimal:2',
        'pulse' => 'integer',
    ];

    /**
     * Latest health record for GTK (time-series data).
     */
    public function latestRecord()
    {
        return $this->hasOne(\App\Models\GtkHealthRecord::class)
            ->where('user_id', $this->user_id)
            ->orderByDesc('check_date')
            ->first();
    }

    /**
     * All health records (time-series) for this GTK.
     */
    public function healthRecords()
    {
        return $this->hasMany(\App\Models\GtkHealthRecord::class, 'user_id', 'user_id');
    }

    /**
     * Get the user that owns the health data.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
