<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WaLog extends Model
{
    protected $table = 'wa_log';

    protected $fillable = [
        'tipe',
        'referensi_id',
        'nomor_tujuan',
        'nama_penerima',
        'pesan',
        'status',
        'error_message',
    ];

    public function scopeBerhasil(Builder $query): Builder
    {
        return $query->where('status', 'terkirim');
    }

    public function scopeGagal(Builder $query): Builder
    {
        return $query->where('status', 'gagal');
    }

    public function scopeUntukTipe(Builder $query, string $tipe): Builder
    {
        return $query->where('tipe', $tipe);
    }
}
