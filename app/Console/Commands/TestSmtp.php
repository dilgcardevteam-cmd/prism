<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSmtp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email? : Email address to send test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMTP configuration by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? config('mail.from.address');
        
        $this->info('=== SMTP Configuration Test ===');
        $this->info('Mail Driver: ' . config('mail.default'));
        $this->info('Host: ' . config('mail.mailers.smtp.host'));
        $this->info('Port: ' . config('mail.mailers.smtp.port'));
        $this->info('Scheme: ' . config('mail.mailers.smtp.scheme'));
        $this->info('Username: ' . config('mail.mailers.smtp.username'));
        $this->info('From: ' . config('mail.from.address'));
        $this->info('');
        
        $this->info("Sending test email to: {$email}");
        
        try {
            Mail::raw('This is a test email from PDMU PDMUOMS. If you received this, SMTP is working correctly!', function ($message) use ($email) {
                $message->to($email)
                        ->subject('PDMU PDMUOMS - SMTP Test Email');
            });
            
            $this->info('✓ Email sent successfully!');
            $this->info('Check your inbox for the test email.');
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Email sending failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->error('Code: ' . $e->getCode());
            
            // Log the error
            \Illuminate\Support\Facades\Log::error('SMTP Test Failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'config' => [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                ]
            ]);
            
            return 1;
        }
    }
}
