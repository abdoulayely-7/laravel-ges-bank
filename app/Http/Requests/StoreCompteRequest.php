<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompteRequest extends FormRequest
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
        $rules = [
            'type' => ['required', Rule::in(['epargne', 'courant', 'cheque'])],
            'soldeInitial' => ['required', 'numeric', 'min:10000'],
            'devise' => ['required', 'string', 'in:FCFA,EUR,USD'],
            'client' => ['required', 'array'],
            'client.id' => ['nullable', 'uuid', 'exists:clients,id'],
        ];

        // Si pas d'ID client fourni, tous les champs client sont requis
        if (empty($this->input('client.id'))) {
            $rules = array_merge($rules, [
                'client.titulaire' => ['required', 'string', 'min:2', 'max:255'],
                'client.email' => ['required', 'email', 'unique:users,email'],
                'client.telephone' => ['required', 'string', 'unique:clients,telephone', new \App\Rules\SenegalTelephone()],
                'client.nci' => ['required', 'string', 'unique:clients,nci', new \App\Rules\SenegalNci()],
                'client.adresse' => ['required', 'string', 'min:5', 'max:500'],
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est requis',
            'type.in' => 'Le type de compte doit être epargne, courant ou cheque',
            'soldeInitial.required' => 'Le solde initial est requis',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre',
            'soldeInitial.min' => 'Le solde initial doit être supérieur ou égal à 10 000',
            'devise.required' => 'La devise est requise',
            'devise.in' => 'La devise doit être FCFA, EUR ou USD',
            'client.required' => 'Les informations du client sont requises',
            'client.array' => 'Les informations du client doivent être un objet',
            'client.id.uuid' => 'L\'ID du client doit être un UUID valide',
            'client.id.exists' => 'Le client spécifié n\'existe pas',
            'client.titulaire.required' => 'Le nom du titulaire est requis',
            'client.titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères',
            'client.email.required' => 'L\'email est requis',
            'client.email.email' => 'L\'email doit être une adresse email valide',
            'client.email.unique' => 'Cet email est déjà utilisé',
            'client.telephone.required' => 'Le numéro de téléphone est requis',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé',
            'client.nci.required' => 'Le NCI est requis',
            'client.nci.unique' => 'Ce NCI est déjà utilisé',
            'client.adresse.required' => 'L\'adresse est requise',
            'client.adresse.min' => 'L\'adresse doit contenir au moins 5 caractères',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Les données fournies sont invalides',
                'details' => $validator->errors()
            ]
        ], 422);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }

}
