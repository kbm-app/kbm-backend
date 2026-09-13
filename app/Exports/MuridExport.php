<?php

namespace App\Exports;

use App\Models\Murid;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class MuridExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function collection(): Collection
    {
        $query = Murid::with(['waliMurid', 'kelasAktif.kelas'])
            ->when($this->filters['search'] ?? null, fn ($q, $v) => $q->where('nama', 'ilike', "%{$v}%"))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['kelas_id'] ?? null, fn ($q, $v) =>
                $q->whereHas('kelasAktif', fn ($k) => $k->whereIn('kelas_id', (array) $v))
            )
            ->when($this->filters['usia_min'] ?? null, fn ($q, $v) =>
                $q->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) >= ?", [$v])
            )
            ->when($this->filters['usia_max'] ?? null, fn ($q, $v) =>
                $q->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) <= ?", [$v])
            )
            ->orderBy('nama')
            ->get();

        return $query->map(function (Murid $murid, int $index) {
            $waliUtama = $murid->waliMurid->firstWhere('is_primary', true)
                ?? $murid->waliMurid->first();

            $kelasNama = $murid->kelasAktif->map(fn ($mk) => $mk->kelas->nama)->join(', ');

            return [
                'no'             => $index + 1,
                'nama'           => $murid->nama,
                'tempat_lahir'   => $murid->tempat_lahir ?? '',
                'jenis_kelamin'  => $murid->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                'tanggal_lahir'  => $murid->tanggal_lahir?->format('d/m/Y') ?? '',
                'tanggal_masuk'  => $murid->tanggal_masuk?->format('d/m/Y') ?? '',
                'kelas'          => $kelasNama,
                'status'         => ucfirst($murid->status),
                'nama_wali'      => $waliUtama?->nama ?? '',
                'hp_wali'        => $waliUtama ? implode(', ', $waliUtama->phones) : '',
                'alamat'         => $murid->alamat ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Tempat Lahir', 'Jenis Kelamin', 'Tanggal Lahir', 'Tanggal Masuk',
                'Kelas', 'Status', 'Nama Wali', 'No. HP Wali', 'Alamat'];
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
        return 'Data Murid';
    }
}
