<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MuridTemplateDataSheet implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function array(): array
    {
        return [
            ['Ahmad Fauzi', 'Jakarta', 'L', '2015-03-12', '2023-07-17', 'Jl. Mawar No. 5, Jakarta', 'Budi Santoso', 'ayah', '081234567890'],
        ];
    }

    public function headings(): array
    {
        return ['nama*', 'tempat_lahir', 'jenis_kelamin*', 'tanggal_lahir*', 'tanggal_masuk', 'alamat', 'nama_wali', 'hubungan_wali', 'hp_wali'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CBD5E1'],
            ]],
            2 => ['font' => ['color' => ['rgb' => '94A3B8'], 'italic' => true]],
        ];
    }

    public function title(): string
    {
        return 'Data Murid';
    }
}
