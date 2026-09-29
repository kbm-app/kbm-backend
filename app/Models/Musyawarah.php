<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Musyawarah extends Model
{
    protected $table = 'musyawarah';

    protected $fillable = [
        'tanggal',
        'bulan',
        'tahun',
        'status',
        'catatan_umum',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'bulan'   => 'integer',
            'tahun'   => 'integer',
        ];
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', 'selesai');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * Laporan per kelas, selalu urut jenjang: usia minimal kelas termuda dulu, lalu nama
     * (PAUD → Kelas 1 → … → Kelas 3-1 → Kelas 3-2 → … → Remaja). Tanpa ini urutan mengikuti
     * urutan baris di database dan berubah-ubah setelah laporan di-regenerate.
     */
    public function laporan(): HasMany
    {
        $kelasKolom = fn (string $kolom) => Kelas::withTrashed()
            ->select($kolom)
            ->whereColumn('kelas.id', 'laporan_musyawarah.kelas_id');

        return $this->hasMany(LaporanMusyawarah::class)
            ->orderBy($kelasKolom('rentang_usia_min'))
            ->orderBy($kelasKolom('nama'));
    }

    public function notulensi(): HasMany
    {
        return $this->hasMany(NotulensiMusyawarah::class);
    }

    public function musyawarahSebelumnya(): ?Musyawarah
    {
        $bulanSebelumnya = $this->bulan === 1 ? 12 : $this->bulan - 1;
        $tahunSebelumnya = $this->bulan === 1 ? $this->tahun - 1 : $this->tahun;

        return static::where('bulan', $bulanSebelumnya)
            ->where('tahun', $tahunSebelumnya)
            ->first();
    }
}
