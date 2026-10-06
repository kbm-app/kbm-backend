<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kelas\AssignPengajarRequest;
use App\Http\Requests\Kelas\EnrollMuridRequest;
use App\Http\Requests\Kelas\NaikKelasRequest;
use App\Http\Requests\Kelas\StoreKelasRequest;
use App\Http\Requests\Kelas\UpdateKelasRequest;
use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\MuridKelas;
use App\Models\Pengajar;
use App\Models\Murid;
use App\Models\KelasPengurus;
use App\Enums\JabatanPengurus;
use App\Services\KelasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public function __construct(private KelasService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Kelas::class);

        $user = $request->user();

        $query = Kelas::withCount(['muridAktif as murid_aktif_count'])
            ->with([
                // Semua guru kelas, pengajar utama lebih dulu
                'kelasGuru' => fn($q) => $q->orderByRaw("CASE WHEN peran = 'utama' THEN 0 ELSE 1 END")->orderBy('id')->with('pengajar.user'),
            ])
            ->when($request->search, fn($q) => $q->where('nama', 'ilike', "%{$request->search}%"))
            ->when($request->has('is_aktif'), fn($q) => $q->where('is_aktif', $request->boolean('is_aktif')));

        // Pengajar hanya melihat kelas yang diajar
        if ($user->role->value === 'pengajar') {
            $query->whereHas('kelasGuru', fn($q) =>
                $q->whereHas('pengajar', fn($p) => $p->where('user_id', $user->id))
            );
        }

        // Murid hanya melihat kelas tempat ia menjadi pengurus
        if ($user->role->value === 'murid') {
            $query->dipegangPengurus($user, array_column(JabatanPengurus::cases(), 'value'));
        }

        return response()->json($query->orderBy('nama')->paginate(20));
    }

    public function store(StoreKelasRequest $request): JsonResponse
    {
        $kelas = Kelas::create($request->validated());
        return response()->json(['kelas' => $kelas], 201);
    }

    public function show(Request $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('view', $kelas);

        $kelas->load([
            'kelasGuru.pengajar.user',
            'muridAktif.murid',
        ]);

        $kelas->loadCount(['muridAktif as murid_aktif_count']);

        return response()->json(['kelas' => $kelas]);
    }

    public function update(UpdateKelasRequest $request, Kelas $kelas): JsonResponse
    {
        $kelas->update($request->validated());
        return response()->json(['kelas' => $kelas]);
    }

    public function destroy(Kelas $kelas): JsonResponse
    {
        $this->authorize('delete', $kelas);
        $kelas->delete();
        return response()->json(null, 204);
    }

    // --- Pengajar sub-resource ---

    public function pengajarIndex(Request $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('view', $kelas);

        $query = $kelas->kelasGuru()->with('pengajar.user');

        if ($request->tahun_ajaran) {
            $query->where('tahun_ajaran', $request->tahun_ajaran);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function assignPengajar(AssignPengajarRequest $request, Kelas $kelas): JsonResponse
    {
        $kelasGuru = $this->service->assignPengajar($kelas, $request->validated());
        return response()->json(['kelas_guru' => $kelasGuru->load('pengajar.user')], 201);
    }

    public function lepaskanPengajar(Request $request, Kelas $kelas, Pengajar $pengajar): JsonResponse
    {
        $this->authorize('manageGuru', $kelas);

        KelasGuru::where('kelas_id', $kelas->id)
            ->where('pengajar_id', $pengajar->id)
            ->delete();

        return response()->json(null, 204);
    }

    // --- Pengurus sub-resource ---

    public function pengurusIndex(Kelas $kelas): JsonResponse
    {
        $this->authorize('view', $kelas);

        return response()->json(['data' => $this->daftarPengurus($kelas)]);
    }

    public function assignPengurus(Request $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('managePengurus', $kelas);

        $data = $request->validate([
            'murid_id' => [
                'required',
                Rule::exists('murid_kelas', 'murid_id')
                    ->where('kelas_id', $kelas->id)
                    ->where('status', 'aktif')
                    ->whereNull('tanggal_keluar'),
            ],
            'jabatan'  => ['required', Rule::enum(JabatanPengurus::class)],
        ], [
            'murid_id.exists' => 'Murid harus terdaftar aktif di kelas ini.',
        ]);

        $sudahAda = $kelas->pengurus()
            ->where('murid_id', $data['murid_id'])
            ->where('jabatan', $data['jabatan'])
            ->exists();
        if ($sudahAda) {
            return response()->json(['message' => 'Murid ini sudah memegang jabatan tersebut.'], 422);
        }

        $kelas->pengurus()->create($data);

        return response()->json(['data' => $this->daftarPengurus($kelas)], 201);
    }

    public function lepaskanPengurus(Kelas $kelas, KelasPengurus $pengurus): JsonResponse
    {
        $this->authorize('managePengurus', $kelas);
        abort_unless($pengurus->kelas_id === $kelas->id, 404);

        $pengurus->delete();

        return response()->json(null, 204);
    }

    private function daftarPengurus(Kelas $kelas)
    {
        return $kelas->pengurus()
            ->with('murid:id,nama,user_id', 'murid.user:id,email,is_active')
            ->get()
            ->sortBy(fn ($p) => [array_search($p->jabatan, JabatanPengurus::cases()), $p->murid?->nama])
            ->values();
    }

    // --- Murid sub-resource ---

    public function muridIndex(Request $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('view', $kelas);

        $query = $kelas->muridAktif()->with('murid');

        return response()->json(['data' => $query->get()]);
    }

    public function enrollMurid(EnrollMuridRequest $request, Kelas $kelas): JsonResponse
    {
        $muridKelas = $this->service->enrollMurid($kelas, $request->validated());
        return response()->json(['murid_kelas' => $muridKelas->load('murid')], 201);
    }

    public function keluarkanMurid(Request $request, Kelas $kelas, Murid $murid): JsonResponse
    {
        $this->authorize('manageMurid', $kelas);

        MuridKelas::where('kelas_id', $kelas->id)
            ->where('murid_id', $murid->id)
            ->where('status', 'aktif')
            ->whereNull('tanggal_keluar')
            ->update([
                'status'         => 'pindah',
                'tanggal_keluar' => now()->toDateString(),
            ]);

        return response()->json(null, 204);
    }

    // --- Naik kelas ---

    public function naikKelas(NaikKelasRequest $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('manageMurid', $kelas);

        $tujuan = Kelas::findOrFail($request->kelas_tujuan_id);
        $this->service->naikKelas($kelas, $tujuan, $request->murid_ids);

        return response()->json(['message' => 'Naik kelas berhasil diproses.']);
    }
}
