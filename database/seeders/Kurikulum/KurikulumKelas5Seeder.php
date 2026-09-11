<?php

namespace Database\Seeders\Kurikulum;

class KurikulumKelas5Seeder extends KurikulumKelasBaseSeeder
{
    protected function kelasNama(): string    { return 'Kelas 5'; }
    protected function kurikulumNama(): string { return 'Kurikulum Kelas 5'; }

    // Data real Juli dari dokumen kurikulum. Bulan lain representatif.
    protected function materiData(): array
    {
        return array_merge(
            // ── Semester 1 (Jul–Des) ──
            [
                // Juli — real data dari gambar kurikulum
                ['bab' => 'I',   'sub_bab' => 'Tata Krama',      'judul' => 'Pribadi',                                 'tipe' => 'umum',     'bulan' => 'juli'],
                ['bab' => 'I',   'sub_bab' => 'Tata Krama',      'judul' => 'Keluarga',                                'tipe' => 'umum',     'bulan' => 'juli'],
                ['bab' => 'I',   'sub_bab' => 'Tata Krama',      'judul' => 'Ulil amri, Guru, Mubaligh-mubalighot',    'tipe' => 'umum',     'bulan' => 'juli'],
                ['bab' => 'I',   'sub_bab' => 'Tata Krama',      'judul' => 'Masyarakat',                              'tipe' => 'umum',     'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Keilmuan',        'judul' => 'Dasa-dasar akidah',                       'tipe' => 'umum',     'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Praktik ibadah',  'judul' => "Praktik wudhu beserta do'anya",           'tipe' => 'umum',     'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Praktik ibadah',  'judul' => 'Praktik sholat beserta bacaanya',         'tipe' => 'umum',     'bulan' => 'juli'],
                ['bab' => 'III', 'sub_bab' => 'Kemandirian',     'judul' => 'Kemandirian pribadi',                     'tipe' => 'umum',     'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Bacaan',          'judul' => 'Q.S. Al-Baqarah 253-286',                 'tipe' => 'individu', 'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Tulis',           'judul' => 'Terampil Menulis Arab dan Pegon',         'tipe' => 'individu', 'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Hafalan Murajaah','judul' => 'Annas - Asyarh',                          'tipe' => 'individu', 'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Hafalan Baru',    'judul' => 'Q.S. Adh-Dhuha',                         'tipe' => 'individu', 'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Hafalan',         'judul' => "Do'a minta dimudahkan segala urusan",     'tipe' => 'individu', 'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Hafalan Dalil',   'judul' => 'Dalil kewajiban beribadah kepada Allah',  'tipe' => 'individu', 'bulan' => 'juli'],
                ['bab' => 'II',  'sub_bab' => 'Makna',           'judul' => 'Q.S. Al-Balad',                           'tipe' => 'individu', 'bulan' => 'juli'],
            ],
            $this->bulan('agustus',
                umum: [
                    ['I',   'Tata Krama',      'Tata krama di jalan dan tempat umum'],
                    ['I',   'Akhlak Terpuji',  'Sifat tawadhu dan qanaah'],
                    ['II',  'Keilmuan',        'Rukun Islam — makna dan hikmah'],
                    ['II',  'Kefahaman agama', 'Syarat dan rukun sholat secara mendalam'],
                    ['II',  'Praktik ibadah',  'Praktik sholat sunnah: dhuha dan tahajud'],
                    ['III', 'Kemandirian',     'Kemandirian dalam keluarga'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. Ali Imran 1-50'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', 'Annas - Ath-Thariq'],
                    ['II', 'Hafalan Baru',     "Q.S. Al-A'la"],
                    ['II', 'Hafalan',          "Do'a masuk kamar mandi"],
                    ['II', 'Hafalan Dalil',    "Dalil tentang sholat berjama'ah"],
                    ['II', 'Makna',            'Q.S. Al-Ghasyiyah'],
                ]
            ),
            $this->bulan('september',
                umum: [
                    ['I',   'Tata Krama',      'Adab bersama teman dan tetangga'],
                    ['II',  'Keilmuan',        'Sifat-sifat Allah (Asmaul Husna bagian 1)'],
                    ['II',  'Kefahaman agama', 'Hukum najis dan bersuci'],
                    ['II',  'Praktik ibadah',  'Praktik tayamum'],
                    ['III', 'Kemandirian',     'Mengelola waktu belajar secara mandiri'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. Ali Imran 51-100'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', 'Annas - Al-Buruj'],
                    ['II', 'Hafalan Baru',     'Q.S. Al-Insyiqaq'],
                    ['II', 'Hafalan',          "Do'a sesudah sholat fardhu"],
                    ['II', 'Hafalan Dalil',    'Dalil tentang bersuci'],
                    ['II', 'Makna',            'Q.S. Al-Muthaffifin'],
                ]
            ),
            $this->bulan('oktober',
                umum: [
                    ['I',   'Tata Krama',      'Adab dalam majelis ilmu dan pengajian'],
                    ['II',  'Keilmuan',        'Sifat-sifat Allah (Asmaul Husna bagian 2)'],
                    ['II',  'Kefahaman agama', 'Hukum puasa dan hikmahnya'],
                    ['II',  'Praktik ibadah',  'Praktik membaca Al-Quran dengan tajwid'],
                    ['III', 'Kemandirian',     'Kemandirian dalam menjaga kebersihan'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. Ali Imran 101-150'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', 'Annas - Al-Infitar'],
                    ['II', 'Hafalan Baru',     'Q.S. At-Takwir'],
                    ['II', 'Hafalan',          "Do'a berbuka puasa"],
                    ['II', 'Hafalan Dalil',    'Dalil kewajiban puasa Ramadhan'],
                    ['II', 'Makna',            'Q.S. Abasa'],
                ]
            ),
            $this->bulan('november',
                umum: [
                    ['I',   'Tata Krama',      'Adab menghadiri walimah dan acara sosial'],
                    ['II',  'Keilmuan',        'Mengenal para malaikat dan tugasnya'],
                    ['II',  'Kefahaman agama', 'Hukum zakat dan perhitungannya'],
                    ['II',  'Praktik ibadah',  "Praktik sholat jum'at dan ketentuannya"],
                    ['III', 'Kemandirian',     'Mandiri berbelanja kebutuhan sehari-hari'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. Ali Imran 151-200'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', "Annas - An-Nazi'at"],
                    ['II', 'Hafalan Baru',     "Q.S. An-Naba'"],
                    ['II', 'Hafalan',          "Do'a mohon perlindungan dari fitnah"],
                    ['II', 'Hafalan Dalil',    'Dalil kewajiban zakat'],
                    ['II', 'Makna',            'Q.S. Al-Mursalat'],
                ]
            ),
            $this->bulan('desember',
                umum: [
                    ['I',   'Tata Krama',  'Evaluasi akhlak semester 1 — muhasabah diri'],
                    ['II',  'Keilmuan',    'Review pokok aqidah dan fiqih semester 1'],
                    ['III', 'Kemandirian', 'Presentasi pencapaian kemandirian semester 1'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Review semester 1: Ali Imran'],
                    ['II', 'Hafalan Murajaah', 'Murajaah semester 1 (Annas - Asyarh)'],
                    ['II', 'Hafalan Baru',     'Hafalan pilihan dari semester 1'],
                ]
            ),
            // ── Semester 2 (Jan–Jun) ──
            $this->bulan('januari',
                umum: [
                    ['I',   'Tata Krama',      'Adab di rumah bersama keluarga'],
                    ['I',   'Akhlak Terpuji',  'Sifat sabar dan pemaaf'],
                    ['II',  'Keilmuan',        'Mengenal kitab-kitab Allah'],
                    ['II',  'Kefahaman agama', 'Hukum haji dan umrah'],
                    ['II',  'Praktik ibadah',  'Praktik manasik haji sederhana'],
                    ['III', 'Kemandirian',     'Kemandirian dalam belajar Al-Quran'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. An-Nisa 1-50'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', 'Annas - Al-Lail'],
                    ['II', 'Hafalan Baru',     "Q.S. Asy-Syams"],
                    ['II', 'Hafalan',          "Do'a mohon ilmu yang bermanfaat"],
                    ['II', 'Hafalan Dalil',    'Dalil keutamaan menuntut ilmu'],
                    ['II', 'Makna',            'Q.S. Al-Balad'],
                ]
            ),
            $this->bulan('februari',
                umum: [
                    ['I',   'Tata Krama',      'Adab berteman dan memilih teman yang baik'],
                    ['II',  'Keilmuan',        'Mengenal para Nabi dan Rasul'],
                    ['II',  'Kefahaman agama', 'Hukum muamalah dan jual beli'],
                    ['II',  'Praktik ibadah',  'Praktik membaca Al-Quran tartil dan benar'],
                    ['III', 'Kemandirian',     'Mandiri mengatur jadwal kegiatan harian'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. An-Nisa 51-100'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', 'Annas - Al-Fajr'],
                    ['II', 'Hafalan Baru',     'Q.S. Al-Ghasyiyah'],
                    ['II', 'Hafalan',          "Do'a ketika mendapat musibah"],
                    ['II', 'Hafalan Dalil',    'Dalil larangan riba'],
                    ['II', 'Makna',            "Q.S. Al-A'la"],
                ]
            ),
            $this->bulan('maret',
                umum: [
                    ['I',   'Tata Krama',      'Adab di tempat ibadah (masjid/mushola)'],
                    ['II',  'Keilmuan',        'Kisah Nabi Musa dan hikmahnya'],
                    ['II',  'Kefahaman agama', 'Hukum sholat dalam perjalanan (qashar)'],
                    ['II',  'Praktik ibadah',  "Praktik sholat qashar dan jama'"],
                    ['III', 'Kemandirian',     'Kemandirian menjaga amanah'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. An-Nisa 101-150'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', 'Annas - At-Thariq'],
                    ['II', 'Hafalan Baru',     'Q.S. Al-Buruj'],
                    ['II', 'Hafalan',          "Do'a agar terhindar dari sifat malas"],
                    ['II', 'Hafalan Dalil',    "Dalil tentang jama' dan qashar"],
                    ['II', 'Makna',            'Q.S. Al-Insyiqaq'],
                ]
            ),
            $this->bulan('april',
                umum: [
                    ['I',   'Tata Krama',      'Adab menerima dan memberi tamu'],
                    ['II',  'Keilmuan',        'Hari kiamat dan tanda-tandanya'],
                    ['II',  'Kefahaman agama', 'Hukum shadaqah dan infaq'],
                    ['II',  'Praktik ibadah',  'Praktik sholat witir'],
                    ['III', 'Kemandirian',     'Kemandirian mengelola keuangan pribadi'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. An-Nisa 151-176'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', 'Annas - Al-Mutaffifin'],
                    ['II', 'Hafalan Baru',     'Q.S. Al-Infitar'],
                    ['II', 'Hafalan',          "Do'a setelah wudhu"],
                    ['II', 'Hafalan Dalil',    'Dalil keutamaan shadaqah'],
                    ['II', 'Makna',            'Q.S. At-Takwir'],
                ]
            ),
            $this->bulan('mei',
                umum: [
                    ['I',   'Tata Krama',      'Persiapan menjadi remaja yang berakhlak'],
                    ['II',  'Keilmuan',        'Review akidah: iman kepada takdir Allah'],
                    ['II',  'Praktik ibadah',  'Praktik sholat idul fitri dan idul adha'],
                    ['III', 'Kemandirian',     'Kemandirian menghadapi ujian sekolah'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Q.S. Al-Maidah 1-50'],
                    ['II', 'Tulis',            'Terampil Menulis Arab dan Pegon'],
                    ['II', 'Hafalan Murajaah', "Annas - An-Naba'"],
                    ['II', 'Hafalan Baru',     'Q.S. Abasa'],
                    ['II', 'Hafalan',          "Do'a khotmil Quran"],
                    ['II', 'Makna',            "Q.S. An-Naba'"],
                ]
            ),
            $this->bulan('juni',
                umum: [
                    ['I',   'Tata Krama',  'Evaluasi akhir tahun — muhasabah menyeluruh'],
                    ['II',  'Keilmuan',    'Review seluruh materi Alim Faqih TA 2026/2027'],
                    ['III', 'Kemandirian', 'Presentasi proyek kemandirian tahunan'],
                ],
                individu: [
                    ['II', 'Bacaan',           'Review semester 2: An-Nisa & Al-Maidah'],
                    ['II', 'Hafalan Murajaah', 'Murajaah akhir tahun (Annas - Asyarh)'],
                    ['II', 'Hafalan Baru',     'Hafalan pilihan ujian akhir tahun'],
                ]
            )
        );
    }
}
