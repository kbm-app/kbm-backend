<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,         // 1. users dulu (semua role)
            PengajarSeeder::class,     // 2. profil pengajar (butuh user)
            MuridSeeder::class,        // 3. profil murid + wali murid
            KelasSeeder::class,        // 4. data kelas
            KelasEnrollmentSeeder::class, // 5. assign pengajar & enroll murid ke kelas
        ]);
    }
}
