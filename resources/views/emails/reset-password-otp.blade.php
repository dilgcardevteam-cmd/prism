<!DOCTYPE html>
<html>
<head>
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
            <h2>PDMU Reporting, Inspection and Monitoring System (PRISM) - Password Reset OTP</h2>
        </div>

        <div class="content">
            <p>Hello {{ trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: 'User' }},</p>

            <p>We received a request to reset your password. Use the OTP below to continue the reset process:</p>

            <div class="otp-box">{{ $otp }}</div>

            <p><strong>This OTP will expire at {{ $expiresAt->format('h:i A') }}.</strong></p>

            <p>If you did not request a password reset, you can safely ignore this email.</p>

            <hr>

            <p>Best regards,<br>PDMU Reporting, Inspection and Monitoring System (PRISM)</p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; 2026 PDMU Reporting, Inspection and Monitoring System (PRISM). All rights reserved.</p>
        </div>
    </div>
</body>
</html>
