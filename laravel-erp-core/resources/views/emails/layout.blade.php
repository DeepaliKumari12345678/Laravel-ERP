<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? $shopName }}</title>
</head>
<body style="margin:0;padding:0;background:#eff1f2;font-family:Arial,Helvetica,sans-serif;color:#363a41;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eff1f2;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #dbe2e8;border-radius:6px;overflow:hidden;">
                <tr>
                    <td style="background:#25b9d7;color:#fff;padding:18px 22px;font-size:18px;font-weight:700;">
                        {{ $shopName }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px 22px;border-top:1px solid #dbe2e8;color:#6c868e;font-size:12px;">
                        This message was sent by {{ $shopName }}.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
