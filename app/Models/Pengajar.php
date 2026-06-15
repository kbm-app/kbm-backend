<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengajar extends Model
{
    use SoftDeletes;

    protected $table = 'pengajar';

    protected $fillable = [
        'user_id',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'pendidikan_terakhir',
        'tanggal_bergabung',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir'    => 'date',
            'tanggal_bergabung' => 'date',
            'is_aktif'         => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelasGuru(): HasMany
    {
        return $this->hasMany(KelasGuru::class);
    }
}
