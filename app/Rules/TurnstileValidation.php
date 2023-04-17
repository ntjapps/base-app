<?php

namespace App\Rules;

use App\Traits\Turnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileValidation implements ValidationRule
{
    use Turnstile;

    private function verifyWebChallenge(mixed $value): bool
    {
        return $this->verifyChallenge($value);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (config('challenge.bypass')) {
            return;
        }

        if (! $this->verifyWebChallenge($value)) {
            $fail('The :attribute is invalid.');
        }
    }
}
