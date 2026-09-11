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

    abstract protected function kelasNama(): string;
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
        $kelas = Kelas::where('nama', $this->kelasNama())->first();
        if (! $kelas) {
            $this->command->warn("Kelas '{$this->kelasNama()}' tidak ditemukan, skip.");
            return;
        }

        $materiData = $this->materiData();
        if (empty($materiData)) {
            $this->command->warn("Data materi '{$this->kelasNama()}' belum diisi, skip.");
            return;
        }

        $kurikulum = Kurikulum::firstOrCreate(
            ['kelas_id' => $kelas->id, 'tahun_ajaran' => static::TA],
            ['nama' => $this->kurikulumNama()]
        );

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
                ],
                [
                    'sub_bab' => $item['sub_bab'] ?? null,
                    'tipe'    => $item['tipe'],
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
