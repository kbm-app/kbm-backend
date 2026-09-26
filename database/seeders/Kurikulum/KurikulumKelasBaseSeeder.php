<?php

namespace Database\Seeders\Kurikulum;

use App\Models\BabKurikulum;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\Materi;
use Illuminate\Database\Seeder;

abstract class KurikulumKelasBaseSeeder extends Seeder
{
    protected const TA = '2026/2027';

    /** Nama kelas pemakai kurikulum — bisa lebih dari satu (mis. ['Kelas 3-1', 'Kelas 3-2']). */
    abstract protected function kelasNama(): string|array;
    abstract protected function kurikulumNama(): string;
    abstract protected function materiData(): array;

    protected function babList(): array
    {
        return [
            ['kode' => 'I',   'nama' => 'Ahlaqul Karimah'],
            ['kode' => 'II',  'nama' => 'Alim Faqih'],
            ['kode' => 'III', 'nama' => 'Mandiri'],
        ];
    }

    public function run(): void
    {
        $kelasNama = (array) $this->kelasNama();
        $kelas     = Kelas::whereIn('nama', $kelasNama)->get();

        foreach (array_diff($kelasNama, $kelas->pluck('nama')->all()) as $nama) {
            $this->command->warn("Kelas '{$nama}' tidak ditemukan, skip.");
        }
        if ($kelas->isEmpty()) {
            return;
        }

        $materiData = $this->materiData();
        if (empty($materiData)) {
            $this->command->warn("Data materi '{$this->kurikulumNama()}' belum diisi, skip.");
            return;
        }

        $kurikulum = Kurikulum::untukKelas($kelas->first()->id)->tahunAjaran(static::TA)->first()
            ?? Kurikulum::create(['nama' => $this->kurikulumNama(), 'tahun_ajaran' => static::TA]);
        $kurikulum->kelas()->syncWithoutDetaching($kelas->pluck('id'));

        $babMap = [];
        foreach ($this->babList() as $idx => $bab) {
            $record = BabKurikulum::firstOrCreate(
                ['kurikulum_id' => $kurikulum->id, 'kode' => $bab['kode']],
                ['nama' => $bab['nama'], 'urutan' => $idx + 1]
            );
            $babMap[$bab['kode']] = $record->id;
        }

        $urutan = [];
        foreach ($materiData as $item) {
            $babId = $babMap[$item['bab']] ?? null;
            if (! $babId) {
                continue;
            }

            $key          = $babId . '_' . $item['tipe'] . '_' . $item['bulan'];
            $urutan[$key] = ($urutan[$key] ?? 0) + 1;

            Materi::firstOrCreate(
                [
                    'kurikulum_id'     => $kurikulum->id,
                    'bab_kurikulum_id' => $babId,
                    'judul'            => $item['judul'],
                    'target_bulan'     => $item['bulan'],
                    // Judul yang sama bisa muncul sebagai materi umum & individu di bulan yang sama
                    'tipe'             => $item['tipe'],
                ],
                [
                    'sub_bab' => $item['sub_bab'] ?? null,
                    'metode'  => $item['metode'] ?? null,
                    'urutan'  => $urutan[$key],
                ]
            );
        }
    }

    /**
     * Helper: ubah data per-bulan menjadi flat array materi.
     *
     * @param array $umum     [[bab_kode, sub_bab, judul], ...]
     * @param array $individu [[bab_kode, sub_bab, judul], ...]
     */
    protected function bulan(string $bulan, array $umum, array $individu): array
    {
        $rows = [];
        foreach ($umum as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'umum', 'bulan' => $bulan];
        }
        foreach ($individu as [$bab, $subBab, $judul]) {
            $rows[] = ['bab' => $bab, 'sub_bab' => $subBab, 'judul' => $judul, 'tipe' => 'individu', 'bulan' => $bulan];
        }
        return $rows;
    }
}
