<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiMurid extends Model
{
    protected $table = 'absensi_murid';

    protected $fillable = [
        'pertemuan_id',
        'murid_id',
        'status',
        'dicatat_oleh',
        'catatan',
    ];

    protected $appends = ['keterangan'];
    protected $hidden  = ['catatan'];

    public function getKeteranganAttribute(): ?string
    {
        return $this->catatan;
    }

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
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
