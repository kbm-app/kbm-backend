<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasTransaksi extends Model
{
    protected $table = 'kas_transaksi';

    protected $fillable = [
        'kelas_id',
        'kategori_id',
        'murid_id',
        'dicatat_oleh',
        'jumlah',
        'keterangan',
        'tanggal',
        'bukti',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah'  => 'decimal:2',
        ];
    }

    public function scopePeriodeAntara(Builder $query, string $dari, string $sampai): Builder
    {
        return $query->whereBetween('tanggal', [$dari, $sampai]);
    }

    public function scopeUntukKelas(Builder $query, int $kelasId): Builder
    {
        return $query->where('kelas_id', $kelasId);
    }

    public function isHariIni(): bool
    {
        return $this->tanggal->isToday();
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KasKategori::class, 'kategori_id');
    }

    public function murid(): BelongsTo
    {
        return $this->belongsTo(Murid::class)->withTrashed();
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh')->withTrashed();
    }
}
