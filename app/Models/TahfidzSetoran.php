<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzSetoran extends Model
{
    protected $table = 'tahfidz_setorans';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'teacher_id', 'tahfidz_group_id', 'academic_year_id',
        'setoran_date', 'week_number', 'setoran_type', 'metode_pembelajaran',
        'surah_start_id', 'surah_start_name', 'ayat_start', 'surah_end_id', 'surah_end_name', 'ayat_end',
        'juz', 'halaman_start', 'baris_start', 'halaman_end', 'baris_end', 'jumlah_halaman', 'jumlah_baris',
        'hasil_hafalan', 'khofi', 'jali', 'nilai_setoran',
        'capaian_target', 'keterangan_capaian',
        'tikrar_task', 'tikrar_repetitions', 'nilai_tikrar', 'robet_status', 'hasil_hafalan_catatan',
        'lahn_khofi_count', 'lahn_jali_count', 'nilai_lahn',
        'status', 'catatan_guru',
        'verification_status', 'verified_by', 'verified_at', 'verification_notes',
    ];

    protected $casts = [
        'setoran_date' => 'date',
        'week_number' => 'integer',
        'ayat_start' => 'integer',
        'ayat_end' => 'integer',
        'juz' => 'integer',
        'halaman_start' => 'decimal:1',
        'baris_start' => 'integer',
        'halaman_end' => 'decimal:1',
        'baris_end' => 'integer',
        'jumlah_halaman' => 'decimal:1',
        'jumlah_baris' => 'integer',
        'hasil_hafalan' => 'integer',
        'khofi' => 'integer',
        'jali' => 'integer',
        'nilai_setoran' => 'decimal:2',
        'tikrar_repetitions' => 'integer',
        'nilai_tikrar' => 'decimal:2',
        'nilai_lahn' => 'decimal:2',
        'lahn_khofi_count' => 'integer',
        'lahn_jali_count' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TahfidzGroup::class, 'tahfidz_group_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getSetoranTypeLabelAttribute(): string
    {
        return match ($this->setoran_type) {
            'ziyadah' => 'Ziyadah (Hafalan Baru)',
            'tikror' => 'Tikrar (Pengulangan Hafalan)',
            'murajaah' => 'Murajaah Tasmi’ (Hafalan Lama)',
            'robet' => 'Robet (Persiapan Materi Berikutnya)',
            default => ucfirst($this->setoran_type ?? ''),
        };
    }

    public function getMateriHafalanAttribute(): string
    {
        $start = trim((string) ($this->surah_start_name ?? ''));
        $end = trim((string) ($this->surah_end_name ?? ''));

        $materi = $start;
        if ($this->ayat_start) {
            $materi .= ':'.$this->ayat_start;
        }

        if ($end !== '' && $end !== $start) {
            $materi .= ($materi !== '' ? ' — ' : '').$end;
            if ($this->ayat_end) {
                $materi .= ':'.$this->ayat_end;
            }
        } elseif ($this->ayat_end && $this->ayat_end !== $this->ayat_start) {
            $materi .= '–'.$this->ayat_end;
        }

        if ($materi === '' && $this->juz) {
            $materi = 'Juz '.$this->juz;
        }

        if ($this->halaman_start) {
            $materi .= ($materi !== '' ? ' · ' : '').'Hal. '.$this->halaman_start;
            if ($this->halaman_end && (float) $this->halaman_end !== (float) $this->halaman_start) {
                $materi .= '–'.$this->halaman_end;
            }
            if ($this->baris_start) {
                $materi .= ' baris '.$this->baris_start;
                if ($this->baris_end && (int) $this->baris_end !== (int) $this->baris_start) {
                    $materi .= '–'.$this->baris_end;
                }
            }
        }

        return $materi !== '' ? $materi : '—';
    }

    public function getRobetStatusLabelAttribute(): string
    {
        return match ($this->robet_status) {
            'siap' => 'Siap',
            'belum_siap' => 'Belum siap',
            'perlu_diulang' => 'Perlu diulang',
            default => '—',
        };
    }

    public function getPenguasaanSymbolAttribute(): string
    {
        return match ($this->capaian_target) {
            'belum_tercapai' => '<',
            'tercapai' => '=',
            'melampaui' => '>',
            default => '—',
        };
    }

    public function getPenguasaanLabelAttribute(): string
    {
        return match ($this->capaian_target) {
            'belum_tercapai' => 'Di bawah target',
            'tercapai' => 'Sesuai target',
            'melampaui' => 'Di atas target',
            default => 'Belum dinilai',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'lulus' => 'Lulus',
            'ulang' => 'Ulang',
            'ditunda' => 'Ditunda',
            default => ucfirst($this->status ?? ''),
        };
    }
}
