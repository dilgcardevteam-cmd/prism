<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titleText }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f8fafc; font-family: Arial, sans-serif; color: #0f172a;">
    <div style="max-width: 680px; margin: 0 auto; background: #ffffff; border: 1px solid #dbeafe; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);">
        <div style="padding: 24px 28px; background: linear-gradient(135deg, #002c76 0%, #0a4fb3 100%); color: #ffffff;">
            <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.85;">PDMU PDMUOMS</div>
            <h1 style="margin: 10px 0 0; font-size: 24px; line-height: 1.3;">{{ $titleText }}</h1>
        </div>

        <div style="padding: 28px;">
            <p style="margin: 0 0 14px; font-size: 14px; line-height: 1.7;">
                Hello {{ trim(($recipient->fname ?? '') . ' ' . ($recipient->lname ?? '')) ?: ($recipient->username ?? 'User') }},
            </p>

            <p style="margin: 0 0 18px; font-size: 14px; line-height: 1.7; color: #475569;">
                {{ $senderName }} sent a bulk notification through PDMU PDMUOMS.
            </p>

            <div style="padding: 18px 20px; border-radius: 14px; background: #eff6ff; border: 1px solid #bfdbfe; color: #0f172a; font-size: 14px; line-height: 1.8;">
                {!! nl2br(e($messageText)) !!}
            </div>

            <div style="margin-top: 22px;">
                <a href="{{ $actionUrl }}" style="display: inline-block; padding: 12px 18px; border-radius: 10px; background: #002c76; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700;">
                    Open in PDMU PDMUOMS
                </a>
            </div>

            <p style="margin: 22px 0 0; font-size: 12px; line-height: 1.7; color: #64748b;">
                This message was delivered through both email and the system notification center.
            </p>
        </div>
    </div>
</body>
</html>
