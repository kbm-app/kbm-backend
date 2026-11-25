{{--
    Kerangka bersama semua email. Warna mengikuti tema aplikasi (kbm-frontend/src/app/globals.css):
      primary    Emerald 600 #059669   (tombol, garis atas kartu)
      primary-fg Emerald 700 #047857   (tautan)
      secondary  Emerald 50  #ecfdf5   (banner judul), border banner Emerald 100 #d1fae5
      background Slate 50    #f8fafc   foreground Slate 900 #0f172a   teks Slate 700 #334155
      muted      Slate 500   #64748b   border Slate 200 #e2e8f0      garis tipis Slate 100 #f1f5f9
      accent     Amber       lencana: #fef3c7 / #92400e
    Semua gaya inline dan berbasis tabel agar konsisten di Gmail dan Outlook.

    Section: judul (wajib), subjudul, preheader, content (wajib), footer.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('judul')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; -webkit-text-size-adjust: 100%;">
    {{-- Teks pratinjau di daftar inbox --}}
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; color: #f8fafc;">
        @yield('preheader', trim($__env->yieldContent('judul')))
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc;">
        <tr>
            <td align="center" style="padding: 32px 16px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 0 4px 16px; font-family: 'Poppins', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 700; color: #059669; letter-spacing: 0.2px;">
                            {{ config('app.name') }}
                        </td>
                    </tr>

                    {{-- Kartu --}}
                    <tr>
                        <td style="background-color: #ffffff; border: 1px solid #e2e8f0; border-top: 4px solid #059669; border-radius: 10px; overflow: hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background-color: #ecfdf5; border-bottom: 1px solid #d1fae5; padding: 24px 28px; font-family: 'Poppins', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                        <div style="font-size: 20px; line-height: 28px; font-weight: 700; color: #0f172a;">
                                            @yield('judul')
                                        </div>
                                        @hasSection('subjudul')
                                            <div style="margin-top: 4px; font-size: 14px; line-height: 20px; color: #64748b;">
                                                @yield('subjudul')
                                            </div>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 24px 28px 28px; font-family: 'Poppins', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 24px; color: #334155;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding: 20px 12px 0; font-family: 'Poppins', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; line-height: 18px; color: #64748b;">
                            @hasSection('footer')
                                @yield('footer')
                            @else
                                Email ini dikirim otomatis oleh {{ config('app.name') }}.
                            @endif
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
