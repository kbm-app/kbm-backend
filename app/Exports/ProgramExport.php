<?php

namespace App\Exports;

use App\Models\Program;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProgramExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Daftar Program';
    }

    public function headings(): array
    {
        return ['No', 'Nama Program', 'Jenis', 'Lokasi', 'Deskripsi', 'Status'];
    }

    public function collection(): Collection
    {
        $rows = Program::query()
            ->when($this->filters['search'] ?? null, fn ($q, $v) =>
                $q->where('nama', 'ilike', "%{$v}%")
            )
            ->when(isset($this->filters['is_aktif']), fn ($q) =>
                $q->where('is_aktif', filter_var($this->filters['is_aktif'], FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy('nama')
            ->get();

        $jenisLabel = [
            'tahfidz'    => 'Tahfidz',
            'tahsin'     => 'Tahsin',
            'fiqih'      => 'Fiqih',
            'akhlak'     => 'Akhlak',
            'bahasa'     => 'Bahasa',
            'lainnya'    => 'Lainnya',
        ];

        return $rows->map(function ($p, $i) use ($jenisLabel) {
            return [
                $i + 1,
                $p->nama,
                $jenisLabel[$p->jenis] ?? ucfirst($p->jenis),
                $p->lokasi ?? '-',
                $p->deskripsi ?? '-',
                $p->is_aktif ? 'Aktif' : 'Tidak Aktif',
            ];
        });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
