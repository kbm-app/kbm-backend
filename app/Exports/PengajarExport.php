<?php

namespace App\Exports;

use App\Models\Pengajar;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class PengajarExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function collection(): Collection
    {
        $query = Pengajar::with('user')
            ->when($this->filters['search'] ?? null, fn ($q, $v) =>
                $q->whereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$v}%"))
            )
            ->when(isset($this->filters['is_aktif']), fn ($q) =>
                $q->where('is_aktif', filter_var($this->filters['is_aktif'], FILTER_VALIDATE_BOOLEAN))
            )
            ->get()
            ->sortBy('user.name')
            ->values();

        return $query->map(function (Pengajar $pengajar, int $index) {
            return [
                'no'                  => $index + 1,
                'nama'                => $pengajar->user?->name ?? '',
                'email'               => $pengajar->user?->email ?? '',
                'no_hp'               => $pengajar->user?->phone ?? '',
                'jenis_kelamin'       => $pengajar->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                'tanggal_lahir'       => $pengajar->tanggal_lahir?->format('d/m/Y') ?? '',
                'pendidikan_terakhir' => $pengajar->pendidikan_terakhir ?? '',
                'tanggal_bergabung'   => $pengajar->tanggal_bergabung?->format('d/m/Y') ?? '',
                'status'              => $pengajar->is_aktif ? 'Aktif' : 'Tidak Aktif',
                'alamat'              => $pengajar->alamat ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Email', 'No. HP', 'Jenis Kelamin',
                'Tanggal Lahir', 'Pendidikan Terakhir', 'Tanggal Bergabung', 'Status', 'Alamat'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ]],
        ];
    }

    public function title(): string
    {
        return 'Data Pengajar';
    }
}
