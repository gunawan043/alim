<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtkRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'gtk_request_id',
        'item_type',
        'jabatan',
        'kebutuhan_ideal',
        'gtk_yang_ada',
        'kualifikasi_minimal',
        'kebutuhan_tambahan',
        'keterangan',
        'nupy',
        'nama',
        'tugas',
        'lembaga',
        'status_gtk',
        'tmt',
        'order',
    ];

    protected $casts = [
        'kebutuhan_ideal' => 'integer',
        'gtk_yang_ada' => 'integer',
        'kebutuhan_tambahan' => 'integer',
        'tmt' => 'date',
        'order' => 'integer',
    ];

    public function gtkRequest()
    {
        return $this->belongsTo(GtkRequest::class);
    }
}
