<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <p>Halo {{ $nama }},</p>

    <p>Sesi berikut sudah lewat jadwalnya tetapi absensinya belum dilakukan:</p>

    @foreach ($sesiPerTanggal as $tanggal => $daftar)
        <p style="margin: 16px 0 4px; font-weight: bold;">
            {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }}
        </p>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($daftar as $sesi)
                <li>
                    {{ $sesi['kelas'] }} &ndash; {{ $sesi['program'] }}
                    ({{ $sesi['jam_mulai'] }}&ndash;{{ $sesi['jam_selesai'] }})
                    @if ($sesi['sudah_dibuka'])
                        <span style="color: #6b7280;">&mdash; sesi sudah dibuka tetapi belum diselesaikan</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endforeach

    <p style="margin-top: 24px;">
        <a href="{{ $appUrl }}" style="display: inline-block; padding: 10px 20px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px;">
            Buka Aplikasi
        </a>
    </p>

    <p>Atau salin tautan berikut ke browser Anda:</p>
    <p><a href="{{ $appUrl }}">{{ $appUrl }}</a></p>

    <p style="color: #6b7280; font-size: 14px;">Jika sesi tersebut memang libur atau dibatalkan, tandai sebagai batal di aplikasi agar tidak diingatkan lagi.</p>
</body>
</html>
