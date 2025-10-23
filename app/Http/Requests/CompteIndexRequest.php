<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompteIndexRequest extends FormRequest
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
            'page'   => 'integer|min:1',
            'limit'  => 'integer|min:1|max:100',
            'type'   => 'nullable|in:epargne,courant,cheque',
            'statut' => 'nullable|in:actif,bloque',
            'search' => 'nullable|string|max:255',
            'sort'   => 'nullable|in:dateCreation,solde,titulaire',
            'order'  => 'nullable|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => "Le paramètre 'page' doit être un entier positif.",
            'limit.integer' => "Le paramètre 'limit' doit être un entier positif.",
            'limit.max' => "Le paramètre 'limit' ne peut pas dépasser 100.",
            'type.in' => "Le type de compte doit être 'epargne', 'courant' ou 'cheque'.",
            'statut.in' => "Le statut doit être 'actif' ou 'bloque'.",
            'sort.in' => "Le tri doit être 'dateCreation', 'solde' ou 'titulaire'.",
            'order.in' => "Le paramètre 'order' doit être 'asc' ou 'desc'.",
        ];
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'page'  => $this->input('page', 1),
            'limit' => $this->input('limit', 10),
            'sort'  => $this->input('sort', 'dateCreation'),
            'order' => $this->input('order', 'desc'),
        ]);
    }
}
