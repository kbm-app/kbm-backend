<?php

namespace Database\Seeders\Kurikulum;

class KurikulumKelas5Seeder extends KurikulumKelasBaseSeeder
{
    protected function kelasNama(): string    { return 'Kelas 5'; }
    protected function kurikulumNama(): string { return 'Kurikulum Kelas 5'; }

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
            ['I',   'Tata Krama',     'Pribadi',                                 '*'],
            ['I',   'Tata Krama',     'Keluarga',                                '*'],
            ['I',   'Tata Krama',     'Ulil Amri, Guru, Muballigh-Muballighot',  '*'],
            ['I',   'Tata Krama',     'Masyarakat',                              '*'],
            ['II',  'Keilmuan',       'Dasar-dasar akidah',                      '*'],
            ['II',  'Praktik Ibadah', "Praktik wudhu beserta do'anya",         '*'],
            ['II',  'Praktik Ibadah', 'Praktik sholat beserta bacaannya',        '*'],
            ['III', 'Kemandirian',    'Kemandirian pribadi',                     '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. Al-Baqarah 253-286'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Adh-Dhuha'],
            ['II', 'Hafalan',          "Do'a minta dimudahkan segala urusan"],
            ['II', 'Hafalan Dalil',    'Dalil kewajiban beribadah kepada Allah'],
            ['II', 'Makna',            'Q.S. Al-Balad'],
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
            ['I',   'Tata Krama',      "Ta'dhim dan berbuat baik pada orang tua", '*'],
            ['II',  'Keilmuan',        'Rukun iman, Islam, ihsan',                  '*'],
            ['II',  'Kefahaman Agama', 'Faham surga neraka',                        '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga kesucian',                  '*'],
            ['II',  'Praktik Ibadah',  'Praktik cara buang air kecil',              '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                       '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. Ali-Imran 1-61'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Adh-Dhuha'],
            ['II', 'Hafalan',          "Do'a minta dimudahkan segala urusan"],
            ['II', 'Hafalan Dalil',    'Dalil kewajiban beribadah kepada Allah'],
            ['II', 'Makna',            'Q.S. Asy-Syams - Asy-Syarh'],
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
            ['I',   'Tata Krama',      'Menghormati saudara yang lebih tua, menyayangi yang lebih muda', '*'],
            ['I',   'Tata Krama',      'Menghormati Guru dan Muballigh-Muballighot',                     '*'],
            ['II',  'Keilmuan',        "Pengertian Qur'an Hadits Jama'ah",                              '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui kewajiban beribadah kepada Allah',                    '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui rukun iman',                                          '*'],
            ['II',  'Praktik Ibadah',  'Praktik cara mensucikan najis',                                  '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                                            '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. Ali-Imran 62-91'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Lail 1-21'],
            ['II', "Hafalan Do'a",   "Do'a minta dipilihkan pada sesuatu yang baik"],
            ['II', 'Hafalan Dalil',    'Dalil kewajiban beribadah kepada Allah'],
            ['II', 'Makna',            'Q.S. Asy-Syams - Asy-Syarh'],
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
            ['I',   'Tata Krama',      'Bergaul dengan teman',                 '*'],
            ['I',   'Tata Krama',      'Ketika di masjid',                     '*'],
            ['II',  'Keilmuan',        'Ilmu manqul, musnad, muttasil',        '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui rukun Islam',               '*'],
            ['II',  'Kefahaman Agama', "Mengetahui Qur'an Hadits Jama'ah",    '*'],
            ['II',  'Praktik Ibadah',  "Praktik sholat berjama'ah",           '*'],
            ['II',  'Praktik Ibadah',  'Praktik dzikir sesudah sholat',        '*'],
            ['III', 'Kemandirian',     'Kemandirian pribadi',                  '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. Ali-Imran 92-153'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Lail 1-21'],
            ['II', "Hafalan Do'a",   "Do'a minta dipilihkan pada sesuatu yang baik"],
            ['II', 'Hafalan Dalil',    'Dalil kewajiban beribadah kepada Allah'],
            ['II', 'Makna',            'Q.S. Az-Zalzalah - At-Takatsur'],
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
            ['I',   'Tata Krama',      'Terhadap lingkungan sekitar',             '*'],
            ['II',  'Keilmuan',        'Kemurnian ibadah',                        '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui halal harom',                  '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui taat dan maksiat',             '*'],
            ['II',  'Praktik Ibadah',  'Praktik puasa Ramadhan',                  '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat sunnah rowatib',           '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',              '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. Ali-Imran 154-200 s/d An-Nisa' 1-6"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Lail 1-21'],
            ['II', 'Hafalan',          "Do'a minta 10 kebaikan"],
            ['II', 'Hafalan Dalil',    'Dalil mengaji dan mengamal'],
            ['II', 'Makna',            'Q.S. Asy-Syarh - Al-Kautsar'],
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
            ['I',   'Tata Krama',      'Terhadap ulil amri',                              '*'],
            ['II',  'Keilmuan',        'Pengetahuan wajibnya taat dan haromnya maksiat',  '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui suci najis',                           '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui batas-batas mahrom',                   '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga pergaulan antara lawan jenis',    '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',                      '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. An-Nisa' 7-23"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Lail 1-21'],
            ['II', 'Hafalan',          "Do'a minta 10 kebaikan"],
            ['II', 'Hafalan Dalil',    'Dalil membela'],
            ['II', 'Makna',            'Q.S. Al-Kafirun - An-Nas'],
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
            ['I',   'Tata Krama',      'Bertamu/diajak bertamu/kedatangan tamu',           '*'],
            ['I',   'Tata Krama',      'Berpakaian',                                       '*'],
            ['II',  'Keilmuan',        'Pengetahuan hukum halal harom',                    '*'],
            ['II',  'Kefahaman Agama', "Mengetahui kemurnian Qur'an Hadits",              '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui ilmu manqul musnad muttasil',           '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui hukum permasalahan haid, mani, madzi',  '*'],
            ['II',  'Praktik Ibadah',  'Praktik mandi junub',                              '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',                       '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. An-Nisa' 24-74"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Asy-Syams'],
            ['II', 'Hafalan',          "Do'a ketika ada petir"],
            ['II', 'Hafalan Dalil',    "Dalil sambung jama'ah"],
            ['II', 'Makna',            'K. Sholah bab thoharoh'],
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
            ['I',   'Tata Krama',      'Ketika tidur',                                      '*'],
            ['I',   'Tata Krama',      'Ketika menguap',                                    '*'],
            ['I',   'Tata Krama',      'Ketika bersin',                                     '*'],
            ['II',  'Keilmuan',        'Thoharoh dan sholat',                               '*'],
            ['II',  'Kefahaman Agama', "Mengetahui haromnya bid'ah, syirik, khurofat",    '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui haromnya adat jahiliyah',                '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui macam-macam dosa besar',                 '*'],
            ['II',  'Praktik Ibadah',  "Praktik pembiasaan berpakaian syar'i",            '*'],
            ['III', 'Kemandirian',     'Kemandirian dalam keluarga',                        '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. An-Nisa' 75-121"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Asy-Syams'],
            ['II', 'Hafalan',          "Do'a ketika ada petir"],
            ['II', 'Hafalan Dalil',    "Dalil tho'at"],
            ['II', 'Makna',            'K. Sholah bab thoharoh'],
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
            ['I',   'Tata Krama',      'Terhadap kerabat',                 '*'],
            ['II',  'Keilmuan',        'Mandi junub',                      '*'],
            ['II',  'Keilmuan',        'Hukum haid, mani, madzi',          '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui 5 bab',                 '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui 4 tali keimanan',       '*'],
            ['II',  'Praktik Ibadah',  'Praktik sholat dhuha',             '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah", '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. An-Nisa' 122-147"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Asy-Syams'],
            ['II', 'Hafalan',          "Do'a ketika ada petir"],
            ['II', 'Hafalan Dalil',    'Dalil bersyukur'],
            ['II', 'Makna',            'K. Sholah bab sholat'],
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
            ['I',   'Tata Krama',      'Tata Krama Terhadap Tetangga',                     '*'],
            ['I',   'Tata Krama',      'Tata Krama Bersepeda',                             '*'],
            ['II',  'Keilmuan',        'Sholat Sunnah Rowatib',                            '*'],
            ['II',  'Keilmuan',        'Sholat Sunnah Dhuha',                              '*'],
            ['II',  'Keilmuan',        'Puasa Ramadhan',                                   '*'],
            ['II',  'Keilmuan',        'Kefadholan puasa',                                 '*'],
            ['II',  'Kefahaman Agama', "Faham Jama'ah (3 sukses, 6 thobi'at luhur)",     '*'],
            ['II',  'Praktik Ibadah',  "Mempraktikkan do'a dan sholat malam",            '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah", '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           "Q.S. An-Nisa' 148-176 s/d Al-Maidah 1-13"],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Balad 1-20'],
            ['II', 'Hafalan',          "Do'a perlindungan dari bencana"],
            ['II', 'Hafalan Dalil',    'Dalil mengagungkan'],
            ['II', 'Makna',            'K. Sholah bab sholat'],
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
            ['I',   'Tata Krama',      'Tata krama makan bersama',                                     '*'],
            ['II',  'Keilmuan',        'Hukum mahrom',                                                 '*'],
            ['II',  'Kefahaman Agama', "Pengertian dan wajibnya berjama'ah",                         '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui struktur kepengurusan',                             '*'],
            ['II',  'Praktik Ibadah',  'Praktik menjaga diri dari lahan, kemaksiatan, dan keharoman',  '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah", '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. Al-Maidah 14-57'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Balad 1-20'],
            ['II', 'Hafalan',          "Do'a perlindungan dari bencana"],
            ['II', 'Hafalan Dalil',    'Dalil mempersungguh'],
            ['II', 'Makna',            'K. Sholah bab dzikir setelah sholat'],
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
            ['I',   'Tata Krama',      'Tata krama mencari ilmu',                 '*'],
            ['II',  'Keilmuan',        'Pengertian taurat',                       '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui wajibnya budi luhur',          '*'],
            ['II',  'Kefahaman Agama', 'Mengetahui 5 syarat kerukunan',           '*'],
            ['II',  'Praktik Ibadah',  "Praktik do'a-do'a yang telah dihafal",  '*'],
            ['III', 'Kemandirian',     "Kemandirian dalam lingkungan jama'ah dan sekolah", '*'],
        ];

        $individu = [
            // [bab_kode, sub_bab, judul]
            ['II', 'Bacaan',           'Q.S. Al-Maidah 58-82'],
            ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
            ['II', 'Hafalan Murajaah', 'Annas - Asyarh'],
            ['II', 'Hafalan Baru',     'Q.S. Al-Balad 1-20'],
            ['II', 'Hafalan',          "Do'a perlindungan dari bencana"],
            ['II', 'Hafalan Dalil',    "Dalil berdo'a"],
            ['II', 'Makna',            'K. Sholah bab dzikir setelah sholat'],
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
