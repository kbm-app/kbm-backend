<?php

namespace App\Services;

use App\Models\KasTransaksi;
use Illuminate\Support\Collection;

class KasService
{
    public function hitungSaldo(int $kelasId, ?int $bulan = null, ?int $tahun = null): array
    {
        $transaksi = KasTransaksi::with('kategori')
            ->where('kelas_id', $kelasId)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($tahun, fn ($q) => $q->whereYear('tanggal', $tahun))
            ->get();

        return $this->ringkasSaldo($transaksi);
    }

    /**
     * Sama seperti hitungSaldo() tapi untuk banyak kelas sekaligus dalam satu query,
     * menghindari N+1 saat dipanggil dalam loop (mis. rekap semua kelas).
     */
    public function hitungSaldoBanyakKelas(Collection $kelasIds, ?int $bulan = null, ?int $tahun = null): array
    {
        $transaksiPerKelas = KasTransaksi::with('kategori')
            ->whereIn('kelas_id', $kelasIds)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($tahun, fn ($q) => $q->whereYear('tanggal', $tahun))
            ->get()
            ->groupBy('kelas_id');

        $result = [];
        foreach ($kelasIds as $kelasId) {
            $result[$kelasId] = $this->ringkasSaldo($transaksiPerKelas->get($kelasId, collect()));
        }

        return $result;
    }

    private function ringkasSaldo(Collection $transaksi): array
    {
        $pemasukan   = $transaksi->filter(fn ($t) => $t->kategori?->jenis === 'pemasukan');
        $pengeluaran = $transaksi->filter(fn ($t) => $t->kategori?->jenis === 'pengeluaran');

        $totalPemasukan   = $pemasukan->sum('jumlah');
        $totalPengeluaran = $pengeluaran->sum('jumlah');

        $breakdownPemasukan = $pemasukan
            ->groupBy('kategori_id')
            ->map(fn ($items) => [
                'kategori' => $items->first()->kategori->nama,
                'jumlah'   => $items->sum('jumlah'),
            ])
            ->values();

        $breakdownPengeluaran = $pengeluaran
            ->groupBy('kategori_id')
            ->map(fn ($items) => [
                'kategori' => $items->first()->kategori->nama,
                'jumlah'   => $items->sum('jumlah'),
            ])
            ->values();

        return [
            'total_pemasukan'      => $totalPemasukan,
            'total_pengeluaran'    => $totalPengeluaran,
            'saldo'                => $totalPemasukan - $totalPengeluaran,
            'breakdown_pemasukan'  => $breakdownPemasukan,
            'breakdown_pengeluaran' => $breakdownPengeluaran,
        ];
    }
}
