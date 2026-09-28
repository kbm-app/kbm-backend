<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressMateriMurid extends Model
{
    protected $table = 'progress_materi_murid';

    protected $fillable = [
        'materi_id',
        'murid_id',
        'pertemuan_id',
        'status',
        'tanggal_selesai',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_selesai' => 'date',
        ];
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    public function murid(): BelongsTo
    {
        return $this->belongsTo(Murid::class)->withTrashed();
    }

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
    }
}
