<?php

namespace Database\Seeders\Kurikulum;

class KurikulumKelas6Seeder extends KurikulumKelasBaseSeeder
{
    protected function kelasNama(): string    { return 'Kelas 6'; }
    protected function kurikulumNama(): string { return 'Kurikulum Kelas 6'; }

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
            ['I',   'Tata Krama',      'Ulil Amri, Guru, Muballigh-Muballighot',                       '*'],
            ['I',   'Tata Krama',      'Masyarakat',                                                   '*'],
            ['II',  'Keilmuan',        'Dasar-dasar akidah',                                           '*'],
            ['II',  'Kefahaman Agama', 'Faham surga neraka',                                           '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga diri dari lahan, kemaksiatan, dan keharoman',  '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat beserta bacaannya',                             '*'],
            ['III', 'Lingkungan',      'Kemandirian pribadi',                                          '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-Maidah 83-120 s/d Al-An'am 1-18"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Fajr 1-30'],
            ['II', 'Hafalan',          "Do'a berlindung dari penganiayaan"],
            ['II', 'Hafalan Dalil',    'Dalil akhlakul karimah'],
            ['II', 'Makna',            "Q.S. An-Naba' 1-30"],
            ['II', 'Makna',            'K. Adab bab kasih sayang'],
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
            ['I',   'Tata Krama',      "Ta'dhim dan berbuat baik kepada kedua orang tua", '*'],
            ['II',  'Keilmuan',        'Rukun Islam, iman, ihsan',                          '*'],
            ['II',  'Kefahaman Agama', 'Faham surga neraka',                                '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga kesucian',                          '*'],
            ['II',  'Praktik Ibadah',  'Praktik cara buang air kecil',                      '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                               '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-An'am 19-81"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Fajr 1-30'],
            ['II', 'Hafalan',          "Do'a berlindung dari penganiayaan"],
            ['II', 'Hafalan Dalil',    'Dalil akhlakul karimah'],
            ['II', 'Makna',            "Q.S. An-Naba' 31-40 s/d An-Nazi'at 1-15"],
            ['II', 'Makna',            'K. Adab bab kasih sayang'],
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
            ['I',   'Tata Krama',      'Menghormati saudara yang lebih tua dan menyayangi yang muda',  '*'],
            ['I',   'Tata Krama',      'Menghormati Guru dan Muballigh-Muballighot',                   '*'],
            ['II',  'Keilmuan',        "Pengertian Qur'an Hadits Jama'ah",                            '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an Al-Hadits",                                  '*'],
            ['II',  'Praktik Ibadah',  'Praktik mensucikan najis dari kencing dan berak',              '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                                          '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-An'am 82-110"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Fajr 1-30'],
            ['II', 'Hafalan',          "Do'a ketika takut dengan orang kafir"],
            ['II', 'Hafalan Dalil',    'Dalil alim faqih'],
            ['II', 'Makna',            "Q.S. An-Nazi'at 16-46"],
            ['II', 'Makna',            'K. Adab bab sabar'],
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
            ['I',   'Tata Krama',      'Bergaul dengan teman',                                         '*'],
            ['I',   'Tata Krama',      'Ketika di masjid',                                             '*'],
            ['II',  'Keilmuan',        'Ilmu manqul, musnad dan muttasil',                             '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an Al-Hadits",                                  '*'],
            ['II',  'Praktik Ibadah',  "Praktik sholat berjama'ah",                                   '*'],
            ['II',  'Praktik Ibadah',  'Praktik dzikir sesudah sholat',                                '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                                          '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-An'am 111-157"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Fajr 1-30'],
            ['II', 'Hafalan',          "Do'a berlindung dari penganiayaan"],
            ['II', 'Hafalan Dalil',    'Dalil alim faqih'],
            ['II', 'Makna',            'Q.S. Abasa 1-42'],
            ['II', 'Makna',            'K. Adab bab sabar'],
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
            ['I',   'Tata Krama',      'Ketika di tempat pengajian',                                                         '*'],
            ['I',   'Tata Krama',      'Terhadap lingkungan sekitar dan alam',                                               '*'],
            ['II',  'Keilmuan',        'Kemurnian ibadah',                                                                   '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an dan Al-Hadits (taat maksiat, thoharoh, batas mahrom)",             '*'],
            ['II',  'Praktik Ibadah',  'Praktik puasa Ramadhan',                                                             '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat sunnah rowatib',                                                      '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',                                                         '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-An'am 158-165 s/d Al-A'raf 1-57"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Ghasyiyah'],
            ['II', 'Hafalan',          "Do'a ketika bertempat di tempat baru"],
            ['II', 'Hafalan Dalil',    'Dalil kemandirian'],
            ['II', 'Makna',            'Q.S. At-Takwir 1-29'],
            ['II', 'Makna',            'K. Adab bab pemaaf'],
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
            ['I',   'Tata Krama',      'Terhadap ulil amri',                                           '*'],
            ['II',  'Keilmuan',        'Pengetahuan wajibnya taat dan hukumnya maksiat',               '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an Al-Hadits",                                  '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga pergaulan antara lawan jenis',                 '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                                          '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-A'raf 58-87"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Ghasyiyah 1-26'],
            ['II', 'Hafalan',          "Do'a ketika bertempat di tempat baru"],
            ['II', 'Hafalan Dalil',    'Dalil kemandirian'],
            ['II', 'Makna',            'Q.S. Al-Infithor 1-19 s/d Al-Muthoffifin 1-6'],
            ['II', 'Makna',            'K. Adab bab pemaaf'],
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
            ['I',   'Tata Krama',      'Bertamu/diajak bertamu/kedatangan tamu',                   '*'],
            ['I',   'Tata Krama',      'Berpakaian',                                               '*'],
            ['II',  'Keilmuan',        'Pengetahuan hukum halal harom',                            '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an Al-Hadits",                               '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui hukum haid, mani, madzi, wadhi',                '*'],
            ['II',  'Kefahaman Agama', "Mengetahui haromnya bid'ah, syirik, khurofat, tahayul",   '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui haromnya adat jahiliyah',                       '*'],
            ['II',  'Praktik Ibadah',  'Praktik mandi junub',                                      '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',                               '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-A'raf 88-155"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Ghasyiyah 1-26'],
            ['II', 'Hafalan',          "Do'a ketika mimpi baik dan jelek"],
            ['II', 'Hafalan Dalil',    'Dalil rukun'],
            ['II', 'Makna',            'Q.S. Al-Muthoffifin 7-34'],
            ['II', 'Makna',            'K. Adab bab ramah tamah'],
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
            ['I',   'Tata Krama',      'Ketika tidur',                              '*'],
            ['I',   'Tata Krama',      'Ketika menguap',                            '*'],
            ['I',   'Tata Krama',      'Ketika bersin',                             '*'],
            ['II',  'Keilmuan',        'Thoharoh dan sholat',                       '*'],
            ['II',  'Kefahaman Agama', "Faham Al-Qur'an Al-Hadits",                '*'],
            ['II',  'Kefahaman Agama', "Faham jama'ah",                            '*'],
            ['II',  'Praktik Ibadah',  "Praktik membiasakan berpakaian syar'i",    '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',                '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Al-A'raf 156-206 s/d Al-Anfal 1-18"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     "Q.S. Al-A'la 1-19"],
            ['II', 'Hafalan Baru',     'Q.S. Al-Ghasyiyah 1-26'],
            ['II', "Hafalan Do'a",   "Do'a ketika bermimpi baik dan jelek"],
            ['II', 'Hafalan Dalil',    'Dalil kompak'],
            ['II', 'Makna',            'Q.S. Al-Muthoffifin 35-36 - Al-Insyiqaq 1-25'],
            ['II', 'Makna',            'K. Adab bab ramah tamah'],
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
            ['I',   'Tata Krama',      'Terhadap kerabat',                                '*'],
            ['II',  'Keilmuan',        'Mandi junub, hukum haid, mani, madzi, wadhi',     '*'],
            ['II',  'Kefahaman Agama', "Faham jama'ah",                                  '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat dhuha',                            '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah", '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. Al-Anfal 9-40'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     "Q.S. Al-A'la 1-19"],
            ['II', "Hafalan Do'a",   "Do'a minta surga firdaus"],
            ['II', "Hafalan Do'a",   "Do'a pengayoman"],
            ['II', 'Hafalan Dalil',    'Dalil kerjasama yang baik'],
            ['II', 'Makna',            'Q.S. Al-Buruj 1-22'],
            ['II', 'Makna',            "K. Adab bab tawadhu'/rendah hati"],
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
            ['I',   'Tata Krama',      'Tata Krama Terhadap Tetangga',              '*'],
            ['I',   'Tata Krama',      'Tata Krama Bersepeda',                      '*'],
            ['II',  'Keilmuan',        'Sholat Sunnah Rowatib dan dhuha',           '*'],
            ['II',  'Keilmuan',        'Puasa Ramadhan',                            '*'],
            ['II',  'Kefahaman Agama', "Pengertian dan wajibnya berjama'ah",       '*'],
            ['II',  'Kefahaman Agama', "Mengetahui struktur dalam jama'ah",        '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah", '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. Al-Anfal 41-75 s/d At-Taubah 1-20'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. At-Thoriq 1-17'],
            ['II', 'Hafalan Baru',     "Q.S. Al-A'la 1-19"],
            ['II', "Hafalan Do'a",   "Do'a minta surga firdaus"],
            ['II', "Hafalan Do'a",   "Do'a pengayoman"],
            ['II', 'Makna',            "Q.S. At-Thoriq 1-17 s/d Al-A'la 1-15"],
            ['II', 'Makna',            "K. Adab bab tawadhu'/rendah hati"],
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
            ['I',   'Tata Krama',      'Tata krama ketika makan bersama',                              '*'],
            ['II',  'Keilmuan',        'Bab mahrom/pengertian batas-batas mahrom',                     '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui pengertian dan wajibnya budi luhur',                '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui 5 syarat kerukunan',                                '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga diri dari lahan, kemaksiatan, dan keharoman',  '*'],
            ['III', 'Lingkungan',      "Kemandirian dalam lingkungan jama'ah dan sekolah",           '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. At-Taubah 21-68'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. At-Thoriq'],
            ['II', 'Hafalan',          "Do'a mas kumambang"],
            ['II', 'Hafalan',          "Do'a sapu jagad"],
            ['II', 'Hafalan Dalil',    'Dalil amanah'],
            ['II', 'Makna',            "K. Adab bab 6 thobi'at luhur"],
            ['II', 'Makna',            "Q.S. Al-A'la 16-19 s/d Al-Ghasyiyah 1-26"],
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
            ['I',   'Tata Krama',        'Tata krama mencari ilmu',                          '*'],
            ['II',  'Keilmuan',          'Memahami aurot',                                   '*'],
            ['II',  'Kefahaman Agama',   'Mengetahui fathonah, bithonah, budi luhur',        '*'],
            ['II',  "Kefahaman Jama'ah", 'Mengetahui 4 maqodirullah',                       '*'],
            ['III', 'Kemandirian',       "Kemandirian dalam lingkungan jama'ah dan sekolah", '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. At-Taubah 69-93'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Albalad'],
            ['II', 'Hafalan Baru',     'Q.S. At-Thoriq'],
            ['II', "Hafalan Do'a",   "Do'a mas kumambang"],
            ['II', "Hafalan Do'a",   "Do'a sapu jagad"],
            ['II', 'Hafalan Dalil',    'Dalil mujhid muzhid'],
            ['II', 'Makna',            'Q.S. Al-Fajr'],
            ['II', 'Makna',            'K. Adab bab birrul walidain'],
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
