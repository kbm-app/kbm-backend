<?php

namespace Database\Seeders;

use App\Models\Pengajar;
use App\Models\User;
use Illuminate\Database\Seeder;

class PengajarSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'email'              => 'ahmad.fauzi@kbm.id',
                'jenis_kelamin'      => 'L',
                'tanggal_lahir'      => '1990-03-15',
                'alamat'             => 'Jl. Mawar No. 12, Jakarta Selatan',
                'pendidikan_terakhir' => 'S1 Pendidikan Agama Islam',
                'tanggal_bergabung'  => '2020-01-01',
                'is_aktif'           => true,
            ],
            [
                'email'              => 'siti.aminah@kbm.id',
                'jenis_kelamin'      => 'P',
                'tanggal_lahir'      => '1993-07-22',
                'alamat'             => 'Jl. Melati No. 5, Jakarta Timur',
                'pendidikan_terakhir' => 'S1 Tahfidz Al-Quran',
                'tanggal_bergabung'  => '2021-06-01',
                'is_aktif'           => true,
            ],
            [
                'email'              => 'ridwan@kbm.id',
                'jenis_kelamin'      => 'L',
                'tanggal_lahir'      => '1988-11-05',
                'alamat'             => 'Jl. Anggrek No. 8, Bekasi',
                'pendidikan_terakhir' => 'D3 Pendidikan',
                'tanggal_bergabung'  => '2019-08-15',
                'is_aktif'           => false,
            ],
        ];

        foreach ($data as $item) {
            $user = User::where('email', $item['email'])->first();
            if (! $user) {
                continue;
            }

            Pengajar::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'jenis_kelamin'       => $item['jenis_kelamin'],
                    'tanggal_lahir'       => $item['tanggal_lahir'],
                    'alamat'              => $item['alamat'],
                    'pendidikan_terakhir' => $item['pendidikan_terakhir'],
                    'tanggal_bergabung'   => $item['tanggal_bergabung'],
                    'is_aktif'            => $item['is_aktif'],
                ]
            );
        }
    }
}
