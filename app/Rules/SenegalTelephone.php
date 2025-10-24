<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalTelephone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
    }

    public function passes($attribute, $value)
    {
        // Exemple : +221771234567 ou 77XXXXXXX
        return preg_match('/^(?:\+221|0)?(77|78|70|76)\d{7}$/', $value);
    }

    public function message()
    {
        return 'Le numéro de téléphone n\'est pas un numéro sénégalais valide.';
    }
}
