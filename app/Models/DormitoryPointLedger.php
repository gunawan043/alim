<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DormitoryPointLedger extends Model
{
    protected $table = 'dormitory_point_ledger';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['student_id','dormitory_id','academic_year_id','semester','kind','points','is_permanent','reference_type','reference_id','note','created_by'];
    protected $casts = ['points'=>'integer','is_permanent'=>'boolean'];
    protected static function booted(): void { static::creating(fn (self $m) => $m->id = $m->id ?: (string) Str::uuid()); }
    public function student() { return $this->belongsTo(Student::class); }
    public function dormitory() { return $this->belongsTo(Dormitory::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function scopeForStudent($q, string $studentId) { return $q->where('student_id', $studentId); }
    public function scopeForDormitory($q, string $dormitoryId) { return $q->where('dormitory_id', $dormitoryId); }
    public function scopeForAcademicYear($q, string $academicYearId) { return $q->where('academic_year_id', $academicYearId); }
}
