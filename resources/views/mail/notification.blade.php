<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f6f7;font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right;color:#1f2933;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;">
        <tr>
            <td style="padding:20px 24px;border-bottom:3px solid #0f4c5c;font-size:22px;font-weight:bold;color:#0f4c5c;">{{ __('site.name') }}</td>
        </tr>
        <tr>
            <td style="padding:24px;font-size:16px;line-height:1.8;">
                <h1 style="font-size:19px;margin:0 0 16px;">{{ $heading }}</h1>
                @foreach ($lines as $line)
                    <p style="margin:0 0 12px;">{{ $line }}</p>
                @endforeach
                @if ($actionUrl)
                    <p style="margin:24px 0 8px;">
                        <a href="{{ $actionUrl }}" style="display:inline-block;background:#0f4c5c;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:6px;">{{ $actionLabel }}</a>
                    </p>
                    <p style="margin:0;font-size:12px;color:#6b7280;word-break:break-all;" dir="ltr">{{ $actionUrl }}</p>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:16px 24px;font-size:12px;color:#6b7280;">{{ __('mail.footer') }}</td>
        </tr>
    </table>
</body>
</html>
