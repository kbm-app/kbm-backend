<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Libur;
use App\Models\Pertemuan;
use App\Models\User;
use App\Services\AbsensiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LiburController extends Controller
{
    public function __construct(private AbsensiService $absensi) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'dari'     => ['required', 'date'],
            'sampai'   => ['required', 'date', 'after_or_equal:dari'],
            'kelas_id' => ['nullable', 'integer'],
        ]);

        $libur = Libur::with(['kelas:id,nama', 'jadwal.program:id,nama'])
            ->beririsan($request->dari, $request->sampai)
            ->when($request->kelas_id, fn ($q, $kelasId) => $q
                ->where(fn ($q) => $q->whereNull('kelas_id')->orWhere('kelas_id', $kelasId)))
            ->orderBy('tanggal_mulai')
            ->get();

        return response()->json(['data' => $libur]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kelas_id'        => ['nullable', 'integer', 'exists:kelas,id', 'required_with:jadwal_id'],
            'jadwal_id'       => ['nullable', 'integer', 'exists:jadwal,id'],
            'tanggal_mulai'   => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan'      => ['required', 'string', 'max:255'],
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'keterangan.required'            => 'Keterangan libur wajib diisi.',
        ]);

        $kelas = isset($data['kelas_id']) ? Kelas::find($data['kelas_id']) : null;
        abort_unless($this->bolehKelola($request->user(), $kelas), 403, 'Anda tidak berhak mengatur libur ini.');

        if (!empty($data['jadwal_id'])) {
            $this->validasiLiburSesi($data);
        }

        $libur = Libur::create($data + ['dibuat_oleh' => $request->user()->id]);

        return response()->json(['libur' => $libur->load(['kelas:id,nama', 'jadwal.program:id,nama'])], 201);
    }

    public function destroy(Request $request, Libur $libur): JsonResponse
    {
        abort_unless($this->bolehKelola($request->user(), $libur->kelas), 403, 'Anda tidak berhak menghapus libur ini.');

        $libur->delete();
        return response()->json(null, 204);
    }

    /**
     * Sama dengan yang boleh membuka sesi (super admin, pengajar, ketua/penerobos kelas).
     * Libur untuk semua kelas hanya oleh super admin.
     */
    private function bolehKelola(User $user, ?Kelas $kelas): bool
    {
        if ($user->role->value === 'super_admin') {
            return true;
        }
        if ($kelas === null) {
            return false;
        }
        return $user->role->value === 'pengajar' || $kelas->bisaKelolaAbsensi($user);
    }

    /** Libur satu sesi: tanggal harus cocok dengan jadwal dan sesinya belum dibuka. */
    private function validasiLiburSesi(array $data): void
    {
        if ($data['tanggal_mulai'] !== $data['tanggal_selesai']) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Libur untuk satu jadwal hanya berlaku satu tanggal.',
            ]);
        }

        $jadwal = Jadwal::findOrFail($data['jadwal_id']);
        $this->absensi->pastikanJadwalCocok($jadwal, [
            'kelas_id' => $data['kelas_id'],
            'tanggal'  => $data['tanggal_mulai'],
        ]);

        $sudahDibuka = Pertemuan::where('jadwal_id', $jadwal->id)
            ->where('tanggal', $data['tanggal_mulai'])
            ->exists();
        if ($sudahDibuka) {
            throw ValidationException::withMessages([
                'jadwal_id' => 'Sesi untuk jadwal ini sudah dibuka pada tanggal tersebut. Batalkan sesinya dulu bila ingin diliburkan.',
            ]);
        }

        if (Libur::untukSesi($data['kelas_id'], $jadwal->id, $data['tanggal_mulai'])->exists()) {
            throw ValidationException::withMessages([
                'jadwal_id' => 'Jadwal ini sudah diliburkan pada tanggal tersebut.',
            ]);
        }
    }
}
