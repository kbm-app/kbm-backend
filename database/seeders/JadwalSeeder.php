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
            ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas Pra-Remaja', 'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => 'rabu',   'jam_mulai' => '16:00', 'jam_selesai' => '17:30', 'mulai_berlaku' => '2025-07-01', 'selesai_berlaku' => '2026-09-28'],
            ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas Remaja',    'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => 'kamis',  'jam_mulai' => '16:00', 'jam_selesai' => '17:30', 'mulai_berlaku' => '2025-07-01', 'selesai_berlaku' => '2026-09-28'],

            // Pengajian Rutin Kelas 6 — jadwal lama, sudah ditutup (histori ganti jadwal)
            ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas 6',         'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => 'senin',  'jam_mulai' => '15:00', 'jam_selesai' => '16:00', 'mulai_berlaku' => '2025-07-01', 'selesai_berlaku' => '2025-12-31'],
        ];

        // Pengajian Rutin PAUD s.d. Kelas 6 (Kelas 3 dibagi 2 kelompok: 3-1 & 3-2) — setiap Senin–Jumat.
        // Mulai 26 Sep 2026 tiap kelas dibedakan waktunya (pengumuman: guru terbatas & target tiap kelas beda);
        // jadwal lama 15:30–16:30 ditutup 25 Sep 2026 sebagai histori ganti jadwal.
        $jamBaru = [
            'Kelas 1'   => ['16:10', '17:00'], 'Kelas 2'   => ['17:00', '17:50'],
            'Kelas 3-1' => ['16:10', '17:00'], 'Kelas 3-2' => ['16:10', '17:00'],
            'Kelas 4'   => ['17:00', '17:50'], 'Kelas 5'   => ['16:10', '17:00'],
            'Kelas 6'   => ['17:00', '17:50'],
        ];
        foreach (['PAUD', 'Kelas 1', 'Kelas 2', 'Kelas 3-1', 'Kelas 3-2', 'Kelas 4', 'Kelas 5', 'Kelas 6'] as $kelas) {
            foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat'] as $hari) {
                $baru = $jamBaru[$kelas] ?? null;
                $jadwal[] = ['program' => 'Pengajian Rutin', 'kelas' => $kelas, 'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => $hari, 'jam_mulai' => '15:30', 'jam_selesai' => '16:30', 'mulai_berlaku' => '2026-07-01', 'selesai_berlaku' => $baru ? '2026-09-25' : null];
                if ($baru) {
                    $jadwal[] = ['program' => 'Pengajian Rutin', 'kelas' => $kelas, 'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => $hari, 'jam_mulai' => $baru[0], 'jam_selesai' => $baru[1], 'mulai_berlaku' => '2026-09-26', 'selesai_berlaku' => null];
                }
            }
        }

        // Pengajian Rutin Pra-Remaja & Remaja — ba'da maghrib Senin–Sabtu mulai 29 Sep 2026
        // (19:00–21:00; Selasa & Kamis sampai 20:00). Jadwal lama Rabu/Kamis 16:00 ditutup 28 Sep 2026.
        foreach (['Kelas Pra-Remaja', 'Kelas Remaja'] as $kelas) {
            foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'] as $hari) {
                $jadwal[] = ['program' => 'Pengajian Rutin', 'kelas' => $kelas, 'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => $hari, 'jam_mulai' => '19:00', 'jam_selesai' => in_array($hari, ['selasa', 'kamis']) ? '20:00' : '21:00', 'mulai_berlaku' => '2026-09-29', 'selesai_berlaku' => null];
            }
        }

        // Pengajian Rutin Kelas Usman — setiap Sabtu & Minggu malam
        foreach (['sabtu', 'minggu'] as $hari) {
            $jadwal[] = ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas Usman', 'frekuensi' => 'mingguan', 'minggu_ke' => null, 'hari' => $hari, 'jam_mulai' => '20:30', 'jam_selesai' => '21:30', 'mulai_berlaku' => '2026-07-01', 'selesai_berlaku' => null];
        }

        // Pengajian Usman ke rumah jamaah — bulanan, Sabtu (malam Minggu) minggu ke-3
        $jadwal[] = ['program' => 'Pengajian Rutin', 'kelas' => 'Kelas Usman', 'frekuensi' => 'bulanan', 'minggu_ke' => 3, 'hari' => 'sabtu', 'jam_mulai' => '19:00', 'jam_selesai' => '21:00', 'mulai_berlaku' => '2026-09-29', 'selesai_berlaku' => null];

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
