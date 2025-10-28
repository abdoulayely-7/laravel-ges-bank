<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeblocageCompteRequest extends FormRequest
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

            // Vérifier que le compte est bloqué
            if ($compte->statut !== 'bloque') {
                $validator->errors()->add('statut', 'Le compte doit être bloqué pour pouvoir être débloqué.');
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
            'motif.required' => 'Le motif de déblocage est obligatoire.',
            'motif.string' => 'Le motif doit être une chaîne de caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser 255 caractères.',
        ];
    }
}
