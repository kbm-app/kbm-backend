<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <p>Halo {{ $user->name }},</p>

    <p>Admin telah meminta reset password untuk akun Anda di {{ config('app.name') }}. Silakan klik tombol di bawah ini untuk mengatur password baru.</p>

    <p>
        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 10px 20px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px;">
            Atur Password Baru
        </a>
    </p>

    <p>Atau salin tautan berikut ke browser Anda:</p>
    <p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>

    <p style="color: #6b7280; font-size: 14px;">Tautan ini berlaku selama 60 menit sejak email ini dikirim. Jika Anda (atau admin) meminta tautan baru sebelum tautan ini digunakan, tautan lama tidak akan berlaku lagi — gunakan email terbaru yang Anda terima.</p>

    <p>Jika Anda tidak merasa meminta reset password ini, abaikan email ini dan password Anda tidak akan berubah.</p>
</body>
</html>
