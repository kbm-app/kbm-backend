<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Murid extends Model
{
    use SoftDeletes;

    protected $table = 'murid';

    protected $appends = ['foto_url'];

    protected $fillable = [
        'user_id',
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'foto',
        'tanggal_masuk',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waliMurid(): HasMany
    {
        return $this->hasMany(WaliMurid::class);
    }

    public function muridKelas(): HasMany
    {
        return $this->hasMany(MuridKelas::class);
    }

    public function kelasAktif(): HasMany
    {
        return $this->hasMany(MuridKelas::class)->where('status', 'aktif')->whereNull('tanggal_keluar');
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? \Storage::disk('r2')->url($this->foto) : null;
    }
}
