<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catatan rapor berdasarkan predikat (Mumtaz Murtafi', Mumtaz, dst.)
 * untuk satu rombel pada TA + semester tertentu. Dipakai saat mencetak
 * rapor bila catatan wali kelas santri masih kosong.
 */
class RaporPredicateNote extends Model
{
    public const PREDICATES = [
        "Mumtaz Murtafi'",
        'Mumtaz',
        'Jayyid Jiddan',
        'Jayyid',
        'Maqbul',
        'Roosib',
    ];

    protected $table = 'rapor_predicate_notes';

    protected $fillable = [
        'school_id',
        'study_group_id',
        'academic_year_id',
        'semester',
        'predicate',
        'note',
        'updated_by',
    ];

    /**
     * Peta predikat => catatan untuk satu rombel/TA/semester.
     *
     * @return array<string, string>
     */
    public static function mapFor(
        string $studyGroupId,
        string $academicYearId,
        string $semester,
    ): array {
        return static::query()
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->whereNotNull('note')
            ->where('note', '!=', '')
            ->pluck('note', 'predicate')
            ->all();
    }
}
