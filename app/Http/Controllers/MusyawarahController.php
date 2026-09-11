<?php

namespace App\Http\Controllers;

use App\Http\Requests\Musyawarah\StoreMusyawarahRequest;
use App\Http\Requests\Musyawarah\UpdateMusyawarahRequest;
use App\Http\Requests\Musyawarah\UpdateLaporanRequest;
use App\Http\Requests\Musyawarah\StoreNotulensiRequest;
use App\Models\LaporanMusyawarah;
use App\Models\Musyawarah;
use App\Models\NotulensiMusyawarah;
use App\Services\MusyawarahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MusyawarahController extends Controller
{
    public function __construct(private MusyawarahService $service) {}

    private function ensureSuperAdmin(): void
    {
        abort_unless(Auth::user()->role->value === 'super_admin', 403);
    }

    // --- Musyawarah ---

    public function index(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();

        $data = Musyawarah::with('createdBy:id,name')
            ->withCount('laporan')
            ->withCount('notulensi')
            ->when($request->tahun, fn ($q) => $q->where('tahun', $request->tahun))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(StoreMusyawarahRequest $request): JsonResponse
    {
        $sudahAda = Musyawarah::where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages([
                'bulan' => 'Musyawarah untuk bulan dan tahun ini sudah ada.',
            ]);
        }

        $musyawarah = DB::transaction(function () use ($request) {
            $musyawarah = Musyawarah::create([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);

            $this->service->generate($musyawarah);

            return $musyawarah;
        });

        return response()->json([
            'musyawarah' => $musyawarah->load(['laporan.kelas', 'notulensi']),
        ], 201);
    }

    public function show(Musyawarah $musyawarah): JsonResponse
    {
        $this->ensureSuperAdmin();

        $musyawarah->load([
            'laporan.kelas.kelasGuru.pengajar.user',
            'notulensi',
            'createdBy:id,name',
        ]);

        $evaluasi = $this->service->getEvaluasi($musyawarah);

        return response()->json([
            'musyawarah' => $musyawarah,
            'evaluasi'   => $evaluasi,
        ]);
    }

    public function update(UpdateMusyawarahRequest $request, Musyawarah $musyawarah): JsonResponse
    {
        $musyawarah->update($request->validated());
        return response()->json(['musyawarah' => $musyawarah]);
    }

    public function destroy(Musyawarah $musyawarah): JsonResponse
    {
        $this->ensureSuperAdmin();

        if ($musyawarah->status === 'selesai') {
            throw ValidationException::withMessages([
                'status' => 'Musyawarah yang sudah selesai tidak dapat dihapus.',
            ]);
        }

        $musyawarah->delete();
        return response()->json(null, 204);
    }

    public function selesai(Musyawarah $musyawarah): JsonResponse
    {
        $this->ensureSuperAdmin();

        if ($musyawarah->status === 'selesai') {
            throw ValidationException::withMessages([
                'status' => 'Musyawarah ini sudah ditutup.',
            ]);
        }

        $musyawarah->update(['status' => 'selesai']);
        return response()->json(['musyawarah' => $musyawarah]);
    }

    public function regenerate(Musyawarah $musyawarah): JsonResponse
    {
        $this->ensureSuperAdmin();

        $this->service->generate($musyawarah);

        return response()->json([
            'laporan' => $musyawarah->laporan()->with('kelas')->get(),
        ]);
    }

    // --- Laporan per kelas ---

    public function laporanIndex(Musyawarah $musyawarah): JsonResponse
    {
        $this->ensureSuperAdmin();

        $laporan  = $musyawarah->laporan()->with('kelas.kelasGuru.pengajar.user')->get();
        $evaluasi = $this->service->getEvaluasi($musyawarah);

        return response()->json([
            'data'    => $laporan,
            'evaluasi' => $evaluasi,
        ]);
    }

    public function laporanUpdate(UpdateLaporanRequest $request, Musyawarah $musyawarah, LaporanMusyawarah $laporan): JsonResponse
    {
        abort_if($laporan->musyawarah_id !== $musyawarah->id, 404);

        $laporan->update($request->validated());
        return response()->json(['laporan' => $laporan->load('kelas')]);
    }

    public function laporanRegenerate(Musyawarah $musyawarah, LaporanMusyawarah $laporan): JsonResponse
    {
        $this->ensureSuperAdmin();
        abort_if($laporan->musyawarah_id !== $musyawarah->id, 404);

        $laporan = $this->service->regenerateKelas($laporan);
        return response()->json(['laporan' => $laporan]);
    }

    // --- Notulensi ---

    public function notulensiIndex(Musyawarah $musyawarah): JsonResponse
    {
        $this->ensureSuperAdmin();

        return response()->json([
            'data' => $musyawarah->notulensi()->orderBy('created_at')->get(),
        ]);
    }

    public function notulensiStore(StoreNotulensiRequest $request, Musyawarah $musyawarah): JsonResponse
    {
        $notulensi = $musyawarah->notulensi()->create($request->validated());
        return response()->json(['notulensi' => $notulensi], 201);
    }

    public function notulensiUpdate(StoreNotulensiRequest $request, Musyawarah $musyawarah, NotulensiMusyawarah $notulensi): JsonResponse
    {
        abort_if($notulensi->musyawarah_id !== $musyawarah->id, 404);

        $notulensi->update($request->validated());
        return response()->json(['notulensi' => $notulensi]);
    }

    public function notulensiDestroy(Musyawarah $musyawarah, NotulensiMusyawarah $notulensi): JsonResponse
    {
        $this->ensureSuperAdmin();
        abort_if($notulensi->musyawarah_id !== $musyawarah->id, 404);

        $notulensi->delete();
        return response()->json(null, 204);
    }
}
