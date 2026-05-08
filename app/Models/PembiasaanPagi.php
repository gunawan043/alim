<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembiasaanPagi extends Model
{
    protected $table = 'pembiasaan_pagi';

    protected $fillable = [
        'admin_book_id',
        'student_id',
        'academic_year_id',
        'semester',
        'skor_doa',
        'skor_hiwar',
        'skor_conversation',
    ];

    protected $casts = [
        'skor_doa' => 'decimal:2',
        'skor_hiwar' => 'decimal:2',
        'skor_conversation' => 'decimal:2',
    ];

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
