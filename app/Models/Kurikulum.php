<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Kurikulum extends Model
{
    protected $table = 'kurikulum';

    protected $fillable = [
        'nama',
        'tahun_ajaran',
        'deskripsi',
    ];

    public function scopeTahunAjaran(Builder $query, string $ta): Builder
    {
        return $query->where('tahun_ajaran', $ta);
    }

    /** Kurikulum yang dipakai oleh kelas tertentu. */
    public function scopeUntukKelas(Builder $query, int $kelasId): Builder
    {
        return $query->whereHas('kelas', fn ($q) => $q->where('kelas.id', $kelasId));
    }

    /** Satu kurikulum bisa dipakai beberapa kelas (mis. Kelas 3-1 & 3-2). */
    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kurikulum_kelas')->withTimestamps();
    }

    public function bab(): HasMany
    {
        return $this->hasMany(BabKurikulum::class)->orderBy('urutan');
    }

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class)->orderBy('urutan');
    }

    public function dipakaiKelas(int $kelasId): bool
    {
        return $this->kelas()->where('kelas.id', $kelasId)->exists();
    }

    /** ID murid aktif dari semua kelas pemakai kurikulum ini, atau hanya dari $kelasId. */
    public function muridAktifIds(?int $kelasId = null): Collection
    {
        $kelasIds = $kelasId ? [$kelasId] : $this->kelas()->pluck('kelas.id');

        return MuridKelas::whereIn('kelas_id', $kelasIds)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->pluck('murid_id')
            ->unique()
            ->values();
    }
}
