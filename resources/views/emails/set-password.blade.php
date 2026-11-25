@extends('emails.layout')

@section('judul', 'Atur Password Akun Anda')
@section('subjudul', 'Akun Anda di '.config('app.name').' telah dibuat')

@section('content')
    <p style="margin: 0 0 16px;">Halo <strong style="color: #0f172a;">{{ $user->name }}</strong>,</p>

    <p style="margin: 0 0 8px;">Akun Anda di {{ config('app.name') }} telah dibuat. Silakan klik tombol di bawah ini untuk mengatur password Anda sebelum login.</p>

    @include('emails.partials.tombol', ['url' => $setPasswordUrl, 'label' => 'Atur Password'])

    <p style="margin: 24px 0 0; font-size: 13px; line-height: 20px; color: #64748b;">Tautan ini berlaku selama 60 menit sejak email ini dikirim. Jika Anda meminta tautan baru sebelum tautan ini digunakan, tautan lama tidak akan berlaku lagi &mdash; gunakan email terbaru yang Anda terima.</p>

    <p style="margin: 12px 0 0; font-size: 13px; line-height: 20px; color: #64748b;">Jika Anda tidak merasa meminta akun ini, abaikan email ini.</p>
@endsection
