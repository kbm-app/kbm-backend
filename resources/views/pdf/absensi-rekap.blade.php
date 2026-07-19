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

  .info-box { font-size: 9px; color: #475569; margin-bottom: 8px; }
  .info-box span { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; margin-right: 4px; }

  table { width: 100%; border-collapse: collapse; }
  thead tr { background-color: #334155; color: #f8fafc; }
  thead th { padding: 6px 7px; text-align: left; font-size: 9px; font-weight: 600; letter-spacing: 0.3px; }
  thead th.text-center { text-align: center; }
  tbody tr:nth-child(even) { background-color: #f8fafc; }
  tbody tr:nth-child(odd) { background-color: #ffffff; }
  tbody td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
  tbody td.text-center { text-align: center; }

  .pct-bar { display: inline-block; width: 40px; height: 5px; background: #e2e8f0; border-radius: 3px; margin-right: 4px; vertical-align: middle; }
  .pct-fill { display: inline-block; height: 100%; border-radius: 3px; }
  .pct-high  { background: #22c55e; }
  .pct-mid   { background: #f59e0b; }
  .pct-low   { background: #ef4444; }

  .pct-text-high  { color: #166534; font-weight: 600; }
  .pct-text-mid   { color: #854d0e; font-weight: 600; }
  .pct-text-low   { color: #991b1b; font-weight: 600; }

  .text-hadir      { color: #166534; font-weight: 600; }
  .text-terlambat  { color: #854d0e; }
  .text-alpha      { color: #991b1b; font-weight: 600; }

  .footer { position: fixed; bottom: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 5px; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; }
  .page-number:after { content: counter(page) " / " counter(pages); }
</style>
</head>
<body>

<div class="header">
  <h1>REKAP ABSENSI MURID</h1>
  <h2>{{ $kelas->nama }} &mdash; {{ $periodeLabel }}</h2>
</div>

<div class="meta">
  <span>Dicetak oleh: {{ $cetakOleh }}</span>
  <span>Tanggal cetak: {{ now()->translatedFormat('d F Y, H:i') }}</span>
</div>

<div class="info-box">
  <span>Total pertemuan: {{ $totalPertemuan }} sesi</span>
</div>

<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
      <th>Nama Murid</th>
      <th style="width:45px" class="text-center">Hadir</th>
      <th style="width:55px" class="text-center">Terlambat</th>
      <th style="width:40px" class="text-center">Izin</th>
      <th style="width:45px" class="text-center">Sakit</th>
      <th style="width:45px" class="text-center">Alpha</th>
      <th style="width:90px" class="text-center">Kehadiran</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rekap as $i => $item)
    @php
      $pct      = $item['persentase'];
      $fillClass = $pct >= 80 ? 'pct-fill pct-high' : ($pct >= 60 ? 'pct-fill pct-mid' : 'pct-fill pct-low');
      $txtClass  = $pct >= 80 ? 'pct-text-high'     : ($pct >= 60 ? 'pct-text-mid'     : 'pct-text-low');
    @endphp
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>{{ $item['nama'] }}</td>
      <td class="text-center text-hadir">{{ $item['hadir'] }}</td>
      <td class="text-center text-terlambat">{{ $item['terlambat'] }}</td>
      <td class="text-center">{{ $item['izin'] }}</td>
      <td class="text-center">{{ $item['sakit'] }}</td>
      <td class="text-center text-alpha">{{ $item['alpha'] }}</td>
      <td class="text-center">
        <span class="pct-bar"><span class="{{ $fillClass }}" style="width:{{ $pct }}%"></span></span>
        <span class="{{ $txtClass }}">{{ $pct }}%</span>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="8" style="text-align:center; color:#94a3b8; padding:20px">Tidak ada data absensi</td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="footer">
  <span>Total: {{ count($rekap) }} murid</span>
  <span>Halaman <span class="page-number"></span></span>
</div>

</body>
</html>
