<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #002C76; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; border: 1px solid #ddd; }
        .button { display: inline-block; background-color: #002C76; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>PDMU Reporting, Inspection and Monitoring System (PRISM) - Email Verification</h2>
        </div>
        
        <div class="content">
            <p>Hello {{ $user->fname }} {{ $user->lname }}!</p>
            
            <p>Thank you for registering with the <strong>PDMU Reporting, Inspection and Monitoring System (PRISM)</strong>.</p>
            
            <p>Your account has been successfully created. Please verify your email address by clicking the button below.</p>

            <p>After verification, your account will remain pending until an administrator approves it.</p>
            
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button" style="color: #ffffff !important;">Verify Email Address</a>
            </div>
            
            <p><strong>This verification link will expire in 60 minutes.</strong></p>
            
            <p>If you did not create an account, no further action is required.</p>
            
            <p>If the button above does not work, copy and paste this URL into your web browser:</p>
            <p style="word-break: break-all; background-color: #f5f5f5; padding: 10px;">{{ $verificationUrl }}</p>
            
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
