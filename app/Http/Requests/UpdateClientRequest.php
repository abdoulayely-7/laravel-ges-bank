<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
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
            'titulaire' => 'sometimes|string|max:255',
            'informationsClient' => 'sometimes|array',
            'informationsClient.telephone' => 'sometimes|string|regex:/^(\+221|221)?[76|7]\d{8}$/|unique:clients,telephone,' . $this->route('compte')->client_id,
            'informationsClient.email' => 'sometimes|email|unique:users,email,' . $this->route('compte')->client->user_id,
            'informationsClient.password' => 'sometimes|string|min:8',
            'informationsClient.nci' => 'sometimes|string|max:20|unique:clients,nci,' . $this->route('compte')->client_id,
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier qu'au moins un champ est fourni
            $hasTitulaire = $this->has('titulaire') && !empty($this->input('titulaire'));
            $hasTelephone = $this->has('informationsClient.telephone') && !empty($this->input('informationsClient.telephone'));
            $hasEmail = $this->has('informationsClient.email') && !empty($this->input('informationsClient.email'));
            $hasPassword = $this->has('informationsClient.password') && !empty($this->input('informationsClient.password'));
            $hasNci = $this->has('informationsClient.nci') && !empty($this->input('informationsClient.nci'));

            if (!$hasTitulaire && !$hasTelephone && !$hasEmail && !$hasPassword && !$hasNci) {
                $validator->errors()->add('general', 'Au moins un champ de modification doit être fourni.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'informationsClient.telephone.regex' => 'Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567).',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'adresse email doit être valide.',
            'informationsClient.email.unique' => 'Cette adresse email est déjà utilisée.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'informationsClient.password.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'informationsClient.nci.string' => 'Le numéro de carte d\'identité doit être une chaîne de caractères.',
            'informationsClient.nci.max' => 'Le numéro de carte d\'identité ne peut pas dépasser 20 caractères.',
            'informationsClient.nci.unique' => 'Ce numéro de carte d\'identité est déjà utilisé.',
        ];
    }
}
