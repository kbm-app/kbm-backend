<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MuridTemplatePanduanSheet implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    public function array(): array
    {
        return [
            ['Kolom', 'Wajib?', 'Keterangan', 'Contoh'],
            ['nama', 'Ya', 'Nama lengkap murid', 'Ahmad Fauzi'],
            ['tempat_lahir', 'Tidak', 'Kota/tempat lahir', 'Jakarta'],
            ['jenis_kelamin', 'Ya', 'L = Laki-laki, P = Perempuan', 'L'],
            ['tanggal_lahir', 'Ya', 'Format: YYYY-MM-DD', '2015-03-12'],
            ['tanggal_masuk', 'Tidak', 'Format: YYYY-MM-DD. Kosongkan jika tidak diketahui.', '2023-07-17'],
            ['alamat', 'Tidak', 'Alamat lengkap', 'Jl. Mawar No. 5, Jakarta'],
            ['nama_wali', 'Tidak', 'Nama wali/orang tua', 'Budi Santoso'],
            ['hubungan_wali', 'Tidak', 'ayah / ibu / kakak / nenek / kakek / wali_lain', 'ayah'],
            ['hp_wali', 'Tidak', 'Nomor HP wali (format bebas, pisahkan dengan koma jika lebih dari satu)', '081234567890, 081298765432'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CBD5E1'],
            ]],
        ];
    }

    public function title(): string
    {
        return 'Panduan';
    }
}
