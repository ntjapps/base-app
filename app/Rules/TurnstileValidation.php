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

    private function checkCapacitorPlatform(mixed $value): bool
    {
        /** Check Key */
        if (config('challenge.mobile') !== $value->mobileKey) {
            return false;
        }

        /** Check Platform */
        if (! in_array($value->platform, config('challenge.platforms'))) {
            return false;
        }

        /** Check Virtual Device */
        if ($value->isVirtual) {
            return false;
        }
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

        $baseData = base64_decode($value, true);

        /** Check if this is not encoded Capacitor Data */
        if ($baseData === false) {
            /** Not capacitor data, must pass turnstile check */
            if (! $this->verifyWebChallenge($value)) {
                $fail('The :attribute is invalid.');
            }
        } else {
            /** Capacitor data, must pass capacitor check */
            if (! $this->checkCapacitorPlatform($baseData)) {
                $fail('The :attribute is invalid.');
            }
        }
    }
}
