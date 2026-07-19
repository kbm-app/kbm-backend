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
  tbody tr:nth-child(even) { background-color: #f8fafc; }
  tbody tr:nth-child(odd) { background-color: #ffffff; }
  tbody td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }

  .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 600; }
  .badge-aktif    { background: #dcfce7; color: #166534; }
  .badge-nonaktif { background: #fee2e2; color: #991b1b; }
  .badge-alumni   { background: #dbeafe; color: #1e40af; }
  .badge-pindah   { background: #fef9c3; color: #854d0e; }

  .footer { position: fixed; bottom: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 5px; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; }
  .page-number:after { content: counter(page) " / " counter(pages); }
</style>
</head>
<body>

<div class="header">
  <h1>DAFTAR MURID KELAS</h1>
  <h2>{{ $kelas->nama }}</h2>
</div>

<div class="meta">
  <span>Dicetak oleh: {{ $cetakOleh }}</span>
  <span>Tanggal cetak: {{ now()->translatedFormat('d F Y, H:i') }}</span>
</div>

@if($kelas->deskripsi)
<div class="info-box">
  <span>{{ $kelas->deskripsi }}</span>
</div>
@endif

<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
      <th>Nama</th>
      <th style="width:50px">JK</th>
      <th style="width:70px">Tgl Lahir</th>
      <th style="width:70px">Tgl Masuk</th>
      <th style="width:48px">Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($muridKelas as $i => $mk)
    @php $murid = $mk->murid; @endphp
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>{{ $murid->nama }}</td>
      <td>{{ $murid->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
      <td>{{ $murid->tanggal_lahir ? \Carbon\Carbon::parse($murid->tanggal_lahir)->format('d/m/Y') : '-' }}</td>
      <td>{{ $mk->tanggal_masuk?->format('d/m/Y') ?? '-' }}</td>
      <td>
        <span class="badge badge-{{ $mk->status }}">{{ ucfirst($mk->status) }}</span>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="6" style="text-align:center; color:#94a3b8; padding:20px">Tidak ada data murid aktif</td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="footer">
  <span>Total: {{ $muridKelas->count() }} murid aktif</span>
  <span>Halaman <span class="page-number"></span></span>
</div>

</body>
</html>
