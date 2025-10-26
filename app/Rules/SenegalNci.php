<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalNci implements ValidationRule
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

        // Vérifier la longueur (13 chiffres maximum)
        if (strlen($cleanValue) > 13) {
            $fail('Le NCI ne peut pas dépasser 13 chiffres.');
            return;
        }

        // Vérifier la longueur minimum (au moins 1 chiffre)
        if (strlen($cleanValue) < 1) {
            $fail('Le NCI doit contenir au moins 1 chiffre.');
            return;
        }

        // Vérifier que ça commence par 1 ou 2 (si assez long)
        if (strlen($cleanValue) >= 1) {
            $firstDigit = substr($cleanValue, 0, 1);
            if (!in_array($firstDigit, ['1', '2'])) {
                $fail('Le NCI doit commencer par 1 ou 2.');
                return;
            }
        }

        // Vérifier que tous les caractères sont des chiffres
        if (!ctype_digit($cleanValue)) {
            $fail('Le NCI ne doit contenir que des chiffres.');
            return;
        }
    }

    public function passes($attribute, $value)
    {
        // Nettoyer la valeur
        $cleanValue = preg_replace('/[^0-9]/', '', $value);

        // Vérifier la longueur
        if (strlen($cleanValue) > 13 || strlen($cleanValue) < 1) {
            return false;
        }

        // Vérifier le premier chiffre
        if (strlen($cleanValue) >= 1) {
            $firstDigit = substr($cleanValue, 0, 1);
            if (!in_array($firstDigit, ['1', '2'])) {
                return false;
            }
        }

        return ctype_digit($cleanValue);
    }

    public function message()
    {
        return 'Le NCI doit être un numéro de maximum 13 chiffres commençant par 1 ou 2.';
    }
}
