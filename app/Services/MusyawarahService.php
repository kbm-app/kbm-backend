<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\LaporanMusyawarah;
use App\Models\Musyawarah;
use App\Models\MuridKelas;
use App\Models\Pertemuan;
use App\Models\PenyampaianMateri;
use App\Models\ProgressMateriMurid;
use Illuminate\Support\Collection;

class MusyawarahService
{
    private const THRESHOLD_KEHADIRAN = 60;

    public function generate(Musyawarah $musyawarah): void
    {
        $kelasIds = Kelas::where('is_aktif', true)
            ->whereHas('muridAktif')
            ->pluck('id');

        foreach ($kelasIds as $kelasId) {
            $data = $this->snapshotKelas($kelasId, $musyawarah->bulan, $musyawarah->tahun);

            LaporanMusyawarah::updateOrCreate(
                ['musyawarah_id' => $musyawarah->id, 'kelas_id' => $kelasId],
                $data
            );
        }
    }

    public function regenerateKelas(LaporanMusyawarah $laporan): LaporanMusyawarah
    {
        $musyawarah = $laporan->musyawarah;
        $data       = $this->snapshotKelas($laporan->kelas_id, $musyawarah->bulan, $musyawarah->tahun);

        $laporan->update($data);
        return $laporan->fresh('kelas');
    }

    private function snapshotKelas(int $kelasId, int $bulan, int $tahun): array
    {
        $jumlahMurid     = MuridKelas::where('kelas_id', $kelasId)->where('status', 'aktif')->whereNull('tanggal_keluar')->count();
        $kehadiranPersen = $this->hitungKehadiranRataRata($kelasId, $bulan, $tahun);
        $progress        = $this->hitungProgressKurikulum($kelasId, $bulan, $tahun);
        $kendalaMurid    = $this->generateNarasiKendalaMurid($kelasId, $bulan, $tahun);

        return [
            'snapshot_jumlah_murid'             => $jumlahMurid,
            'snapshot_kehadiran_persen'         => $kehadiranPersen,
            'snapshot_progress_persen'          => $progress['keseluruhan'],
            'snapshot_progress_umum_persen'     => $progress['umum'],
            'snapshot_progress_individu_persen' => $progress['individu'],
            'kendala_murid_auto'                => $kendalaMurid,
        ];
    }

    private function hitungKehadiranRataRata(int $kelasId, int $bulan, int $tahun): ?float
    {
        $pertemuan = $this->pertemuanSelesai($kelasId, $bulan, $tahun);
        $muridIds  = $this->muridAktifIds($kelasId);
        if ($pertemuan->isEmpty() || $muridIds->isEmpty()) {
            return null;
        }

        // Rata-rata persentase murid; murid yang baru masuk setelah periode ini (0 pertemuan) tidak dihitung
        $rekap = app(AbsensiService::class)->rekapKehadiranPerMurid($kelasId, $pertemuan, $muridIds)
            ->filter(fn ($r) => $r['total_pertemuan'] > 0);

        return $rekap->isEmpty() ? null : round($rekap->avg('persentase'), 1);
    }

