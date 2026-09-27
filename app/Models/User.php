<?php

namespace App\Models;

use App\Enums\JabatanPengurus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'is_active'         => 'boolean',
        ];
    }

    public function murid(): HasOne
    {
        return $this->hasOne(Murid::class);
    }

    /** Murid yang sedang memegang salah satu jabatan ini di minimal satu kelas. */
    public function punyaJabatan(JabatanPengurus ...$jabatan): bool
    {
        return $this->role === UserRole::Murid
            && KelasPengurus::berlakuUntuk($this)
                ->whereIn('jabatan', array_map(fn ($j) => $j->value, $jabatan))
                ->exists();
    }

    /** Jabatan pengurus kelas yang sedang dipegang akun ini, untuk menentukan akses di frontend. */
    public function jabatanPengurus(): array
    {
        return KelasPengurus::berlakuUntuk($this)
            ->with('kelas:id,nama')
            ->get()
            ->map(fn ($p) => [
                'kelas_id'   => $p->kelas_id,
                'kelas_nama' => $p->kelas?->nama,
                'jabatan'    => $p->jabatan->value,
            ])
            ->values()
            ->all();
    }
}
