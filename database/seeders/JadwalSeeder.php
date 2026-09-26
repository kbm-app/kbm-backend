<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\Program;
use Illuminate\Database\Seeder;

class JadwalSeeder extends Seeder
{
    private const TA = '2026/2027';

    public function run(): void
    {
        $jadwal = [
            ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas Pra-Remaja', 'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => 'rabu',   'jam_mulai' => '16:00', 'jam_selesai' => '17:30', 'mulai_berlaku' => '2025-07-01', 'selesai_berlaku' => null],
            ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas Remaja',    'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => 'kamis',  'jam_mulai' => '16:00', 'jam_selesai' => '17:30', 'mulai_berlaku' => '2025-07-01', 'selesai_berlaku' => null],

            // Pengajian Rutin Kelas 6 — jadwal lama, sudah ditutup (histori ganti jadwal)
            ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas 6',         'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => 'senin',  'jam_mulai' => '15:00', 'jam_selesai' => '16:00', 'mulai_berlaku' => '2025-07-01', 'selesai_berlaku' => '2025-12-31'],
        ];

        // Pengajian Rutin PAUD s.d. Kelas 6 (Kelas 3 dibagi 2 kelompok: 3-1 & 3-2) — setiap Senin–Jumat
        foreach (['PAUD', 'Kelas 1', 'Kelas 2', 'Kelas 3-1', 'Kelas 3-2', 'Kelas 4', 'Kelas 5', 'Kelas 6'] as $kelas) {
            foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat'] as $hari) {
                $jadwal[] = ['program' => 'Pengajian Rutin', 'kelas' => $kelas, 'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => $hari, 'jam_mulai' => '15:30', 'jam_selesai' => '16:30', 'mulai_berlaku' => '2026-07-01', 'selesai_berlaku' => null];
            }
        }

        // Pengajian Rutin Kelas Usman — setiap Sabtu & Minggu malam
        foreach (['sabtu', 'minggu'] as $hari) {
            $jadwal[] = ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas Usman', 'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => $hari, 'jam_mulai' => '20:30', 'jam_selesai' => '21:30', 'mulai_berlaku' => '2026-07-01', 'selesai_berlaku' => null];
        }

        foreach ($jadwal as $item) {
            $program = Program::where('nama', $item['program'])->first();
            $kelas   = Kelas::where('nama', $item['kelas'])->first();

            if (! $program || ! $kelas) {
                continue;
            }

            Jadwal::firstOrCreate(
                [
                    'program_id'    => $program->id,
                    'kelas_id'      => $kelas->id,
                    'frekuensi'     => $item['frekuensi'],
                    'minggu_ke'     => $item['minggu_ke'],
                    'hari'          => $item['hari'],
                    'mulai_berlaku' => $item['mulai_berlaku'],
                ],
                [
                    'pengajar_id'     => $this->pengajarUtamaId($kelas->id),
                    'jam_mulai'       => $item['jam_mulai'],
                    'jam_selesai'     => $item['jam_selesai'],
                    'selesai_berlaku' => $item['selesai_berlaku'],
                ]
            );
        }

        // Jadwal bulanan lintas kelas (kelas_id null)
        $jadwalBulanan = [
            // Keakraban gabungan — bulanan, minggu ke-3, minggu pagi
            ['program' => 'Keakraban', 'frekuensi' => 'bulanan', 'minggu_ke' => 3, 'hari' => 'minggu', 'jam_mulai' => '19:00', 'jam_selesai' => '21:00', 'mulai_berlaku' => '2026-09-25', 'selesai_berlaku' => null],
        ];

        foreach ($jadwalBulanan as $item) {
            $program = Program::where('nama', $item['program'])->first();
            if (! $program) {
                continue;
            }

            Jadwal::firstOrCreate(
                [
                    'program_id'    => $program->id,
                    'kelas_id'      => null,
                    'frekuensi'     => $item['frekuensi'],
                    'minggu_ke'     => $item['minggu_ke'],
                    'hari'          => $item['hari'],
                    'mulai_berlaku' => $item['mulai_berlaku'],
                ],
                [
                    'pengajar_id'     => null,
                    'jam_mulai'       => $item['jam_mulai'],
                    'jam_selesai'     => $item['jam_selesai'],
                    'selesai_berlaku' => $item['selesai_berlaku'],
                ]
            );
        }
    }

    private function pengajarUtamaId(int $kelasId): ?int
    {
        $kelasGuru = KelasGuru::where('kelas_id', $kelasId)
            ->tahunAjaran(self::TA)
            ->get();

        $utama = $kelasGuru->firstWhere('peran', 'utama');

        return $utama->pengajar_id ?? $kelasGuru->first()?->pengajar_id;
    }
}
