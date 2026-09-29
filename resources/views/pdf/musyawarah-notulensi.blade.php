@php
  // Warna persentase disamakan dengan UI: hijau ≥ 80, kuning ≥ 60, merah < 60
  $warnaPersen = fn ($v) => $v === null ? '#94a3b8' : ($v >= 80 ? '#15803d' : ($v >= 60 ? '#b45309' : '#b91c1c'));
  $persen = fn ($v) => $v === null ? '–' : rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ',') . '%';

  $rata = function (string $kolom) use ($laporan) {
      $nilai = $laporan->pluck($kolom)->filter(fn ($v) => $v !== null);
      return $nilai->isEmpty() ? null : round($nilai->avg(), 1);
  };

  $kategoriLabel = ['usulan' => 'Usulan', 'keputusan' => 'Keputusan', 'problem' => 'Problem', 'lainnya' => 'Lainnya'];
  $kategoriWarna = [
      'usulan'    => ['#dbeafe', '#1d4ed8'],
      'keputusan' => ['#dcfce7', '#15803d'],
      'problem'   => ['#fee2e2', '#b91c1c'],
      'lainnya'   => ['#f1f5f9', '#475569'],
  ];
  $statusTl = [
      'open'    => ['Open',    '#fef3c7', '#b45309'],
      'selesai' => ['Selesai', '#dcfce7', '#15803d'],
      'ditunda' => ['Ditunda', '#f1f5f9', '#475569'],
  ];

  $laporanDenganCatatan = $laporan->filter(fn ($l) =>
      $l->kendala_murid_auto || $l->kendala_pengajar || $l->planning || $l->tindak_lanjut
  );
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
@include('pdf.partials.font')
<style>
  @page { margin: 16mm 16mm 20mm 16mm; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.5; }
  table { width: 100%; border-collapse: collapse; }
  td, th { vertical-align: top; }
  .muted { color: #64748b; }
  .right { text-align: right; }
  .center { text-align: center; }

  /* Header */
  .header { background: #059669; color: #ffffff; border-radius: 6px; }
  .header td { padding: 14px 16px; vertical-align: middle; }
  .brand { font-size: 8px; letter-spacing: 1px; text-transform: uppercase; color: #d1fae5; }
  .title { font-size: 18px; font-weight: bold; margin-top: 2px; }
  .subtitle { font-size: 10px; color: #ecfdf5; margin-top: 1px; }
  .status { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8.5px; font-weight: bold; }
  .status-selesai { background: #ffffff; color: #047857; }
  .status-draft { background: #fef3c7; color: #92400e; }
  .header-date { font-size: 9px; color: #d1fae5; margin-top: 6px; }

  .meta { margin: 8px 0 14px; font-size: 8px; color: #94a3b8; }

  /* Kartu ringkasan */
  .stats td.stat { width: 25%; padding: 0 4px; }
  .stats td.stat:first-child { padding-left: 0; }
  .stats td.stat:last-child { padding-right: 0; }
  .stat-box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; background: #f8fafc; }
  .stat-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
  .stat-value { font-size: 15px; font-weight: bold; margin-top: 2px; }

  /* Section */
  .section { margin-top: 18px; }
  .section-title { font-size: 11px; font-weight: bold; color: #0f172a; padding-left: 7px; border-left: 3px solid #059669; margin-bottom: 8px; }

  .catatan-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 9px 11px; }

  /* Tabel evaluasi */
  .tabel thead th { background: #f1f5f9; color: #475569; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.4px;
                    font-weight: bold; padding: 6px 8px; border-bottom: 1px solid #cbd5e1; text-align: left; }
  .tabel thead th.center { text-align: center; }
  .tabel tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
  .tabel tbody tr:nth-child(even) td { background: #fafafa; }
  .kelas-nama { font-weight: bold; }
  .pct { font-weight: bold; }
  .bar { height: 3px; background: #e2e8f0; border-radius: 2px; margin-top: 2px; }
  .bar-fill { height: 3px; border-radius: 2px; }

  /* Detail per kelas */
  .kelas-card { border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; page-break-inside: avoid; }
  .kelas-card-head { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 6px 10px; font-weight: bold; font-size: 10px; }
  .kelas-card-body { padding: 4px 10px 6px; }
  .kelas-card-body td { padding: 3px 0; }
  .kelas-card-body td.label { width: 120px; color: #64748b; font-size: 8.5px; }
  .pre { white-space: pre-line; }

  /* Notulensi */
  .keep-together { page-break-inside: avoid; }
  .tabel tr { page-break-inside: avoid; }
  .kategori { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8px; font-weight: bold; margin: 6px 0 4px; }
  .badge { display: inline-block; padding: 1px 7px; border-radius: 8px; font-size: 7.5px; font-weight: bold; }

  /* Footer tiap halaman */
  .footer { position: fixed; bottom: -12mm; left: 0; right: 0; border-top: 1px solid #e2e8f0; padding-top: 4px; font-size: 7.5px; color: #94a3b8; }
  .page-number:after { content: counter(page); }
</style>
</head>
<body>

<div class="footer">
  <table>
    <tr>
      <td>Notulensi Musyawarah &middot; {{ $bulanLabel }} {{ $musyawarah->tahun }}</td>
      <td class="right">Halaman <span class="page-number"></span></td>
    </tr>
  </table>
</div>

{{-- Header --}}
<table class="header">
  <tr>
    <td>
      <div class="brand">{{ config('app.name') }} &middot; Sidomulyo 1</div>
      <div class="title">Notulensi Musyawarah</div>
      <div class="subtitle">Evaluasi bulanan periode {{ $bulanLabel }} {{ $musyawarah->tahun }}</div>
    </td>
    <td class="right" style="width: 170px;">
      <span class="status {{ $musyawarah->status === 'selesai' ? 'status-selesai' : 'status-draft' }}">
        {{ $musyawarah->status === 'selesai' ? 'SELESAI' : 'DRAFT' }}
      </span>
      <div class="header-date">{{ $musyawarah->tanggal->locale('id')->translatedFormat('l, d F Y') }}</div>
    </td>
  </tr>
</table>

<table class="meta">
  <tr>
    <td>Dicetak oleh {{ $cetakOleh }}</td>
    <td class="right">{{ now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H:i') }} WIB</td>
  </tr>
</table>

{{-- Ringkasan --}}
@if($laporan->count() > 0)
@php
  $rataKehadiran = $rata('snapshot_kehadiran_persen');
  $rataProgress  = $rata('snapshot_progress_persen');
@endphp
<table class="stats">
  <tr>
    <td class="stat">
      <div class="stat-box">
        <div class="stat-label">Kelas</div>
        <div class="stat-value">{{ $laporan->count() }}</div>
      </div>
    </td>
    <td class="stat">
      <div class="stat-box">
        <div class="stat-label">Total Murid</div>
        <div class="stat-value">{{ $laporan->sum('snapshot_jumlah_murid') }}</div>
      </div>
    </td>
    <td class="stat">
      <div class="stat-box">
        <div class="stat-label">Rata-rata Kehadiran</div>
        <div class="stat-value" style="color: {{ $warnaPersen($rataKehadiran) }}">{{ $persen($rataKehadiran) }}</div>
      </div>
    </td>
    <td class="stat">
      <div class="stat-box">
        <div class="stat-label">Rata-rata Progres Materi</div>
        <div class="stat-value" style="color: {{ $warnaPersen($rataProgress) }}">{{ $persen($rataProgress) }}</div>
      </div>
    </td>
  </tr>
</table>
@endif

@if($musyawarah->catatan_umum)
<div class="section">
  <div class="section-title">Catatan Umum</div>
  <div class="catatan-box pre">{{ $musyawarah->catatan_umum }}</div>
</div>
@endif

{{-- Evaluasi per kelas --}}
@if($laporan->count() > 0)
<div class="section">
  <div class="section-title">Evaluasi Per Kelas</div>
  <table class="tabel">
    <thead>
      <tr>
        <th>Kelas</th>
        <th class="center" style="width: 48px;">Murid</th>
        <th class="center" style="width: 70px;">Kehadiran</th>
        <th class="center" style="width: 70px;">Materi Umum</th>
        <th class="center" style="width: 70px;">Materi Individu</th>
        <th class="center" style="width: 76px;">Materi Keseluruhan</th>
      </tr>
    </thead>
    <tbody>
      @foreach($laporan as $l)
      <tr>
        <td class="kelas-nama">{{ $l->kelas?->nama ?? '-' }}</td>
        <td class="center">{{ $l->snapshot_jumlah_murid ?? '–' }}</td>
        @foreach([
          $l->snapshot_kehadiran_persen,
          $l->snapshot_progress_umum_persen,
          $l->snapshot_progress_individu_persen,
          $l->snapshot_progress_persen,
        ] as $nilai)
        <td class="center">
          <span class="pct" style="color: {{ $warnaPersen($nilai) }}">{{ $persen($nilai) }}</span>
          @if($nilai !== null)
          <div class="bar"><div class="bar-fill" style="width: {{ min(100, max(0, (float) $nilai)) }}%; background: {{ $warnaPersen($nilai) }};"></div></div>
          @endif
        </td>
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

{{-- Catatan per kelas --}}
@if($laporanDenganCatatan->count() > 0)
<div class="section" style="page-break-before: always; margin-top: 0;">
  <div class="section-title">Kendala &amp; Rencana Per Kelas</div>
  @foreach($laporanDenganCatatan as $l)
  <div class="kelas-card">
    <div class="kelas-card-head">{{ $l->kelas?->nama ?? '-' }}</div>
    <div class="kelas-card-body">
      <table>
        @if($l->kendala_murid_auto)
        <tr><td class="label">Kendala murid</td><td class="pre">{{ $l->kendala_murid_auto }}</td></tr>
        @endif
        @if($l->kendala_pengajar)
        <tr><td class="label">Kendala pengajar</td><td class="pre">{{ $l->kendala_pengajar }}</td></tr>
        @endif
        @if($l->planning)
        <tr><td class="label">Planning bulan ini</td><td class="pre">{{ $l->planning }}</td></tr>
        @endif
        @if($l->tindak_lanjut)
        <tr><td class="label">Tindak lanjut planning lalu</td><td class="pre">{{ $l->tindak_lanjut }}</td></tr>
        @endif
      </table>
    </div>
  </div>
  @endforeach
</div>
@endif

{{-- Notulensi --}}
@if($notulensi->count() > 0)
<div class="section">
  @foreach($notulensiByKategori as $kategori => $items)
  @php [$bg, $fg] = $kategoriWarna[$kategori] ?? $kategoriWarna['lainnya']; @endphp
  {{-- Label kategori + tabelnya tidak dipisah antar halaman; judul section ikut kelompok pertama --}}
  <div class="keep-together">
  @if($loop->first)
  <div class="section-title">Notulensi Musyawarah</div>
  @endif
  <span class="kategori" style="background: {{ $bg }}; color: {{ $fg }};">
    {{ $kategoriLabel[$kategori] ?? ucfirst($kategori) }} ({{ $items->count() }})
  </span>
  <table class="tabel">
    <thead>
      <tr>
        <th style="width: 22px;">No</th>
        <th>Isi</th>
        <th style="width: 110px;">Penanggung Jawab</th>
        <th class="center" style="width: 62px;">Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $i => $item)
      @php [$tlLabel, $tlBg, $tlFg] = $statusTl[$item->status_tindak_lanjut] ?? [ucfirst($item->status_tindak_lanjut), '#f1f5f9', '#475569']; @endphp
      <tr>
        <td class="muted">{{ $loop->iteration }}</td>
        <td class="pre">{{ $item->isi }}</td>
        <td>{{ $item->penanggung_jawab ?: '–' }}</td>
        <td class="center">
          <span class="badge" style="background: {{ $tlBg }}; color: {{ $tlFg }};">{{ $tlLabel }}</span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  </div>
  @endforeach
</div>
@endif

</body>
</html>
