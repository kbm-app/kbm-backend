<?php

namespace App\Http\Controllers;

use App\Exports\AbsensiRekapExport;
use App\Exports\KasTransaksiExport;
use App\Exports\KelasRosterExport;
use App\Exports\MuridExport;
use App\Exports\MuridTemplateExport;
use App\Exports\PengajarExport;
use App\Exports\PengajarTemplateExport;
use App\Exports\ProgramExport;
use App\Models\AbsensiMurid;
use App\Models\Kelas;
use App\Models\KasTransaksi;
use App\Models\MuridKelas;
use App\Models\Murid;
use App\Models\Musyawarah;
use App\Models\Pengajar;
use App\Models\Pertemuan;
use App\Models\Program;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    // ───────────────── MURID ─────────────────

    public function muridExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Murid::class);

        $filters  = $request->only(['search', 'status', 'kelas_id', 'usia_min', 'usia_max']);
        $filename = 'data-murid-' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new MuridExport($filters), $filename);
    }

    public function muridPdf(Request $request): Response
    {
        $this->authorize('viewAny', Murid::class);

        $filters     = $request->only(['search', 'status', 'kelas_id', 'usia_min', 'usia_max']);
        $filterLabel = $this->buildMuridFilterLabel($filters);

        $murid = Murid::with(['waliMurid', 'kelasAktif.kelas'])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('nama', 'ilike', "%{$v}%"))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['kelas_id'] ?? null, fn ($q, $v) =>
                $q->whereHas('kelasAktif', fn ($k) => $k->where('kelas_id', $v))
            )
            ->when($filters['usia_min'] ?? null, fn ($q, $v) =>
                $q->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) >= ?", [$v])
            )
            ->when($filters['usia_max'] ?? null, fn ($q, $v) =>
                $q->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) <= ?", [$v])
            )
            ->orderBy('nama')
            ->get()
            ->each(function (Murid $m) {
                $m->waliUtama = $m->waliMurid->firstWhere('is_primary', true) ?? $m->waliMurid->first();
            });

        $pdf = Pdf::loadView('pdf.murid-list', [
            'murid'       => $murid,
            'cetakOleh'   => $request->user()->name,
            'filterLabel' => $filterLabel,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('data-murid-' . now()->format('Ymd') . '.pdf');
    }

    public function muridTemplate(): BinaryFileResponse
    {
        $this->authorize('create', Murid::class);

        return Excel::download(new MuridTemplateExport(), 'template-import-murid.xlsx');
    }

    // ───────────────── PENGAJAR ─────────────────

    public function pengajarExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Pengajar::class);

        $filters  = $request->only(['search', 'is_aktif']);
        $filename = 'data-pengajar-' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new PengajarExport($filters), $filename);
    }

    public function pengajarPdf(Request $request): Response
    {
        $this->authorize('viewAny', Pengajar::class);

        $filters     = $request->only(['search', 'is_aktif']);
        $filterLabel = $this->buildPengajarFilterLabel($filters);

        $pengajar = Pengajar::with('user')
            ->when($filters['search'] ?? null, fn ($q, $v) =>
                $q->whereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$v}%"))
            )
            ->when(isset($filters['is_aktif']), fn ($q) =>
                $q->where('is_aktif', filter_var($filters['is_aktif'], FILTER_VALIDATE_BOOLEAN))
            )
            ->get()
            ->sortBy('user.name')
            ->values();

        $pdf = Pdf::loadView('pdf.pengajar-list', [
            'pengajar'    => $pengajar,
            'cetakOleh'   => $request->user()->name,
            'filterLabel' => $filterLabel,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('data-pengajar-' . now()->format('Ymd') . '.pdf');
    }

    public function pengajarTemplate(): BinaryFileResponse
    {
        $this->authorize('create', Pengajar::class);

        return Excel::download(new PengajarTemplateExport(), 'template-import-pengajar.xlsx');
    }

    // ───────────────── KAS ─────────────────

    public function kasExcel(Request $request): BinaryFileResponse
    {
        $request->validate(['kelas_id' => 'required|exists:kelas,id']);

        $kelas   = Kelas::findOrFail($request->kelas_id);
        $this->authorize('viewKasTransaksi', $kelas);

        $filters = $request->only(['bulan', 'tahun', 'kategori_id', 'jenis']);
        $periode = $this->buildPeriodeLabel($filters['bulan'] ?? null, $filters['tahun'] ?? null);
        $slug    = str_replace(' ', '-', strtolower($kelas->nama));
        $filename = "kas-{$slug}-{$periode}-" . now()->format('Ymd') . '.xlsx';

        return Excel::download(new KasTransaksiExport($kelas, $filters), $filename);
    }

    public function kasPdf(Request $request): Response
    {
        $request->validate(['kelas_id' => 'required|exists:kelas,id']);

        $kelas   = Kelas::findOrFail($request->kelas_id);
        $this->authorize('viewKasTransaksi', $kelas);

        $filters = $request->only(['bulan', 'tahun', 'kategori_id', 'jenis']);
        $filterLabel = $this->buildKasFilterLabel($filters, $kelas);

        $transaksi = KasTransaksi::with(['kategori'])
            ->where('kelas_id', $kelas->id)
            ->when($filters['bulan'] ?? null, fn ($q, $v) => $q->whereMonth('tanggal', $v))
            ->when($filters['tahun'] ?? null, fn ($q, $v) => $q->whereYear('tanggal', $v))
            ->when($filters['kategori_id'] ?? null, fn ($q, $v) => $q->where('kategori_id', $v))
            ->when($filters['jenis'] ?? null, fn ($q, $v) =>
                $q->whereHas('kategori', fn ($k) => $k->where('jenis', $v))
            )
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $totalPemasukan  = $transaksi->filter(fn ($t) => $t->kategori?->jenis === 'pemasukan')->sum('jumlah');
        $totalPengeluaran = $transaksi->filter(fn ($t) => $t->kategori?->jenis === 'pengeluaran')->sum('jumlah');
        $saldo            = $totalPemasukan - $totalPengeluaran;

        $periodeLabel = $this->buildPeriodeLabel($filters['bulan'] ?? null, $filters['tahun'] ?? null);
        $slug         = str_replace(' ', '-', strtolower($kelas->nama));

        $pdf = Pdf::loadView('pdf.kas-transaksi', [
            'kelas'           => $kelas,
            'transaksi'       => $transaksi,
            'totalPemasukan'  => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldo'           => $saldo,
            'cetakOleh'       => $request->user()->name,
            'filterLabel'     => $filterLabel,
            'periodeLabel'    => $periodeLabel,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("kas-{$slug}-{$periodeLabel}-" . now()->format('Ymd') . '.pdf');
    }

    // ───────────────── ABSENSI REKAP ─────────────────

    public function absensiRekapExcel(Request $request): BinaryFileResponse
    {
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'bulan'    => 'required|integer|min:1|max:12',
            'tahun'    => 'required|integer',
        ]);

        $kelas        = Kelas::findOrFail($request->kelas_id);
        $this->authorize('view', $kelas);

        $bulan        = (int) $request->bulan;
        $tahun        = (int) $request->tahun;
        $periodeLabel = $this->buildPeriodeLabel($bulan, $tahun);
        $slug         = str_replace(' ', '-', strtolower($kelas->nama));
        $filename     = "rekap-absensi-{$slug}-{$periodeLabel}-" . now()->format('Ymd') . '.xlsx';

        return Excel::download(new AbsensiRekapExport($kelas, $bulan, $tahun), $filename);
    }

    public function absensiRekapPdf(Request $request): Response
    {
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'bulan'    => 'required|integer|min:1|max:12',
            'tahun'    => 'required|integer',
        ]);

        $kelas = Kelas::findOrFail($request->kelas_id);
        $this->authorize('view', $kelas);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $pertemuanIds = Pertemuan::selesai()
            ->where('kelas_id', $kelas->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('id');

        $totalPertemuan = $pertemuanIds->count();

        $rekap = AbsensiMurid::whereIn('pertemuan_id', $pertemuanIds)
            ->with('murid')
            ->get()
            ->groupBy('murid_id')
            ->map(function ($items) use ($totalPertemuan) {
                $murid  = $items->first()->murid;
                $counts = $items->countBy('status');
                $hadir  = ($counts['hadir'] ?? 0) + ($counts['terlambat'] ?? 0);

                return [
                    'nama'            => $murid->nama,
                    'hadir'           => $counts['hadir'] ?? 0,
                    'terlambat'       => $counts['terlambat'] ?? 0,
                    'izin'            => $counts['izin'] ?? 0,
                    'sakit'           => $counts['sakit'] ?? 0,
                    'alpha'           => $counts['alpha'] ?? 0,
                    'total_pertemuan' => $totalPertemuan,
                    'persentase'      => $totalPertemuan > 0
                        ? round(($hadir / $totalPertemuan) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('hadir')
            ->values();

        $periodeLabel = $this->buildPeriodeLabel($bulan, $tahun);
        $slug         = str_replace(' ', '-', strtolower($kelas->nama));

        $pdf = Pdf::loadView('pdf.absensi-rekap', [
            'kelas'          => $kelas,
            'rekap'          => $rekap,
            'totalPertemuan' => $totalPertemuan,
            'cetakOleh'      => $request->user()->name,
            'periodeLabel'   => $periodeLabel,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("rekap-absensi-{$slug}-{$periodeLabel}-" . now()->format('Ymd') . '.pdf');
    }

    // ───────────────── KELAS ROSTER ─────────────────

    public function kelasRosterExcel(Request $request, Kelas $kelas): BinaryFileResponse
    {
        $this->authorize('view', $kelas);

        $slug     = str_replace(' ', '-', strtolower($kelas->nama));
        $filename = "roster-kelas-{$slug}-" . now()->format('Ymd') . '.xlsx';

        return Excel::download(new KelasRosterExport($kelas), $filename);
    }

    public function kelasRosterPdf(Request $request, Kelas $kelas): Response
    {
        $this->authorize('view', $kelas);

        $muridKelas = MuridKelas::with('murid')
            ->where('kelas_id', $kelas->id)
            ->aktif()
            ->get()
            ->sortBy('murid.nama')
            ->values();

        $slug = str_replace(' ', '-', strtolower($kelas->nama));

        $pdf = Pdf::loadView('pdf.kelas-roster', [
            'kelas'      => $kelas,
            'muridKelas' => $muridKelas,
            'cetakOleh'  => $request->user()->name,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("roster-kelas-{$slug}-" . now()->format('Ymd') . '.pdf');
    }

    // ───────────────── PROGRAM ─────────────────

    public function programExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Program::class);

        $filters  = $request->only(['search', 'is_aktif']);
        $filename = 'data-program-' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new ProgramExport($filters), $filename);
    }

    // ───────────────── MUSYAWARAH ─────────────────

    public function musyawarahPdf(Request $request, Musyawarah $musyawarah): Response
    {
        abort_unless($request->user()->role->value === 'super_admin', 403);

        $musyawarah->load(['laporan.kelas', 'notulensi']);

        $notulensiByKategori = $musyawarah->notulensi->groupBy('kategori');

        $bulanLabel = Carbon::createFromDate($musyawarah->tahun, $musyawarah->bulan, 1)
            ->translatedFormat('F');

        $pdf = Pdf::loadView('pdf.musyawarah-notulensi', [
            'musyawarah'         => $musyawarah,
            'laporan'            => $musyawarah->laporan,
            'notulensi'          => $musyawarah->notulensi,
            'notulensiByKategori' => $notulensiByKategori,
            'bulanLabel'         => $bulanLabel,
            'cetakOleh'          => $request->user()->name,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("notulensi-musyawarah-{$bulanLabel}-{$musyawarah->tahun}-" . now()->format('Ymd') . '.pdf');
    }

    // ───────────────── HELPERS ─────────────────

    private function buildMuridFilterLabel(array $filters): array
    {
        $labels = [];

        if (!empty($filters['search'])) {
            $labels[] = 'Cari: ' . $filters['search'];
        }
        if (!empty($filters['status'])) {
            $labels[] = 'Status: ' . ucfirst($filters['status']);
        }
        if (!empty($filters['kelas_id'])) {
            $kelas = Kelas::find($filters['kelas_id']);
            if ($kelas) {
                $labels[] = 'Kelas: ' . $kelas->nama;
            }
        }
        if (!empty($filters['usia_min']) || !empty($filters['usia_max'])) {
            $min = $filters['usia_min'] ?? '–';
            $max = $filters['usia_max'] ?? '–';
            $labels[] = "Usia: {$min}–{$max} tahun";
        }

        return $labels;
    }

    private function buildPengajarFilterLabel(array $filters): array
    {
        $labels = [];

        if (!empty($filters['search'])) {
            $labels[] = 'Cari: ' . $filters['search'];
        }
        if (isset($filters['is_aktif'])) {
            $labels[] = filter_var($filters['is_aktif'], FILTER_VALIDATE_BOOLEAN) ? 'Status: Aktif' : 'Status: Tidak Aktif';
        }

        return $labels;
    }

    private function buildKasFilterLabel(array $filters, Kelas $kelas): array
    {
        $labels   = [];
        $labels[] = 'Kelas: ' . $kelas->nama;

        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $labels[] = 'Periode: ' . $this->buildPeriodeLabel($filters['bulan'], $filters['tahun']);
        } elseif (!empty($filters['bulan'])) {
            $labels[] = 'Bulan: ' . Carbon::createFromDate(null, $filters['bulan'], 1)->translatedFormat('F');
        } elseif (!empty($filters['tahun'])) {
            $labels[] = 'Tahun: ' . $filters['tahun'];
        }

        return $labels;
    }

    private function buildPeriodeLabel(?int $bulan, ?int $tahun): string
    {
        if ($bulan && $tahun) {
            return Carbon::createFromDate($tahun, $bulan, 1)->translatedFormat('F-Y');
        }
        if ($bulan) {
            return Carbon::createFromDate(null, $bulan, 1)->translatedFormat('F');
        }
        if ($tahun) {
            return (string) $tahun;
        }
        return now()->format('Y');
    }
}
