<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlocageCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'motif' => 'required|string|max:255',
            'duree' => 'required|integer|min:1|max:365',
            'unite' => 'required|in:jours,mois',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $compte = $this->route('compte');

            // Vérifier que le compte existe
            if (!$compte) {
                $validator->errors()->add('compte', 'Le compte spécifié n\'existe pas.');
                return;
            }

            // Vérifier que c'est un compte épargne
            if ($compte->type !== 'epargne') {
                $validator->errors()->add('type', 'Seuls les comptes épargne peuvent être bloqués.');
                return;
            }

            // Vérifier que le compte est actif
            if ($compte->statut !== 'actif') {
                $validator->errors()->add('statut', 'Le compte doit être actif pour pouvoir être bloqué.');
                return;
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de blocage est obligatoire.',
            'motif.string' => 'Le motif doit être une chaîne de caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser 255 caractères.',
            'duree.required' => 'La durée de blocage est obligatoire.',
            'duree.integer' => 'La durée doit être un nombre entier.',
            'duree.min' => 'La durée doit être d\'au moins 1 jour.',
            'duree.max' => 'La durée ne peut pas dépasser 365 jours.',
            'unite.required' => 'L\'unité de temps est obligatoire.',
            'unite.in' => 'L\'unité doit être "jours" ou "mois".',
        ];
    }
}