    private function hitungProgressKurikulum(int $kelasId, int $bulan, int $tahun): array
    {
        $null = ['umum' => null, 'individu' => null, 'keseluruhan' => null];

        $tahunAjaran  = $bulan >= 7
            ? "{$tahun}/" . ($tahun + 1)
            : ($tahun - 1) . "/{$tahun}";
        $namaBulan    = $this->bulanIndonesia($bulan);

        $kurikulum = Kurikulum::untukKelas($kelasId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->first();

        if (!$kurikulum) {
            return $null;
        }

        $muridIds = MuridKelas::where('kelas_id', $kelasId)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->pluck('murid_id');

        // --- Progress Umum: materi target bulan ini yang sudah disampaikan di kelas ini ---
        $materiUmumIds = $kurikulum->materi()->umum()->targetBulan($namaBulan)->pluck('id');
        $totalUmum     = $materiUmumIds->count();
        $progressUmum  = null;

        if ($totalUmum > 0) {
            $selesaiUmum  = PenyampaianMateri::where('kelas_id', $kelasId)
                ->whereIn('materi_id', $materiUmumIds)
                ->count();
            $progressUmum = round(($selesaiUmum / $totalUmum) * 100, 1);
        }

        // --- Progress Individu: materi target bulan ini, dihitung per murid ---
        $materiIndividuIds = $kurikulum->materi()->individu()->targetBulan($namaBulan)->pluck('id');
        $totalIndividu     = $materiIndividuIds->count();
        $progressIndividu  = null;

        if ($totalIndividu > 0 && $muridIds->isNotEmpty()) {
            $selesaiIndividu  = ProgressMateriMurid::whereIn('materi_id', $materiIndividuIds)
                ->whereIn('murid_id', $muridIds)
                ->where('status', 'selesai')
                ->count();
            $maxPossible      = $totalIndividu * $muridIds->count();
            $progressIndividu = round(($selesaiIndividu / $maxPossible) * 100, 1);
        }

        // --- Progress Keseluruhan ---
        $keseluruhan = match (true) {
            $progressUmum !== null && $progressIndividu !== null => round(($progressUmum + $progressIndividu) / 2, 1),
            $progressUmum !== null                               => $progressUmum,
            $progressIndividu !== null                           => $progressIndividu,
            default                                              => null,
        };

        return ['umum' => $progressUmum, 'individu' => $progressIndividu, 'keseluruhan' => $keseluruhan];
    }

    /** Pertemuan selesai sebuah kelas di bulan tertentu (id & tanggal) */
    private function pertemuanSelesai(int $kelasId, int $bulan, int $tahun): Collection
    {
        return Pertemuan::selesai()
            ->where('kelas_id', $kelasId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get(['id', 'tanggal']);
    }

    private function muridAktifIds(int $kelasId): Collection
    {
        return MuridKelas::where('kelas_id', $kelasId)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->pluck('murid_id');
    }

    private function bulanIndonesia(int $n): string
    {
        return ['januari','februari','maret','april','mei','juni',
                'juli','agustus','september','oktober','november','desember'][$n - 1];
    }

    private function generateNarasiKendalaMurid(int $kelasId, int $bulan, int $tahun): ?string
    {
        $pertemuan = $this->pertemuanSelesai($kelasId, $bulan, $tahun);
        if ($pertemuan->isEmpty()) {
            return null;
        }

        // Persentase tiap murid dihitung sejak absensi pertamanya di kelas ini
        $rekap = app(AbsensiService::class)->rekapKehadiranPerMurid($kelasId, $pertemuan, $this->muridAktifIds($kelasId));

        $muridBermasalah = [];

        foreach ($rekap as $r) {
            if ($r['total_pertemuan'] > 0 && $r['persentase'] < self::THRESHOLD_KEHADIRAN) {
                $muridBermasalah[] = [
                    'nama'   => $r['nama'],
                    'persen' => $r['persentase'],
                    'hadir'  => $r['hadir'] + $r['terlambat'],
                    'alpha'  => $r['alpha'],
                    'total'  => $r['total_pertemuan'],
                ];
            }
        }

        if (empty($muridBermasalah)) {
            return null;
        }

        $jumlah = count($muridBermasalah);
        $baris  = array_map(
            fn ($m) => "- {$m['nama']} ({$m['persen']}%) — {$m['hadir']}x hadir dari {$m['total']} pertemuan, {$m['alpha']}x alpha",
            $muridBermasalah
        );

        return "Terdapat {$jumlah} murid dengan kehadiran di bawah " . self::THRESHOLD_KEHADIRAN . "%:\n" . implode("\n", $baris);
    }

    public function getEvaluasi(Musyawarah $musyawarah): array
    {
        $sebelumnya = $musyawarah->musyawarahSebelumnya();
        if (!$sebelumnya) {
            return ['per_kelas' => [], 'notulensi_open' => []];
        }

        $laporanLalu       = $sebelumnya->laporan()->with('kelas')->get();
        $laporanIniByKelas = $musyawarah->laporan()->get()->keyBy('kelas_id');

        $perKelas = $laporanLalu->map(function ($ll) use ($laporanIniByKelas) {
            $ini = $laporanIniByKelas->get($ll->kelas_id);

            return [
                'kelas_id'        => $ll->kelas_id,
                'kelas_nama'      => $ll->kelas?->nama,
                'planning_lalu'   => $ll->planning,
                'tindak_lanjut'   => $ll->tindak_lanjut,
                'delta_kehadiran' => ($ini && $ll->snapshot_kehadiran_persen !== null && $ini->snapshot_kehadiran_persen !== null)
                    ? round($ini->snapshot_kehadiran_persen - $ll->snapshot_kehadiran_persen, 1)
                    : null,
                'delta_progress'  => ($ini && $ll->snapshot_progress_persen !== null && $ini->snapshot_progress_persen !== null)
                    ? round($ini->snapshot_progress_persen - $ll->snapshot_progress_persen, 1)
                    : null,
            ];
        })->values()->all();

        $notulensiOpen = $sebelumnya->notulensi()->open()->get()->toArray();

        return [
            'per_kelas'      => $perKelas,
            'notulensi_open' => $notulensiOpen,
        ];
    }
}
