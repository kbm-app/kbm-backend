<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\Materi;
use App\Models\Murid;
use App\Models\MuridKelas;
use App\Models\ProgressMateriMurid;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgressMateriMuridSeeder extends Seeder
{
    private const TA = '2026/2027';

    /**
     * Tingkat penyelesaian realistis per bulan (Juli 2025–Juni 2026).
     * Bulan yang sudah lewat punya completion tinggi, bulan berjalan rendah.
     */
    private array $completionRate = [
        'juli'       => 0.92,
        'agustus'    => 0.88,
        'september'  => 0.82,
        'oktober'    => 0.78,
        'november'   => 0.73,
        'desember'   => 0.88, // bulan review — completion naik
        'januari'    => 0.75,
        'februari'   => 0.70,
        'maret'      => 0.65,
        'april'      => 0.82, // bulan ujian
        'mei'        => 0.58,
        'juni'       => 0.28, // bulan berjalan
    ];

    public function run(): void
    {
        $kelasList = ['Kelas 5', 'Kelas 6', 'Kelas Pra-Remaja'];

        foreach ($kelasList as $kelasNama) {
            $kelas = Kelas::where('nama', $kelasNama)->first();
            if (! $kelas) {
                continue;
            }

            $kurikulum = Kurikulum::where('kelas_id', $kelas->id)
                ->where('tahun_ajaran', self::TA)
                ->first();
            if (! $kurikulum) {
                continue;
            }

            // Ambil murid aktif di kelas ini untuk TA ini
            $muridIds = MuridKelas::where('kelas_id', $kelas->id)
                ->where('tahun_ajaran', self::TA)
                ->where('status', 'aktif')
                ->pluck('murid_id');

            if ($muridIds->isEmpty()) {
                continue;
            }

            $materiList = Materi::where('kurikulum_id', $kurikulum->id)->get();

            foreach ($muridIds as $muridId) {
                $this->seedProgressMurid($muridId, $materiList);
            }
        }
    }

    private function seedProgressMurid(int $muridId, $materiList): void
    {
        // Setiap murid punya variasi acak ±10% dari completion rate kelas
        // sehingga tiap murid terlihat berbeda di tabel progress
        $muridOffset = (mt_rand(-10, 10)) / 100;

        $inserts = [];
        $now     = now()->toDateTimeString();

        foreach ($materiList as $materi) {
            // Skip jika progress sudah ada (idempotent)
            $exists = ProgressMateriMurid::where('materi_id', $materi->id)
                ->where('murid_id', $muridId)
                ->exists();
            if ($exists) {
                continue;
            }

            $bulan  = $materi->target_bulan;
            $rate   = ($this->completionRate[$bulan] ?? 0.5) + $muridOffset;
            $rate   = max(0.1, min(1.0, $rate));
            $roll   = mt_rand(1, 100) / 100;

            if ($roll <= $rate) {
                $status        = 'selesai';
                $tanggalSelesai = $this->tanggalSelesaiBulan($bulan);
            } elseif ($roll <= $rate + 0.15) {
                $status        = 'sedang';
                $tanggalSelesai = null;
            } else {
                $status        = 'belum';
                $tanggalSelesai = null;
            }

            $inserts[] = [
                'materi_id'      => $materi->id,
                'murid_id'       => $muridId,
                'pertemuan_id'   => null,
                'status'         => $status,
                'tanggal_selesai'=> $tanggalSelesai,
                'catatan'        => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (! empty($inserts)) {
            // Chunk agar tidak timeout pada data besar
            foreach (array_chunk($inserts, 200) as $chunk) {
                DB::table('progress_materi_murid')->insertOrIgnore($chunk);
            }
        }
    }

    /**
     * Buat tanggal selesai realistis: di pertengahan bulan yang bersangkutan.
     * Untuk bulan yang sudah lewat; untuk bulan berjalan (Juni) gunakan awal bulan.
     */
    private function tanggalSelesaiBulan(string $bulan): string
    {
        $bulanMap = [
            'juli'      => '2025-07', 'agustus'   => '2025-08', 'september' => '2025-09',
            'oktober'   => '2025-10', 'november'  => '2025-11', 'desember'  => '2025-12',
            'januari'   => '2026-01', 'februari'  => '2026-02', 'maret'     => '2026-03',
            'april'     => '2026-04', 'mei'        => '2026-05', 'juni'      => '2026-06',
        ];

        $yearMonth = $bulanMap[$bulan] ?? '2026-01';

        // Hari acak: 10–25 untuk bulan lalu, 1–17 untuk bulan berjalan (Juni 2026)
        $isBulanBerjalan = $yearMonth === '2026-06';
        $hari = $isBulanBerjalan
            ? mt_rand(1, 17)
            : mt_rand(10, 25);

        return sprintf('%s-%02d', $yearMonth, $hari);
    }
}
