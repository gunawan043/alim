<?php

namespace App\Models;

use App\Support\StudentOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentClassHistory extends Model
{
    protected $table = 'student_class_histories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());

        // Satu santri hanya boleh punya satu riwayat kelas aktif per tahun ajaran.
        // Tahun ajaran dapat tersimpan sebagai dua baris semester, jadi cocokkan
        // berdasarkan nama tahun ajaran.
        static::saving(function (StudentClassHistory $model) {
            if (! $model->is_active || ! $model->student_id || ! $model->academic_year_id) {
                return;
            }

            $year = AcademicYear::find($model->academic_year_id);
            $yearIds = $year
                ? AcademicYear::where('name', $year->name)->pluck('id')->all()
                : [$model->academic_year_id];

            $query = static::query()
                ->where('student_id', $model->student_id)
                ->whereIn('academic_year_id', $yearIds)
                ->where('is_active', true);

            if ($model->exists) {
                $query->where('id', '!=', $model->id);
            }

            $query->update(['is_active' => false]);
        });
    }

    protected $fillable = [
        'student_id', 'study_group_id', 'academic_year_id',
        'attendance_number', 'is_active', 'notes', 'join_date', 'leave_date',
    ];

    protected $casts = [
        'attendance_number' => 'integer',
        'is_active' => 'boolean',
        'join_date' => 'date',
        'leave_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Urutkan baris riwayat kelas menurut DATA SANTRI (nama atau NIS),
     * bukan menurut attendance_number.
     *
     * attendance_number diisi mengikuti urutan input (lihat
     * BulkPromotionController/StudentMoveController: $count + 1), sehingga
     * santri yang masuk belakangan mendapat nomor acak di tengah daftar dan
     * daftar nilainya tampak berantakan — bukan menurut nama.
     */
    public function scopeOrderedByStudent(Builder $q, ?string $order = null): Builder
    {
        if ($order === StudentOrder::NIS) {
            // Urutan NIS membandingkan angka, bukan teks. Subquery ORDER BY
            // hanya boleh satu kolom, jadi angkanya dipadatkan menjadi satu
            // string berpanjang tetap agar perbandingan teks = perbandingan
            // angka (portabel SQLite & MySQL).
            return $q->orderByRaw(self::nisSortKeySql($q));
        }

        return $q->orderBy(
            Student::query()
                ->select('name')
                ->whereColumn('students.id', 'student_class_histories.student_id')
                ->limit(1)
        );
    }

    /**
     * Kunci urut NIS: digit NIS dipadatkan dengan nol di depan.
     *
     * Padding diambil dari potongan literal nol (substr) karena REPEAT ada di
     * MySQL tetapi tidak di SQLite. Hasilnya string berpanjang tetap sehingga
     * perbandingan teks sejajar dengan perbandingan angka:
     * "9" → 000...009 < "10" → 000...010.
     */
    private static function nisSortKeySql(Builder $q): string
    {
        $digits = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nis,' ',''),'/',''),'-',''),'.',''),',','')";
        $sqlite = $q->getConnection()->getDriverName() === 'sqlite';
        $len = $sqlite ? 'length' : 'CHAR_LENGTH';
        $substr = $sqlite ? 'substr' : 'SUBSTRING';
        $pad = str_repeat('0', 20);

        // Di MySQL `||` berarti OR, jadi penyambungan memakai CONCAT.
        $concat = $sqlite
            ? "{$substr}('{$pad}', 1, 20 - {$len}({$digits})) || {$digits}"
            : "CONCAT({$substr}('{$pad}', 1, 20 - {$len}({$digits})), {$digits})";

        return "(select "
            ."CASE WHEN nis IS NULL OR TRIM(nis) = '' THEN 'zzzzzzzzzzzzzzzzzzzzzz' "
            ."ELSE {$concat} END "
            ."from students where students.id = student_class_histories.student_id and students.deleted_at is null limit 1)";
    }
}
