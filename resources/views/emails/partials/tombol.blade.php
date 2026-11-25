{{-- Tombol utama + tautan cadangan. Variabel: $url, $label --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center" style="padding: 16px 0 8px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" bgcolor="#059669" style="border-radius: 8px;">
                        <a href="{{ $url }}" style="display: inline-block; padding: 14px 32px; font-family: 'Poppins', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">
                            {{ $label }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding: 8px 0 0; font-size: 12px; line-height: 18px; color: #64748b;">
            Tombol tidak berfungsi? Salin tautan ini:<br>
            <a href="{{ $url }}" style="color: #047857; word-break: break-all;">{{ $url }}</a>
        </td>
    </tr>
</table>
