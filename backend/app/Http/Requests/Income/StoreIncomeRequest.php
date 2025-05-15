<?php

namespace App\Http\Requests\Income;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreIncomeRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'compte_id' => 'required|exists:accounts,id,utilisateur_id,' . Auth::id(),
            'source' => 'required|string|max:100',
            'montant' => 'required|numeric|min:0',
            'date_perception' => 'required|date',
            'periodicite' => 'sometimes|string|in:unique,quotidien,hebdomadaire,mensuel,trimestriel,annuel',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'compte_id.required' => 'Le compte est obligatoire',
            'compte_id.exists' => 'Le compte sélectionné n\'existe pas ou ne vous appartient pas',
            'source.required' => 'La source du revenu est obligatoire',
            'source.max' => 'La source du revenu ne peut pas dépasser 100 caractères',
            'montant.required' => 'Le montant est obligatoire',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant ne peut pas être négatif',
            'date_perception.required' => 'La date de perception est obligatoire',
            'date_perception.date' => 'La date de perception doit être une date valide',
            'periodicite.in' => 'La périodicité doit être l\'une des valeurs suivantes : unique, quotidien, hebdomadaire, mensuel, trimestriel, annuel',
        ];
    }
}