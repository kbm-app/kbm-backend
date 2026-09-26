<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materi extends Model
{
    protected $table = 'materi';

    protected $fillable = [
        'kurikulum_id',
        'bab_kurikulum_id',
        'sub_bab',
        'judul',
        'kompetensi',
        'metode',
        'tipe',
        'target_bulan',
        'file_url',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderBy('urutan');
    }

    public function scopeUmum(Builder $query): Builder
    {
        return $query->where('tipe', 'umum');
    }

    public function scopeIndividu(Builder $query): Builder
    {
        return $query->where('tipe', 'individu');
    }

    public function scopeTargetBulan(Builder $query, string $bulan): Builder
    {
        return $query->where('target_bulan', $bulan);
    }

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class);
    }

    public function bab(): BelongsTo
    {
        return $this->belongsTo(BabKurikulum::class, 'bab_kurikulum_id');
    }

    /** Progress per murid — hanya untuk materi individu */
    public function progress(): HasMany
    {
        return $this->hasMany(ProgressMateriMurid::class);
    }

    /** Penyampaian per kelas — hanya untuk materi umum */
    public function penyampaian(): HasMany
    {
        return $this->hasMany(PenyampaianMateri::class);
    }
}
