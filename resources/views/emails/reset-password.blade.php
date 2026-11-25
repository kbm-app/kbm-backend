@extends('emails.layout')

@section('judul', 'Reset Password Akun Anda')
@section('subjudul', 'Admin meminta reset password untuk akun Anda')

@section('content')
    <p style="margin: 0 0 16px;">Halo <strong style="color: #0f172a;">{{ $user->name }}</strong>,</p>

    <p style="margin: 0 0 8px;">Admin telah meminta reset password untuk akun Anda di {{ config('app.name') }}. Silakan klik tombol di bawah ini untuk mengatur password baru.</p>

    @include('emails.partials.tombol', ['url' => $resetUrl, 'label' => 'Atur Password Baru'])

    <p style="margin: 24px 0 0; font-size: 13px; line-height: 20px; color: #64748b;">Tautan ini berlaku selama 60 menit sejak email ini dikirim. Jika Anda (atau admin) meminta tautan baru sebelum tautan ini digunakan, tautan lama tidak akan berlaku lagi &mdash; gunakan email terbaru yang Anda terima.</p>

    <p style="margin: 12px 0 0; font-size: 13px; line-height: 20px; color: #64748b;">Jika Anda tidak merasa meminta reset password ini, abaikan email ini dan password Anda tidak akan berubah.</p>
@endsection
