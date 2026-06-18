<?php

namespace App\Http\Controllers;

use App\Http\Requests\Jadwal\StoreJadwalRequest;
use App\Http\Requests\Jadwal\UpdateJadwalRequest;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Services\JadwalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function __construct(private JadwalService $service) {}

    public function index(Request $request): JsonResponse
    {
        $query = Jadwal::with(['program', 'kelas', 'pengajar.user'])
            ->when($request->program_id, fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->kelas_id, fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->when($request->hari, fn ($q) => $q->where('hari', $request->hari))
            ->when($request->boolean('hanya_aktif'), fn ($q) => $q->aktif());

        return response()->json(['data' => $query->orderByRaw("
            CASE hari
                WHEN 'senin' THEN 1
                WHEN 'selasa' THEN 2
                WHEN 'rabu' THEN 3
                WHEN 'kamis' THEN 4
                WHEN 'jumat' THEN 5
                WHEN 'sabtu' THEN 6
                WHEN 'minggu' THEN 7
            END
        ")->orderBy('jam_mulai')->get()]);
    }

    public function store(StoreJadwalRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['mulai_berlaku'])) {
            $data['mulai_berlaku'] = now()->toDateString();
        }

        $jadwal = Jadwal::create($data);
        return response()->json(['jadwal' => $jadwal->load(['program', 'kelas', 'pengajar.user'])], 201);
    }

    public function show(Request $request, Jadwal $jadwal): JsonResponse
    {
        $this->authorize('view', $jadwal);
        return response()->json(['jadwal' => $jadwal->load(['program', 'kelas', 'pengajar.user'])]);
    }

    public function update(UpdateJadwalRequest $request, Jadwal $jadwal): JsonResponse
    {
        $jadwal->update($request->validated());
        return response()->json(['jadwal' => $jadwal->load(['program', 'kelas', 'pengajar.user'])]);
    }

    public function destroy(Jadwal $jadwal): JsonResponse
    {
        $this->authorize('delete', $jadwal);
        $jadwal->delete();
        return response()->json(null, 204);
    }

    public function ganti(Request $request, Jadwal $jadwal): JsonResponse
    {
        $this->authorize('update', $jadwal);

        $validated = $request->validate([
            'program_id'      => ['sometimes', 'integer', 'exists:program,id'],
            'kelas_id'        => ['nullable', 'integer', 'exists:kelas,id'],
            'pengajar_id'     => ['nullable', 'integer', 'exists:pengajar,id'],
            'hari'            => ['sometimes', 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
            'jam_mulai'       => ['sometimes', 'date_format:H:i'],
            'jam_selesai'     => ['sometimes', 'date_format:H:i'],
            'mulai_berlaku'   => ['sometimes', 'date', 'after_or_equal:today'],
        ]);

        $baru = $this->service->ganti($jadwal, $validated);
        return response()->json(['jadwal' => $baru->load(['program', 'kelas', 'pengajar.user'])], 201);
    }

    public function jadwalKelas(Request $request, Kelas $kelas): JsonResponse
    {
        $jadwal = $this->service->getAktif($kelas->id);
        return response()->json(['data' => $jadwal]);
    }

    public function mingguIni(Request $request): JsonResponse
    {
        $jadwal = Jadwal::aktif()
            ->with(['program', 'kelas', 'pengajar.user'])
            ->when($request->program_id, fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->kelas_id, fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->get();

        $urutan = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

        // Hitung minggu ke- bulan ini untuk setiap hari dalam pekan ini
        $senin = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $mingguKePerHari = collect($urutan)->mapWithKeys(
            fn ($hari, $i) => [$hari => (int) ceil($senin->copy()->addDays($i)->day / 7)]
        );

        $grouped = $jadwal->groupBy('hari');

        $result = collect($urutan)->mapWithKeys(function ($hari) use ($grouped, $mingguKePerHari) {
            $filtered = $grouped->get($hari, collect())->filter(
                fn ($j) => $j->frekuensi === 'mingguan' || $j->minggu_ke === $mingguKePerHari[$hari]
            );
            return [$hari => $filtered->sortBy('jam_mulai')->values()];
        });

        return response()->json(['data' => $result]);
    }
}
