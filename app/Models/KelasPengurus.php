<?php

namespace App\Models;

use App\Enums\JabatanPengurus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasPengurus extends Model
{
    protected $table = 'kelas_pengurus';

    protected $fillable = [
        'kelas_id',
        'murid_id',
        'jabatan',
    ];

    protected function casts(): array
    {
        return [
            'jabatan' => JabatanPengurus::class,
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function murid(): BelongsTo
    {
        return $this->belongsTo(Murid::class)->withTrashed();
    }

    /**
     * Jabatan milik akun user ini yang masih berlaku: murid-nya harus masih aktif
     * di kelas tersebut, jadi akses otomatis hilang saat murid keluar/pindah kelas.
     */
    public function scopeBerlakuUntuk(Builder $query, User $user): Builder
    {
        return $query->whereHas('murid', fn ($m) => $m
            ->where('user_id', $user->id)
            ->whereHas('kelasAktif', fn ($k) => $k->whereColumn('murid_kelas.kelas_id', 'kelas_pengurus.kelas_id'))
        );
    }
}
