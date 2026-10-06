<?php

namespace App\Services;

use App\Models\BabKurikulum;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\Materi;
use App\Models\PenyampaianMateri;
use App\Models\Pertemuan;
use App\Models\ProgressMateriMurid;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KurikulumService
{
    /**
     * Satu kelas hanya boleh memakai satu kurikulum per tahun ajaran.
     *
     * @param  int[]  $kelasIds
     */
    public function pastikanKelasBelumPunyaKurikulum(array $kelasIds, string $tahunAjaran, ?int $kecualiKurikulumId = null, string $field = 'kelas_ids'): void
    {
        $bentrok = Kelas::whereIn('id', $kelasIds)
            ->whereHas('kurikulum', fn ($q) => $q->where('tahun_ajaran', $tahunAjaran)
                ->when($kecualiKurikulumId, fn ($q) => $q->where('kurikulum.id', '!=', $kecualiKurikulumId)))
            ->pluck('nama');

        if ($bentrok->isNotEmpty()) {
            throw ValidationException::withMessages([
                $field => "{$bentrok->join(', ', ' dan ')} sudah memiliki kurikulum di tahun ajaran {$tahunAjaran}.",
            ]);
        }
    }

    public function duplikat(Kurikulum $asal, string $tahunAjaranBaru): Kurikulum
    {
        $kelasIds = $asal->kelas()->pluck('kelas.id')->all();
        $this->pastikanKelasBelumPunyaKurikulum($kelasIds, $tahunAjaranBaru, field: 'tahun_ajaran');

        return DB::transaction(function () use ($asal, $tahunAjaranBaru, $kelasIds) {
            $kurikulumBaru = Kurikulum::create([
                'nama'         => $asal->nama,
                'tahun_ajaran' => $tahunAjaranBaru,
                'deskripsi'    => $asal->deskripsi,
            ]);
            $kurikulumBaru->kelas()->sync($kelasIds);

            $babLama = $asal->bab()->with('materi')->get();
            foreach ($babLama as $bab) {
                $babBaru = BabKurikulum::create([
                    'kurikulum_id' => $kurikulumBaru->id,
                    'kode'         => $bab->kode,
                    'nama'         => $bab->nama,
                    'urutan'       => $bab->urutan,
                ]);

                foreach ($bab->materi as $materi) {
                    Materi::create([
                        'kurikulum_id'     => $kurikulumBaru->id,
                        'bab_kurikulum_id' => $babBaru->id,
                        'sub_bab'          => $materi->sub_bab,
                        'judul'            => $materi->judul,
                        'kompetensi'       => $materi->kompetensi,
                        'metode'           => $materi->metode,
                        'tipe'             => $materi->tipe,
                        'target_bulan'     => $materi->target_bulan,
                        'file_url'         => $materi->file_url,
                        'urutan'           => $materi->urutan,
                    ]);
                }
            }

            return $kurikulumBaru->load('kelas');
        });
    }

    public function hitungProgressBulan(Kurikulum $kurikulum, string $bulan): array
    {
        $materiIds = $kurikulum->materi()->targetBulan($bulan)->pluck('id');
        $totalTarget = $materiIds->count();

        if ($totalTarget === 0) {
            return ['total_target' => 0, 'selesai' => 0, 'per_bab' => []];
        }

        // Individu: selesai bila ada murid yang menyelesaikan; umum: bila sudah disampaikan di salah satu kelas
        $materiSelesaiIds = ProgressMateriMurid::whereIn('materi_id', $materiIds)
            ->where('status', 'selesai')
            ->pluck('materi_id')
            ->merge(PenyampaianMateri::whereIn('materi_id', $materiIds)->pluck('materi_id'))
            ->unique();

        $perBab = Materi::whereIn('id', $materiIds)
            ->with('bab:id,kode,nama')
            ->get()
            ->groupBy('bab_kurikulum_id')
            ->map(function ($items) use ($materiSelesaiIds) {
                $bab    = $items->first()->bab;
                $ids    = $items->pluck('id');
                $done   = $ids->intersect($materiSelesaiIds)->count();

                return [
                    'bab'    => $bab?->kode . ' - ' . $bab?->nama,
                    'target' => $ids->count(),
                    'selesai' => $done,
                ];
            })
            ->values();

        return [
            'total_target' => $totalTarget,
            'selesai'      => $materiSelesaiIds->count(),
            'per_bab'      => $perBab,
        ];
    }

    /**
     * Catat bahwa materi umum sudah disampaikan di sebuah kelas (target pengajar, bukan per murid).
     * Kelas diambil dari pertemuan, atau $kelasId, atau satu-satunya kelas pemakai kurikulum.
     * Bila sudah pernah dicatat, hanya metodenya yang diperbarui (jika dikirim).
     */
    public function catatPenyampaian(
        Materi $materi,
        ?int $pertemuanId,
        ?int $kelasId,
        ?int $userId,
        bool $setMetode = false,
        ?string $metode = null,
    ): PenyampaianMateri {
        if ($materi->tipe !== 'umum') {
            throw ValidationException::withMessages([
                'tipe' => 'Hanya materi umum yang dicatat penyampaiannya per kelas.',
            ]);
        }

        $pertemuan = $pertemuanId ? Pertemuan::find($pertemuanId) : null;
        $kelasIds  = $materi->kurikulum->kelas()->pluck('kelas.id');
        $kelasId   = $pertemuan?->kelas_id ?? $kelasId ?? ($kelasIds->count() === 1 ? $kelasIds->first() : null);

        if (! $kelasId || ! $kelasIds->contains($kelasId)) {
            throw ValidationException::withMessages([
                'kelas_id' => 'Pilih kelas yang memakai kurikulum ini.',
            ]);
        }

        $metode = $metode !== null && trim($metode) !== '' ? trim($metode) : null;

        $penyampaian = PenyampaianMateri::firstOrNew(['materi_id' => $materi->id, 'kelas_id' => $kelasId]);

        if (! $penyampaian->exists) {
            $penyampaian->fill([
                'pertemuan_id' => $pertemuan?->id,
                'tanggal'      => $pertemuan?->tanggal ?? now()->toDateString(),
                'dicatat_oleh' => $userId,
                'metode'       => $metode,
            ]);
        } elseif ($setMetode) {
            $penyampaian->metode = $metode;
        }

        $penyampaian->save();

        return $penyampaian;
    }

    public function batalkanPenyampaian(Materi $materi, int $pertemuanId): void
    {
        PenyampaianMateri::where('materi_id', $materi->id)
            ->where('pertemuan_id', $pertemuanId)
            ->delete();
    }
}
