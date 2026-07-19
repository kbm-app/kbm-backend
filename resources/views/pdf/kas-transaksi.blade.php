<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 15mm 18mm; }
  * { box-sizing: border-box; }
  body, h1, h2 { margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }

  .header { text-align: center; padding-bottom: 12px; border-bottom: 2px solid #334155; margin-bottom: 10px; }
  .header h1 { font-size: 15px; font-weight: bold; letter-spacing: 0.5px; }
  .header h2 { font-size: 11px; font-weight: normal; color: #475569; margin-top: 2px; }

  .meta { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 9px; color: #64748b; }

  .filter-info { font-size: 9px; color: #475569; margin-bottom: 8px; }
  .filter-info span { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; margin-right: 4px; }

  table { width: 100%; border-collapse: collapse; }
  thead tr { background-color: #334155; color: #f8fafc; }
  thead th { padding: 6px 7px; text-align: left; font-size: 9px; font-weight: 600; letter-spacing: 0.3px; }
  tbody tr:nth-child(even) { background-color: #f8fafc; }
  tbody tr:nth-child(odd) { background-color: #ffffff; }
  tbody td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }

  .text-right { text-align: right; }
  .text-center { text-align: center; }

  .pemasukan { color: #166534; font-weight: 600; }
  .pengeluaran { color: #991b1b; font-weight: 600; }

  .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 600; }
  .badge-pemasukan  { background: #dcfce7; color: #166534; }
  .badge-pengeluaran { background: #fee2e2; color: #991b1b; }

  .summary { margin-top: 12px; border-top: 2px solid #334155; padding-top: 10px; }
  .summary table { width: 280px; margin-left: auto; }
  .summary td { padding: 3px 8px; font-size: 10px; }
  .summary .total-row { font-weight: bold; border-top: 1px solid #334155; }
  .summary .saldo-positif { color: #166534; font-weight: bold; }
  .summary .saldo-negatif { color: #991b1b; font-weight: bold; }

  .footer { position: fixed; bottom: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 5px; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; }
  .page-number:after { content: counter(page) " / " counter(pages); }
</style>
</head>
<body>

<div class="header">
  <h1>LAPORAN KAS KELAS</h1>
  <h2>{{ $kelas->nama }} &mdash; {{ $periodeLabel }}</h2>
</div>

<div class="meta">
  <span>Dicetak oleh: {{ $cetakOleh }}</span>
  <span>Tanggal cetak: {{ now()->translatedFormat('d F Y, H:i') }}</span>
</div>

@if(count($filterLabel) > 0)
<div class="filter-info">
  Filter:
  @foreach($filterLabel as $label)
    <span>{{ $label }}</span>
  @endforeach
</div>
@endif

<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
      <th style="width:68px">Tanggal</th>
      <th>Keterangan</th>
      <th style="width:90px">Kategori</th>
      <th style="width:70px" class="text-center">Jenis</th>
      <th style="width:90px" class="text-right">Jumlah (Rp)</th>
    </tr>
  </thead>
  <tbody>
    @forelse($transaksi as $i => $t)
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>{{ $t->tanggal->format('d/m/Y') }}</td>
      <td>{{ $t->keterangan ?? '-' }}</td>
      <td>{{ $t->kategori?->nama ?? '-' }}</td>
      <td class="text-center">
        <span class="badge badge-{{ $t->kategori?->jenis ?? '' }}">
          {{ $t->kategori?->jenis === 'pemasukan' ? 'Pemasukan' : ($t->kategori?->jenis === 'pengeluaran' ? 'Pengeluaran' : '-') }}
        </span>
      </td>
      <td class="{{ $t->kategori?->jenis === 'pemasukan' ? 'pemasukan' : 'pengeluaran' }} text-right">
        {{ number_format((float)$t->jumlah, 0, ',', '.') }}
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="6" style="text-align:center; color:#94a3b8; padding:20px">Tidak ada data transaksi</td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="summary">
  <table>
    <tr>
      <td>Total Pemasukan</td>
      <td class="text-right pemasukan">{{ number_format($totalPemasukan, 0, ',', '.') }}</td>
    </tr>
    <tr>
      <td>Total Pengeluaran</td>
      <td class="text-right pengeluaran">{{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
    </tr>
    <tr class="total-row">
      <td>Saldo</td>
      <td class="text-right {{ $saldo >= 0 ? 'saldo-positif' : 'saldo-negatif' }}">
        {{ number_format($saldo, 0, ',', '.') }}
      </td>
    </tr>
  </table>
</div>

<div class="footer">
  <span>Total: {{ $transaksi->count() }} transaksi</span>
  <span>Halaman <span class="page-number"></span></span>
</div>

</body>
</html>
