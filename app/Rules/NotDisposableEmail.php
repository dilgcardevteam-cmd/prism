<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class NotDisposableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = Str::lower(trim((string) $value));
        $separator = strrpos($email, '@');

        if ($separator === false) {
            return;
        }

        $domain = substr($email, $separator + 1);
        foreach (config('disposable_email_domains', []) as $disposableDomain) {
            if ($domain === $disposableDomain || Str::endsWith($domain, '.'.$disposableDomain)) {
                $fail('Please use a non-disposable email address.');

                return;
            }
        }
    }
}
