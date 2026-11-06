<?php

namespace Tests\Unit;

use App\Rules\NotDisposableEmail;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NotDisposableEmailTest extends TestCase
{
    public function test_disposable_domains_and_their_subdomains_are_rejected(): void
    {
        foreach (['user@mailinator.com', 'user@sub.mailinator.com'] as $email) {
            $validator = Validator::make(
                ['email' => $email],
                ['email' => [new NotDisposableEmail]],
            );

            $this->assertTrue($validator->fails());
            $this->assertSame(
                'Please use a non-disposable email address.',
                $validator->errors()->first('email'),
            );
        }
    }

    public function test_regular_email_domains_are_accepted(): void
    {
        $validator = Validator::make(
            ['email' => 'user@dilg.gov.ph'],
            ['email' => [new NotDisposableEmail]],
        );

        $this->assertFalse($validator->fails());
    }
}
