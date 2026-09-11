<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <p>Halo {{ $user->name }},</p>

    <p>Akun Anda di {{ config('app.name') }} telah dibuat. Silakan klik tombol di bawah ini untuk mengatur password Anda sebelum login.</p>

    <p>
        <a href="{{ $setPasswordUrl }}" style="display: inline-block; padding: 10px 20px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px;">
            Atur Password
        </a>
    </p>

    <p>Atau salin tautan berikut ke browser Anda:</p>
    <p><a href="{{ $setPasswordUrl }}">{{ $setPasswordUrl }}</a></p>

    <p style="color: #6b7280; font-size: 14px;">Tautan ini berlaku selama 60 menit sejak email ini dikirim. Jika Anda meminta tautan baru sebelum tautan ini digunakan, tautan lama tidak akan berlaku lagi — gunakan email terbaru yang Anda terima.</p>

    <p>Jika Anda tidak merasa meminta akun ini, abaikan email ini.</p>
</body>
</html>
