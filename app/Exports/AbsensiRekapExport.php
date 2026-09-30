<?php

namespace App\Exports;

use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Services\AbsensiService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AbsensiRekapExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public int $totalPertemuan = 0;

    public function __construct(
        private Kelas $kelas,
        private int $bulan,
        private int $tahun,
    ) {}

    public function title(): string
    {
        return 'Rekap Absensi';
    }

    public function headings(): array
    {
        return ['No', 'Nama Murid', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha', 'Total Pertemuan', 'Kehadiran (%)'];
    }

    public function collection(): Collection
    {
        $pertemuan = Pertemuan::selesai()
            ->where('kelas_id', $this->kelas->id)
            ->whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->get(['id', 'tanggal']);

        $this->totalPertemuan = $pertemuan->count();

        // Persentase tiap murid dihitung sejak absensi pertamanya di kelas ini
        $rekap = app(AbsensiService::class)->rekapKehadiranPerMurid($this->kelas->id, $pertemuan)
            ->sortByDesc('hadir')
            ->values();

        return $rekap->map(function ($item, $i) {
            return [
                $i + 1,
                $item['nama'],
                $item['hadir'],
                $item['terlambat'],
                $item['izin'],
                $item['sakit'],
                $item['alpha'],
                $item['total_pertemuan'],
                $item['persentase'] . '%',
            ];
        });
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("C1:I{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font'      => ['bold' => true],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
