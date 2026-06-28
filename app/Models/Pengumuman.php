<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $fillable = [
        'judul',
        'konten',
        'target',
        'kelas_id',
        'dibuat_oleh',
        'terkirim_at',
        'jumlah_penerima',
    ];

    protected function casts(): array
    {
        return [
            'terkirim_at' => 'datetime',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function waLogs(): HasMany
    {
        return $this->hasMany(WaLog::class, 'referensi_id')
            ->where('tipe', 'pengumuman');
    }
}
