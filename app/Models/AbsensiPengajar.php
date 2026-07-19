<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiPengajar extends Model
{
    protected $table = 'absensi_pengajar';

    protected $fillable = [
        'pertemuan_id',
        'pengajar_id',
        'pengganti_id',
        'status',
        'keterangan',
    ];

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
    }

    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(Pengajar::class);
    }

    public function pengganti(): BelongsTo
    {
        return $this->belongsTo(Pengajar::class, 'pengganti_id');
    }
}
