<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuridKelas extends Model
{
    protected $table = 'murid_kelas';

    protected $fillable = [
        'murid_id',
        'kelas_id',
        'tahun_ajaran',
        'tanggal_masuk',
        'tanggal_keluar',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk'  => 'date',
            'tanggal_keluar' => 'date',
        ];
    }

    public function murid(): BelongsTo
    {
        return $this->belongsTo(Murid::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif')->whereNull('tanggal_keluar');
    }
}
