<?php

namespace App\Services;

use App\Models\BabKurikulum;
use App\Models\Kurikulum;
use App\Models\Materi;
use App\Models\MuridKelas;
use App\Models\ProgressMateriMurid;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KurikulumService
{
    public function duplikat(Kurikulum $asal, string $tahunAjaranBaru): Kurikulum
    {
        $sudahAda = Kurikulum::where('kelas_id', $asal->kelas_id)
            ->where('tahun_ajaran', $tahunAjaranBaru)
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages([
                'tahun_ajaran' => 'Kurikulum untuk kelas ini di tahun ajaran tersebut sudah ada.',
            ]);
        }

        return DB::transaction(function () use ($asal, $tahunAjaranBaru) {
            $kurikulumBaru = Kurikulum::create([
                'kelas_id'     => $asal->kelas_id,
                'nama'         => $asal->nama,
                'tahun_ajaran' => $tahunAjaranBaru,
                'deskripsi'    => $asal->deskripsi,
            ]);

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

    public function hitungProgressKelas(Kurikulum $kurikulum): array
    {
        $muridIds = MuridKelas::where('kelas_id', $kurikulum->kelas_id)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->pluck('murid_id');

        $totalUmum     = $kurikulum->materi()->umum()->count();
        $totalIndividu = $kurikulum->materi()->individu()->count();

        $progressRows = ProgressMateriMurid::whereIn('murid_id', $muridIds)
            ->whereHas('materi', fn ($q) => $q->where('kurikulum_id', $kurikulum->id))
            ->where('status', 'selesai')
            ->with('materi:id,tipe')
            ->get();

        $result = [];
        foreach ($muridIds as $muridId) {
            $muridProgress = $progressRows->where('murid_id', $muridId);
            $selesaiUmum     = $muridProgress->filter(fn ($p) => $p->materi?->tipe === 'umum')->count();
            $selesaiIndividu = $muridProgress->filter(fn ($p) => $p->materi?->tipe === 'individu')->count();

            $result[$muridId] = [
                'umum'      => $totalUmum > 0 ? round(($selesaiUmum / $totalUmum) * 100, 1) : 0,
                'individu'  => $totalIndividu > 0 ? round(($selesaiIndividu / $totalIndividu) * 100, 1) : 0,
                'total'     => ($totalUmum + $totalIndividu) > 0
                    ? round((($selesaiUmum + $selesaiIndividu) / ($totalUmum + $totalIndividu)) * 100, 1)
                    : 0,
            ];
        }

        return $result;
    }

    public function hitungProgressBulan(Kurikulum $kurikulum, string $bulan): array
    {
        $materiIds = $kurikulum->materi()->targetBulan($bulan)->pluck('id');
        $totalTarget = $materiIds->count();

        if ($totalTarget === 0) {
            return ['total_target' => 0, 'selesai' => 0, 'per_bab' => []];
        }

        $selesai = ProgressMateriMurid::whereIn('materi_id', $materiIds)
            ->where('status', 'selesai')
            ->distinct('materi_id')
            ->count('materi_id');

        $perBab = Materi::whereIn('id', $materiIds)
            ->with('bab:id,kode,nama')
            ->get()
            ->groupBy('bab_kurikulum_id')
            ->map(function ($items) {
                $bab    = $items->first()->bab;
                $ids    = $items->pluck('id');
                $done   = ProgressMateriMurid::whereIn('materi_id', $ids)
                    ->where('status', 'selesai')
                    ->distinct('materi_id')
                    ->count('materi_id');

                return [
                    'bab'    => $bab?->kode . ' - ' . $bab?->nama,
                    'target' => $ids->count(),
                    'selesai' => $done,
                ];
            })
            ->values();

        return [
            'total_target' => $totalTarget,
            'selesai'      => $selesai,
            'per_bab'      => $perBab,
        ];
    }

    public function selesaikanMateriUmum(Materi $materi, ?int $pertemuanId): void
    {
        if ($materi->tipe !== 'umum') {
            throw ValidationException::withMessages([
                'tipe' => 'Hanya materi umum yang bisa diselesaikan sekaligus untuk semua murid.',
            ]);
        }

        $muridIds = MuridKelas::where('kelas_id', $materi->kurikulum->kelas_id)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->pluck('murid_id');

        $now = now();
        $rows = $muridIds->map(fn ($muridId) => [
            'materi_id'       => $materi->id,
            'murid_id'        => $muridId,
            'pertemuan_id'    => $pertemuanId,
            'status'          => 'selesai',
            'tanggal_selesai' => $now->toDateString(),
            'created_at'      => $now,
            'updated_at'      => $now,
        ])->all();

        // Tidak menimpa murid yang sudah selesai lebih awal
        ProgressMateriMurid::upsert(
            $rows,
            ['materi_id', 'murid_id'],
            ['pertemuan_id', 'status', 'tanggal_selesai', 'updated_at']
        );
    }
}
