<?php

namespace Database\Seeders\Kurikulum;

class KurikulumKelas4Seeder extends KurikulumKelasBaseSeeder
{
    protected function kelasNama(): string    { return 'Kelas 4'; }
    protected function kurikulumNama(): string { return 'Kurikulum Kelas 4'; }

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
            ['I',   'Tata Krama',      'Pribadi',                                                      '*'],
            ['I',   'Tata Krama',      'Keluarga',                                                     '*'],
            ['I',   'Tata Krama',      'Ulil Amri, Guru, Muballigh-Muballighot',                         '*'],
            ['I',   'Tata Krama',      'Masyarakat',                                                   '*'],
            ['II',  'Keilmuan',        'Dasar-dasar Akidah',                                           '*'],
            ['II',  'Kefahaman Agama', 'Faham surga neraka',                                           '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga diri dari lahan, kemaksiatan, dan keharoman',  '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat beserta bacaannya',                             '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                                          '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-Maidah 83-120 s/d Al-An'am 1-18"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Fajr 1-30'],
            ['II', 'Hafalan',          "Do'a berlindung dari penganiayaan"],
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
            ['I',   'Tata Krama',      "Ta'dim dan Berbuat Baik Kepada Orang Tua",  '*'],
            ['II',  'Keilmuan',        'Rukun iman, rukun Islam, ihsan',            '*'],
            ['II',  'Kefahaman Agama', 'Faham surga neraka',                        '*'],
            ['II',  'Praktik Ibadah',  "Praktik wudhu beserta do'anya",             '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat beserta bacaannya',          '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                       '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. At-Takwir s/d Al-Insyiqoq'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Alaq'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-85"],
            ['II', 'Hafalan',          "Do'a sujud Al-Qur'an"],
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
            ['I',   'Tata Krama',      'Menghormati Saudara Yang Lebih Tua Dan Menyayangi Saudara Yang Lebih Muda', '*'],
            ['I',   'Tata Krama',      'Tata Krama Menghormati Guru dan Muballigh-Muballighot',                     '*'],
            ['II',  'Keilmuan',        "Pengertian Qur'an Hadits Jama'ah",                                          '*'],
            ['II',  'Kefahaman Agama', 'Faham surga neraka',                                                        '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat beserta bacaannya',                                          '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                                                       '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Buruj s/d Al-Fajr'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Alaq 1-19'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-85"],
            ['II', 'Hafalan',          "Kumpulan do'a Nabi Muhammad"],
            ['II', 'Hafalan',          "Do'a sujud Al-Qur'an"],
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
            ['I',   'Tata Krama',      'Bergaul dengan teman',         '*'],
            ['I',   'Tata Krama',      'Ketika di masjid',             '*'],
            ['II',  'Keilmuan',        'Ilmu manqul musnad muttasil',  '*'],
            ['II',  'Kefahaman Agama', 'Faham surga neraka',           '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga kesucian',     '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',          '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Balad s/d Al-Alaq'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Alaq 1-19'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-90"],
            ['II', 'Hafalan',          "Kumpulan do'a Nabi Muhammad"],
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
            ['I',   'Tata Krama',      'Ketika di tempat pengajian dan sekolah',  '*'],
            ['I',   'Tata Krama',      'Terhadap lingkungan dan alam sekitar',    '*'],
            ['II',  'Keilmuan',        'Kemurnian ibadah',                        '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an dan Al-Hadits",            '*'],
            ['II',  'Praktik Ibadah',  'Praktik cara buang air kecil',            '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',              '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Qodr s/d Al-Fiil'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Alaq 1-19'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-90"],
            ['II', 'Hafalan',          "Do'a berlindung dari sifat munafiq"],
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
            ['I',   'Tata Krama',      'Terhadap ulil amri',                               '*'],
            ['II',  'Keilmuan',        'Pengetahuan wajibnya taat dan haromnya maksiat',   '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an dan Al-Hadits",                     '*'],
            ['II',  'Praktik Ibadah',  "Praktik sholat berjama'ah",                      '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                              '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Quroisy s/d An-Nas'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Alaq 1-19'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-90"],
            ['II', 'Hafalan',          "Kumpulan do'a Nabi Muhammad"],
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
            ['I',   'Tata Krama',      'Bertamu/diajak bertamu/kedatangan tamu', '*'],
            ['I',   'Tata Krama',      'Berpakaian',                             '*'],
            ['II',  'Keilmuan',        'Pengetahuan hukum halal harom',          '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an dan Al-Hadits",           '*'],
            ['II',  'Praktik Ibadah',  'Praktik dzikir setelah sholat',          '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',             '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Fatihah 1-7 s/d Al-Baqarah 1-61'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. At-Tin 1-9'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-95"],
            ['II', 'Hafalan',          "Do'a agar bisa bersyukur"],
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
            ['I',   'Tata Krama',      'Ketika tidur',                  '*'],
            ['I',   'Tata Krama',      'Ketika menguap',                '*'],
            ['I',   'Tata Krama',      'Ketika bersin',                 '*'],
            ['II',  'Keilmuan',        'Thoharoh dan sholat',           '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an dan Al-Hadits",  '*'],
            ['II',  'Praktik Ibadah',  'Praktik puasa Ramadhan',        '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat sunnah rowatib', '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',    '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Baqarah 62-112'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. At-Tin 1-9'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-95"],
            ['II', 'Hafalan',          "Do'a agar bisa bersyukur"],
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
            ['I',   'Tata Krama',      'Terhadap kerabat',                                    '*'],
            ['II',  'Keilmuan',        'Mandi junub',                                         '*'],
            ['II',  'Keilmuan',        'Hukum haid, mani, madzi, wadhi',                      '*'],
            ['II',  'Kefahaman Agama', "Faham jama'ah",                                     '*'],
            ['II',  'Praktik Ibadah',  'Menjaga pergaulan antara laki-laki dan perempuan',    '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah",  '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Baqarah 113-141'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. At-Tin 1-9'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-95"],
            ['II', 'Hafalan',          "Do'a berlindung dari jeleknya pendengaran, ucapan, dan penglihatan"],
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
            ['I',   'Tata Krama',      'Terhadap tetangga',                                   '*'],
            ['I',   'Tata Krama',      'Bersepeda',                                           '*'],
            ['II',  'Keilmuan',        'Sholat sunnah rowatib dan dhuha',                     '*'],
            ['II',  'Keilmuan',        'Puasa Ramadhan',                                      '*'],
            ['II',  'Keilmuan',        'Kefadholan puasa',                                    '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui 5 bab',                                    '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui 4 tali keimanan',                          '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat dhuha',                                '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah",  '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Baqarah 142-190'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. Asy-Syarh 1-8'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-99"],
            ['II', 'Hafalan',          "Do'a berlindung dari jeleknya pendengaran, ucapan, dan penglihatan"],
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
            ['I',   'Tata Krama',      'Tata Krama ketika makan bersama',                     '*'],
            ['II',  'Keilmuan',        'Hukum batas-batas mahrom',                            '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui 3 sukses',                                 '*'],
            ['II',  'Kefahaman Agama', "Mengetahui 6 thobi'at luhur",                       '*'],
            ['II',  'Kefahaman Agama', "Pengertian jama'ah",                                '*'],
            ['II',  'Praktik Ibadah',  'Mempraktikkan mandi junub',                           '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah",  '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Baqarah 191-233'],
            ['II', 'Tulis',            'Latihan makna pegon dan kode-kodenya'],
            ['II', 'Hafalan Murajaah', 'Annas - Alqodr'],
            ['II', 'Hafalan Baru',     'Q.S. Asy-Syarh 1-8'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-99"],
            ['II', 'Hafalan',          "Do'a berlindung dari sifat pelit dan penakut"],
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
            ['I',   'Tata Krama',      'Mencari ilmu',                                        '*'],
            ['II',  'Keilmuan',        'Aurot',                                               '*'],
            ['II',  'Kefahaman Agama', "Tri sukses, 6 thobi'at luhur, wajibnya berjama'ah", '*'],
            ['II',  'Praktik Ibadah',  "Mempraktikkan do'a-do'a yang telah dihafal",        '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat beserta bacaannya',                    '*'],
            ['III', 'Lingkungan',      "Kemandirian dalam lingkungan jama'ah dan sekolah",  '*'],
        ];

        $individu = [
            ['II', 'Bacaan',           'Q.S. Al-Baqarah 234-252'],
            ['II', 'Tulis',            'Latihan makna pegon dan kode-kodenya'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Asy-Syarh 1-8'],
            ['II', 'Hafalan',          "Asma'ul Husna 1-99"],
            ['II', 'Hafalan',          "Do'a berlindung dari sifat pelit dan penakut"],
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
