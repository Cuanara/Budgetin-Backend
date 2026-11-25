<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kode Verifikasi Email</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:32px 0;">
    <tr>
        <td align="center">
            <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;padding:32px;">
                <tr>
                    <td style="font-size:18px;color:#111;padding-bottom:16px;">Halo {{ $name }},</td>
                </tr>
                <tr>
                    <td style="font-size:14px;color:#444;line-height:1.6;padding-bottom:24px;">
                        Terima kasih telah mendaftar di {{ config('app.name') }}.
                        Gunakan kode berikut untuk memverifikasi email Anda:
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-bottom:24px;">
                        <div style="display:inline-block;font-size:32px;font-weight:bold;letter-spacing:8px;color:#111;background:#f0f0f3;border-radius:6px;padding:12px 24px;">
                            {{ $code }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:13px;color:#666;line-height:1.6;">
                        Kode ini berlaku selama <strong>{{ $expiresIn }} menit</strong>.
                        Jangan berikan kode ini kepada siapa pun.<br><br>
                        Jika Anda tidak merasa mendaftar, abaikan email ini.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>