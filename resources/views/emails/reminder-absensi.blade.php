@extends('emails.layout')

@section('judul', $totalSesi.' sesi belum diabsen')
@section('subjudul', 'Tersebar di '.$jumlahHari.' hari')
@section('preheader', $totalSesi.' sesi di '.$jumlahHari.' hari belum diabsen. Buka aplikasi untuk mengisinya.')

@section('content')
    <p style="margin: 0 0 8px;">Halo <strong style="color: #0f172a;">{{ $nama }}</strong>,<br>
        sesi berikut sudah lewat jadwalnya, tetapi absensinya belum dilakukan.</p>

    @foreach ($sesiPerTanggal as $tanggal => $daftar)
        <div style="margin-top: 16px; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; font-size: 12px; line-height: 16px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; color: #64748b;">
            {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }}
        </div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            @foreach ($daftar as $sesi)
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; line-height: 20px; color: #0f172a;">
                        <strong>{{ $sesi['kelas'] }}</strong>
                        <span style="color: #64748b;">&middot; {{ $sesi['program'] }}</span>
                        @if ($sesi['sudah_dibuka'])
                            <br><span style="display: inline-block; margin-top: 4px; padding: 1px 8px; background-color: #fef3c7; color: #92400e; border-radius: 999px; font-size: 12px; line-height: 18px;">Sesi dibuka, belum diselesaikan</span>
                        @endif
                    </td>
                    <td align="right" valign="top" style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; line-height: 20px; color: #64748b; white-space: nowrap;">
                        {{ $sesi['jam_mulai'] }}&ndash;{{ $sesi['jam_selesai'] }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endforeach

    <div style="margin-top: 16px;">
        @include('emails.partials.tombol', ['url' => $appUrl, 'label' => 'Buka Aplikasi'])
    </div>

    <p style="margin: 24px 0 0; font-size: 13px; line-height: 20px; color: #64748b;">Jika sesi tersebut libur atau dibatalkan, tandai sebagai batal di aplikasi agar tidak diingatkan lagi.</p>
@endsection

@section('footer')
    Email otomatis dari {{ config('app.name') }}, dikirim setiap hari pukul 19:00 WITA selama masih ada sesi yang belum diabsen.
@endsection
