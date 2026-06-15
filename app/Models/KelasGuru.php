<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasGuru extends Model
{
    protected $table = 'kelas_pengajar';

    protected $fillable = [
        'kelas_id',
        'pengajar_id',
        'peran',
        'tahun_ajaran',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(Pengajar::class);
    }

    public function scopeTahunAjaran($query, string $ta)
    {
        return $query->where('tahun_ajaran', $ta);
    }
}
