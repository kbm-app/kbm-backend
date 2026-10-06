<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Pengajar;
use Illuminate\Support\Facades\DB;

class PengajarService
{
    /**
     * Soft delete pengajar. Riwayat (pertemuan, absensi pengajar, penugasan kelas) tetap tersimpan;
     * pengajar hanya dilepas dari jadwal yang masih berlaku agar pertemuan baru tidak memakainya.
     * Penugasan kelas tidak dihapus tapi tersembunyi (Kelas::kelasGuru hanya memuat pengajar aktif).
     */
    public function hapus(Pengajar $pengajar): void
    {
        DB::transaction(function () use ($pengajar) {
            $jadwalBerlaku = Jadwal::where(fn ($q) => $q
                ->whereNull('selesai_berlaku')
                ->orWhere('selesai_berlaku', '>=', now()->toDateString()))
                ->pluck('id');
            $pengajar->jadwal()->detach($jadwalBerlaku);

            $pengajar->delete();
        });
    }
}
