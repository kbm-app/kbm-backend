<?php

namespace App\Http\Controllers;

use App\Models\AbsensiMurid;
use App\Models\Kelas;
use App\Models\KasTransaksi;
use App\Models\Kurikulum;
use App\Models\Murid;
use App\Models\MuridKelas;
use App\Models\Program;
use App\Models\ProgressMateriMurid;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalMurid   = Murid::count();
        $muridAktif   = Murid::where('status', 'aktif')->count();
        $kelasAktif   = Kelas::where('is_aktif', true)->count();
        $programAktif = Program::where('is_aktif', true)->count();

        [$totalPemasukan, $totalPengeluaran] = $this->hitungKasTotal($user);

        $kehadiranPersen = $this->hitungKehadiranBulanIni();

        return response()->json([
            'total_murid'       => $totalMurid,
            'murid_aktif'       => $muridAktif,
            'kelas_aktif'       => $kelasAktif,
            'program_aktif'     => $programAktif,
            'total_pemasukan'   => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo_total'       => $totalPemasukan - $totalPengeluaran,
            'kehadiran_persen'  => $kehadiranPersen,
            'bulan'             => now()->month,
            'tahun'             => now()->year,
        ]);
    }

    public function chartAbsensi(Request $request): JsonResponse
    {
        $kelasId = $request->integer('kelas_id') ?: null;

        // Tren 6 bulan: % kehadiran per bulan
        $tren = [];
        for ($i = 5; $i >= 0; $i--) {
            $periode = now()->subMonths($i);
            $bulan   = $periode->month;
            $tahun   = $periode->year;

            $baseQuery = fn () => AbsensiMurid::whereHas('pertemuan', fn ($q) =>
                $q->whereMonth('tanggal', $bulan)
                  ->whereYear('tanggal', $tahun)
                  ->where('status', 'selesai')
                  ->when($kelasId, fn ($k) => $k->where('kelas_id', $kelasId))
            );

            $total = $baseQuery()->count();
            $hadir = $total > 0
                ? $baseQuery()->whereIn('status', ['hadir', 'terlambat'])->count()
                : 0;

            $tren[] = [
                'bulan'  => $periode->translatedFormat('M Y'),
                'persen' => $total > 0 ? round($hadir / $total * 100, 1) : 0,
                'total'  => $total,
                'hadir'  => $hadir,
            ];
        }

        // Distribusi status bulan ini
        $distribusi = AbsensiMurid::whereHas('pertemuan', fn ($q) =>
            $q->whereMonth('tanggal', now()->month)
              ->whereYear('tanggal', now()->year)
              ->when($kelasId, fn ($k) => $k->where('kelas_id', $kelasId))
        )
        ->selectRaw('status, COUNT(*) as jumlah')
        ->groupBy('status')
        ->pluck('jumlah', 'status');

        $statusList     = ['hadir', 'terlambat', 'izin', 'sakit', 'alpha'];
        $distribusiData = collect($statusList)->map(fn ($s) => [
            'status' => $s,
            'jumlah' => $distribusi[$s] ?? 0,
        ])->values();

        return response()->json([
            'tren'       => $tren,
            'distribusi' => $distribusiData,
        ]);
    }

    public function chartKas(Request $request): JsonResponse
    {
        $user    = $request->user();
        $kelasId = $request->integer('kelas_id') ?: null;

        $kelasQuery = Kelas::where('is_aktif', true)
            ->when($user->role->value === 'pengajar', fn ($q) =>
                $q->whereHas('kelasGuru', fn ($k) =>
                    $k->whereHas('pengajar', fn ($p) => $p->where('user_id', $user->id))
                )
            )
            ->when($kelasId, fn ($q) => $q->where('id', $kelasId));

        $kelasIds = $kelasQuery->pluck('id');

        // Total all-time (tidak dibatasi 6 bulan) untuk kartu ringkasan
        $semuaTransaksi = KasTransaksi::with('kategori')
            ->whereIn('kelas_id', $kelasIds)
            ->get();

        $totalPemasukan   = (float) $semuaTransaksi->filter(fn ($t) => $t->kategori?->jenis === 'pemasukan')->sum('jumlah');
        $totalPengeluaran = (float) $semuaTransaksi->filter(fn ($t) => $t->kategori?->jenis === 'pengeluaran')->sum('jumlah');

        // Tren 6 bulan
        $tren = [];
        for ($i = 5; $i >= 0; $i--) {
            $periode = now()->subMonths($i);
            $bulan   = $periode->month;
            $tahun   = $periode->year;

            $transaksi = $semuaTransaksi
                ->filter(fn ($t) =>
                    (int) date('n', strtotime($t->tanggal)) === $bulan &&
                    (int) date('Y', strtotime($t->tanggal)) === $tahun
                );

            $pemasukan   = $transaksi->filter(fn ($t) => $t->kategori?->jenis === 'pemasukan')->sum('jumlah');
            $pengeluaran = $transaksi->filter(fn ($t) => $t->kategori?->jenis === 'pengeluaran')->sum('jumlah');

            $tren[] = [
                'bulan'       => $periode->translatedFormat('M Y'),
                'pemasukan'   => (float) $pemasukan,
                'pengeluaran' => (float) $pengeluaran,
                'saldo'       => (float) ($pemasukan - $pengeluaran),
            ];
        }

        return response()->json([
            'total_pemasukan'   => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo_total'       => $totalPemasukan - $totalPengeluaran,
            'tren'              => $tren,
        ]);
    }

    public function chartMateri(Request $request): JsonResponse
    {
        $kelasId = $request->integer('kelas_id') ?: null;

        if (!$kelasId) {
            return response()->json(null);
        }

        $bulanIni  = $this->bulanIndonesia(now()->month);
        $kurikulum = Kurikulum::where('kelas_id', $kelasId)
            ->where('tahun_ajaran', $this->currentTahunAjaran())
            ->first();

        if (!$kurikulum) {
            return response()->json(null);
        }

        $muridIds = MuridKelas::where('kelas_id', $kelasId)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->pluck('murid_id');

        // === UMUM: tren 6 bulan ===
        $tren = [];
        for ($i = 5; $i >= 0; $i--) {
            $periode   = now()->subMonths($i);
            $bulanTren = $this->bulanIndonesia($periode->month);
            $ids       = $kurikulum->materi()->umum()->targetBulan($bulanTren)->pluck('id');
            $total     = $ids->count();
            $selesai   = $total > 0
                ? ProgressMateriMurid::whereIn('materi_id', $ids)
                    ->whereIn('murid_id', $muridIds)
                    ->where('status', 'selesai')
                    ->pluck('materi_id')
                    ->unique()
                    ->count()
                : 0;

            $tren[] = [
                'bulan'   => $periode->translatedFormat('M Y'),
                'target'  => $total,
                'selesai' => $selesai,
            ];
        }

        // === UMUM: target bulan ini ===
        $materiUmumBulanIni = $kurikulum->materi()
            ->umum()
            ->targetBulan($bulanIni)
            ->with('bab:id,kode,nama')
            ->get();

        $selesaiUmum = ProgressMateriMurid::whereIn('materi_id', $materiUmumBulanIni->pluck('id'))
            ->whereIn('murid_id', $muridIds)
            ->where('status', 'selesai')
            ->pluck('materi_id')
            ->unique();

        $umumPerBab = $materiUmumBulanIni->groupBy('bab_kurikulum_id')
            ->map(function ($items) use ($selesaiUmum) {
                $bab = $items->first()->bab;
                $ids = $items->pluck('id');
                return [
                    'bab_kode' => $bab?->kode,
                    'bab_nama' => $bab?->nama,
                    'total'    => $ids->count(),
                    'selesai'  => $selesaiUmum->intersect($ids)->count(),
                ];
            })->sortBy('bab_kode')->values();

        // === UMUM: backlog (bulan-bulan lalu yang belum selesai) ===
        $bulanLalu        = $this->bulanPastDalamTahunAjaran();
        $backlogMateri    = empty($bulanLalu) ? collect() : $kurikulum->materi()
            ->umum()
            ->whereIn('target_bulan', $bulanLalu)
            ->with('bab:id,kode,nama')
            ->get();

        $selesaiBacklog = $backlogMateri->isNotEmpty()
            ? ProgressMateriMurid::whereIn('materi_id', $backlogMateri->pluck('id'))
                ->whereIn('murid_id', $muridIds)
                ->where('status', 'selesai')
                ->pluck('materi_id')
                ->unique()
            : collect();

        $backlog = $backlogMateri
            ->groupBy(fn ($m) => $m->target_bulan . '_' . $m->bab_kurikulum_id)
            ->map(function ($items) use ($selesaiBacklog) {
                $ids     = $items->pluck('id');
                $selesai = $selesaiBacklog->intersect($ids)->count();
                if ($selesai >= $ids->count()) return null;
                $bab = $items->first()->bab;
                return [
                    'bab_kode'     => $bab?->kode,
                    'bab_nama'     => $bab?->nama,
                    'bulan_target' => $items->first()->target_bulan,
                    'total'        => $ids->count(),
                    'selesai'      => $selesai,
                ];
            })
            ->filter()
            ->values();

        // === INDIVIDU: target bulan ini per murid ===
        $materiIndividuIds = $kurikulum->materi()
            ->individu()
            ->targetBulan($bulanIni)
            ->pluck('id');

        $selesaiIndividu = $materiIndividuIds->isNotEmpty()
            ? ProgressMateriMurid::whereIn('materi_id', $materiIndividuIds)
                ->whereIn('murid_id', $muridIds)
                ->where('status', 'selesai')
                ->selectRaw('murid_id, COUNT(*) as jumlah')
                ->groupBy('murid_id')
                ->pluck('jumlah', 'murid_id')
            : collect();

        $muridList      = Murid::whereIn('id', $muridIds)->orderBy('nama')->get(['id', 'nama']);
        $individuMurid  = $muridList->map(fn ($m) => [
            'id'      => $m->id,
            'nama'    => $m->nama,
            'total'   => $materiIndividuIds->count(),
            'selesai' => (int) ($selesaiIndividu[$m->id] ?? 0),
        ])->values();

        return response()->json([
            'bulan_ini' => $bulanIni,
            'umum' => [
                'tren'    => $tren,
                'per_bab' => $umumPerBab,
                'total'   => $materiUmumBulanIni->count(),
                'selesai' => $selesaiUmum->count(),
                'backlog' => $backlog,
            ],
            'individu' => [
                'total_materi' => $materiIndividuIds->count(),
                'murid'        => $individuMurid,
            ],
        ]);
    }

    private function bulanIndonesia(int $n): string
    {
        return ['januari','februari','maret','april','mei','juni',
                'juli','agustus','september','oktober','november','desember'][$n - 1];
    }

    private function bulanPastDalamTahunAjaran(): array
    {
        $ta       = $this->currentTahunAjaran();
        [$taStart, $taEnd] = explode('/', $ta);

        $result = [];
        for ($i = 1; $i <= 11; $i++) {
            $past      = now()->subMonths($i);
            $pastYear  = (int) $past->format('Y');
            $pastMonth = (int) $past->format('n');

            $inAcYear = ($pastYear === (int) $taStart && $pastMonth >= 7)
                     || ($pastYear === (int) $taEnd   && $pastMonth <= 6);

            if (!$inAcYear) break;
            $result[] = $this->bulanIndonesia($pastMonth);
        }

        return array_unique($result);
    }

    private function currentTahunAjaran(): string
    {
        $year  = (int) now()->format('Y');
        $month = (int) now()->format('n');

        return $month >= 7
            ? "{$year}/" . ($year + 1)
            : ($year - 1) . "/{$year}";
    }

    private function hitungKasTotal(User $user): array
    {
        $kelasIds = Kelas::where('is_aktif', true)
            ->when($user->role->value === 'pengajar', fn ($q) =>
                $q->whereHas('kelasGuru', fn ($k) =>
                    $k->whereHas('pengajar', fn ($p) => $p->where('user_id', $user->id))
                )
            )
            ->pluck('id');

        $transaksi = KasTransaksi::with('kategori')
            ->whereIn('kelas_id', $kelasIds)
            ->get();

        $pemasukan   = $transaksi->filter(fn ($t) => $t->kategori?->jenis === 'pemasukan')->sum('jumlah');
        $pengeluaran = $transaksi->filter(fn ($t) => $t->kategori?->jenis === 'pengeluaran')->sum('jumlah');

        return [(float) $pemasukan, (float) $pengeluaran];
    }

    private function hitungKehadiranBulanIni(): float
    {
        $bulan = now()->month;
        $tahun = now()->year;

        $total = AbsensiMurid::whereHas('pertemuan', fn ($q) =>
            $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
        )->count();

        if ($total === 0) {
            return 0.0;
        }

        $hadir = AbsensiMurid::whereHas('pertemuan', fn ($q) =>
            $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
        )->whereIn('status', ['hadir', 'terlambat'])->count();

        return round($hadir / $total * 100, 1);
    }
}
