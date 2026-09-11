<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaliMurid extends Model
{
    protected $table = 'wali_murid';

    protected $fillable = [
        'user_id',
        'murid_id',
        'nama',
        'hubungan',
        'phones',
        'pekerjaan',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'phones'     => 'array',
            'is_primary' => 'boolean',
        ];
    }

    /** Nomor HP utama (elemen pertama dari `phones`), untuk kode yang masih butuh satu nomor. */
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phones[0] ?? null,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function murid(): BelongsTo
    {
        return $this->belongsTo(Murid::class);
    }
}
