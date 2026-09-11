<?php

namespace App\Http\Controllers;

use App\Http\Requests\Program\StoreProgramRequest;
use App\Http\Requests\Program\UpdateProgramRequest;
use App\Models\Kelas;
use App\Models\Program;
use App\Models\ProgramKelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Program::class);

        $query = Program::withCount(['programKelas as jumlah_kelas'])
            ->when($request->jenis, fn ($q) => $q->where('jenis', $request->jenis))
            ->when($request->has('is_aktif'), fn ($q) => $q->where('is_aktif', $request->boolean('is_aktif')))
            ->when($request->search, fn ($q) => $q->where('nama', 'ilike', "%{$request->search}%"));

        return response()->json($query->orderBy('nama')->paginate(20));
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        $program = Program::create($request->validated());
        return response()->json(['program' => $program], 201);
    }

    public function show(Request $request, Program $program): JsonResponse
    {
        $this->authorize('view', $program);

        $program->load(['programKelas.kelas', 'jadwal' => fn ($q) => $q->aktif()->with(['kelas', 'pengajar.user'])]);
        $program->loadCount(['programKelas as jumlah_kelas']);

        return response()->json(['program' => $program]);
    }

    public function update(UpdateProgramRequest $request, Program $program): JsonResponse
    {
        $program->update($request->validated());
        return response()->json(['program' => $program]);
    }

    public function destroy(Program $program): JsonResponse
    {
        $this->authorize('delete', $program);
        $program->delete();
        return response()->json(null, 204);
    }

    public function toggleAktif(Request $request, Program $program): JsonResponse
    {
        $this->authorize('update', $program);
        $program->update(['is_aktif' => !$program->is_aktif]);
        return response()->json(['program' => $program]);
    }

    public function assignKelas(Request $request, Program $program): JsonResponse
    {
        $this->authorize('manageKelas', $program);

        $request->validate(['kelas_id' => ['required', 'integer', 'exists:kelas,id']]);

        $pk = ProgramKelas::firstOrCreate([
            'program_id' => $program->id,
            'kelas_id'   => $request->kelas_id,
        ]);

        return response()->json(['program_kelas' => $pk->load('kelas')], 201);
    }

    public function lepasKelas(Request $request, Program $program, Kelas $kelas): JsonResponse
    {
        $this->authorize('manageKelas', $program);

        ProgramKelas::where('program_id', $program->id)
            ->where('kelas_id', $kelas->id)
            ->delete();

        return response()->json(null, 204);
    }
}
