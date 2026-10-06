<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hari libur KBM dalam rentang tanggal. Cakupannya: semua kelas (kelas_id null),
 * satu kelas, atau satu jadwal di kelas itu (jadwal_id diisi).
 */
class Libur extends Model
{
    protected $table = 'libur';

    protected $fillable = [
        'kelas_id',
        'jadwal_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai'   => 'date:Y-m-d',
            'tanggal_selesai' => 'date:Y-m-d',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh')->withTrashed();
    }

    /** Libur yang beririsan dengan rentang tanggal [dari, sampai]. */
    public function scopeBeririsan(Builder $query, string $dari, string $sampai): Builder
    {
        return $query->where('tanggal_mulai', '<=', $sampai)->where('tanggal_selesai', '>=', $dari);
    }

    /** Libur yang berlaku untuk sesi jadwal tertentu di satu kelas pada satu tanggal. */
    public function scopeUntukSesi(Builder $query, int $kelasId, ?int $jadwalId, string $tanggal): Builder
    {
        return $query->beririsan($tanggal, $tanggal)
            ->where(fn ($q) => $q->whereNull('kelas_id')->orWhere('kelas_id', $kelasId))
            ->where(fn ($q) => $q->whereNull('jadwal_id')->orWhere('jadwal_id', $jadwalId));
    }
}
