<?php

namespace App\Services;

use App\Models\AbsensiMurid;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\LaporanMusyawarah;
use App\Models\Musyawarah;
use App\Models\MuridKelas;
use App\Models\Pertemuan;
use App\Models\ProgressMateriMurid;

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
        $pertemuanIds = Pertemuan::selesai()
            ->where('kelas_id', $kelasId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('id');

        $totalPertemuan = $pertemuanIds->count();
        if ($totalPertemuan === 0) {
            return null;
        }

        $muridIds = MuridKelas::where('kelas_id', $kelasId)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->pluck('murid_id');

        if ($muridIds->isEmpty()) {
            return null;
        }

        $hadirPerMurid = AbsensiMurid::whereIn('pertemuan_id', $pertemuanIds)
            ->whereIn('murid_id', $muridIds)
            ->whereIn('status', ['hadir', 'terlambat'])
            ->selectRaw('murid_id, count(*) as total')
            ->groupBy('murid_id')
            ->pluck('total', 'murid_id');

        $totalPersen = 0;
        foreach ($muridIds as $muridId) {
            $hadir = $hadirPerMurid->get($muridId, 0);
            $totalPersen += ($hadir / $totalPertemuan) * 100;
        }

        return round($totalPersen / $muridIds->count(), 1);
    }

    private function hitungProgressKurikulum(int $kelasId, int $bulan, int $tahun): array
    {
        $null = ['umum' => null, 'individu' => null, 'keseluruhan' => null];

        $tahunAjaran  = $bulan >= 7
            ? "{$tahun}/" . ($tahun + 1)
            : ($tahun - 1) . "/{$tahun}";
        $namaBulan    = $this->bulanIndonesia($bulan);

        $kurikulum = Kurikulum::where('kelas_id', $kelasId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->first();

        if (!$kurikulum) {
            return $null;
        }

        $muridIds = MuridKelas::where('kelas_id', $kelasId)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->pluck('murid_id');

        // --- Progress Umum: materi target bulan ini, selesai jika ada 1 murid yang selesai ---
        $materiUmumIds = $kurikulum->materi()->umum()->targetBulan($namaBulan)->pluck('id');
        $totalUmum     = $materiUmumIds->count();
        $progressUmum  = null;

        if ($totalUmum > 0) {
            $selesaiUmum  = ProgressMateriMurid::whereIn('materi_id', $materiUmumIds)
                ->where('status', 'selesai')
                ->distinct('materi_id')
                ->count('materi_id');
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

    private function bulanIndonesia(int $n): string
    {
        return ['januari','februari','maret','april','mei','juni',
                'juli','agustus','september','oktober','november','desember'][$n - 1];
    }

    private function generateNarasiKendalaMurid(int $kelasId, int $bulan, int $tahun): ?string
    {
        $pertemuanIds = Pertemuan::selesai()
            ->where('kelas_id', $kelasId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('id');

        $totalPertemuan = $pertemuanIds->count();
        if ($totalPertemuan === 0) {
            return null;
        }

        $muridAktif = MuridKelas::with('murid')
            ->where('kelas_id', $kelasId)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->get();

        $absensiPerMurid = AbsensiMurid::whereIn('pertemuan_id', $pertemuanIds)
            ->whereIn('murid_id', $muridAktif->pluck('murid_id'))
            ->get()
            ->groupBy('murid_id');

        $muridBermasalah = [];

        foreach ($muridAktif as $mk) {
            $absensi = $absensiPerMurid->get($mk->murid_id, collect());

            $hadir  = $absensi->whereIn('status', ['hadir', 'terlambat'])->count();
            $alpha  = $absensi->where('status', 'alpha')->count();
            $persen = round(($hadir / $totalPertemuan) * 100, 1);

            if ($persen < self::THRESHOLD_KEHADIRAN) {
                $muridBermasalah[] = [
                    'nama'   => $mk->murid->nama,
                    'persen' => $persen,
                    'hadir'  => $hadir,
                    'alpha'  => $alpha,
                ];
            }
        }

        if (empty($muridBermasalah)) {
            return null;
        }

        $jumlah = count($muridBermasalah);
        $baris  = array_map(
            fn ($m) => "- {$m['nama']} ({$m['persen']}%) — {$m['hadir']}x hadir, {$m['alpha']}x alpha",
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
