<?php

namespace App\Models;

use App\Enums\JabatanPengurus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    use SoftDeletes;

    protected $table = 'kelas';

    protected $fillable = [
        'nama',
        'deskripsi',
        'rentang_usia_min',
        'rentang_usia_max',
        'kapasitas',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    /** Penugasan pengajar; pengajar yang sudah dihapus (soft delete) tidak ikut */
    public function kelasGuru(): HasMany
    {
        return $this->hasMany(KelasGuru::class)->whereHas('pengajar');
    }

    /** ID pengajar yang ditugaskan di kelas ini (utama maupun asisten). */
    public function pengajarIds(): Collection
    {
        return $this->kelasGuru()->pluck('pengajar_id')->unique()->values();
    }

    /**
     * Tolak pengajar yang belum ditugaskan di kelas ini.
     *
     * @param  int[]  $pengajarIds
     */
    public function pastikanPengajarKelas(array $pengajarIds, string $field = 'pengajar_ids'): void
    {
        $bukanPengajarKelas = collect($pengajarIds)->diff($this->pengajarIds());
        if ($bukanPengajarKelas->isNotEmpty()) {
            throw ValidationException::withMessages([
                $field => "Pengajar harus yang sudah ditugaskan di kelas {$this->nama}.",
            ]);
        }
    }

    public function muridKelas(): HasMany
    {
        return $this->hasMany(MuridKelas::class);
    }

    public function kurikulum(): BelongsToMany
    {
        return $this->belongsToMany(Kurikulum::class, 'kurikulum_kelas')->withTimestamps();
    }

    public function pengurus(): HasMany
    {
        return $this->hasMany(KelasPengurus::class);
    }

    public function scopeDiajarOleh(Builder $query, User $user): Builder
    {
        return $query->whereHas('kelasGuru', fn ($k) =>
            $k->whereHas('pengajar', fn ($p) => $p->where('user_id', $user->id))
        );
    }

    /** Kelas tempat akun murid ini memegang salah satu jabatan yang diberikan. */
    public function scopeDipegangPengurus(Builder $query, User $user, array $jabatan): Builder
    {
        return $query->whereHas('pengurus', fn ($p) => $p
            ->whereIn('jabatan', $jabatan)
            ->berlakuUntuk($user)
        );
    }

    /**
     * Kelas yang kasnya boleh dikelola user: super admin semua kelas,
     * pengajar kelas yang diajar, murid kelas tempat ia menjadi bendahara.
     */
    public function scopeAksesKas(Builder $query, User $user): Builder
    {
        return match ($user->role->value) {
            'super_admin' => $query,
            'pengajar'    => $query->diajarOleh($user),
            'murid'       => $query->dipegangPengurus($user, [JabatanPengurus::Bendahara->value]),
            default       => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Kelas yang sesi & absensinya boleh dikelola user: super admin semua kelas,
     * pengajar kelas yang diajar, murid kelas tempat ia menjadi ketua/penerobos.
     */
    public function scopeAksesAbsensi(Builder $query, User $user): Builder
    {
        return match ($user->role->value) {
            'super_admin' => $query,
            'pengajar'    => $query->diajarOleh($user),
            'murid'       => $query->dipegangPengurus($user, array_column(JabatanPengurus::pengelolaAbsensi(), 'value')),
            default       => $query->whereRaw('1 = 0'),
        };
    }

    /** Kelas yang kurikulum & progresnya boleh dilihat murid pengurus (ketua). */
    public function scopeAksesKurikulum(Builder $query, User $user): Builder
    {
        return $query->dipegangPengurus($user, array_column(JabatanPengurus::pelihatKurikulum(), 'value'));
    }

    /**
     * Kelas yang jadwalnya boleh dilihat murid pengurus: ketua (untuk membuka sesi)
     * dan penerobos (untuk mengingatkan jadwal & pengajar). Staf melihat semua jadwal.
     */
    public function scopeAksesJadwal(Builder $query, User $user): Builder
    {
        return match ($user->role->value) {
            'super_admin', 'pengajar' => $query,
            'murid'   => $query->dipegangPengurus($user, [
                JabatanPengurus::Ketua->value,
                JabatanPengurus::Penerobos->value,
            ]),
            default   => $query->whereRaw('1 = 0'),
        };
    }

    public function bisaKelolaAbsensi(User $user): bool
    {
        return static::whereKey($this->id)->aksesAbsensi($user)->exists();
    }

    public function bisaKelolaKas(User $user): bool
    {
        return static::whereKey($this->id)->aksesKas($user)->exists();
    }

    public function muridAktif(): HasMany
    {
        return $this->hasMany(MuridKelas::class)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar');
    }
}
