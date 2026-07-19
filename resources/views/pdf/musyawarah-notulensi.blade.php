<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 18mm 20mm; }
  * { box-sizing: border-box; }
  body, h1, h2 { margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; line-height: 1.5; }

  .header { text-align: center; padding-bottom: 12px; border-bottom: 2px solid #334155; margin-bottom: 12px; }
  .header h1 { font-size: 14px; font-weight: bold; letter-spacing: 0.5px; }
  .header h2 { font-size: 11px; font-weight: normal; color: #475569; margin-top: 2px; }

  .meta { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 9px; color: #64748b; }

  .info-grid { display: table; width: 100%; margin-bottom: 14px; font-size: 9px; }
  .info-row  { display: table-row; }
  .info-label { display: table-cell; width: 110px; color: #64748b; padding: 2px 0; }
  .info-value { display: table-cell; font-weight: 600; padding: 2px 0; }

  .catatan-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 10px; margin-bottom: 14px; font-size: 9px; }
  .catatan-box .label { font-size: 8px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }

  .section-title { font-size: 11px; font-weight: bold; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px; margin-bottom: 8px; margin-top: 14px; }

  /* Laporan per kelas */
  .laporan-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  .laporan-head { display: table-row; background-color: #334155; color: #f8fafc; }
  .laporan-row  { display: table-row; }
  .laporan-row:nth-child(even) { background: #f8fafc; }
  .laporan-head .cell, .laporan-row .cell { display: table-cell; padding: 5px 7px; font-size: 9px; border-bottom: 1px solid #e2e8f0; }
  .laporan-head .cell { font-weight: 600; font-size: 8px; letter-spacing: 0.3px; }
  .text-center { text-align: center; }

  /* Notulensi */
  .notulensi-group { margin-bottom: 10px; }
  .notulensi-kategori { font-size: 9px; font-weight: bold; color: #475569; background: #f1f5f9; padding: 4px 8px; border-radius: 3px; margin-bottom: 4px; display: inline-block; }

  .notulensi-item { border: 1px solid #e2e8f0; border-radius: 4px; padding: 7px 10px; margin-bottom: 5px; }
  .notulensi-isi { font-size: 10px; margin-bottom: 4px; }
  .notulensi-meta { font-size: 8px; color: #64748b; }
  .badge-tl { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 7px; font-weight: 600; }
  .badge-open   { background: #fef9c3; color: #854d0e; }
  .badge-done   { background: #dcfce7; color: #166534; }
  .badge-na     { background: #f1f5f9; color: #475569; }

  .footer { position: fixed; bottom: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 5px; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; }
  .page-number:after { content: counter(page) " / " counter(pages); }
</style>
</head>
<body>

<div class="header">
  <h1>NOTULENSI MUSYAWARAH</h1>
  <h2>{{ $bulanLabel }} {{ $musyawarah->tahun }}</h2>
</div>

<div class="meta">
  <span>Dicetak oleh: {{ $cetakOleh }}</span>
  <span>Tanggal cetak: {{ now()->translatedFormat('d F Y, H:i') }}</span>
</div>

<div class="info-grid">
  <div class="info-row">
    <span class="info-label">Tanggal Musyawarah</span>
    <span class="info-value">{{ $musyawarah->tanggal->translatedFormat('d F Y') }}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Periode</span>
    <span class="info-value">{{ $bulanLabel }} {{ $musyawarah->tahun }}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Status</span>
    <span class="info-value">{{ $musyawarah->status === 'selesai' ? 'Selesai' : 'Draft' }}</span>
  </div>
</div>

@if($musyawarah->catatan_umum)
<div class="catatan-box">
  <div class="label">Catatan Umum</div>
  <div>{{ $musyawarah->catatan_umum }}</div>
</div>
@endif

@if($laporan->count() > 0)
<div class="section-title">Evaluasi Per Kelas</div>
<div class="laporan-grid">
  <div class="laporan-head">
    <span class="cell">Kelas</span>
    <span class="cell text-center">Jml Murid</span>
    <span class="cell text-center">Kehadiran</span>
    <span class="cell text-center">Progress</span>
    <span class="cell">Kendala / Planning</span>
  </div>
  @foreach($laporan as $l)
  <div class="laporan-row">
    <span class="cell">{{ $l->kelas?->nama ?? '-' }}</span>
    <span class="cell text-center">{{ $l->snapshot_jumlah_murid ?? '-' }}</span>
    <span class="cell text-center">{{ $l->snapshot_kehadiran_persen !== null ? $l->snapshot_kehadiran_persen . '%' : '-' }}</span>
    <span class="cell text-center">{{ $l->snapshot_progress_persen !== null ? $l->snapshot_progress_persen . '%' : '-' }}</span>
    <span class="cell" style="font-size:9px">
      @if($l->kendala_pengajar) <strong>Kendala:</strong> {{ $l->kendala_pengajar }}<br> @endif
      @if($l->planning) <strong>Plan:</strong> {{ $l->planning }} @endif
    </span>
  </div>
  @endforeach
</div>
@endif

@if($notulensi->count() > 0)
<div class="section-title">Notulensi</div>
@foreach($notulensiByKategori as $kategori => $items)
<div class="notulensi-group">
  <span class="notulensi-kategori">{{ ucfirst($kategori) }}</span>
  @foreach($items as $item)
  <div class="notulensi-item">
    <div class="notulensi-isi">{{ $item->isi }}</div>
    <div class="notulensi-meta">
      @if($item->penanggung_jawab) PJ: <strong>{{ $item->penanggung_jawab }}</strong> &nbsp;&bull;&nbsp; @endif
      Tindak Lanjut:
      <span class="badge-tl {{ $item->status_tindak_lanjut === 'open' ? 'badge-open' : ($item->status_tindak_lanjut === 'done' ? 'badge-done' : 'badge-na') }}">
        {{ $item->status_tindak_lanjut === 'open' ? 'Open' : ($item->status_tindak_lanjut === 'done' ? 'Done' : 'N/A') }}
      </span>
    </div>
  </div>
  @endforeach
</div>
@endforeach
@endif

<div class="footer">
  <span>Notulensi &mdash; {{ $bulanLabel }} {{ $musyawarah->tahun }}</span>
  <span>Halaman <span class="page-number"></span></span>
</div>

</body>
</html>
