<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'nama'      => 'Pengajian Rutin',
                'jenis'     => 'pengajian_rutin',
                'deskripsi' => 'Kegiatan pengajian mingguan untuk seluruh kelas aktif',
                'lokasi'    => 'Aula Utama Masjid',
                'is_aktif'  => true,
                'kelas'     => [
                    'PAUD',
                    'Kelas 1',
                    'Kelas 2',
                    'Kelas 3-1',
                    'Kelas 3-2',
                    'Kelas 4',
                    'Kelas 5',
                    'Kelas 6',
                    'Kelas Pra-Remaja',
                    'Kelas Remaja',
                    'Kelas Usman',
                ],
            ],
            [
                'nama'      => 'Persinas ASAD',
                'jenis'     => 'persinas_asad',
                'deskripsi' => 'Latihan bela diri Persinas ASAD untuk santri remaja',
                'lokasi'    => 'Halaman Masjid',
                'is_aktif'  => true,
                'kelas'     => [
                    'PAUD',
                    'Kelas 1',
                    'Kelas 2',
                    'Kelas 3-1',
                    'Kelas 3-2',
                    'Kelas 4',
                    'Kelas 5',
                    'Kelas 6',
                    'Kelas Pra-Remaja',
                    'Kelas Remaja',
                    'Kelas Usman',
                ],
            ],
            [
                'nama'      => 'Keakraban',
                'jenis'     => 'keakraban',
                'deskripsi' => 'Kegiatan keakraban dan outing lintas kelas',
                'lokasi'    => null,
                'is_aktif'  => true,
                'kelas'     => [
                    'Kelas Pra-Remaja',
                    'Kelas Remaja',
                    'Kelas Usman',
                ],
            ],
            [
                'nama'      => 'Amal Sholih',
                'jenis'     => 'amal_sholih',
                'deskripsi' => 'Kegiatan amal sholih untuk lingkungan sekitar masjid',
                'lokasi'    => null,
                'is_aktif'  => false,
                'kelas'     => [],
            ],
        ];

        foreach ($programs as $item) {
            $program = Program::firstOrCreate(
                ['nama' => $item['nama']],
                [
                    'jenis'     => $item['jenis'],
                    'deskripsi' => $item['deskripsi'],
                    'lokasi'    => $item['lokasi'],
                    'is_aktif'  => $item['is_aktif'],
                ]
            );

            $kelasIds = Kelas::whereIn('nama', $item['kelas'])->pluck('id');
            $program->kelas()->syncWithoutDetaching($kelasIds);
        }
    }
}
