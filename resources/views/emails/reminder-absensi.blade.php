<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Pengingat Absensi</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; -webkit-text-size-adjust: 100%;">
    {{-- Teks pratinjau di daftar inbox --}}
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; color: #f3f4f6;">
        {{ $totalSesi }} sesi di {{ $jumlahHari }} hari belum diabsen. Buka aplikasi untuk mengisinya.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 32px 16px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 0 4px 16px; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; color: #1d4ed8; letter-spacing: 0.2px;">
                            {{ config('app.name') }}
                        </td>
                    </tr>

                    {{-- Kartu --}}
                    <tr>
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background-color: #eff6ff; border-bottom: 1px solid #dbeafe; padding: 24px 28px; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                        <div style="font-size: 20px; line-height: 28px; font-weight: 700; color: #111827;">
                                            {{ $totalSesi }} sesi belum diabsen
                                        </div>
                                        <div style="margin-top: 4px; font-size: 14px; line-height: 20px; color: #4b5563;">
                                            Tersebar di {{ $jumlahHari }} hari
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 24px 28px 8px; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 24px; color: #374151;">
                                        Halo <strong style="color: #111827;">{{ $nama }}</strong>,<br>
                                        sesi berikut sudah lewat jadwalnya, tetapi absensinya belum dilakukan.
                                    </td>
                                </tr>

                                {{-- Daftar per tanggal --}}
                                @foreach ($sesiPerTanggal as $tanggal => $daftar)
                                    <tr>
                                        <td style="padding: 16px 28px 0; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            <div style="font-size: 12px; line-height: 16px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; color: #6b7280; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb;">
                                                {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }}
                                            </div>
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                @foreach ($daftar as $sesi)
                                                    <tr>
                                                        <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; line-height: 20px; color: #111827;">
                                                            <strong>{{ $sesi['kelas'] }}</strong>
                                                            <span style="color: #6b7280;">&middot; {{ $sesi['program'] }}</span>
                                                            @if ($sesi['sudah_dibuka'])
                                                                <br><span style="display: inline-block; margin-top: 4px; padding: 1px 8px; background-color: #fef3c7; color: #92400e; border-radius: 999px; font-size: 12px; line-height: 18px;">Sesi dibuka, belum diselesaikan</span>
                                                            @endif
                                                        </td>
                                                        <td align="right" valign="top" style="padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; line-height: 20px; color: #6b7280; white-space: nowrap;">
                                                            {{ $sesi['jam_mulai'] }}&ndash;{{ $sesi['jam_selesai'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Tombol --}}
                                <tr>
                                    <td align="center" style="padding: 32px 28px 8px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" bgcolor="#2563eb" style="border-radius: 8px;">
                                                    <a href="{{ $appUrl }}" style="display: inline-block; padding: 14px 32px; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">
                                                        Buka Aplikasi
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td align="center" style="padding: 8px 28px 28px; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; line-height: 18px; color: #6b7280;">
                                        Tombol tidak berfungsi? Salin tautan ini:<br>
                                        <a href="{{ $appUrl }}" style="color: #2563eb; word-break: break-all;">{{ $appUrl }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding: 20px 12px 0; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; line-height: 18px; color: #6b7280;">
                            Jika sesi tersebut libur atau dibatalkan, tandai sebagai batal di aplikasi agar tidak diingatkan lagi.<br>
                            Email otomatis dari {{ config('app.name') }}, dikirim setiap hari pukul 19:00 WITA selama masih ada sesi yang belum diabsen.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
