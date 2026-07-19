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
  tbody td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }

  .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 600; }
  .badge-aktif    { background: #dcfce7; color: #166534; }
  .badge-nonaktif { background: #fee2e2; color: #991b1b; }

  .footer { position: fixed; bottom: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 5px; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; }
  .page-number:after { content: counter(page) " / " counter(pages); }
</style>
</head>
<body>

<div class="header">
  <h1>DAFTAR PENGAJAR</h1>
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
      <th>Nama</th>
      <th style="width:40px">JK</th>
      <th style="width:65px">Tgl Lahir</th>
      <th style="width:65px">Tgl Bergabung</th>
      <th>Pendidikan</th>
      <th>No. HP</th>
      <th style="width:52px">Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($pengajar as $i => $p)
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>{{ $p->user?->name ?? '-' }}</td>
      <td>{{ $p->jenis_kelamin }}</td>
      <td>{{ $p->tanggal_lahir?->format('d/m/Y') ?? '-' }}</td>
      <td>{{ $p->tanggal_bergabung?->format('d/m/Y') }}</td>
      <td>{{ $p->pendidikan_terakhir ?? '-' }}</td>
      <td>{{ $p->user?->phone ?? '-' }}</td>
      <td>
        <span class="badge badge-{{ $p->is_aktif ? 'aktif' : 'nonaktif' }}">
          {{ $p->is_aktif ? 'Aktif' : 'Tidak Aktif' }}
        </span>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="8" style="text-align:center; color:#94a3b8; padding:20px">Tidak ada data</td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="footer">
  <span>Total: {{ $pengajar->count() }} pengajar</span>
  <span>Halaman <span class="page-number"></span></span>
</div>

</body>
</html>
