<?php

namespace App\Http\Controllers;

use App\Http\Requests\Absensi\AbsensiPengajarRequest;
use App\Http\Requests\Absensi\BukaSesiRequest;
use App\Http\Requests\Absensi\InputAbsensiBulkRequest;
use App\Http\Requests\Absensi\UpdateAbsensiMuridRequest;
use App\Http\Requests\Absensi\UpdatePertemuanRequest;
use App\Models\AbsensiMurid;
use App\Models\AbsensiPengajar;
use App\Models\Kelas;
use App\Models\Murid;
use App\Models\Pertemuan;
use App\Services\AbsensiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PertemuanController extends Controller
{
    public function __construct(private AbsensiService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Pertemuan::class);

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

        // Pengajar hanya melihat pertemuan di kelas yang diajar, ketua kelas di kelasnya
        if ($user->role->value !== 'super_admin') {
            $query->whereIn('kelas_id', Kelas::aksesAbsensi($user)->select('id'));
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

        $data = $request->validated();

        if (isset($data['jam_mulai']) || isset($data['jam_selesai'])) {
            if ($pertemuan->status !== 'selesai') {
                throw ValidationException::withMessages([
                    'jam_mulai' => 'Jam sesi hanya bisa dikoreksi pada sesi yang sudah selesai.',
                ]);
            }

            $jamMulai   = $data['jam_mulai'] ?? substr($pertemuan->jam_mulai, 0, 5);
            $jamSelesai = $data['jam_selesai'] ?? substr((string) $pertemuan->jam_selesai, 0, 5);
            if ($jamSelesai <= $jamMulai) {
                throw ValidationException::withMessages([
                    'jam_selesai' => "Jam selesai harus setelah jam mulai ({$jamMulai}).",
                ]);
            }
        }

        $pertemuan->update($data);
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
        $data = $request->validated();
        $absensiMurid->update([
            'status'       => $data['status'],
            'catatan'      => array_key_exists('keterangan', $data) ? $data['keterangan'] : $absensiMurid->catatan,
            'dicatat_oleh' => $request->user()->id,
        ]);
        return response()->json(['absensi' => $absensiMurid->load('murid')]);
    }

    public function sinkronMurid(Request $request, Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('sinkronMurid', $pertemuan);

        $ditambahkan = $this->service->sinkronMurid($pertemuan, $request->user()->id);

        return response()->json(['ditambahkan' => $ditambahkan]);
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

    public function selesai(Request $request, Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('tutupSesi', $pertemuan);

        // Jam selesai diisi manual: sesi bisa ditutup belakangan (beda jam/tanggal dari sesi berlangsung)
        $data = $request->validate([
            'jam_selesai' => ['required', 'date_format:H:i'],
        ], [
            'jam_selesai.required'    => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai: HH:MM.',
        ]);

        $pertemuan = $this->service->tutupSesi($pertemuan, $data['jam_selesai']);
        return response()->json(['pertemuan' => $pertemuan]);
    }

    public function batalkan(Pertemuan $pertemuan): JsonResponse
    {
        $this->authorize('tutupSesi', $pertemuan);

        if ($pertemuan->status !== 'berlangsung') {
            return response()->json(['message' => 'Hanya sesi berlangsung yang bisa dibatalkan.'], 422);
        }

        // Sesi yang dibatalkan tidak disimpan: dihapus beserta absensi murid & pengajarnya (FK cascade).
        // Progress materi individu & penyampaian materi umum tetap ada, pertemuan_id-nya menjadi null.
        $pertemuan->delete();

        return response()->json(null, 204);
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

        if ($request->user()->role->value === 'murid') {
            abort_unless(Kelas::findOrFail($request->kelas_id)->bisaKelolaAbsensi($request->user()), 403);
        }

        $pertemuan = Pertemuan::selesai()
            ->where('kelas_id', $request->kelas_id)
            ->whereMonth('tanggal', $request->bulan)
            ->whereYear('tanggal', $request->tahun)
            ->get(['id', 'tanggal']);

        $totalPertemuan = $pertemuan->count();

        // Persentase tiap murid dihitung sejak absensi pertamanya di kelas ini
        $rekap = $this->service->rekapKehadiranPerMurid((int) $request->kelas_id, $pertemuan)->values();

        return response()->json([
            'data'            => $rekap,
            'total_pertemuan' => $totalPertemuan,
        ]);
    }

    public function rekapSatuMurid(Request $request, int $muridId): JsonResponse
    {
        $this->authorize('view', Murid::findOrFail($muridId));

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
