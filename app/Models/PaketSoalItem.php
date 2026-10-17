<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaketSoalItem extends Model
{
    protected $table = 'paket_soal_items';

    public $timestamps = true;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'paket_soal_id', 'soal_id', 'urutan', 'bobot_override',
        'konten_snapshot', 'jenis_soal', 'nomor',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'nomor' => 'integer',
        'bobot_override' => 'decimal:2',
        'konten_snapshot' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class, 'paket_soal_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id');
    }

    /**
     * Isi butir final: snapshot naskah bila ada, selain itu data soal live.
     *
     * @return array<string, mixed>
     */
    public function kontenFinal(): array
    {
        if (is_array($this->konten_snapshot) && $this->konten_snapshot !== []) {
            $konten = $this->konten_snapshot;

            // Naskah lama (sebelum fitur wacana) belum menyimpan kunci ini —
            // lengkapi dari data soal agar lembar tetap menampilkan wacana.
            if (! array_key_exists('wacana', $konten)) {
                $konten['wacana'] = $this->soal?->wacanaPayload();
            }

            if (! array_key_exists('bahasa', $konten)) {
                $konten['bahasa'] = $this->soal?->bahasaEfektif();
            }

            return $konten;
        }

        $soal = $this->soal;

        return [
            'nomor' => $this->nomor ?? $this->urutan,
            'jenis' => $this->jenis_soal ?? $soal?->tipe_soal,
            'bahasa' => $soal?->bahasaEfektif(),
            'soal_id' => $soal?->id,
            'wacana' => $soal?->wacanaPayload(),
            'tp_id' => $soal?->tp_id,
            'pertanyaan' => $soal?->pertanyaan,
            'opsi' => $soal?->options->map(fn ($option) => [
                'label' => $option->label,
                'teks' => $option->teks_opsi,
                'kunci' => (bool) $option->is_correct,
            ])->values()->all() ?? [],
            'pembahasan' => $soal?->pembahasan,
            'bobot' => (float) ($this->bobot_override ?? $soal?->bobot_default ?? 0),
        ];
    }
}
