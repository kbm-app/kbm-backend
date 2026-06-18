<?php

namespace App\Services;

use App\Models\Jadwal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class JadwalService
{
    public function ganti(Jadwal $lama, array $dataBaru): Jadwal
    {
        $mulai = Carbon::parse($dataBaru['mulai_berlaku'] ?? now()->toDateString());

        // Tutup jadwal lama satu hari sebelum jadwal baru berlaku
        // sehingga perubahan langsung terlihat tanpa overlap maupun gap
        $lama->update([
            'selesai_berlaku' => $mulai->copy()->subDay()->toDateString(),
        ]);

        return Jadwal::create([
            'program_id'  => $lama->program_id,
            'kelas_id'    => $lama->kelas_id,
            'pengajar_id' => $lama->pengajar_id,
            'frekuensi'   => $lama->frekuensi,
            'minggu_ke'   => $lama->minggu_ke,
            'hari'        => $lama->hari,
            'jam_mulai'   => $lama->jam_mulai,
            'jam_selesai' => $lama->jam_selesai,
            ...$dataBaru,
            'mulai_berlaku' => $mulai->toDateString(),
        ]);
    }

    public function getAktif(?int $kelasId = null): Collection
    {
        return Jadwal::aktif()
            ->when($kelasId, fn ($q) => $q->untukKelas($kelasId))
            ->with(['program', 'kelas', 'pengajar.user'])
            ->get();
    }
}
