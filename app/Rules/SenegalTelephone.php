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
        // Nettoyer la valeur (supprimer espaces et caractères spéciaux)
        $cleanValue = preg_replace('/[^0-9]/', '', $value);

        // Vérifier la longueur (9 chiffres exactement)
        if (strlen($cleanValue) !== 9) {
            $fail('Le numéro de téléphone doit contenir exactement 9 chiffres.');
            return;
        }

        // Vérifier que ça commence par les indicatifs valides
        $validPrefixes = ['77', '78', '70', '75', '76'];
        $prefix = substr($cleanValue, 0, 2);

        if (!in_array($prefix, $validPrefixes)) {
            $fail('Le numéro de téléphone doit commencer par 77, 78, 70, 75 ou 76.');
            return;
        }

        // Vérifier que tous les caractères sont des chiffres
        if (!ctype_digit($cleanValue)) {
            $fail('Le numéro de téléphone ne doit contenir que des chiffres.');
            return;
        }
    }

    public function passes($attribute, $value)
    {
        // Nettoyer la valeur
        $cleanValue = preg_replace('/[^0-9]/', '', $value);

        // Vérifier la longueur
        if (strlen($cleanValue) !== 9) {
            return false;
        }

        // Vérifier le préfixe
        $validPrefixes = ['77', '78', '70', '75', '76'];
        $prefix = substr($cleanValue, 0, 2);

        return in_array($prefix, $validPrefixes) && ctype_digit($cleanValue);
    }

    public function message()
    {
        return 'Le numéro de téléphone doit être un numéro sénégalais valide de 9 chiffres commençant par 77, 78, 70, 75 ou 76.';
    }
}
