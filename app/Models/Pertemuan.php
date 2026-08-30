<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pertemuan extends Model
{
    protected $table = 'pertemuan';

    protected $fillable = [
        'jadwal_id',
        'program_id',
        'kelas_id',
        'pengajar_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'status',
        'materi',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', 'selesai');
    }

    public function scopeBerlangsung(Builder $query): Builder
    {
        return $query->where('status', 'berlangsung');
    }

    public function scopeUntukKelas(Builder $query, int $kelasId): Builder
    {
        return $query->where('kelas_id', $kelasId);
    }

    public function scopePeriodeAntara(Builder $query, string $dari, string $sampai): Builder
    {
        return $query->whereBetween('tanggal', [$dari, $sampai]);
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(Pengajar::class);
    }

    public function absensiMurid(): HasMany
    {
        return $this->hasMany(AbsensiMurid::class)->orderBy('murid_id');
    }

    public function absensiPengajar(): HasOne
    {
        return $this->hasOne(AbsensiPengajar::class);
    }
}
