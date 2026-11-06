<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #002C76; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; border: 1px solid #ddd; }
        .otp-box { font-size: 28px; font-weight: bold; letter-spacing: 6px; text-align: center; background: #f5f5f5; padding: 14px; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>PRISM Login Verification</h2>
        </div>
        <div class="content">
            <p>Hello {{ trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: 'User' }},</p>
            <p>Use this one-time code to finish signing in to PRISM:</p>
            <div class="otp-box">{{ $otp }}</div>
            <p><strong>This code expires at {{ $expiresAt->format('h:i A') }}.</strong></p>
            <p>If you did not try to sign in, you can ignore this email and consider changing your password.</p>
        </div>
        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; {{ $expiresAt->format('Y') }} PDMU Reporting, Inspection and Monitoring System (PRISM).</p>
        </div>
    </div>
</body>
</html>
