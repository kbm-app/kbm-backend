<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kurikulum\DuplikatKurikulumRequest;
use App\Http\Requests\Kurikulum\StoreKurikulumRequest;
use App\Http\Requests\Kurikulum\UpdateKurikulumRequest;
use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\Kurikulum;
use App\Models\PenyampaianMateri;
use App\Services\KurikulumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class KurikulumController extends Controller
{
    public function __construct(private KurikulumService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Kurikulum::class);

        $user = $request->user();

        $query = Kurikulum::with(['kelas'])
            ->withCount('materi')
            ->when($request->kelas_id, fn ($q) => $q->untukKelas((int) $request->kelas_id))
            ->when($request->tahun_ajaran, fn ($q) => $q->where('tahun_ajaran', $request->tahun_ajaran));

        if ($user->role->value === 'pengajar') {
            $query->whereHas('kelas.kelasGuru', fn ($q) =>
                $q->whereHas('pengajar', fn ($p) => $p->where('user_id', $user->id))
            );
        }

        if ($user->role->value === 'murid') {
            $query->whereHas('kelas', fn ($q) => $q->aksesKurikulum($user));
        }

        return response()->json(['data' => $query->orderBy('tahun_ajaran', 'desc')->orderBy('nama')->get()]);
    }

    public function store(StoreKurikulumRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->service->pastikanKelasBelumPunyaKurikulum($data['kelas_ids'], $data['tahun_ajaran']);

        $kurikulum = DB::transaction(function () use ($data) {
            $kurikulum = Kurikulum::create(Arr::except($data, 'kelas_ids'));
            $kurikulum->kelas()->sync($data['kelas_ids']);
            return $kurikulum;
        });

        return response()->json(['kurikulum' => $kurikulum->load('kelas')], 201);
    }

    public function show(Request $request, Kurikulum $kurikulum): JsonResponse
    {
        $this->authorize('view', $kurikulum);

        $kurikulum->load([
            'kelas',
            'bab.materi',
        ]);

        // Kelas pemakai kurikulum yang diajar user ini — dipakai sebagai pilihan awal di tab Progres
        $kurikulum->setAttribute('kelas_diajar_ids', KelasGuru::whereIn('kelas_id', $kurikulum->kelas->pluck('id'))
            ->whereHas('pengajar', fn ($q) => $q->where('user_id', $request->user()->id))
            ->pluck('kelas_id')
            ->unique()
            ->values());

        return response()->json(['kurikulum' => $kurikulum]);
    }

    public function update(UpdateKurikulumRequest $request, Kurikulum $kurikulum): JsonResponse
    {
        $this->authorize('update', $kurikulum);

        $data = $request->validated();
        $this->service->pastikanKelasBelumPunyaKurikulum(
            $data['kelas_ids'] ?? $kurikulum->kelas()->pluck('kelas.id')->all(),
            $data['tahun_ajaran'] ?? $kurikulum->tahun_ajaran,
            $kurikulum->id,
        );

        DB::transaction(function () use ($kurikulum, $data) {
            $kurikulum->update(Arr::except($data, 'kelas_ids'));
            if (isset($data['kelas_ids'])) {
                $kurikulum->kelas()->sync($data['kelas_ids']);
            }
        });

        return response()->json(['kurikulum' => $kurikulum->load('kelas')]);
    }

    public function destroy(Kurikulum $kurikulum): JsonResponse
    {
        $this->authorize('delete', $kurikulum);

        $kurikulum->delete();
        return response()->json(null, 204);
    }

    public function duplikat(DuplikatKurikulumRequest $request, Kurikulum $kurikulum): JsonResponse
    {
        $this->authorize('create', Kurikulum::class);

        $baru = $this->service->duplikat($kurikulum, $request->validated('tahun_ajaran'));
        return response()->json(['kurikulum' => $baru->load('kelas')], 201);
    }

    public function aktifUntukKelas(Request $request, Kelas $kelas): JsonResponse
    {
        $kurikulum = Kurikulum::untukKelas($kelas->id)
            ->where('tahun_ajaran', $this->currentTahunAjaran())
            ->first();

        if (!$kurikulum) {
            return response()->json(null, 404);
        }

        $this->authorize('view', $kurikulum);

        $materiUmumIds = $kurikulum->materi()->where('tipe', 'umum')->pluck('id');

        // Materi umum yang sudah disampaikan di kelas ini
        $penyampaian = PenyampaianMateri::where('kelas_id', $kelas->id)
            ->whereIn('materi_id', $materiUmumIds)
            ->get(['materi_id', 'pertemuan_id', 'metode'])
            ->keyBy('materi_id');
        $selesaiIds = $penyampaian->keys();

        // Flag per-materi untuk sesi tertentu (opsional — dipakai di detail sesi)
        $pertemuanId     = $request->query('pertemuan_id') ? (int) $request->query('pertemuan_id') : null;
        $dicatatDiSesiIni = $pertemuanId
            ? $penyampaian->where('pertemuan_id', $pertemuanId)->keys()
            : null;

        $bab = $kurikulum->bab()
            ->with(['materi' => fn ($q) => $q->where('tipe', 'umum')->orderBy('urutan')])
            ->get()
            ->filter(fn ($b) => $b->materi->isNotEmpty())
            ->map(fn ($b) => [
                'id'          => $b->id,
                'kode'        => $b->kode,
                'nama'        => $b->nama,
                'materi_umum' => $b->materi->map(fn ($m) => [
                    'id'                  => $m->id,
                    'judul'               => $m->judul,
                    'sudah_selesai'       => $selesaiIds->contains($m->id),
                    'dicatat_di_sesi_ini' => $dicatatDiSesiIni?->contains($m->id),
                    'metode'              => $penyampaian->get($m->id)?->metode,
                ])->values(),
            ])
            ->values();

        return response()->json([
            'kurikulum_id'      => $kurikulum->id,
            'nama'              => $kurikulum->nama,
            'tahun_ajaran'      => $kurikulum->tahun_ajaran,
            'bab'               => $bab,
            'total_materi_umum' => $materiUmumIds->count(),
            'total_selesai'     => $selesaiIds->count(),
        ]);
    }

    private function currentTahunAjaran(): string
    {
        $now   = now();
        $year  = (int) $now->format('Y');
        $month = (int) $now->format('n');

        return $month >= 7
            ? "{$year}/" . ($year + 1)
            : ($year - 1) . "/{$year}";
    }
}
