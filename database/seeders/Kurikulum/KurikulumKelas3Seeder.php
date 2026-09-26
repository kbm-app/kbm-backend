<?php

namespace Database\Seeders\Kurikulum;

class KurikulumKelas3Seeder extends KurikulumKelasBaseSeeder
{
    protected function kelasNama(): array     { return ['Kelas 3-1', 'Kelas 3-2']; }
    protected function kurikulumNama(): string { return 'Kurikulum Kelas 3'; }

    protected function materiData(): array
    {
        return array_merge(
            $this->juli(),
            $this->agustus(),
            $this->september(),
            $this->oktober(),
            $this->november(),
            $this->desember(),
            $this->januari(),
            $this->februari(),
            $this->maret(),
            $this->april(),
            $this->mei(),
            $this->juni(),
        );
    }

    // -------------------------------------------------------------------------
    // JULI
    // -------------------------------------------------------------------------
    private function juli(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',         'Pribadi',                                                                  '*'],
            ['I',   'Tata Krama',         'Keluarga',                                                                 '*'],
            ['I',   'Tata Krama',         'Ulim Amri, Guru, dan Muballigh-Muballighot',                               '*'],
            ['I',   'Tata Krama',         'Masyarakat',                                                               '*'],
            ['II',  'Dasar-Dasar Aqidah', 'Dasar-Dasar Aqidah',                                                       '*'],
            ['II',  'Dasar-Dasar Aqidah', 'Penyaksian bahwa tiada Tuhan yang berhak disembah kecuali Alloh',          '*'],
            ['II',  'Dasar-Dasar Aqidah', 'Penyaksian bahwa sesungguhnya Nabi Muhammad adalah hamba Alloh',           '*'],
            ['II',  'Pengertian QHJ',     'Faham Surga dan Neraka',                                                   '*'],
            ['II',  'Praktik Ibadah',     "Mempraktikkan wudlu beserta do'a sebelum dan sesudahnya",                   '*'],
            ['III', 'Pribadi',            'Kemandirian Pribadi',                                                      '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 5 halaman 1-8'],
            ['II', 'Baca Tulis', 'Menulis rangkaian kata Arab'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-65"],
            ['II', 'Hafalan',    "Do'a Ketika Menjenguk Orang Sakit"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'juli'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'juli'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // AGUSTUS
    // -------------------------------------------------------------------------
    private function agustus(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',                         "Tata Krama Ta'dim dan Berbuat Baik Kepada Kedua Orang Tua", '*'],
            ['II',  'Rukun Iman, Rukun Islam dan Ihsan',  'Rukun iman, rukun Islam dan ihsan',                         '*'],
            ['II',  'Dasar-Dasar Aqidah',                 'Rukun Islam',                                               '*'],
            ['II',  'Dasar-Dasar Aqidah',                 'Ihsan',                                                     '*'],
            ['II',  'Kefahaman Agama',                    'Faham Surga dan Neraka',                                    '*'],
            ['II',  'Praktik Ibadah',                     "Mempraktikkan wudlu beserta do'a sebelum dan sesudahnya",    '*'],
            ['III', 'Pribadi',                            'Kemandirian Pribadi',                                       '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 5 halaman 9-16'],
            ['II', 'Baca Tulis', 'Menulis rangkaian kata Arab'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-65"],
            ['II', 'Hafalan',    "Do'a Ketika Menjenguk Orang Sakit"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'agustus'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'agustus'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // SEPTEMBER
    // -------------------------------------------------------------------------
    private function september(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',      'Tata Krama Menghormati Saudara Yang Lebih Tua Dan Menyayangi Saudara Yang Lebih Muda', '*'],
            ['I',   'Tata Krama',      'Tata Krama Menghormati Guru dan Muballigh-Muballighot',                                '*'],
            ['II',  'Pengertian QHJ',  "Pengertian Al-Qur'an",                                                               '*'],
            ['II',  'Pengertian QHJ',  'Pengertian Al-Hadits',                                                                 '*'],
            ['II',  'Pengertian QHJ',  "Pengertian Jama'ah",                                                                 '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui dan hafal enam alam kehidupan manusia',                                     '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui tujuan ibadah',                                                             '*'],
            ['II',  'Praktik Ibadah',  "Mempraktikkan sholat beserta bacaan dan do'anya",                                       '*'],
            ['III', 'Pribadi',         'Kemandirian Pribadi',                                                                  '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 5 halaman 17-22'],
            ['II', 'Baca Tulis', 'Menulis rangkaian kata Arab'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-65"],
            ['II', 'Hafalan',    "Do'a Ketika Memakai Pakaian Baru"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'september'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'september'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // OKTOBER
    // -------------------------------------------------------------------------
    private function oktober(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',        'Tata Krama Bergaul Dengan Teman',                          '*'],
            ['I',   'Tata Krama',        'Tata Krama Ketika di Masjid',                              '*'],
            ['II',  'Kemurnian Ibadah',  'Murni pedomannya',                                         '*'],
            ['II',  'Kemurnian Ibadah',  'Murni pengamalannya',                                      '*'],
            ['II',  'Kemurnian Ibadah',  'Murni niatnya',                                            '*'],
            ['II',  'Kefahaman Agama',   'Mengetahui dan hafal enam alam kehidupan manusia',         '*'],
            ['II',  'Kefahaman Agama',   'Mengetahui tujuan ibadah',                                 '*'],
            ['II',  'Praktik Ibadah',    'Mempraktikkan menjaga kesucian (mengenal suci najis)',     '*'],
            ['III', 'Pribadi',           'Kemandirian Pribadi',                                      '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 5 halaman 23-30'],
            ['II', 'Baca Tulis', 'Menulis rangkaian kata Arab'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-70"],
            ['II', 'Hafalan',    "Do'a Ketika Memakai Pakaian Baru"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'oktober'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'oktober'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // NOVEMBER
    // -------------------------------------------------------------------------
    private function november(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',        'Tata Krama Ketika di Tempat Pengajian Dan Sekolah',                             '*'],
            ['I',   'Tata Krama',        'Tata Krama Terhadap Lingkungan dan Alam Sekitar',                               '*'],
            ['II',  'Kemurnian Ibadah',  'Pengetahuan Wajibnya Taat dan Haromnya Maksiat',                                '*'],
            ['II',  'Kefahaman Agama',   "Mengetahui kewajiban beribadah kepada Allah berdasarkan Al-Qur'an dan Al-Hadits", '*'],
            ['II',  'Kefahaman Agama',   'Mengetahui rukun iman',                                                         '*'],
            ['II',  'Kefahaman Agama',   'Mengetahui rukun Islam',                                                        '*'],
            ['II',  'Praktik Ibadah',    'Mempraktikkan cara buang air kecil (kencing) dan air besar (berak)',            '*'],
            ['III', 'Pribadi',           'Kemandirian dalam keluarga',                                                    '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 5 halaman 31-38'],
            ['II', 'Baca Tulis', 'Menulis rangkaian kata Arab'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-70"],
            ['II', 'Hafalan',    "Do'a Ketika Naik Kendaraan"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'november'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'november'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // DESEMBER
    // -------------------------------------------------------------------------
    private function desember(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',        'Tata Krama Terhadap Ulil Amri',                                    '*'],
            ['II',  'Kemurnian Ibadah',  'Pengetahuan Hukum Halal dan Harom',                                '*'],
            ['II',  'Kemurnian Ibadah',  'Hukum harom',                                                      '*'],
            ['II',  'Kefahaman Agama',   "Pengertian Al-Qur'an",                                           '*'],
            ['II',  'Kefahaman Agama',   'Pengertian Al-Hadits',                                             '*'],
            ['II',  'Kefahaman Agama',   "Pengertian Jama'ah",                                             '*'],
            ['II',  'Kefahaman Agama',   'Mengetahui halal dan harom',                                       '*'],
            ['II',  'Praktik Ibadah',    'Mempraktikkan cara mensucikan najis setelah kencing dan berak',    '*'],
            ['III', 'Pribadi',           'Kemandirian dalam keluarga',                                       '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 5 halaman 39-44'],
            ['II', 'Baca Tulis', 'Menulis rangkaian kata Arab'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-70"],
            ['II', 'Hafalan',    "Do'a Ketika Naik Kendaraan"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'desember'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'desember'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // JANUARI
    // -------------------------------------------------------------------------
    private function januari(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',          'Tata Krama Bertamu/diajak Bertamu dan Kedatangan Tamu', '*'],
            ['II',  'Thoharoh dan Sholat', 'Wudlu',                                                 '*'],
            ['II',  'Thoharoh dan Sholat', 'Mandi wajib',                                           '*'],
            ['II',  'Thoharoh dan Sholat', 'Tayamum',                                               '*'],
            ['II',  'Sholat',              'Sholat wajib',                                          '*'],
            ['II',  'Sholat',              'Sholat sunnah',                                         '*'],
            ['II',  'Kefahaman Agama',     "Faham Al-Qur'an dan Al-Hadits",                         '*'],
            ['II',  'Kefahaman Agama',     'Mengetahui suci dan najis (thoharoh)',                  '*'],
            ['II',  'Praktik Ibadah',      "Mempraktikkan sholat berjama'ah",                        '*'],
            ['III', 'Pribadi',             'Kemandirian dalam keluarga',                            '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 6 halaman 1-8'],
            ['II', 'Baca Tulis', 'Menulis kata Arab baku/potongan ayat'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-75"],
            ['II', 'Hafalan',    "Do'a Lailatul Qodar"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'januari'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'januari'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // FEBRUARI
    // -------------------------------------------------------------------------
    private function februari(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',             'Tata Krama Tidur',                                '*'],
            ['I',   'Tata Krama',             'Tata Krama Ketika Menguap',                       '*'],
            ['I',   'Tata Krama',             'Tata Krama Ketika Bersin',                        '*'],
            ['II',  'Sholat Sunnah Rowatib',  'Sholat Sunnah Rowatib',                           '*'],
            ['II',  'Kefahaman Agama',        'Mengetahui batas-batas mahrom',                   '*'],
            ['II',  'Praktik Ibadah',         'Mempraktikkan dzikir setiap selesai sholat',      '*'],
            ['III', 'Pribadi',                'Kemandirian dalam keluarga',                      '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 6 halaman 9-16'],
            ['II', 'Baca Tulis', 'Menulis kata Arab baku/potongan ayat'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-75"],
            ['II', 'Hafalan',    "Do'a Ketika Masuk Pasar"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'februari'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'februari'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // MARET
    // -------------------------------------------------------------------------
    private function maret(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',      'Tata Krama Terhadap Kerabat',                    '*'],
            ['II',  'Puasa Ramadhan',  'Puasa Ramadhan',                                 '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui Lima Bab',                            '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui Empat Tali Keimanan',                 '*'],
            ['II',  'Praktik Ibadah',  'Mempraktikkan puasa Ramadhan',                   '*'],
            ['III', 'Pribadi',         "Kemandirian dalam lingkungan jama'ah dan sekolah", '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 6 halaman 17-22'],
            ['II', 'Baca Tulis', 'Menulis kata Arab baku/potongan ayat'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Bayyinah"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-75"],
            ['II', 'Hafalan',    "Do'a Berlindung dari Syirik"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'maret'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'maret'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // APRIL
    // -------------------------------------------------------------------------
    private function april(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',      'Tata Krama Terhadap Tetangga',                                 '*'],
            ['I',   'Tata Krama',      'Tata Krama Bersepeda',                                         '*'],
            ['II',  'Puasa',           'Kefadhlolan puasa',                                            '*'],
            ['II',  'Puasa',           'Mandi Wajib',                                                  '*'],
            ['II',  'Puasa',           'Hal-hal yang diperbolehkan ketika berpuasa',                   '*'],
            ['II',  'Puasa',           'Hal-hal yang membatalkan puasa',                               '*'],
            ['II',  'Sholat',          'Sholat Sunnah',                                                '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui 5 bab dan 4 tali keimanan',                         '*'],
            ['II',  'Kefahaman Agama', 'Mempraktikkan sholat sunnah rowatib',                          '*'],
            ['III', 'Lingkungan',      'Menata dan merapikan perlengkapan pengajian dan sekolah',      '*'],
            ['III', 'Lingkungan',      'Tidak ditunggui orang tua ketika mengaji dan sekolah',         '*'],
            ['III', 'Lingkungan',      'Tanggap hal-hal yang isrof dan mubazir',                       '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 6 halaman 23-30'],
            ['II', 'Baca Tulis', 'Menulis kata Arab baku/potongan ayat'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Qodr"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-80"],
            ['II', 'Hafalan',    "Do'a Berlindung dari Syirik"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'april'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'april'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // MEI
    // -------------------------------------------------------------------------
    private function mei(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',             'Tata Krama Makan Bersama',                                   '*'],
            ['II',  'Mahrom',                 'Mahrom bagi laki-laki',                                      '*'],
            ['II',  'Mahrom',                 'Mahrom bagi perempuan',                                      '*'],
            ['II',  'Kefahaman Agama',        'Tri Sukses Generus',                                         '*'],
            ['II',  'Kefahaman Agama',        "Mengetahui 6 Thobi'at Luhur",                                 '*'],
            ['II',  'Kefahaman Agama',        "Mengetahui pengertian dan wajibnya berjama'ah",               '*'],
            ['II',  'Kefahaman Agama',        'Praktik menjaga pergaulan laki-laki dan perempuan',          '*'],
            ['III', 'Lingkungan dan Sekolah', 'Menata dan merapikan perlengkapan ngaji dan sekolah',        '*'],
            ['III', 'Lingkungan dan Sekolah', 'Tidak ditunggui orang tua ketika ngaji dan sekolah',         '*'],
            ['III', 'Lingkungan dan Sekolah', 'Tanggap hal yang isrof dan mubazir',                         '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis',   'Tilawati 6 halaman 31-38'],
            ['II', 'Baca Tulis',   'Menulis kata Arab baku/potongan ayat'],
            ['II', 'Hafalan',      "Al-Qur'an Surat Al-Qodr"],
            ['II', 'Hafalan',      "Asma'ul Husna 1-80"],
            ['II', "Hafalan Do'a",  "Do'a Berlindung dari Siksa Kubur"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'mei'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'mei'];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // JUNI
    // -------------------------------------------------------------------------
    private function juni(): array
    {
        $umum = [
            // [bab_kode, sub_bab, judul, metode]
            ['I',   'Tata Krama',      'Tata Krama Mencari Ilmu',                                      '*'],
            ['II',  'Hukum Mahrom',    'Hukum Mahrom',                                                 '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui Tri Sukses Generasi Penerus',                       '*'],
            ['II',  'Kefahaman Agama', "Mengetahui Enam Thobi'at Luhur",                                '*'],
            ['II',  'Kefahaman Agama', "Mengetahui Pengertian dan Wajibnya Berjama'ah",                 '*'],
            ['III', 'Pribadi',         "Kemandirian dalam lingkungan jama'ah dan sekolah",              '*'],
        ];

        $individu = [
            ['II', 'Baca Tulis', 'Tilawati 6 halaman 39-44'],
            ['II', 'Baca Tulis', 'Menulis kata Arab baku/potongan ayat'],
            ['II', 'Hafalan',    "Al-Qur'an Surat Al-Qodar"],
            ['II', 'Hafalan',    "Asma'ul Husna 1-80"],
            ['II', 'Hafalan',    "Do'a Berlindung dari Siksa Kubur"],
        ];

        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul, $metode]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'metode' => $metode, 'tipe' => 'umum', 'bulan' => 'juni'];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => 'juni'];
        }
        return $rows;
    }
}
