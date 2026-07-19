<?php

namespace Database\Seeders;

use App\Models\AbsensiMurid;
use App\Models\AbsensiPengajar;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\Murid;
use App\Models\MuridKelas;
use App\Models\Pengajar;
use App\Models\Pertemuan;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AbsensiSeeder extends Seeder
{
    private const TA = '2025/2026';

    public function run(): void
    {
        $this->seedSesiSelesai();
        $this->seedSesiBerlangsung();
    }

    private function seedSesiSelesai(): void
    {
        $sesiList = [
            // Pengajian Rutin — Kelas 5 (setiap senin, pengajar utama: Siti Aminah)
            [
                'program'   => 'Pengajian Rutin',
                'kelas'     => 'Kelas 5',
                'tanggal'   => '2026-05-26',
                'jam_mulai' => '15:30:00',
                'jam_selesai' => '16:30:00',
                'materi'    => 'Iqra Jilid 3 halaman 1–4',
                'catatan'   => 'Murid tampak semangat mengikuti pelajaran',
                'ap_status' => 'digantikan',
                'ap_pengganti_email' => 'ahmad.fauzi@kbm.id',
                'ap_catatan' => 'Siti Aminah berhalangan, digantikan Ahmad Fauzi',
                'absensi_murid' => [
                    ['nama' => 'Nadia Sari', 'status' => 'hadir',     'catatan' => null],
                ],
            ],
            [
                'program'   => 'Pengajian Rutin',
                'kelas'     => 'Kelas 5',
                'tanggal'   => '2026-06-02',
                'jam_mulai' => '15:30:00',
                'jam_selesai' => '16:35:00',
                'materi'    => 'Iqra Jilid 3 halaman 5–9',
                'catatan'   => null,
                'ap_status' => 'hadir',
                'ap_pengganti_email' => null,
                'ap_catatan' => null,
                'absensi_murid' => [
                    ['nama' => 'Nadia Sari', 'status' => 'terlambat', 'catatan' => null],
                ],
            ],
            [
                'program'   => 'Pengajian Rutin',
                'kelas'     => 'Kelas 5',
                'tanggal'   => '2026-06-09',
                'jam_mulai' => '15:30:00',
                'jam_selesai' => '16:30:00',
                'materi'    => 'Iqra Jilid 3 halaman 10–15',
                'catatan'   => null,
                'ap_status' => 'hadir',
                'ap_pengganti_email' => null,
                'ap_catatan' => null,
                'absensi_murid' => [
                    ['nama' => 'Nadia Sari', 'status' => 'hadir', 'catatan' => null],
                ],
            ],

            // Pengajian Rutin — Kelas Pra-Remaja (setiap rabu, pengajar utama: Ahmad Fauzi)
            [
                'program'   => 'Pengajian Rutin',
                'kelas'     => 'Kelas Pra-Remaja',
                'tanggal'   => '2026-05-28',
                'jam_mulai' => '16:00:00',
                'jam_selesai' => '17:30:00',
                'materi'    => 'Tajwid: Hukum Nun Mati — Izhar',
                'catatan'   => null,
                'ap_status' => 'hadir',
                'ap_pengganti_email' => null,
                'ap_catatan' => null,
                'absensi_murid' => [
                    ['nama' => 'Budi Santoso',  'status' => 'hadir', 'catatan' => null],
                    ['nama' => 'Fajar Nugroho', 'status' => 'alpha', 'catatan' => null],
                    ['nama' => 'Aisyah Putri',  'status' => 'hadir', 'catatan' => null],
                ],
            ],
            [
                'program'   => 'Pengajian Rutin',
                'kelas'     => 'Kelas Pra-Remaja',
                'tanggal'   => '2026-06-04',
                'jam_mulai' => '16:00:00',
                'jam_selesai' => '17:28:00',
                'materi'    => 'Tajwid: Hukum Nun Mati — Idgham',
                'catatan'   => null,
                'ap_status' => 'berhalangan',
                'ap_pengganti_email' => null,
                'ap_catatan' => 'Ahmad Fauzi berhalangan hadir, sesi tetap jalan dipandu mandiri',
                'absensi_murid' => [
                    ['nama' => 'Budi Santoso',  'status' => 'hadir',     'catatan' => null],
                    ['nama' => 'Fajar Nugroho', 'status' => 'hadir',     'catatan' => null],
                    ['nama' => 'Aisyah Putri',  'status' => 'terlambat', 'catatan' => null],
                ],
            ],
            [
                'program'   => 'Pengajian Rutin',
                'kelas'     => 'Kelas Pra-Remaja',
                'tanggal'   => '2026-06-11',
                'jam_mulai' => '16:00:00',
                'jam_selesai' => '17:30:00',
                'materi'    => 'Tajwid: Hukum Nun Mati — Ikhfa',
                'catatan'   => null,
                'ap_status' => 'hadir',
                'ap_pengganti_email' => null,
                'ap_catatan' => null,
                'absensi_murid' => [
                    ['nama' => 'Budi Santoso',  'status' => 'hadir', 'catatan' => null],
                    ['nama' => 'Fajar Nugroho', 'status' => 'izin',  'catatan' => 'Ada acara keluarga'],
                    ['nama' => 'Aisyah Putri',  'status' => 'hadir', 'catatan' => null],
                ],
            ],

            // Tahfidz — Kelas Pra-Remaja (setiap selasa malam, pengajar: Ahmad Fauzi)
            [
                'program'   => 'Tahfidz',
                'kelas'     => 'Kelas Pra-Remaja',
                'tanggal'   => '2026-06-03',
                'jam_mulai' => '19:30:00',
                'jam_selesai' => '20:30:00',
                'materi'    => 'Murajaah surat Al-Mulk',
                'catatan'   => null,
                'ap_status' => 'hadir',
                'ap_pengganti_email' => null,
                'ap_catatan' => null,
                'absensi_murid' => [
                    ['nama' => 'Budi Santoso',  'status' => 'hadir', 'catatan' => null],
                    ['nama' => 'Fajar Nugroho', 'status' => 'sakit', 'catatan' => 'Demam'],
                    ['nama' => 'Aisyah Putri',  'status' => 'hadir', 'catatan' => null],
                ],
            ],
            [
                'program'   => 'Tahfidz',
                'kelas'     => 'Kelas Pra-Remaja',
                'tanggal'   => '2026-06-10',
                'jam_mulai' => '19:30:00',
                'jam_selesai' => '20:28:00',
                'materi'    => 'Setoran surat Al-Mulk ayat 1–10',
                'catatan'   => 'Semua murid sudah hafal target minggu ini',
                'ap_status' => 'hadir',
                'ap_pengganti_email' => null,
                'ap_catatan' => null,
                'absensi_murid' => [
                    ['nama' => 'Budi Santoso',  'status' => 'hadir',     'catatan' => null],
                    ['nama' => 'Fajar Nugroho', 'status' => 'hadir',     'catatan' => null],
                    ['nama' => 'Aisyah Putri',  'status' => 'terlambat', 'catatan' => null],
                ],
            ],

            // Pengajian Rutin — Kelas 6 (selasa, pengajar utama: Siti Aminah)
            [
                'program'   => 'Pengajian Rutin',
                'kelas'     => 'Kelas 6',
                'tanggal'   => '2026-06-02',
                'jam_mulai' => '15:30:00',
                'jam_selesai' => '16:30:00',
                'materi'    => 'Fiqih: Tata Cara Sholat Jenazah',
                'catatan'   => null,
                'ap_status' => 'hadir',
                'ap_pengganti_email' => null,
                'ap_catatan' => null,
                'absensi_murid' => [
                    ['nama' => 'Dewi Rahayu', 'status' => 'hadir', 'catatan' => null],
                ],
            ],
            [
                'program'   => 'Pengajian Rutin',
                'kelas'     => 'Kelas 6',
                'tanggal'   => '2026-06-09',
                'jam_mulai' => '15:30:00',
                'jam_selesai' => '16:32:00',
                'materi'    => 'Fiqih: Doa dan Dzikir Setelah Sholat',
                'catatan'   => null,
                'ap_status' => 'hadir',
                'ap_pengganti_email' => null,
                'ap_catatan' => null,
                'absensi_murid' => [
                    ['nama' => 'Dewi Rahayu', 'status' => 'izin', 'catatan' => 'Ujian sekolah'],
                ],
            ],
        ];

        $superadminId = User::where('email', 'superadmin@kbm.id')->value('id');

        foreach ($sesiList as $sesi) {
            $program = Program::where('nama', $sesi['program'])->first();
            $kelas   = Kelas::where('nama', $sesi['kelas'])->first();

            if (! $program || ! $kelas) {
                continue;
            }

            $pengajar = $this->pengajarUtamaKelas($kelas->id);
            if (! $pengajar) {
                continue;
            }

            $jadwal = Jadwal::where('program_id', $program->id)
                ->where('kelas_id', $kelas->id)
                ->whereNull('selesai_berlaku')
                ->first();

            $pertemuan = Pertemuan::firstOrCreate(
                [
                    'program_id' => $program->id,
                    'kelas_id'   => $kelas->id,
                    'tanggal'    => $sesi['tanggal'],
                ],
                [
                    'jadwal_id'   => $jadwal?->id,
                    'pengajar_id' => $pengajar->id,
                    'jam_mulai'   => $sesi['jam_mulai'],
                    'jam_selesai' => $sesi['jam_selesai'],
                    'status'      => 'selesai',
                    'materi'      => $sesi['materi'],
                    'catatan'     => $sesi['catatan'],
                ]
            );

            // Tentukan pengajar yang mencatat absensi
            $pencatatId = $sesi['ap_status'] === 'digantikan' && $sesi['ap_pengganti_email']
                ? $this->userIdDariEmail($sesi['ap_pengganti_email'])
                : ($pengajar->user_id ?? $superadminId);

            foreach ($sesi['absensi_murid'] as $item) {
                $murid = Murid::where('nama', $item['nama'])->first();
                if (! $murid) {
                    continue;
                }

                AbsensiMurid::firstOrCreate(
                    ['pertemuan_id' => $pertemuan->id, 'murid_id' => $murid->id],
                    [
                        'status'      => $item['status'],
                        'dicatat_oleh' => $pencatatId,
                        'catatan'     => $item['catatan'],
                    ]
                );
            }

            // Absensi pengajar
            $penggantiId = null;
            if ($sesi['ap_pengganti_email']) {
                $penggantiUserId = $this->userIdDariEmail($sesi['ap_pengganti_email']);
                if ($penggantiUserId) {
                    $penggantiId = Pengajar::where('user_id', $penggantiUserId)->value('id');
                }
            }

            AbsensiPengajar::firstOrCreate(
                ['pertemuan_id' => $pertemuan->id],
                [
                    'pengajar_id'   => $pengajar->id,
                    'pengganti_id'  => $penggantiId,
                    'status'        => $sesi['ap_status'],
                    'keterangan'    => $sesi['ap_catatan'],
                ]
            );
        }
    }

    private function seedSesiBerlangsung(): void
    {
        // Hari ini (2026-06-16, Selasa) — Kelas 6, Pengajian Rutin jadwal selasa
        $program = Program::where('nama', 'Pengajian Rutin')->first();
        $kelas   = Kelas::where('nama', 'Kelas 6')->first();

        if (! $program || ! $kelas) {
            return;
        }

        $pengajar = $this->pengajarUtamaKelas($kelas->id);
        if (! $pengajar) {
            return;
        }

        $jadwal = Jadwal::where('program_id', $program->id)
            ->where('kelas_id', $kelas->id)
            ->whereNull('selesai_berlaku')
            ->first();

        $pertemuan = Pertemuan::firstOrCreate(
            [
                'program_id' => $program->id,
                'kelas_id'   => $kelas->id,
                'tanggal'    => '2026-06-16',
            ],
            [
                'jadwal_id'   => $jadwal?->id,
                'pengajar_id' => $pengajar->id,
                'jam_mulai'   => '15:30:00',
                'jam_selesai' => null,
                'status'      => 'berlangsung',
                'materi'      => null,
                'catatan'     => null,
            ]
        );

        // Draft absensi murid — semua alpha sebagai default awal sesi
        $muridList = $this->muridAktifDiKelas($kelas->id);
        $pencatatId = $pengajar->user_id;

        foreach ($muridList as $murid) {
            AbsensiMurid::firstOrCreate(
                ['pertemuan_id' => $pertemuan->id, 'murid_id' => $murid->id],
                [
                    'status'      => 'alpha',
                    'dicatat_oleh' => $pencatatId,
                    'catatan'     => null,
                ]
            );
        }

        AbsensiPengajar::firstOrCreate(
            ['pertemuan_id' => $pertemuan->id],
            [
                'pengajar_id'  => $pengajar->id,
                'pengganti_id' => null,
                'status'       => 'hadir',
                'keterangan'   => null,
            ]
        );
    }

    private function pengajarUtamaKelas(int $kelasId): ?Pengajar
    {
        $rows = KelasGuru::where('kelas_id', $kelasId)
            ->tahunAjaran(self::TA)
            ->get();

        $utama = $rows->firstWhere('peran', 'utama') ?? $rows->first();

        return $utama ? Pengajar::find($utama->pengajar_id) : null;
    }

    private function muridAktifDiKelas(int $kelasId): Collection
    {
        return MuridKelas::where('kelas_id', $kelasId)
            ->where('status', 'aktif')
            ->get()
            ->map(fn($mk) => Murid::find($mk->murid_id))
            ->filter()
            ->values();
    }

    private function userIdDariEmail(string $email): ?int
    {
        return User::where('email', $email)->value('id');
    }
}
