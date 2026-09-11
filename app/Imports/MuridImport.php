<?php

namespace App\Imports;

use App\Models\Murid;
use App\Models\WaliMurid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;

class MuridImport implements ToCollection, WithHeadingRow
{
    public int $totalRows    = 0;
    public int $successCount = 0;
    public int $errorCount   = 0;

    /** @var Failure[] */
    private array $importFailures = [];

    public function collection(Collection $rows): void
    {
        $this->totalRows = $rows->count();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2: baris 1 = heading, index mulai 0

            $errors = $this->validateRow($row->toArray());
            if (!empty($errors)) {
                $this->errorCount++;
                foreach ($errors as $field => $messages) {
                    $this->importFailures[] = new Failure($rowNumber, $field, $messages, $row->toArray());
                }
                continue;
            }

            $murid = Murid::create([
                'nama'          => trim($row['nama']),
                'tempat_lahir'  => $row['tempat_lahir'] ? trim($row['tempat_lahir']) : null,
                'jenis_kelamin' => strtoupper(substr(trim($row['jenis_kelamin']), 0, 1)),
                'tanggal_lahir' => Carbon::parse($row['tanggal_lahir']),
                'tanggal_masuk' => $row['tanggal_masuk'] ? Carbon::parse($row['tanggal_masuk']) : null,
                'alamat'        => $row['alamat'] ? trim($row['alamat']) : null,
                'status'        => 'aktif',
            ]);

            if (!empty($row['nama_wali'])) {
                $hubungan = strtolower(trim($row['hubungan_wali'] ?? 'wali_lain'));
                if (!in_array($hubungan, ['ayah', 'ibu', 'kakak', 'nenek', 'kakek', 'wali_lain'])) {
                    $hubungan = 'wali_lain';
                }

                $phones = array_values(array_filter(array_map(
                    'trim',
                    explode(',', $row['hp_wali'] ?? '')
                )));

                WaliMurid::create([
                    'murid_id'   => $murid->id,
                    'nama'       => trim($row['nama_wali']),
                    'hubungan'   => $hubungan,
                    'phones'     => $phones ?: [''],
                    'is_primary' => true,
                ]);
            }

            $this->successCount++;
        }
    }

    private function validateRow(array $row): array
    {
        $errors = [];

        if (empty($row['nama'])) {
            $errors['nama'] = ['Kolom nama wajib diisi.'];
        }

        $jk = strtoupper(substr(trim($row['jenis_kelamin'] ?? ''), 0, 1));
        if (!in_array($jk, ['L', 'P'])) {
            $errors['jenis_kelamin'] = ['Jenis kelamin harus L (Laki-laki) atau P (Perempuan).'];
        }

        if (empty($row['tanggal_lahir'])) {
            $errors['tanggal_lahir'] = ['Kolom tanggal lahir wajib diisi.'];
        } elseif (!$this->isValidDate($row['tanggal_lahir'])) {
            $errors['tanggal_lahir'] = ['Format tanggal lahir tidak valid. Gunakan format YYYY-MM-DD.'];
        }

        if (!empty($row['tanggal_masuk']) && !$this->isValidDate($row['tanggal_masuk'])) {
            $errors['tanggal_masuk'] = ['Format tanggal masuk tidak valid. Gunakan format YYYY-MM-DD.'];
        }

        return $errors;
    }

    private function isValidDate(mixed $value): bool
    {
        try {
            Carbon::parse($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /** @return Failure[] */
    public function getFailures(): array
    {
        return $this->importFailures;
    }
}
