<?php

namespace App\Http\Controllers;

use App\Http\Requests\Absensi\AbsensiPengajarRequest;
use App\Http\Requests\Absensi\BukaSesiRequest;
use App\Http\Requests\Absensi\InputAbsensiBulkRequest;
use App\Http\Requests\Absensi\UpdateAbsensiMuridRequest;
use App\Http\Requests\Absensi\UpdatePertemuanRequest;
use App\Models\AbsensiMurid;
use App\Models\AbsensiPengajar;
use App\Models\Pertemuan;
use App\Services\AbsensiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PertemuanController extends Controller
{
    public function __construct(private AbsensiService $service) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Pertemuan::with(['kelas', 'program', 'pengajar.user'])
            ->withCount(['absensiMurid as total_murid'])
            ->withCount(['absensiMurid as total_hadir' => fn ($q) => $q->whereIn('status', ['hadir', 'terlambat'])])
            ->withCount(['absensiMurid as total_alpha' => fn ($q) => $q->where('status', 'alpha')])
            ->when($request->kelas_id, fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->when($request->program_id, fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->bulan, fn ($q) => $q->whereMonth('tanggal', $request->bulan))
            ->when($request->tahun, fn ($q) => $q->whereYear('tanggal', $request->tahun));

        // Pengajar hanya melihat pertemuan di kelas yang diajar
        if ($user->role->value === 'pengajar') {
            $query->whereHas('kelas.kelasGuru', fn ($q) =>
                $q->whereHas('pengajar', fn ($p) => $p->where('user_id', $user->id))
            );
        }

        return response()->json(['data' => $query->orderByDesc('tanggal')->orderByDesc('jam_mulai')->get()]);
    }

    public function store(BukaSesiRequest $request): JsonResponse
    {
        $pertemuan = $this->service->bukaSesi($request->validated());
        return response()->json(['pertemuan' => $pertemuan], 201);
    }

    public function show(Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('view', $pertemuan);

        $pertemuan->load([
            'kelas',
            'program',
            'pengajar.user',
            'jadwal',
            'absensiMurid.murid',
            'absensiPengajar.pengajar.user',
            'absensiPengajar.pengganti.user',
        ]);

        return response()->json(['pertemuan' => $pertemuan]);
    }

    public function update(UpdatePertemuanRequest $request, Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('update', $pertemuan);

        $pertemuan->update($request->validated());
        return response()->json(['pertemuan' => $pertemuan]);
    }

    public function destroy(Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('delete', $pertemuan);
        $pertemuan->delete();
        return response()->json(null, 204);
    }

    // --- Absensi Murid ---

    public function absensiIndex(Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('view', $pertemuan);

        return response()->json([
            'data' => $pertemuan->absensiMurid()->with('murid')->orderBy('murid_id')->get(),
        ]);
    }

    public function absensiBulk(InputAbsensiBulkRequest $request, Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('inputAbsensi', $pertemuan);

        $this->service->inputAbsensiBulk(
            $pertemuan,
            $request->validated('absensi'),
            $request->user()->id
        );

        return response()->json([
            'data' => $pertemuan->absensiMurid()->with('murid')->get(),
        ]);
    }

    public function absensiUpdate(UpdateAbsensiMuridRequest $request, AbsensiMurid $absensiMurid): JsonResponse
    {
        // Authorization handled by UpdateAbsensiMuridRequest (super_admin only)
        $absensiMurid->update($request->validated());
        return response()->json(['absensi' => $absensiMurid->load('murid')]);
    }

    // --- Absensi Pengajar ---

    public function absensiPengajarStore(AbsensiPengajarRequest $request, Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('inputAbsensi', $pertemuan);

        $data = array_merge($request->validated(), ['pengajar_id' => $pertemuan->pengajar_id]);

        $absensi = AbsensiPengajar::updateOrCreate(
            ['pertemuan_id' => $pertemuan->id],
            $data
        );

        return response()->json(['absensi_pengajar' => $absensi->load(['pengajar.user', 'pengganti.user'])]);
    }

    // --- Selesai / Batalkan ---

    public function selesai(Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('tutupSesi', $pertemuan);

        $pertemuan = $this->service->tutupSesi($pertemuan);
        return response()->json(['pertemuan' => $pertemuan]);
    }

    public function batalkan(Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('tutupSesi', $pertemuan);

        if ($pertemuan->status !== 'berlangsung') {
            return response()->json(['message' => 'Hanya sesi berlangsung yang bisa dibatalkan.'], 422);
        }

        $pertemuan->update(['status' => 'batal', 'jam_selesai' => now()->format('H:i')]);
        return response()->json(['pertemuan' => $pertemuan]);
    }

    // --- Rekap ---

    public function rekapMurid(Request $request): JsonResponse
    {
        $this->authorize('viewRekap', Pertemuan::class);

        $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'bulan'    => ['required', 'integer', 'min:1', 'max:12'],
            'tahun'    => ['required', 'integer', 'min:2020'],
        ]);

        $pertemuanIds = Pertemuan::selesai()
            ->where('kelas_id', $request->kelas_id)
            ->whereMonth('tanggal', $request->bulan)
            ->whereYear('tanggal', $request->tahun)
            ->pluck('id');

        $totalPertemuan = $pertemuanIds->count();

        $rekap = AbsensiMurid::whereIn('pertemuan_id', $pertemuanIds)
            ->with('murid')
            ->get()
            ->groupBy('murid_id')
            ->map(function ($items) use ($totalPertemuan) {
                $murid = $items->first()->murid;
                $counts = $items->countBy('status');
                $hadir = ($counts['hadir'] ?? 0) + ($counts['terlambat'] ?? 0);

                return [
                    'murid_id'        => $murid->id,
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
            ->values();

        return response()->json([
            'data'            => $rekap,
            'total_pertemuan' => $totalPertemuan,
        ]);
    }

    public function rekapSatuMurid(Request $request, int $muridId): JsonResponse
    {
        $request->validate([
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun' => ['required', 'integer', 'min:2020'],
        ]);

        $persentase = $this->service->hitungPersentaseKehadiran($muridId, $request->bulan, $request->tahun);

        $absensi = AbsensiMurid::where('murid_id', $muridId)
            ->whereHas('pertemuan', fn ($q) => $q
                ->selesai()
                ->whereMonth('tanggal', $request->bulan)
                ->whereYear('tanggal', $request->tahun)
            )
            ->with(['pertemuan.kelas', 'pertemuan.program'])
            ->get();

        return response()->json([
            'persentase' => $persentase,
            'data'       => $absensi,
        ]);
    }
}
