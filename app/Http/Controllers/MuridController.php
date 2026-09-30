<?php

namespace App\Http\Controllers;

use App\Http\Requests\Murid\StoreMuridRequest;
use App\Http\Requests\Murid\UpdateMuridRequest;
use App\Enums\JabatanPengurus;
use App\Enums\UserRole;
use App\Mail\SetPasswordMail;
use App\Models\Kelas;
use App\Models\Murid;
use App\Models\User;
use App\Services\MuridService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class MuridController extends Controller
{
    public function __construct(private MuridService $muridService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Murid::class);

        $query = Murid::with(['waliMurid', 'kelasAktif.kelas'])
            ->when($request->search, fn($q) => $q->where('nama', 'ilike', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->boolean('tanpa_kelas'), fn($q) => $q->whereDoesntHave('kelasAktif'))
            ->when($request->kelas_id, fn($q, $v) => $q->whereHas('kelasAktif', fn($k) => $k->whereIn('kelas_id', (array) $v)))
            ->when($request->usia_min, fn($q) => $q->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) >= ?", [$request->usia_min]))
            ->when($request->usia_max, fn($q) => $q->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) <= ?", [$request->usia_max]));

        // Murid pengurus hanya bisa mencari teman sekelasnya
        $isMurid = $request->user()->role->value === 'murid';
        if ($isMurid) {
            $kelasIds = Kelas::dipegangPengurus($request->user(), array_column(JabatanPengurus::cases(), 'value'))->pluck('id');
            $query->whereHas('kelasAktif', fn($k) => $k->whereIn('kelas_id', $kelasIds));
        }

        $jenisKelaminCount = (clone $query)
            ->without(['waliMurid', 'kelasAktif.kelas'])
            ->selectRaw('jenis_kelamin, count(*) as total')
            ->groupBy('jenis_kelamin')
            ->pluck('total', 'jenis_kelamin');

        $page = $query->paginate(15);

        // Jangan bocorkan data pribadi (alamat, wali, nomor HP) ke sesama murid
        if ($isMurid) {
            $page->getCollection()->transform(fn($m) => $m->only(['id', 'nama', 'jenis_kelamin']));
        }

        return response()->json(array_merge($page->toArray(), [
            'jenis_kelamin_summary' => [
                'laki_laki' => (int) ($jenisKelaminCount['L'] ?? 0),
                'perempuan' => (int) ($jenisKelaminCount['P'] ?? 0),
            ],
        ]));
    }

    public function store(StoreMuridRequest $request): JsonResponse
    {
        $murid = $this->muridService->create($request->validated());
        return response()->json(['murid' => $murid], 201);
    }

    public function show(Murid $murid): JsonResponse
    {
        $this->authorize('view', $murid);

        return response()->json(['murid' => $murid->load('waliMurid', 'user')]);
    }

    public function update(UpdateMuridRequest $request, Murid $murid): JsonResponse
    {
        $murid = $this->muridService->update($murid, $request->validated());
        return response()->json(['murid' => $murid]);
    }

    public function dampakTanggalMasuk(Request $request, Murid $murid): JsonResponse
    {
        $this->authorize('update', $murid);
        $request->validate(['tanggal_masuk' => ['required', 'date']]);

        return response()->json($this->muridService->dampakTanggalMasuk($murid, $request->input('tanggal_masuk')));
    }

    public function deleteImpact(Murid $murid): JsonResponse
    {
        $this->authorize('delete', $murid);
        return response()->json($this->muridService->deleteImpact($murid));
    }

    public function destroy(Murid $murid): JsonResponse
    {
        $this->authorize('delete', $murid);
        $this->muridService->delete($murid);
        return response()->json(null, 204);
    }

    /**
     * Buatkan akun login (role murid) untuk murid, lalu kirim email atur password.
     * Dipakai agar pengurus kelas (ketua, bendahara, ...) bisa login.
     */
    public function buatAkun(Request $request, Murid $murid): JsonResponse
    {
        $this->authorize('update', $murid);

        if ($murid->user_id) {
            return response()->json(['message' => 'Murid ini sudah memiliki akun.'], 422);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        $user = DB::transaction(function () use ($murid, $data) {
            $user = User::create([
                'name'     => $murid->nama,
                'email'    => $data['email'],
                'role'     => UserRole::Murid,
                'password' => Hash::make(Str::random(40)),
            ]);
            $murid->update(['user_id' => $user->id]);

            return $user;
        });

        $token = Password::broker()->createToken($user);
        Mail::to($user)->send(new SetPasswordMail($user, $token));

        return response()->json(['user' => $user], 201);
    }
}
