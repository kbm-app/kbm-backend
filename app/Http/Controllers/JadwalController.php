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
        $this->authorize('viewAny', Jadwal::class);

        $query = Jadwal::with(['program', 'kelas', 'pengajar.user'])
            ->when($request->program_id, fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->kelas_id, fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->when($request->hari, fn ($q) => $q->where('hari', $request->hari))
            ->when($request->boolean('hanya_aktif'), fn ($q) => $q->aktif())
            ->when($request->user()->role->value === 'murid', fn ($q) =>
                $q->whereIn('kelas_id', Kelas::aksesJadwal($request->user())->select('id'))
            );

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
        $pengajarIds = $data['pengajar_ids'] ?? [];
        unset($data['pengajar_ids']);
        $this->pastikanPengajarKelas($data['kelas_id'] ?? null, $pengajarIds);

        $jadwal = Jadwal::create($data);
        $jadwal->pengajar()->sync($pengajarIds);
        return response()->json(['jadwal' => $jadwal->load(['program', 'kelas', 'pengajar.user'])], 201);
    }

    public function show(Request $request, Jadwal $jadwal): JsonResponse
    {
        $this->authorize('view', $jadwal);
        return response()->json(['jadwal' => $jadwal->load(['program', 'kelas', 'pengajar.user'])]);
    }

    public function update(UpdateJadwalRequest $request, Jadwal $jadwal): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('pengajar_ids', $data)) {
            $kelasId = array_key_exists('kelas_id', $data) ? $data['kelas_id'] : $jadwal->kelas_id;
            $this->pastikanPengajarKelas($kelasId, $data['pengajar_ids'] ?? []);
            $jadwal->pengajar()->sync($data['pengajar_ids'] ?? []);
            unset($data['pengajar_ids']);
        }
        $jadwal->update($data);
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
            'pengajar_ids'    => ['sometimes', 'nullable', 'array'],
            'pengajar_ids.*'  => ['integer', 'distinct', 'exists:pengajar,id'],
            'frekuensi'       => ['sometimes', 'in:mingguan,bulanan'],
            'minggu_ke'       => ['nullable', 'integer', 'min:1', 'max:4', 'required_if:frekuensi,bulanan'],
            'hari'            => ['sometimes', 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
            'jam_mulai'       => ['sometimes', 'date_format:H:i'],
            'jam_selesai'     => ['sometimes', 'date_format:H:i'],
            'mulai_berlaku'   => ['sometimes', 'date', 'after_or_equal:today'],
        ]);

        if (array_key_exists('pengajar_ids', $validated)) {
            $kelasId = array_key_exists('kelas_id', $validated) ? $validated['kelas_id'] : $jadwal->kelas_id;
            $this->pastikanPengajarKelas($kelasId, $validated['pengajar_ids'] ?? []);
        }

        $baru = $this->service->ganti($jadwal, $validated);
        return response()->json(['jadwal' => $baru->load(['program', 'kelas', 'pengajar.user'])], 201);
    }

    public function jadwalKelas(Request $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('viewJadwal', $kelas);

        $jadwal = $this->service->getAktif($kelas->id);
        return response()->json(['data' => $jadwal]);
    }

    public function mingguIni(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Jadwal::class);

        $jadwal = Jadwal::aktif()
            ->with(['program', 'kelas', 'pengajar.user'])
            ->when($request->program_id, fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->kelas_id, fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->when($request->user()->role->value === 'murid', fn ($q) =>
                $q->whereIn('kelas_id', Kelas::aksesJadwal($request->user())->select('id'))
            )
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

    /** Jadwal untuk satu kelas hanya boleh diampu pengajar kelas itu; jadwal semua kelas bebas. */
    private function pastikanPengajarKelas(?int $kelasId, array $pengajarIds): void
    {
        if ($kelasId && $pengajarIds) {
            Kelas::findOrFail($kelasId)->pastikanPengajarKelas($pengajarIds);
        }
    }
}
