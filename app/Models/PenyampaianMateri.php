<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan bahwa pengajar sebuah kelas sudah menyampaikan satu materi umum,
 * beserta metode penyampaiannya (mis. "Nasehat & Praktek").
 */
class PenyampaianMateri extends Model
{
    protected $table = 'penyampaian_materi';

    protected $fillable = [
        'materi_id',
        'kelas_id',
        'pertemuan_id',
        'metode',
        'tanggal',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
    }
}
