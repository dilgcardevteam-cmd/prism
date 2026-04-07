<?php
/**
 * Diagnostic test for SMTP connection and email sending
 * Run: php artisan tinker < diagnostic_smtp.php
 */

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

echo "\n=== SMTP Diagnostic Test ===\n\n";

// Test 1: Check configuration
echo "Test 1: Mail Configuration\n";
echo "  Driver: " . config('mail.default') . "\n";
echo "  Host: " . config('mail.mailers.smtp.host') . "\n";
echo "  Port: " . config('mail.mailers.smtp.port') . "\n";
echo "  Scheme: " . config('mail.mailers.smtp.scheme') . "\n";
echo "  Username: " . config('mail.mailers.smtp.username') . "\n";
echo "  From: " . config('mail.from.address') . "\n\n";

// Test 2: Try to send via raw Mail facade
echo "Test 2: Send Test Email via Mail Facade\n";
try {
    $result = Mail::raw('Test email from PDMU PDMUOMS system. If received, SMTP is working!', function ($message) {
        $message->to('subaybayancordillera@gmail.com')
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject('PDMU PDMUOMS - SMTP Test Email');
    });
    
    echo "  ✓ Email sent successfully!\n";
    echo "  Result: " . json_encode($result) . "\n";
} catch (\Exception $e) {
    echo "  ✗ Error sending email:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  Code: " . $e->getCode() . "\n";
}

// Test 3: Check if SwiftMailer is available
echo "\nTest 3: Check Mail Transport\n";
$transport = Mail::getSwiftMailer()->getTransport();
echo "  Transport: " . get_class($transport) . "\n";
if ($transport instanceof \Swift_SmtpTransport) {
    echo "  SMTP Transport detected\n";
    echo "  Host: " . $transport->getHost() . "\n";
    echo "  Port: " . $transport->getPort() . "\n";
}

// Test 4: Try SMTP connection test
echo "\nTest 4: SMTP Connection Test\n";
try {
    $transport = new \Swift_SmtpTransport(
        config('mail.mailers.smtp.host'),
        config('mail.mailers.smtp.port'),
        config('mail.mailers.smtp.scheme')
    );
    $transport->setUsername(config('mail.mailers.smtp.username'));
    $transport->setPassword(config('mail.mailers.smtp.password'));
    
    echo "  Attempting to connect to: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port') . "\n";
    
    if ($transport->start()) {
        echo "  ✓ SMTP Connection Successful!\n";
        $transport->stop();
    } else {
        echo "  ✗ SMTP Connection Failed\n";
    }
} catch (\Exception $e) {
    echo "  ✗ Connection Error: " . $e->getMessage() . "\n";
}

echo "\n=== End of Diagnostic ===\n";
