<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    use SoftDeletes;

    protected $table = 'kelas';

    protected $fillable = [
        'nama',
        'deskripsi',
        'rentang_usia_min',
        'rentang_usia_max',
        'kapasitas',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function kelasGuru(): HasMany
    {
        return $this->hasMany(KelasGuru::class);
    }

    public function muridKelas(): HasMany
    {
        return $this->hasMany(MuridKelas::class);
    }

    public function muridAktif(): HasMany
    {
        return $this->hasMany(MuridKelas::class)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar');
    }
}
