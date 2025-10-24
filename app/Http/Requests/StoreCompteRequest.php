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
        return [
            'type' => ['required', Rule::in(['epargne', 'courant', 'cheque'])],
            'soldeInitial' => ['required', 'numeric', 'min:10000'],
            'devise' => ['required', 'string'],
            'client.titulaire' => ['required', 'string'],
            'client.email' => ['required', 'email', 'unique:users,email'],
            'client.telephone' => ['required', 'string', 'unique:users,telephone', new \App\Rules\SenegalTelephone()],
            'client.nci' => ['required', 'string', 'unique:users,nci', new \App\Rules\SenegalNci()],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est requis',
            'type.in' => 'Le type de compte doit être epargne, courant ou cheque',
            'soldeInitial.required' => 'Le solde initial est requis',
            'soldeInitial.min' => 'Le solde initial doit être supérieur ou égal à 10 000',
            'client.titulaire.required' => 'Le nom du titulaire est requis',
            'client.email.required' => 'L\'email est requis',
            'client.email.unique' => 'Cet email est déjà utilisé',
            'client.telephone.required' => 'Le numéro de téléphone est requis',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé',
            'client.nci.required' => 'Le NCI est requis',
            'client.nci.unique' => 'Ce NCI est déjà utilisé',
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
