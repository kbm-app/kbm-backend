<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $fillable = [
        'program_id',
        'kelas_id',
        'frekuensi',
        'minggu_ke',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'mulai_berlaku',
        'selesai_berlaku',
    ];

    protected function casts(): array
    {
        return [
            'minggu_ke'       => 'integer',
            'mulai_berlaku'   => 'date',
            'selesai_berlaku' => 'date',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('mulai_berlaku', '<=', $today)
            ->where(fn ($q) => $q->whereNull('selesai_berlaku')
                ->orWhere('selesai_berlaku', '>=', $today));
    }

    public function scopeUntukKelas(Builder $query, int $kelasId): Builder
    {
        return $query->where('kelas_id', $kelasId);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /** Semua pengajar jadwal ini; yang bertugas di tiap sesi dipilih saat buka sesi. */
    public function pengajar(): BelongsToMany
    {
        return $this->belongsToMany(Pengajar::class, 'jadwal_pengajar')->withTimestamps()->withTrashed();
    }
}
