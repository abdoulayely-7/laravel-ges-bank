<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SenegalTelephone;
use App\Rules\SenegalNci;

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
            'informationsClient.telephone' => ['sometimes', 'string', new SenegalTelephone(), 'unique:clients,telephone,' . ($this->route('compte') ? $this->route('compte')->client_id : 'null')],
            'informationsClient.email' => 'sometimes|email|unique:users,email,' . ($this->route('compte') ? $this->route('compte')->client->user_id : 'null'),
            'informationsClient.password' => 'sometimes|string|min:8',
            'informationsClient.nci' => ['sometimes', 'string', 'max:20', new SenegalNci(), 'unique:clients,nci,' . ($this->route('compte') ? $this->route('compte')->client_id : 'null')],
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
            'informationsClient.telephone' => 'Le numéro de téléphone doit être un numéro sénégalais valide.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'adresse email doit être valide.',
            'informationsClient.email.unique' => 'Cette adresse email est déjà utilisée.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'informationsClient.password.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'informationsClient.nci' => 'Le NCI doit être un numéro de maximum 13 chiffres commençant par 1 ou 2.',
        ];
    }
}
